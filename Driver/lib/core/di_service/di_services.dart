import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:liztogo_pro/data/controller/common/theme_controller.dart';
import 'package:liztogo_pro/data/controller/localization/localization_controller.dart';
import 'package:liztogo_pro/data/controller/splash/splash_controller.dart';
import 'package:liztogo_pro/data/repo/auth/general_setting_repo.dart';
import 'package:liztogo_pro/data/repo/splash/splash_repo.dart';
import 'package:liztogo_pro/data/services/api_client.dart';
import 'package:liztogo_pro/data/controller/ride/ride_request_manager.dart';

Future<Map<String, Map<String, String>>> init() async {
  final sharedPreferences = await SharedPreferences.getInstance();

  Get.lazyPut(() => sharedPreferences, fenix: true);

  // ApiClient and LocalStorageService are already registered in ApiClient.init()
  // Only register if not already registered
  if (!Get.isRegistered<ApiClient>()) {
    Get.lazyPut(() => ApiClient(sharedPreferences: Get.find()));
  }
  if (!Get.isRegistered<GeneralSettingRepo>()) {
    Get.lazyPut(() => GeneralSettingRepo(apiClient: Get.find()));
  }
  if (!Get.isRegistered<SplashRepo>()) {
    Get.lazyPut(() => SplashRepo(apiClient: Get.find()));
  }
  if (!Get.isRegistered<LocalizationController>()) {
    Get.lazyPut(() => LocalizationController(sharedPreferences: Get.find()));
  }
  if (!Get.isRegistered<SplashController>()) {
    Get.lazyPut(
      () => SplashController(repo: Get.find(), localizationController: Get.find()),
    );
  }
  if (!Get.isRegistered<ThemeController>()) {
    Get.lazyPut(() => ThemeController(sharedPreferences: Get.find()));
  }
  if (!Get.isRegistered<RideRequestManager>()) {
    Get.put(RideRequestManager(apiClient: Get.find()), permanent: true);
  }

  Map<String, Map<String, String>> language = {};
  language['es_PE'] = {'': ''};

  return language;
}
