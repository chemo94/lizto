import 'dart:io';

import 'package:liztogo_pro/core/utils/method.dart';
import 'package:liztogo_pro/core/utils/my_strings.dart';
import 'package:liztogo_pro/core/utils/url_container.dart';
import 'package:liztogo_pro/data/model/authorization/authorization_response_model.dart';
import 'package:liztogo_pro/data/model/global/formdata/global_kyc_form_data.dart';
import 'package:liztogo_pro/data/model/global/response_model/response_model.dart';
import 'package:liztogo_pro/data/model/kyc/kyc_response_model.dart';
import 'package:liztogo_pro/data/services/api_client.dart';
import 'package:liztogo_pro/presentation/components/snack_bar/show_custom_snackbar.dart';

class DriverVerificationKycRepo {
  ApiClient apiClient;
  DriverVerificationKycRepo({required this.apiClient});

  Future<DriverKycResponseModel> getDriverVerificationKycData() async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.driverVerificationFormUrl}';
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.getMethod,
      null,
      passHeader: true,
    );

    if (responseModel.statusCode == 200) {
      DriverKycResponseModel model = DriverKycResponseModel.fromJson(
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
      return DriverKycResponseModel();
    }
  }

  List<Map<String, String>> fieldList = [];
  List<ModelDynamicValue> filesList = [];

  Future<AuthorizationResponseModel> submitDriverVerificationKycData(
    List<GlobalFormModel> list,
  ) async {
    apiClient.initToken();
    fieldList.clear();
    filesList.clear();
    await modelToMap(list);
    String url = '${UrlContainer.baseUrl}${UrlContainer.driverVerificationFormUrl}';

    Map<String, String> finalMap = {};

    for (var element in fieldList) {
      finalMap.addAll(element);
    }

    Map<String, File> attachmentFiles = {};
    for (var fileItem in filesList) {
      if (fileItem.key != null && fileItem.key!.isNotEmpty && fileItem.value is File) {
        attachmentFiles[fileItem.key!] = fileItem.value as File;
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

  //
}

class ModelDynamicValue {
  String? key;
  dynamic value;
  ModelDynamicValue(this.key, this.value);
}
