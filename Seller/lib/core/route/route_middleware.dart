import 'package:get/get.dart';
import 'package:lizto_store/core/helper/shared_preference_helper.dart';
import 'package:lizto_store/core/helper/string_format_helper.dart';
import 'package:lizto_store/core/route/route.dart';
import 'package:lizto_store/data/model/global/user/global_user_model.dart';
import 'package:lizto_store/data/services/api_client.dart';
import 'package:lizto_store/data/services/push_notification_service.dart';

class RouteMiddleware {
  static Future<void> checkNGotoNext({
    GlobalUser? user,
    String accessToken = "",
    String tokenType = "",
  }) async {
    try {
      final apiClient = ApiClient(sharedPreferences: Get.find());
      await apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userIdKey,
        user?.id.toString() ?? '-1',
      );
      await apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userEmailKey,
        user?.email ?? '',
      );
      await apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userPhoneNumberKey,
        user?.mobile ?? '',
      );
      await apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userNameKey,
        user?.username ?? '',
      );
      await apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userProfileKey,
        user?.imageWithPath ?? '',
      );
      await apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.userFullNameKey,
        '${user?.firstname} ${user?.lastname}',
      );
      if (accessToken.isNotEmpty) {
        await apiClient.sharedPreferences.setString(
          SharedPreferenceHelper.accessTokenType,
          tokenType,
        );
        await apiClient.sharedPreferences.setString(
          SharedPreferenceHelper.accessTokenKey,
          accessToken,
        );
        await apiClient.sharedPreferences.setString(
          SharedPreferenceHelper.sellerTokenKey,
          accessToken,
        );
        await apiClient.sharedPreferences.setBool(
          SharedPreferenceHelper.rememberMeKey,
          true,
        );
      }

      PushNotificationService(apiClient: Get.find()).sendUserToken();
      Get.offAllNamed(RouteHelper.sellerDashboardScreen);
    } catch (e) {
      printD(e);
    }
  }
}
