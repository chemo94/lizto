import 'dart:io';
import 'package:firebase_core/firebase_core.dart';
import 'package:lizto_store/core/helper/string_format_helper.dart';
import 'package:lizto_store/core/theme/light/light.dart';
import 'package:lizto_store/core/utils/my_images.dart';
import 'package:lizto_store/core/utils/util.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/environment.dart';
import 'package:lizto_store/firebase_options.dart';
import 'package:lizto_store/data/services/push_notification_service.dart';
import 'package:lizto_store/core/route/route.dart';
import 'package:lizto_store/core/utils/messages.dart';
import 'package:lizto_store/data/controller/localization/localization_controller.dart';
import 'package:toastification/toastification.dart';
import 'core/di_service/di_services.dart' as di_service;
import 'data/services/api_client.dart';
import 'package:timezone/data/latest.dart' as tz;

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await ApiClient.init();

  Map<String, Map<String, String>> languages = await di_service.init();

  MyUtils.allScreen();

  MyUtils().stopLandscape();

  HttpOverrides.global = MyHttpOverrides();
  tz.initializeTimeZones();

  // Ejecutamos runApp inmediatamente para establecer la UI y enlazar la ventana con FlutterSceneDelegate
  runApp(OvoApp(languages: languages));

  // Inicializar Firebase y Notificaciones en segundo plano tras el render del primer frame
  WidgetsBinding.instance.addPostFrameCallback((_) async {
    try {
      await Firebase.initializeApp(
        options: DefaultFirebaseOptions.currentPlatform,
      );
      // Una vez inicializado Firebase, configuramos las notificaciones push
      PushNotificationService(apiClient: Get.find()).setupInteractedMessage();
    } catch (e) {
      printX("Error inicializando Firebase: $e");
    }
  });
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
        ),
      ),
    );
  }
}
