import 'dart:io';

import 'package:liztogo_pro/core/utils/method.dart';
import 'package:liztogo_pro/core/utils/my_strings.dart';
import 'package:liztogo_pro/core/utils/url_container.dart';
import 'package:liztogo_pro/data/model/authorization/authorization_response_model.dart';
import 'package:liztogo_pro/data/model/global/formdata/global_kyc_form_data.dart';
import 'package:liztogo_pro/data/model/global/response_model/response_model.dart';
import 'package:liztogo_pro/data/model/vehicle_verification/vehicle_verification_model.dart';
import 'package:liztogo_pro/data/services/api_client.dart';
import 'package:liztogo_pro/presentation/components/snack_bar/show_custom_snackbar.dart';

class VehicleVerificationRepo {
  ApiClient apiClient;
  VehicleVerificationRepo({required this.apiClient});

  Future<VehicleKycResponseModel> getVahicleVerificationKycData() async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.vehicleVerificationFormUrl}';
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.getMethod,
      null,
      passHeader: true,
    );

    if (responseModel.statusCode == 200) {
      VehicleKycResponseModel model = VehicleKycResponseModel.fromJson(
        (responseModel.responseJson),
      );

      if (model.status == 'success') {
        return model;
      } else {
        if (model.remark?.toLowerCase() != 'already_verified' && model.remark?.toLowerCase() != 'under_review') {
          CustomSnackBar.error(
            errorList: model.message ?? [MyStrings.somethingWentWrong],
          );
        }

        return model;
      }
    } else {
      return VehicleKycResponseModel();
    }
  }

  List<Map<String, String>> fieldList = [];
  List<ModelDynamicValue> filesList = [];

  Future<AuthorizationResponseModel> submitVehicleVerificationKycData({
    required List<GlobalFormModel> formList,
    required String serviceId,
    required String brandId,
    required String modelValue,
    required String yearValue,
    required String colorValue,
    required String vehicleNumber,
    required List<String> ruleIds,
    File? vehicleImageFile,
    File? vehicleDocumentFile,
  }) async {
    apiClient.initToken();
    fieldList.clear();
    filesList.clear();

    // Add hardcoded vehicle fields
    fieldList.add({'service_id': serviceId});
    fieldList.add({'brand_id': brandId});
    fieldList.add({'model': modelValue});
    fieldList.add({'year': yearValue});
    fieldList.add({'color': colorValue});
    fieldList.add({'vehicle_number': vehicleNumber});

    // Add rules
    for (int i = 0; i < ruleIds.length; i++) {
      fieldList.add({'rules[$i]': ruleIds[i]});
    }

    // Add dynamic form fields
    await modelToMap(formList);

    String url = '${UrlContainer.baseUrl}${UrlContainer.vehicleVerificationFormUrl}';

    Map<String, String> finalMap = {};

    for (var element in fieldList) {
      finalMap.addAll(element);
    }

    // Build files map directly with correct types
    Map<String, File> attachmentFiles = {};
    if (vehicleImageFile != null) {
      attachmentFiles['imagen_del_vehiculo'] = vehicleImageFile;
      attachmentFiles['image'] = vehicleImageFile;
    }
    if (vehicleDocumentFile != null) {
      attachmentFiles['documento_del_vehiculo'] = vehicleDocumentFile;
    }

    ResponseModel responseModel = await apiClient.multipartRequest(
      url,
      Method.postMethod,
      finalMap,
      files: attachmentFiles,
      passHeader: true,
    );
    AuthorizationResponseModel model = AuthorizationResponseModel.fromJson(
      (responseModel.responseJson),
    );

    return model;
  }

  Future<dynamic> modelToMap(List<GlobalFormModel> list) async {
    for (var e in list) {
      if (e.type == 'checkbox') {
        if (e.cbSelected != null && e.cbSelected!.isNotEmpty) {
          for (int i = 0; i < e.cbSelected!.length; i++) {
            fieldList.add({'${e.label}[$i]': e.cbSelected![i]});
          }
        }
      } else if (e.type == 'file') {
        // Skip file fields from dynamic form - we use dedicated image picker
      } else {
        if (e.selectedValue != null && e.selectedValue.toString().isNotEmpty) {
          fieldList.add({e.label ?? '': e.selectedValue});
        }
      }
    }
  }
}

class ModelDynamicValue {
  String? key;
  dynamic value;
  ModelDynamicValue(this.key, this.value);
}
