import 'package:firebase_messaging/firebase_messaging.dart';
import '../../../core/utils/method.dart';
import '../../../core/utils/url_container.dart';
import '../../model/global/response_model/response_model.dart';
import '../../services/api_client.dart';

class SocialAuthRepo {
  ApiClient apiClient;

  SocialAuthRepo({required this.apiClient});

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
      map = {'token': accessToken, 'provider': "google", 'service_type': 'ride'};
    }

    if (provider == 'linkedin') {
      map = {'token': accessToken, 'provider': "linkedin", 'service_type': 'ride'};
    }

    if (provider == 'apple') {
      map = {'token': accessToken, 'provider': "apple", 'service_type': 'ride'};
    }
    if (provider == 'phone') map = {'token': accessToken, 'provider': 'phone', 'service_type': 'ride'};

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
