import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/helper/string_format_helper.dart';
import 'package:liztogo_pro/core/utils/my_strings.dart';
import 'package:liztogo_pro/data/model/authorization/authorization_response_model.dart';
import 'package:liztogo_pro/data/model/global/formdata/global_kyc_form_data.dart';
import 'package:liztogo_pro/data/model/global/ride/app_service_model.dart';
import 'package:liztogo_pro/data/model/global/ride/ride_rulse_model.dart';
import 'package:liztogo_pro/data/model/kyc/kyc_pending_data_model.dart';
import 'package:liztogo_pro/data/model/vehicle_verification/vehicle_verification_model.dart';
import 'package:liztogo_pro/data/repo/vehicle_verification/vehicle_verification_repo.dart';
import 'package:liztogo_pro/presentation/components/snack_bar/show_custom_snackbar.dart';

import '../../../core/helper/date_converter.dart';

class VehicleVerificationController extends GetxController {
  VehicleVerificationRepo repo;
  VehicleVerificationController({required this.repo});

  bool isLoading = true;
  List<GlobalFormModel> formList = [];

  String selectOne = MyStrings.selectOne;

  VehicleKycResponseModel model = VehicleKycResponseModel();
  bool isNoDataFound = false;
  bool isAlreadyVerified = false;
  bool isAlreadyPending = false;

  List<KycPendingData> pendingData = [];

  String path = '';

  // Vehicle data lists from API
  List<AppService> serviceList = [];
  List<Brand> brandList = [];
  List<VerifyElement> modelList = [];
  List<VerifyElement> yearList = [];
  List<VerifyElement> colorList = [];
  List<RiderRule> riderRuleList = [];

  // Selected values
  AppService? selectedService;
  Brand? selectedBrand;
  VerifyElement? selectedModel;
  VerifyElement? selectedYear;
  VerifyElement? selectedColor;
  String vehicleNumber = '';
  List<String> selectedRuleIds = [];

  // Files
  File? vehicleImageFile;
  File? vehicleDocumentFile;

  // Image paths from API
  String serviceImagePath = '';
  String brandImagePath = '';

  Future<void> beforeInitLoadKycData() async {
    setStatusTrue();

    try {
      model = await repo.getVahicleVerificationKycData();
      if (model.data != null && model.status?.toLowerCase() == MyStrings.success.toLowerCase()) {
        if (model.remark?.toLowerCase() == 'under_review') {
          isAlreadyPending = true;
        }

        path = model.data?.path ?? '';
        serviceImagePath = model.data?.serviceImagePath ?? '';
        brandImagePath = model.data?.brandImagePath ?? '';

        List<KycPendingData>? pList = model.data?.vehicleData;
        if (pList != null && pList.isNotEmpty) {
          pendingData.clear();
          pendingData.addAll(pList);
        }

        if (model.data?.pendingVehicleData != null) {
          pendingVehicleData = model.data?.pendingVehicleData;
        }

        // Load services
        if (model.data?.services != null) {
          serviceList.clear();
          serviceList.addAll(model.data!.services!);
          if (model.data?.selectedServices != null) {
            selectedService = model.data!.selectedServices;
          }
        }

        // Load brands
        if (model.data?.brands != null) {
          brandList.clear();
          brandList.addAll(model.data!.brands!);
          if (pendingVehicleData?.brand != null) {
            selectedBrand = brandList.firstWhereOrNull(
              (b) => b.id == pendingVehicleData?.brand?.id,
            );
            if (selectedBrand != null) {
              _loadModelsForBrand(selectedBrand!);
            }
          }
        }

        // Load rider rules
        if (model.data?.riderRules != null) {
          riderRuleList.clear();
          riderRuleList.addAll(model.data!.riderRules!);
        }

        // Load years
        if (model.data?.years != null) {
          yearList.clear();
          yearList.addAll(model.data!.years!);
          if (pendingVehicleData?.year != null) {
            selectedYear = yearList.firstWhereOrNull(
              (y) => y.id == pendingVehicleData?.year?.id,
            );
          }
        }

        // Load colors
        if (model.data?.colors != null) {
          colorList.clear();
          colorList.addAll(model.data!.colors!);
          if (pendingVehicleData?.color != null) {
            selectedColor = colorList.firstWhereOrNull(
              (c) => c.id == pendingVehicleData?.color?.id,
            );
          }
        }

        // Load pending model
        if (pendingVehicleData?.model != null && selectedBrand != null) {
          selectedModel = modelList.firstWhereOrNull(
            (m) => m.id == pendingVehicleData?.model?.id,
          );
        }

        List<GlobalFormModel>? tList = model.data?.form?.list;

        if (tList != null && tList.isNotEmpty) {
          formList.clear();
          for (var element in tList) {
            if (element.type == 'select') {
              bool? isEmpty = element.options?.isEmpty;
              bool empty = isEmpty ?? true;
              if (element.options != null && empty != true) {
                element.options?.insert(0, selectOne);
                element.selectedValue = element.options?.first;
                formList.add(element);
              }
            } else {
              formList.add(element);
            }
          }
          if (selectedYear != null) {
            _syncYearWithFormList(selectedYear?.name);
          }
        }
        if (model.remark?.toLowerCase() == 'already_verified') {
          isAlreadyVerified = true;
        }
        isNoDataFound = false;

        update();
      } else {
        isNoDataFound = true;
      }
    } finally {
      setStatusFalse();
    }
    setStatusFalse();
  }

