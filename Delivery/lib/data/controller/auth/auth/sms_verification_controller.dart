import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/helper/shared_preference_helper.dart';
import 'package:lizto_delivery/core/route/route_middleware.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';
import 'package:lizto_delivery/data/model/authorization/authorization_response_model.dart';
import 'package:lizto_delivery/data/model/global/response_model/response_model.dart';
import 'package:lizto_delivery/data/repo/auth/sms_email_verification_repo.dart';
import 'package:lizto_delivery/presentation/components/snack_bar/show_custom_snackbar.dart';

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
      userPhone = _phoneFrom(response);
    } catch (error) {
      CustomSnackBar.error(errorList: [error.toString()]);
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

    submitLoading = true;
    update();

    ResponseModel responseModel = await repo.verify(
      currentText,
      isEmail: false,
      isTFA: false,
    );

    if (responseModel.statusCode == 200) {
      AuthorizationResponseModel model = AuthorizationResponseModel.fromJson((responseModel.responseJson));

      if (model.status == MyStrings.success) {
        CustomSnackBar.success(
          successList: model.message ?? ['${MyStrings.sms.tr} ${MyStrings.verificationSuccess.tr}'],
        );
        RouteMiddleware.checkNGotoNext(user: model.data?.user);
      } else {
        CustomSnackBar.error(
          errorList: model.message ?? ['${MyStrings.sms.tr} ${MyStrings.verificationFailed}'],
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
      CustomSnackBar.success(
        successList: ['Código reenviado exitosamente a tu WhatsApp'],
      );
    }
    currentText = "";
    resendLoading = false;
    update();
  }

  String _phoneFrom(ResponseModel response) {
    final json = response.responseJson;
    if (json is Map && json['data'] is Map && json['data']['phone_number'] != null) {
      return json['data']['phone_number'].toString();
    }
    return userPhone.startsWith('+') ? userPhone : '+$userPhone';
  }
}
