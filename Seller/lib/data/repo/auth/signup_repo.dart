import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:lizto_store/core/helper/shared_preference_helper.dart';
import 'package:lizto_store/core/utils/method.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/data/model/auth/sign_up_model/registration_response_model.dart';
import 'package:lizto_store/data/model/auth/sign_up_model/sign_up_model.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/services/api_client.dart';

class RegistrationRepo {
  ApiClient apiClient;

  RegistrationRepo({required this.apiClient});

  Future<RegistrationResponseModel> registerUser(SignUpModel model) async {
    final map = modelToMap(model);
    String deviceToken = '';
    try {
      deviceToken = await FirebaseMessaging.instance.getToken() ?? '';
    } catch (_) {}
    if (deviceToken.isNotEmpty) {
      map['device_token'] = deviceToken;
    }
    String url = '${UrlContainer.baseUrl}${UrlContainer.registrationEndPoint}';
    final res = await apiClient.request(
      url,
      Method.postMethod,
      map,
      passHeader: true,
      isOnlyAcceptType: true,
    );

    RegistrationResponseModel responseModel = RegistrationResponseModel.fromJson(res.responseJson);
    return responseModel;
  }

  Map<String, dynamic> modelToMap(SignUpModel model) {
    Map<String, dynamic> bodyFields = {
      'name': model.name,
      'email': model.email,
      'password': model.password,
      'phone': model.phone,
      'address': model.address,
      'zone_id': model.zoneId,
      'service_type': 'seller',
    };
    if (model.latitude != null) bodyFields['latitude'] = model.latitude;
    if (model.longitude != null) bodyFields['longitude'] = model.longitude;
    if (model.businessName != null && model.businessName!.isNotEmpty) bodyFields['business_name'] = model.businessName;
    if (model.tradeName != null && model.tradeName!.isNotEmpty) bodyFields['trade_name'] = model.tradeName;
    return bodyFields;
  }

  Future<dynamic> getCountryList() async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.countryEndPoint}';
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
