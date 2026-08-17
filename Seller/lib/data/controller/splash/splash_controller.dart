import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/helper/shared_preference_helper.dart';
import 'package:lizto_store/core/helper/string_format_helper.dart';
import 'package:lizto_store/core/route/route.dart';
import 'package:lizto_store/core/utils/messages.dart';
import 'package:lizto_store/core/utils/my_strings.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/data/controller/localization/localization_controller.dart';
import 'package:lizto_store/data/model/authorization/authorization_response_model.dart';
import 'package:lizto_store/data/model/general_setting/general_setting_response_model.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/repo/auth/general_setting_repo.dart';
import 'package:lizto_store/data/services/push_notification_service.dart';
import 'package:lizto_store/presentation/components/snack_bar/show_custom_snackbar.dart';

class SplashController extends GetxController {
  GeneralSettingRepo repo;
  LocalizationController localizationController;
  SplashController({required this.repo, required this.localizationController});

  bool isLoading = true;
  Future<void> gotoNextPage() async {
    await loadLanguage();
    bool isRemember = repo.apiClient.sharedPreferences.getBool(
          SharedPreferenceHelper.rememberMeKey,
        ) ??
        false;

    noInternet = false;
    update();

    initSharedData();

    getGSData(isRemember);
  }

  bool noInternet = false;
  bool isMaintenance = false;
  void getGSData(bool isRemember) async {
    ResponseModel response;
    try {
      response = await repo.getGeneralSetting().timeout(
        const Duration(seconds: 8),
        onTimeout: () => ResponseModel(false, 'Timeout', 503, {}),
      );
    } catch (e) {
      response = ResponseModel(false, e.toString(), 503, {});
    }

    bool isOnboardAlreadyDisplayed = repo.apiClient.sharedPreferences.getBool(
          SharedPreferenceHelper.onBoardKey,
        ) ??
        false;

    if (response.statusCode == 200) {
      try {
        GeneralSettingResponseModel model = GeneralSettingResponseModel.fromJson((response.responseJson));
        if (model.status?.toLowerCase() == MyStrings.success) {
          isMaintenance = model.data?.generalSetting?.maintenanceMode == "1" ? true : false;
          printD(isMaintenance);
          repo.apiClient.storeGeneralSetting(model);
          repo.apiClient.storePushSetting(
            model.data?.generalSetting?.pushConfig ?? PusherConfig(),
          );
          repo.apiClient.storeNotificationAudio("${UrlContainer.domainUrl}/${model.data?.notificationAudioPath}/${model.data?.generalSetting?.notificationAudio ?? ""}");
        } else {
          if (model.remark == "maintenance_mode") {
            Future.delayed(const Duration(seconds: 1), () {
              Get.offAndToNamed(RouteHelper.maintenanceScreen);
            });
            return;
          } else {
            List<String> message = [MyStrings.somethingWentWrong];
            CustomSnackBar.error(errorList: model.message ?? message);
          }
        }
      } catch (_) {}
    } else {
      if (response.statusCode == 503) {
        noInternet = true;
        update();
      }
    }

    isLoading = false;
    update();

    // Always navigate forward even if the server call failed
    void _navigate() {
      final isStaff = repo.apiClient.sharedPreferences.getBool('is_staff') ?? false;
      final staffPosition = repo.apiClient.sharedPreferences.getString('staff_position') ?? '';
      final staffPermissions = repo.apiClient.sharedPreferences.getStringList('staff_permissions') ?? [];
      final hasToken = repo.apiClient.sharedPreferences.getString(SharedPreferenceHelper.sellerTokenKey)?.isNotEmpty == true;
      final isRememberLocal = repo.apiClient.sharedPreferences.getBool(SharedPreferenceHelper.rememberMeKey) ?? false;

      if (!isOnboardAlreadyDisplayed) {
        Get.offAndToNamed(RouteHelper.onboardScreen);
      } else if (isRememberLocal || hasToken) {
        PushNotificationService(apiClient: repo.apiClient).sendUserToken(force: true);
        if (isStaff) {
          if (staffPosition == 'cocinero' || (staffPermissions.contains('kitchen') && !staffPermissions.contains('pos_orders'))) {
            Get.offNamed(RouteHelper.sellerKitchenScreen);
          } else if (staffPermissions.contains('pos_orders')) {
            Get.offNamed(RouteHelper.mozoTablesScreen);
          } else {
            Get.offNamed(RouteHelper.sellerDashboardScreen);
          }
        } else {
          Get.offNamed(RouteHelper.sellerDashboardScreen);
        }
      } else {
        Get.offAndToNamed(RouteHelper.loginScreen);
      }
    }

    if (!noInternet) {
      Future.delayed(const Duration(seconds: 1), _navigate);
    }
  }

  Future<bool> initSharedData() {
    if (!repo.apiClient.sharedPreferences.containsKey(SharedPreferenceHelper.countryCode)) {
      return repo.apiClient.sharedPreferences.setString(SharedPreferenceHelper.countryCode, localizationController.defaultLanguage.countryCode);
    }
    if (!repo.apiClient.sharedPreferences.containsKey(SharedPreferenceHelper.languageCode)) {
      return repo.apiClient.sharedPreferences.setString(SharedPreferenceHelper.languageCode, localizationController.defaultLanguage.languageCode);
    }
    return Future.value(true);
  }

  Future<void> loadLanguage() async {
    localizationController.loadCurrentLanguage();
    String languageCode = localizationController.locale.languageCode;
    ResponseModel response;
    try {
      response = await repo.getLanguage(languageCode).timeout(const Duration(seconds: 6));
    } catch (_) {
      return; // silently continue — language fallback already loaded from cache
    }

    if (response.statusCode == 200) {
      AuthorizationResponseModel model = AuthorizationResponseModel.fromJson(
        (response.responseJson),
      );
      if (model.remark == "maintenance_mode") {
        Future.delayed(const Duration(seconds: 1), () {
          Get.offAndToNamed(RouteHelper.maintenanceScreen);
        });
        return;
      }
      try {
        Map<String, Map<String, String>> language = {};
        var resJson = (response.responseJson);
        saveLanguageList(jsonEncode(response.responseJson));
        var value = resJson['data']['file'].toString() == '[]' ? {} : resJson['data']['file'];
        Map<String, String> json = {};
        printX(value);
        value.forEach((key, value) {
          json[key] = value.toString();
        });
        language['${localizationController.locale.languageCode}_${localizationController.locale.countryCode}'] = json;
        Get.addTranslations(Messages(languages: language).keys);
      } catch (e) {
        if (kDebugMode) {
          CustomSnackBar.error(errorList: [e.toString()]);
        }
      }
    }
  }

  void saveLanguageList(String languageJson) async {
    await repo.apiClient.sharedPreferences.setString(
      SharedPreferenceHelper.languageListKey,
      languageJson,
    );
    return;
  }
}
