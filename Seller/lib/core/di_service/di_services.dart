import 'package:get/get.dart';
import 'package:lizto_store/data/controller/location/app_location_controller.dart';
import 'package:lizto_store/data/repo/location/location_search_repo.dart';
import 'package:lizto_store/data/controller/common/theme_controller.dart';
import 'package:lizto_store/data/controller/localization/localization_controller.dart';
import 'package:lizto_store/data/controller/splash/splash_controller.dart';
import 'package:lizto_store/data/repo/auth/general_setting_repo.dart';
import 'package:lizto_store/data/repo/splash/splash_repo.dart';

Future<Map<String, Map<String, String>>> init() async {
  // ApiClient y SharedPreferences ya fueron registrados permanentemente
  // en ApiClient.init() antes de llamar a este método.
  // Solo registramos los demás servicios usando Get.find() para obtenerlos.
  Get.lazyPut(() => SplashRepo(apiClient: Get.find()), fenix: true);
  Get.lazyPut(() => AppLocationController(), fenix: true);
  Get.lazyPut(() => LocalizationController(sharedPreferences: Get.find()), fenix: true);
  Get.lazyPut(() => SplashController(repo: Get.find(), localizationController: Get.find()), fenix: true);
  Get.lazyPut(() => ThemeController(sharedPreferences: Get.find()), fenix: true);
  Get.lazyPut(() => GeneralSettingRepo(apiClient: Get.find()), fenix: true);
  Get.lazyPut(() => LocationSearchRepo(apiClient: Get.find()), fenix: true);

  Map<String, Map<String, String>> language = {};
  language['es_PE'] = {'': ''};

  return language;
}
