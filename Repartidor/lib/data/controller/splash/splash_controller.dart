import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/shared_preference_helper.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:liztogo_repartidor/core/utils/messages.dart';
import 'package:liztogo_repartidor/core/utils/my_strings.dart';
import 'package:liztogo_repartidor/core/utils/url_container.dart';
import 'package:liztogo_repartidor/data/controller/localization/localization_controller.dart';
import 'package:liztogo_repartidor/data/model/general_setting/general_setting_response_model.dart';
import 'package:liztogo_repartidor/data/model/global/response_model/response_model.dart';
import 'package:liztogo_repartidor/data/repo/auth/general_setting_repo.dart';
import 'package:liztogo_repartidor/data/services/push_notification_service.dart';
import 'package:liztogo_repartidor/presentation/components/snack_bar/show_custom_snackbar.dart';

import '../../model/authorization/authorization_response_model.dart';

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
        GeneralSettingResponseModel model = GeneralSettingResponseModel.fromJson(
          (response.responseJson),
        );
        if (model.status?.toLowerCase() == MyStrings.success) {
          repo.apiClient.storeGeneralSetting(model);
          repo.apiClient.storePushSetting(
            model.data?.generalSetting?.pushConfig ?? PusherConfig(),
          );
          repo.apiClient.storeNotificationAudio(
            "${UrlContainer.domainUrl}/${model.data?.notificationAudioPath}/${model.data?.generalSetting?.notificationAudio ?? ""}",
          );
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

    // Always navigate forward even on network failure
    if (!noInternet) {
      void navigate() {
        if (!isOnboardAlreadyDisplayed) {
          Get.offAndToNamed(RouteHelper.onboardScreen);
        } else if (isRemember || repo.apiClient.getToken().isNotEmpty) {
          PushNotificationService(apiClient: repo.apiClient).sendUserToken(force: true);
          Get.offAndToNamed(RouteHelper.dashboard);
        } else {
          Get.offAndToNamed(RouteHelper.loginScreen);
        }
      }
      Future.delayed(const Duration(seconds: 1), navigate);
    }
  }

  Future<bool> initSharedData() {
    if (!repo.apiClient.sharedPreferences.containsKey(
      SharedPreferenceHelper.countryCode,
    )) {
      return repo.apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.countryCode,
        localizationController.defaultLanguage.countryCode,
      );
    }
    if (!repo.apiClient.sharedPreferences.containsKey(
      SharedPreferenceHelper.languageCode,
    )) {
      return repo.apiClient.sharedPreferences.setString(
        SharedPreferenceHelper.languageCode,
        localizationController.defaultLanguage.languageCode,
      );
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
