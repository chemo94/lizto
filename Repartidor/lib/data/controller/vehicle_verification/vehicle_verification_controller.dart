import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/utils/my_strings.dart';
import 'package:liztogo_repartidor/data/model/authorization/authorization_response_model.dart';
import 'package:liztogo_repartidor/data/model/global/formdata/global_kyc_form_data.dart';
import 'package:liztogo_repartidor/data/model/kyc/kyc_pending_data_model.dart';
import 'package:liztogo_repartidor/data/model/vehicle_verification/vehicle_verification_model.dart';
import 'package:liztogo_repartidor/data/repo/vehicle_verification/vehicle_verification_repo.dart';
import 'package:liztogo_repartidor/presentation/components/snack_bar/show_custom_snackbar.dart';

import 'package:shared_preferences/shared_preferences.dart';
import 'package:liztogo_repartidor/core/helper/shared_preference_helper.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
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

  Future<void> beforeInitLoadKycData() async {
    setStatusTrue();

    try {
      model = await repo.getVahicleVerificationKycData();
      if (model.data != null && model.status?.toLowerCase() == MyStrings.success.toLowerCase()) {
        if (model.remark?.toLowerCase() == 'under_review') {
          isAlreadyPending = true;
        }

        path = model.data?.path ?? '';

        List<KycPendingData>? pList = model.data?.vehicleData;
        if (pList != null && pList.isNotEmpty) {
          pendingData.clear();
          pendingData.addAll(pList);
        }

        if (model.data?.pendingVehicleData != null) {
          pendingVehicleData = model.data?.pendingVehicleData;
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
    submitLoading = true;
    update();
    try {
      AuthorizationResponseModel response = await repo.submitVehicleVerificationKycData(
        formList: formList,
      );

      if (response.status?.toLowerCase() == MyStrings.success.toLowerCase()) {
        isAlreadyPending = true;
        try {
          SharedPreferences preferences = await SharedPreferences.getInstance();
          await preferences.setString(SharedPreferenceHelper.onboardingStepKey, 'completed');
        } catch (e) {
          printX(e);
        }
        update();
        CustomSnackBar.success(
          successList: response.message ?? [MyStrings.success.tr],
        );
        Future.delayed(const Duration(milliseconds: 1200), () {
          Get.offAllNamed(RouteHelper.dashboard);
        });
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
    for (var element in formList) {
      if (element.isRequired == 'required') {
        if (element.type == 'checkbox') {
          if (element.cbSelected == null) {
            errorList.add('${element.name} ${MyStrings.isRequired}');
          }
        } else if (element.type == 'file') {
          if (element.imageFile == null) {
            errorList.add('${element.name} ${MyStrings.isRequired}');
          }
        } else {
          if (element.selectedValue == '' || element.selectedValue == selectOne) {
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
