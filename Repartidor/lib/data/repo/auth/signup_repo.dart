import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:liztogo_repartidor/core/helper/shared_preference_helper.dart';
import 'package:liztogo_repartidor/core/utils/method.dart';
import 'package:liztogo_repartidor/core/utils/url_container.dart';
import 'package:liztogo_repartidor/data/model/auth/sign_up_model/sign_up_model.dart';
import 'package:liztogo_repartidor/data/model/global/response_model/response_model.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';

class RegistrationRepo {
  ApiClient apiClient;

  RegistrationRepo({required this.apiClient});

  Future<ResponseModel> registerUser(SignUpModel model) async {
    String deviceToken = '';
    try {
      deviceToken = await FirebaseMessaging.instance.getToken() ?? '';
    } catch (_) {}

    final map = model.toMap();
    if (deviceToken.isNotEmpty) {
      map['device_token'] = deviceToken;
    }

    String url = '${UrlContainer.baseUrl}${UrlContainer.registrationEndPoint}';
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.postMethod,
      map,
      passHeader: true,
      isOnlyAcceptType: true,
    );
    return responseModel;
  }

  Future<dynamic> getCountryList() async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.countryEndPoint}';
    ResponseModel model = await apiClient.request(url, Method.getMethod, null);
    return model;
  }

  Future<dynamic> getZoneList(String page) async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.zones}?page=$page';
    ResponseModel model = await apiClient.request(url, Method.getMethod, null);
    return model;
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
    Map<String, String> map = {'token': deviceToken.toString()};
    return map;
  }

  Future<ResponseModel> socialLoginUser({
    String accessToken = '',
    String? provider,
  }) async {
    Map<String, String>? map;

    if (provider == 'google') {
      map = {'token': accessToken, 'provider': "google"};
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
