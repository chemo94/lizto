import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:liztogo_repartidor/core/helper/shared_preference_helper.dart';
import '../../../core/utils/method.dart';
import '../../../core/utils/url_container.dart';
import '../../model/global/response_model/response_model.dart';
import '../../services/api_client.dart';

class SocialAuthRepo {
  ApiClient apiClient;

  SocialAuthRepo({required this.apiClient});

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
    Map<String, String> map = {'token': deviceToken.toString()};
    await apiClient.request(url, Method.postMethod, map, passHeader: true);
    return true;
  }

  Future<ResponseModel> socialLoginUser({
    String accessToken = '',
    String? provider,
  }) async {
    Map<String, String>? map;

    String deviceToken = '';
    try {
      deviceToken = await FirebaseMessaging.instance.getToken() ?? '';
    } catch (_) {}

    if (provider == 'google') {
      map = {'token': accessToken, 'provider': "google", 'service_type': 'delivery'};
    }

    if (provider == 'linkedin') {
      map = {'token': accessToken, 'provider': "linkedin", 'service_type': 'delivery'};
    }

    if (provider == 'apple') {
      map = {'token': accessToken, 'provider': "apple", 'service_type': 'delivery'};
    }
    if (provider == 'phone') map = {'token': accessToken, 'provider': 'phone', 'service_type': 'delivery'};

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

  Future<ResponseModel> sendWhatsAppOtp({
    required String mobile,
    String dialCode = '51',
    String userType = 'driver',
  }) async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.whatsappSendOtp}';
    Map<String, String> map = {
      'mobile': mobile,
      'dial_code': dialCode,
      'user_type': userType,
    };
    return await apiClient.request(url, Method.postMethod, map, passHeader: false);
  }

  Future<ResponseModel> verifyWhatsAppOtp({
    required String mobile,
    required String otpCode,
    String dialCode = '51',
    String userType = 'driver',
  }) async {
    String deviceToken = '';
    try {
      deviceToken = await FirebaseMessaging.instance.getToken() ?? '';
    } catch (_) {}

    String url = '${UrlContainer.baseUrl}${UrlContainer.whatsappVerifyOtp}';
    Map<String, String> map = {
      'mobile': mobile,
      'otp_code': otpCode,
      'dial_code': dialCode,
      'user_type': userType,
      if (deviceToken.isNotEmpty) 'device_token': deviceToken,
    };
    return await apiClient.request(url, Method.postMethod, map, passHeader: false);
  }
}
