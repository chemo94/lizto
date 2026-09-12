import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/helper/shared_preference_helper.dart';
import 'package:liztogo_pro/core/route/route.dart';
import 'package:liztogo_pro/core/utils/my_strings.dart';
import 'package:liztogo_pro/data/model/authorization/authorization_response_model.dart';
import 'package:liztogo_pro/data/model/global/response_model/response_model.dart';
import 'package:liztogo_pro/data/repo/auth/sms_email_verification_repo.dart';
import 'package:liztogo_pro/data/services/firebase_phone_auth_service.dart';
import 'package:liztogo_pro/presentation/components/snack_bar/show_custom_snackbar.dart';

class SmsVerificationController extends GetxController {
  SmsEmailVerificationRepo repo;
  SmsVerificationController({required this.repo});

  bool hasError = false;
  bool isLoading = true;
  String currentText = '';
  String userPhone = '';
  final FirebasePhoneAuthService firebasePhoneAuth = FirebasePhoneAuthService();

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
      await _sendFirebaseCode();
    } catch (e) {
      CustomSnackBar.error(errorList: [e.toString()]);
    } finally {
      isLoading = false;
      update();
    }
    return;
  }

  bool submitLoading = false;
  Future<void> verifyYourSms(String currentText) async {
    if (currentText.isEmpty) {
      CustomSnackBar.error(errorList: [MyStrings.otpFieldEmptyMsg.tr]);
      return;
    }

    submitLoading = true;
    update();
    String idToken;
    try {
      idToken = await firebasePhoneAuth.confirmCode(currentText);
    } catch (error) {
      submitLoading = false;
      update();
      CustomSnackBar.error(errorList: [_firebaseError(error)]);
      return;
    }

    ResponseModel responseModel = await repo.verify(
      idToken,
      isEmail: false,
      isTFA: false,
    );

    if (responseModel.statusCode == 200) {
      AuthorizationResponseModel model = AuthorizationResponseModel.fromJson(
        (responseModel.responseJson),
      );

      if (model.status == MyStrings.success) {
        CustomSnackBar.success(
          successList: model.message ?? ['${MyStrings.sms.tr} ${MyStrings.verificationSuccess.tr}'],
        );
        // RouteMiddleware.checkNGotoNext(user: model.data?.user);
        RouteHelper.checkUserStatusAndGoToNextStep(model.data?.user);
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
    await _sendFirebaseCode(resend: true);
    currentText = "";
    resendLoading = false;
    update();
  }

  String _phoneFrom(ResponseModel response) {
    final json = response.responseJson;
    if (json is Map && json['data'] is Map && json['data']['phone_number'] != null) return json['data']['phone_number'].toString();
    return userPhone.startsWith('+') ? userPhone : '+$userPhone';
  }

  Future<void> _sendFirebaseCode({bool resend = false}) => firebasePhoneAuth.sendCode(phoneNumber: userPhone, resend: resend, onAutoVerified: _submitFirebaseToken, onCodeSent: () {}, onError: (error) => CustomSnackBar.error(errorList: [_firebaseError(error)]));
  Future<void> _submitFirebaseToken(String token) async {
    final response = await repo.verify(token, isEmail: false);
    if (response.statusCode == 200) {
      final model = AuthorizationResponseModel.fromJson(response.responseJson);
      if (model.status == MyStrings.success) RouteHelper.checkUserStatusAndGoToNextStep(model.data?.user);
    }
  }

  String _firebaseError(Object error) => error is FirebaseAuthException ? (error.message ?? error.code) : error.toString();
}
