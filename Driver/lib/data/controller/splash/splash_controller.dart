import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/helper/shared_preference_helper.dart';
import 'package:liztogo_pro/core/helper/string_format_helper.dart';
import 'package:liztogo_pro/core/route/route.dart';
import 'package:liztogo_pro/core/utils/messages.dart';
import 'package:liztogo_pro/core/utils/my_strings.dart';
import 'package:liztogo_pro/core/utils/url_container.dart';
import 'package:liztogo_pro/data/controller/localization/localization_controller.dart';
import 'package:liztogo_pro/data/model/general_setting/general_setting_response_model.dart';
import 'package:liztogo_pro/data/model/global/response_model/response_model.dart';
import 'package:liztogo_pro/data/repo/auth/general_setting_repo.dart';
import 'package:liztogo_pro/presentation/screens/maintenance/maintanance_screen.dart';
import 'package:liztogo_pro/presentation/components/snack_bar/show_custom_snackbar.dart';
import 'package:liztogo_pro/presentation/screens/auth/login/login_screen.dart';
import 'package:liztogo_pro/presentation/screens/dashboard/dashboard_screen.dart';
import 'package:liztogo_pro/presentation/screens/onbaord/onboard_intro_screen.dart';

import 'package:liztogo_pro/data/services/push_notification_service.dart';

import '../../model/authorization/authorization_response_model.dart';

class SplashController extends GetxController {
  GeneralSettingRepo repo;
  LocalizationController localizationController;
  SplashController({required this.repo, required this.localizationController});

  bool isLoading = true;
  Future<void> gotoNextPage() async {
    printX('🔵 SplashController.gotoNextPage() called');
    try {
      await loadLanguage();
      bool isRemember = repo.apiClient.sharedPreferences.getBool(
            SharedPreferenceHelper.rememberMeKey,
          ) ??
          false;
      noInternet = false;
      update();

      initSharedData();

      await getGSData(isRemember);
    } catch (e, stack) {
      printX('❌ SplashController.gotoNextPage() error: $e');
      printX('   Stack: $stack');
      isLoading = false;
      update();
      Get.offNamed(RouteHelper.loginScreen);
    }
  }

  bool noInternet = false;
  Future<void> getGSData(bool isRemember) async {
    printX('🔵 SplashController.getGSData() called, isRemember=$isRemember');
    try {
      ResponseModel response = await repo.getGeneralSetting();
      printX('🔵 General setting response: ${response.statusCode}');

      bool isOnboardAlreadyDisplayed = repo.apiClient.sharedPreferences.getBool(
            SharedPreferenceHelper.onBoardKey,
          ) ??
          false;

      if (response.statusCode == 200) {
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
      } else {
        if (response.statusCode == 503) {
          noInternet = true;
          update();
        }
        CustomSnackBar.error(errorList: [response.message]);
      }

      isLoading = false;
      update();

      printX('🔵 Navigating: onboard=$isOnboardAlreadyDisplayed, remember=$isRemember');
      if (isOnboardAlreadyDisplayed == false) {
        printX('🔵 Get.offNamed() to onboardScreen');
        Get.offNamed(RouteHelper.onboardScreen);
      } else if (isRemember) {
        PushNotificationService(apiClient: repo.apiClient).sendUserToken(force: true);
        printX('🔵 Get.offNamed() to dashboard');
        Get.offNamed(RouteHelper.dashboard);
      } else {
        printX('🔵 Get.offNamed() to loginScreen');
        Get.offNamed(RouteHelper.loginScreen);
      }
    } catch (e, stack) {
      printX('❌ SplashController.getGSData() error: $e');
      printX('   Stack: $stack');
      isLoading = false;
      update();
      Get.offNamed(RouteHelper.loginScreen);
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
    printX('🔵 SplashController.loadLanguage() called');
    try {
      localizationController.loadCurrentLanguage();
      String languageCode = localizationController.locale.languageCode;
      ResponseModel response = await repo.getLanguage(languageCode);

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
      } else {
        CustomSnackBar.error(errorList: [response.message]);
      }
    } catch (e, stack) {
      printX('❌ SplashController.loadLanguage() error: $e');
      printX('   Stack: $stack');
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
