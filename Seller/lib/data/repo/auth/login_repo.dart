import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:lizto_store/core/helper/shared_preference_helper.dart';
import 'package:lizto_store/core/utils/method.dart';
import 'package:lizto_store/core/utils/my_strings.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/core/utils/util.dart';
import 'package:lizto_store/data/model/auth/verification/email_verification_model.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/services/api_client.dart';
import 'package:lizto_store/presentation/components/snack_bar/show_custom_snackbar.dart';

class LoginRepo {
  ApiClient apiClient;

  LoginRepo({required this.apiClient});

  Future<ResponseModel> loginUser(String email, String password) async {
    String deviceToken = '';
    try {
      deviceToken = await FirebaseMessaging.instance.getToken() ?? '';
    } catch (_) {}

    Map<String, String> map = {'email': email, 'password': password, 'service_type': 'seller'};
    if (deviceToken.isNotEmpty) map['device_token'] = deviceToken;

    String url = '${UrlContainer.baseUrl}${UrlContainer.loginEndPoint}';

    ResponseModel model = await apiClient.request(
      url,
      Method.postMethod,
      map,
      passHeader: false,
    );

    return model;
  }

  Future<String> forgetPassword(String type, String value) async {
    final map = modelToMap(value, type);
    String url = '${UrlContainer.baseUrl}${UrlContainer.forgetPasswordEndPoint}';
    final response = await apiClient.request(
      url,
      Method.postMethod,
      map,
      isOnlyAcceptType: true,
      passHeader: true,
    );

    EmailVerificationModel model = EmailVerificationModel.fromJson((response.responseJson));

    if (model.status.toLowerCase() == "success") {
      apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userEmailKey,
        model.data?.email ?? '',
      );
      CustomSnackBar.success(
        successList: [
          '${MyStrings.passwordResetEmailSentTo} ${model.data?.email != null ? MyUtils.getFormatMaskMail(model.data?.email ?? "") : MyStrings.yourEmail}',
        ],
      );
      return model.data?.email ?? '';
    } else {
      CustomSnackBar.error(errorList: model.message ?? [MyStrings.requestFail]);
      return '';
    }
  }

  Map<String, String> modelToMap(String value, String type) {
    Map<String, String> map = {'type': type, 'value': value};
    return map;
  }

  Future<EmailVerificationModel> verifyForgetPassCode(String code) async {
    String? email = apiClient.sharedPreferences.getString(
          SharedPreferenceHelper.userEmailKey,
        ) ??
        '';
    Map<String, String> map = {'code': code, 'email': email};

    String url = '${UrlContainer.baseUrl}${UrlContainer.passwordVerifyEndPoint}';

    final response = await apiClient.request(
      url,
      Method.postMethod,
      map,
      passHeader: true,
      isOnlyAcceptType: true,
    );

    EmailVerificationModel model = EmailVerificationModel.fromJson((response.responseJson));
    if (model.status == 'success') {
      model.setCode(200);
      return model;
    } else {
      model.setCode(400);
      return model;
    }
  }

  Future<EmailVerificationModel> resetPassword(
    String email,
    String password,
    String code,
  ) async {
    Map<String, String> map = {
      'token': code,
      'email': email,
      'password': password,
      'password_confirmation': password,
    };

    String url = '${UrlContainer.baseUrl}${UrlContainer.resetPasswordEndPoint}';
    final response = await apiClient.request(
      url,
      Method.postMethod,
      map,
      passHeader: true,
      isOnlyAcceptType: true,
    );

    EmailVerificationModel model = EmailVerificationModel.fromJson((response.responseJson));

    if (model.status == 'success') {
      CustomSnackBar.success(successList: model.message ?? []);
      model.setCode(200);
      return model;
    } else {
      CustomSnackBar.error(errorList: model.message ?? []);
      model.setCode(400);
      return model;
    }
  }

  Future<bool> sendUserToken() async {
    FirebaseMessaging firebaseMessaging = FirebaseMessaging.instance;
    final cachedToken = apiClient.sharedPreferences.getString(SharedPreferenceHelper.fcmDeviceKey) ?? '';
    final currentToken = await firebaseMessaging.getToken() ?? '';

    bool success = currentToken.isNotEmpty;
    if (currentToken.isNotEmpty && currentToken != cachedToken) {
      success = await sendUpdatedToken(currentToken);
      if (success) {
        await apiClient.sharedPreferences.setString(SharedPreferenceHelper.fcmDeviceKey, currentToken);
      }
    }

    firebaseMessaging.onTokenRefresh.listen((fcmDeviceToken) async {
      final storedToken = apiClient.sharedPreferences.getString(SharedPreferenceHelper.fcmDeviceKey) ?? '';
      if (fcmDeviceToken == storedToken) return;
      final sent = await sendUpdatedToken(fcmDeviceToken);
      if (sent) {
        await apiClient.sharedPreferences.setString(SharedPreferenceHelper.fcmDeviceKey, fcmDeviceToken);
      }
    });

    return success;
  }

  Future<bool> sendUpdatedToken(String deviceToken) async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.deviceTokenEndPoint}';
    Map<String, String> map = deviceTokenMap(deviceToken);
    await apiClient.request(url, Method.postMethod, map, passHeader: true);
    return true;
  }

  Map<String, String> deviceTokenMap(String deviceToken) {
    Map<String, String> map = {'token': deviceToken.toString(), 'service_type': 'seller'};
    return map;
  }

  Future<ResponseModel> socialLoginUser({
    String accessToken = '',
    String? provider,
  }) async {
    String deviceToken = '';
    try {
      deviceToken = await FirebaseMessaging.instance.getToken() ?? '';
    } catch (_) {}

    Map<String, String>? map;

    if (provider == 'google') {
      map = {'token': accessToken, 'provider': "google", 'service_type': 'seller'};
    }
    if (provider == 'linkedin') {
      map = {'token': accessToken, 'provider': "linkedin", 'service_type': 'seller'};
    }

    if (map != null && deviceToken.isNotEmpty) {
      map['device_token'] = deviceToken;
    }

    String url = '${UrlContainer.baseUrl}${UrlContainer.socialLoginEndPoint}';

    ResponseModel model = await apiClient.request(
      url,
      Method.postMethod,
      map,
      passHeader: false,
    );

    return model;
  }
}