  void _loadModelsForBrand(Brand brand) {
    modelList.clear();
    selectedModel = null;
    if (brand.models != null) {
      modelList.addAll(brand.models!);
    }
  }

  // Selection setters
  void selectService(AppService service) {
    selectedService = service;
    update();
  }

  void selectBrand(Brand brand) {
    selectedBrand = brand;
    _loadModelsForBrand(brand);
    update();
  }

  void selectModel(VerifyElement model) {
    selectedModel = model;
    update();
  }

  void selectYear(VerifyElement year) {
    selectedYear = year;
    _syncYearWithFormList(year.name);
    update();
  }

  void _syncYearWithFormList(String? yearName) {
    if (yearName == null || yearName.isEmpty) return;
    for (var element in formList) {
      final label = (element.label ?? '').toLowerCase();
      final name = (element.name ?? '').toLowerCase();
      if (label.contains('año') || label.contains('ano') || label.contains('year') ||
          name.contains('año') || name.contains('ano') || name.contains('year')) {
        element.selectedValue = yearName;
        element.textEditingController?.text = yearName;
      }
    }
  }

  void selectColor(VerifyElement color) {
    selectedColor = color;
    update();
  }

  void setVehicleNumber(String value) {
    vehicleNumber = value;
  }

  void toggleRule(String ruleId) {
    if (selectedRuleIds.contains(ruleId)) {
      selectedRuleIds.remove(ruleId);
    } else {
      selectedRuleIds.add(ruleId);
    }
    update();
  }

