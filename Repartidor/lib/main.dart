import 'dart:io';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/theme/light/light.dart';
import 'package:liztogo_repartidor/core/utils/my_images.dart';
import 'package:liztogo_repartidor/core/utils/util.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:liztogo_repartidor/core/utils/messages.dart';
import 'package:liztogo_repartidor/data/controller/localization/localization_controller.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';
import 'package:liztogo_repartidor/core/di_service/di_services.dart' as di_service;
import 'package:liztogo_repartidor/data/services/forground_location_service.dart';
import 'package:liztogo_repartidor/presentation/components/foreground_task_widget.dart';
import 'package:liztogo_repartidor/data/services/push_notification_service.dart';
import 'package:liztogo_repartidor/environment.dart';
import 'data/services/api_client.dart';
import 'package:timezone/data/latest.dart' as tz;
import 'package:toastification/toastification.dart';

//APP ENTRY POINT
Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await ApiClient.init();

  Map<String, Map<String, String>> languages = await di_service.init();
  MyUtils.allScreen();
  MyUtils().stopLandscape();

  try {
    PushNotificationService(apiClient: Get.find()).setupInteractedMessage();
  } catch (e) {
    printX(e);
  }

  HttpOverrides.global = MyHttpOverrides();
  tz.initializeTimeZones();
  FlutterForegroundTask.initCommunicationPort();
  runApp(OvoApp(languages: languages));
}

@pragma('vm:entry-point')
void startForegroundTask() {
  FlutterForegroundTask.setTaskHandler(ForgroundLocationService());
}

class MyHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) {
    return super.createHttpClient(context)..badCertificateCallback = (X509Certificate cert, String host, int port) => false;
  }
}

class OvoApp extends StatefulWidget {
  final Map<String, Map<String, String>> languages;

  const OvoApp({super.key, required this.languages});

  @override
  State<OvoApp> createState() => _OvoAppState();
}

class _OvoAppState extends State<OvoApp> {
  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    MyUtils.precacheImagesFromPathList(context, [MyImages.backgroundImage, MyImages.logoWhite, MyImages.noDataImage]);
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<LocalizationController>(
      builder: (localizeController) => ToastificationWrapper(
        config: ToastificationConfig(maxToastLimit: 10),
        child: GetMaterialApp(
          title: Environment.appName,
          debugShowCheckedModeBanner: false,
          theme: lightThemeData,
          defaultTransition: Transition.fadeIn,
          transitionDuration: const Duration(milliseconds: 300),
          initialRoute: RouteHelper.splashScreen,
          getPages: RouteHelper().routes,
          locale: localizeController.locale,
          translations: Messages(languages: widget.languages),
          fallbackLocale: Locale(
            localizeController.locale.languageCode,
            localizeController.locale.countryCode,
          ),
          builder: (context, child) => ForGroundTaskWidget(
            key: foregroundTaskKey,
            onWillStart: () async {
              final c = Get.isRegistered<CourierController>() ? Get.find<CourierController>() : null;
              return c?.isOnline ?? false;
            },
            callback: startForegroundTask,
            child: child!,
          ),
        ),
      ),
    );
  }
}
