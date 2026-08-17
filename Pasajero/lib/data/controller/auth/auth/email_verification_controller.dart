import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/route/route_middleware.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/data/model/authorization/authorization_response_model.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/repo/auth/sms_email_verification_repo.dart';
import 'package:liztogo/data/services/otp_auto_fill_service.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';

class EmailVerificationController extends GetxController {
  SmsEmailVerificationRepo repo;
  EmailVerificationController({required this.repo});

  bool needSmsVerification = false;
  bool isProfileCompleteEnable = false;

  String currentText = "";
  String userEmail = "";

  bool needTwoFactor = false;
  bool submitLoading = false;
  bool isLoading = true;
  bool resendLoading = false;

  final otpTextController = TextEditingController();
  VoidCallback? _otpListener;

  @override
  void onClose() {
    if (_otpListener != null) {
      otpPushCode.removeListener(_otpListener!);
    }
    otpTextController.dispose();
    super.onClose();
  }

  Future<void> loadData() async {
    isLoading = true;
    userEmail = repo.apiClient.getUserEmail();
    update();
    await repo.sendAuthorizationRequest();
    isLoading = false;
    update();
    _listenToOtpPush();
  }

  void _listenToOtpPush() {
    _otpListener = () {
      final code = otpPushCode.value;
      if (code != null && code.length == 6) {
        currentText = code;
        otpTextController.text = code;
        update();
        verifyEmail(code);
      }
    };
    otpPushCode.addListener(_otpListener!);

    final pendingCode = otpPushCode.value;
    if (pendingCode != null && pendingCode.length == 6) {
      currentText = pendingCode;
      otpTextController.text = pendingCode;
      update();
      verifyEmail(pendingCode);
    }
  }

  Future<void> verifyEmail(String text) async {
    if (text.isEmpty) {
      CustomSnackBar.error(errorList: [MyStrings.otpFieldEmptyMsg]);
      return;
    }

    submitLoading = true;
    update();

    ResponseModel responseModel = await repo.verify(text);

    if (responseModel.statusCode == 200) {
      AuthorizationResponseModel model = AuthorizationResponseModel.fromJson((responseModel.responseJson));

      if (model.status == MyStrings.success) {
        CustomSnackBar.success(
          successList: model.message ?? [(MyStrings.emailVerificationSuccess)],
        );
        RouteMiddleware.checkNGotoNext(user: model.data?.user);
      } else {
        CustomSnackBar.error(
          errorList: model.message ?? [(MyStrings.emailVerificationFailed)],
        );
      }
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }
    submitLoading = false;
    update();
  }

  Future<void> sendCodeAgain() async {
    resendLoading = true;
    update();
    await repo.resendVerifyCode(isEmail: true);
    resendLoading = false;
    update();
  }
}
