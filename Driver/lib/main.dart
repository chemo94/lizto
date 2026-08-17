import 'dart:async';
import 'dart:io';
import 'dart:ui';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'package:liztogo_pro/core/helper/string_format_helper.dart';
import 'package:liztogo_pro/core/theme/light/light.dart';
import 'package:liztogo_pro/core/utils/audio_utils.dart';
import 'package:liztogo_pro/core/utils/my_images.dart';
import 'package:liztogo_pro/core/utils/util.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/route/route.dart';
import 'package:liztogo_pro/core/utils/messages.dart';
import 'package:liztogo_pro/data/controller/localization/localization_controller.dart';
import 'package:liztogo_pro/core/di_service/di_services.dart' as di_service;
import 'package:liztogo_pro/presentation/screens/dashboard/forground_task_widget.dart';
import 'package:liztogo_pro/data/services/forground_location_service.dart';
import 'package:liztogo_pro/data/services/push_notification_service.dart';
import 'package:liztogo_pro/environment.dart';
import 'data/services/api_client.dart';
import 'package:timezone/data/latest.dart' as tz;
import 'package:toastification/toastification.dart';

//APP ENTRY POINT
Future<void> main() async {
  printX('🚀 main() started');

  WidgetsFlutterBinding.ensureInitialized();
  printX('✅ WidgetsFlutterBinding initialized');

  // Global error handlers
  FlutterError.onError = (FlutterErrorDetails details) {
    printX('❌ FlutterError: ${details.exception}');
    printX('   Stack: ${details.stack}');
  };
  PlatformDispatcher.instance.onError = (error, stack) {
    printX('❌ PlatformDispatcher error: $error');
    printX('   Stack: $stack');
    return true;
  };

  // Initialize the API client for network communication
  await ApiClient.init();
  printX('✅ ApiClient initialized');

  // Load and initialize localization/language support
  Map<String, Map<String, String>> languages = await di_service.init();
  printX('✅ DI services initialized, languages: ${languages.keys}');

  // Configure app UI to support all screen sizes
  MyUtils.allScreen();

  // Lock device orientation to portrait mode
  MyUtils().stopLandscape();

  // Initialize audio utilities
  AudioUtils();

  // Override HTTP settings
  HttpOverrides.global = MyHttpOverrides();
  tz.initializeTimeZones();
  FlutterForegroundTask.initCommunicationPort();

  printX('🚀 runApp() called');
  runApp(OvoApp(languages: languages));

  // Fire-and-forget push notification setup so the system permission
  // dialog doesn't block the splash → onboard route transition
  unawaited(
    PushNotificationService(apiClient: Get.find()).setupInteractedMessage()
      .then((_) => printX('✅ PushNotificationService initialized'))
      .catchError((e, stack) {
        printX('❌ PushNotificationService error: $e');
        printX('   Stack: $stack');
      }),
  );
}

@pragma('vm:entry-point')
void startForgroundTask() {
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
    MyUtils.precacheImagesFromPathList(context, [MyImages.backgroundImage, MyImages.logoWhite]);
  }

  @override
  Widget build(BuildContext context) {
    printX('🏗️ OvoApp.build() called');
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
          getPages: RouteHelper.routes,
          locale: localizeController.locale,
          translations: Messages(languages: widget.languages),
          fallbackLocale: Locale(
            localizeController.locale.languageCode,
            localizeController.locale.countryCode,
          ),
          builder: (context, child) {
            printX('🏗️ GetMaterialApp.builder called, child=${child?.runtimeType}');
            if (Platform.isAndroid) {
              return ForGroundTaskWidget(
                key: foregroundTaskKey,
                onWillStart: () {
                  return Future.value(true);
                },
                callback: startForgroundTask,
                child: child ?? Container(),
              );
            }
            return child!;
          },
        ),
      ),
    );
  }
}
