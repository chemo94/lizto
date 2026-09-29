import 'dart:io';

import 'package:liztogo_repartidor/core/utils/method.dart';
import 'package:liztogo_repartidor/core/utils/my_strings.dart';
import 'package:liztogo_repartidor/core/utils/url_container.dart';
import 'package:liztogo_repartidor/data/model/authorization/authorization_response_model.dart';
import 'package:liztogo_repartidor/data/model/global/formdata/global_kyc_form_data.dart';
import 'package:liztogo_repartidor/data/model/global/response_model/response_model.dart';
import 'package:liztogo_repartidor/data/model/vehicle_verification/vehicle_verification_model.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:liztogo_repartidor/presentation/components/snack_bar/show_custom_snackbar.dart';

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
  }) async {
    apiClient.initToken();
    fieldList.clear();
    filesList.clear();
    await modelToMap(formList);

    String url = '${UrlContainer.baseUrl}${UrlContainer.vehicleVerificationFormUrl}';

    Map<String, String> finalMap = {};

    for (var element in fieldList) {
      finalMap.addAll(element);
    }

    Map<String, File> attachmentFiles = {};
    for (var f in filesList) {
      if (f.key != null && f.key!.isNotEmpty && f.value is File) {
        attachmentFiles[f.key!] = f.value as File;
      }
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
      String key = (e.label != null && e.label!.isNotEmpty) ? e.label! : (e.name ?? '');
      if (key.isEmpty) continue;

      if (e.type == 'checkbox') {
        if (e.cbSelected != null && e.cbSelected!.isNotEmpty) {
          for (int i = 0; i < e.cbSelected!.length; i++) {
            fieldList.add({'$key[$i]': e.cbSelected![i]});
          }
        }
      } else if (e.type == 'file') {
        if (e.imageFile != null) {
          filesList.add(ModelDynamicValue(key, e.imageFile!));
        }
      } else {
        if (e.selectedValue != null && e.selectedValue.toString().isNotEmpty) {
          fieldList.add({key: e.selectedValue.toString()});
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