  void pickVehicleImage() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      allowMultiple: false,
      type: FileType.custom,
      allowedExtensions: ['jpg', 'png', 'jpeg'],
    );

    if (result == null) return;

    vehicleImageFile = File(result.files.single.path!);
    update();
  }

  void pickVehicleDocument() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      allowMultiple: false,
      type: FileType.custom,
      allowedExtensions: ['jpg', 'png', 'jpeg', 'pdf'],
    );

    if (result == null) return;

    vehicleDocumentFile = File(result.files.single.path!);
    update();
  }

  PendingVehicleData? pendingVehicleData;

  void setStatusTrue() {
    isLoading = true;
    update();
  }

  void setStatusFalse() {
    isLoading = false;
    update();
  }

  bool submitLoading = false;
  Future<void> submitKycData() async {
    if (selectedYear != null) {
      _syncYearWithFormList(selectedYear?.name);
    }

    List<String> list = hasError();

    if (list.isNotEmpty) {
      CustomSnackBar.error(errorList: list);
      return;
    }

    submitLoading = true;
    update();
    try {
      AuthorizationResponseModel response = await repo.submitVehicleVerificationKycData(
        formList: formList,
        serviceId: selectedService?.id ?? '',
        brandId: selectedBrand?.id ?? '',
        modelValue: selectedModel?.name ?? '',
        yearValue: selectedYear?.name ?? '',
        colorValue: selectedColor?.name ?? '',
        vehicleNumber: vehicleNumber,
        ruleIds: selectedRuleIds,
        vehicleImageFile: vehicleImageFile,
        vehicleDocumentFile: vehicleDocumentFile,
      );

      if (response.status?.toLowerCase() == MyStrings.success.toLowerCase()) {
        isAlreadyPending = true;
        Get.back();
        CustomSnackBar.success(
          successList: response.message ?? [MyStrings.success.tr],
        );
      } else {
        CustomSnackBar.error(
          errorList: response.message ?? [MyStrings.requestFail.tr],
        );
      }
    } catch (e) {
      printX(e);
    } finally {
      submitLoading = false;
      update();
    }
  }

  List<String> hasError() {
    List<String> errorList = [];
    errorList.clear();

    // Validate vehicle-specific fields
    if (selectedService == null) {
      errorList.add('Service ${MyStrings.isRequired}');
    }
    if (selectedBrand == null) {
      errorList.add('Brand ${MyStrings.isRequired}');
    }
    if (selectedModel == null) {
      errorList.add('Model ${MyStrings.isRequired}');
    }
    if (selectedYear == null) {
      errorList.add('Year ${MyStrings.isRequired}');
    }
    if (selectedColor == null) {
      errorList.add('Color ${MyStrings.isRequired}');
    }
    if (vehicleNumber.trim().isEmpty) {
      errorList.add('Vehicle number ${MyStrings.isRequired}');
    }
    if (selectedRuleIds.isEmpty) {
      errorList.add('Rules ${MyStrings.isRequired}');
    }
    if (vehicleImageFile == null) {
      errorList.add('Image ${MyStrings.isRequired}');
    }

    // Validate dynamic form fields
    for (var element in formList) {
      final label = (element.label ?? '').toLowerCase();
      final name = (element.name ?? '').toLowerCase();
      final isYear = label.contains('año') || label.contains('ano') || label.contains('year') ||
                     name.contains('año') || name.contains('ano') || name.contains('year');
      if (isYear && selectedYear != null) {
        element.selectedValue = selectedYear?.name;
        continue;
      }

      if (element.isRequired == 'required') {
        if (element.type == 'checkbox') {
          if (element.cbSelected == null || element.cbSelected!.isEmpty) {
            errorList.add('${element.name} ${MyStrings.isRequired}');
          }
        } else if (element.type == 'file') {
          if (element.imageFile == null && vehicleImageFile == null) {
            errorList.add('${element.name} ${MyStrings.isRequired}');
          }
        } else {
          if (element.selectedValue == '' || element.selectedValue == selectOne || element.selectedValue == null) {
            errorList.add('${element.name} ${MyStrings.isRequired}');
          }
        }
      }
    }
    return errorList;
  }

  void changeSelectedValue(dynamic value, int index) {
    formList[index].selectedValue = value;
    update();
  }

  void changeSelectedRadioBtnValue(int listIndex, int selectedIndex) {
    formList[listIndex].selectedValue = formList[listIndex].options?[selectedIndex];
    update();
  }

  void changeSelectedCheckBoxValue(int listIndex, String value) {
    List<String> list = value.split('_');
    int index = int.parse(list[0]);
    bool status = list[1] == 'true' ? true : false;

    List<String>? selectedValue = formList[listIndex].cbSelected;

    if (selectedValue != null) {
      String? value = formList[listIndex].options?[index];
      if (status) {
        if (!selectedValue.contains(value)) {
          selectedValue.add(value!);
          formList[listIndex].cbSelected = selectedValue;
          update();
        }
      } else {
        if (selectedValue.contains(value)) {
          selectedValue.removeWhere((element) => element == value);
          formList[listIndex].cbSelected = selectedValue;
          update();
        }
      }
    } else {
      selectedValue = [];
      String? value = formList[listIndex].options?[index];
      if (status) {
        if (!selectedValue.contains(value)) {
          selectedValue.add(value!);
          formList[listIndex].cbSelected = selectedValue;
          update();
        }
      } else {
        if (selectedValue.contains(value)) {
          selectedValue.removeWhere((element) => element == value);
          formList[listIndex].cbSelected = selectedValue;
          update();
        }
      }
    }
  }

  void changeSelectedDateTimeValue(int index, BuildContext context) async {
    printX("tap");

    DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(1900),
      lastDate: DateTime(2101),
    );
    if (pickedDate != null) {
      TimeOfDay? pickedTime = await showTimePicker(
        context: context,
        initialTime: TimeOfDay.now(),
      );
      if (pickedTime != null) {
        final DateTime selectedDateTime = DateTime(
          pickedDate.year,
          pickedDate.month,
          pickedDate.day,
          pickedTime.hour,
          pickedTime.minute,
        );

        formList[index].selectedValue = DateConverter.estimatedDateTime(
          selectedDateTime,
        );
        formList[index].textEditingController?.text = DateConverter.estimatedDateTime(selectedDateTime);

        update();
      }
    }

    update();
  }

  void changeSelectedDateOnlyValue(int index, BuildContext context) async {
    DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(1900),
      lastDate: DateTime(2101),
    );
    if (pickedDate != null) {
      final DateTime selectedDateTime = DateTime(
        pickedDate.year,
        pickedDate.month,
        pickedDate.day,
      );

      formList[index].selectedValue = DateConverter.estimatedDate(
        selectedDateTime,
      );
      formList[index].textEditingController?.text = DateConverter.estimatedDate(
        selectedDateTime,
      );
      printX(formList[index].textEditingController?.text);
      printX(formList[index].selectedValue);
      update();
    }

    update();
  }

  void changeSelectedTimeOnlyValue(int index, BuildContext context) async {
    TimeOfDay? pickedTime = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (pickedTime != null) {
      final DateTime selectedDateTime = DateTime(
        DateTime.now().year,
        DateTime.now().month,
        DateTime.now().day,
        pickedTime.hour,
        pickedTime.minute,
      );

      formList[index].selectedValue = DateConverter.estimatedTime(
        selectedDateTime,
      );
      formList[index].textEditingController?.text = DateConverter.estimatedTime(
        selectedDateTime,
      );
      printX(formList[index].textEditingController?.text);
      printX(formList[index].selectedValue);
      update();
    }

    update();
  }

  void pickFile(int index) async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      allowMultiple: false,
      type: FileType.custom,
      allowedExtensions: ['jpg', 'png', 'jpeg', 'pdf', 'doc', 'docx'],
    );

    if (result == null) return;

    formList[index].imageFile = File(result.files.single.path!);
    String fileName = result.files.single.name;
    formList[index].selectedValue = fileName;

    update();
    return;
  }
}
