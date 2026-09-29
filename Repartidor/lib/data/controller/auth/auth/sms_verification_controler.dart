import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/shared_preference_helper.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:liztogo_repartidor/core/utils/my_strings.dart';
import 'package:liztogo_repartidor/data/model/authorization/authorization_response_model.dart';
import 'package:liztogo_repartidor/data/model/global/response_model/response_model.dart';
import 'package:liztogo_repartidor/data/repo/auth/sms_email_verification_repo.dart';
import 'package:liztogo_repartidor/presentation/components/snack_bar/show_custom_snackbar.dart';

class SmsVerificationController extends GetxController {
  SmsEmailVerificationRepo repo;
  SmsVerificationController({required this.repo});

  bool hasError = false;
  bool isLoading = true;
  String currentText = '';
  String userPhone = '';

  final otpTextController = TextEditingController();

  @override
  void onClose() {
    otpTextController.dispose();
    super.onClose();
  }

  Future<void> loadBefore() async {
    try {
      isLoading = true;
      userPhone = repo.apiClient.sharedPreferences.getString(
            SharedPreferenceHelper.userPhoneNumberKey,
          ) ??
          '';
      update();

      final response = await repo.sendAuthorizationRequest();
      if (response.statusCode == 200) {
        userPhone = _phoneFrom(response);
      }
    } catch (e) {
      CustomSnackBar.error(errorList: [e.toString()]);
    } finally {
      isLoading = false;
      update();
    }
  }

  bool submitLoading = false;
  Future<void> verifyYourSms(String currentText) async {
    if (currentText.isEmpty) {
      CustomSnackBar.error(errorList: [MyStrings.otpFieldEmptyMsg.tr]);
      return;
    }

    if (currentText.length != 6) {
      CustomSnackBar.error(errorList: ['Ingresa el código completo de 6 dígitos']);
      return;
    }

    submitLoading = true;
    update();

    ResponseModel responseModel = await repo.verify(
      currentText,
      isEmail: false,
      isTFA: false,
    );

    if (responseModel.statusCode == 200) {
      AuthorizationResponseModel model = AuthorizationResponseModel.fromJson(
        (responseModel.responseJson),
      );

      if (model.status?.toLowerCase() == MyStrings.success.toLowerCase()) {
        CustomSnackBar.success(
          successList: model.message ?? ['WhatsApp verificado exitosamente'],
        );
        RouteHelper.checkUserStatusAndGoToNextStep(model.data?.user);
      } else {
        CustomSnackBar.error(
          errorList: model.message ?? ['Código de verificación incorrecto'],
        );
      }
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }

    submitLoading = false;
    update();
  }

  bool resendLoading = false;
  Future<void> sendCodeAgain() async {
    resendLoading = true;
    update();

    bool success = await repo.resendVerifyCode(isEmail: false);
    if (success) {
      currentText = "";
      otpTextController.clear();
    }

    resendLoading = false;
    update();
  }

  String _phoneFrom(ResponseModel response) {
    final json = response.responseJson;
    if (json is Map && json['data'] is Map && json['data']['phone_number'] != null) {
      return json['data']['phone_number'].toString();
    }
    return userPhone;
  }
}
