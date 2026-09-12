import 'dart:convert';
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:get/get.dart' as getx;
import 'package:liztogo/core/helper/shared_preference_helper.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'package:liztogo/data/services/otp_auto_fill_service.dart';
import 'package:liztogo/firebase_options.dart';
import 'package:path_provider/path_provider.dart';

Future<void> _messageHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
}

class PushNotificationService {
  ApiClient apiClient;
  PushNotificationService({required this.apiClient});

  Future<void> setupInteractedMessage() async {
    FirebaseMessaging.onBackgroundMessage(_messageHandler);
    await Firebase.initializeApp(
      options: DefaultFirebaseOptions.currentPlatform,
    );
    FirebaseMessaging messaging = FirebaseMessaging.instance;
    await _requestPermissions();

    await messaging.requestPermission(
      alert: true,
      announcement: false,
      badge: true,
      carPlay: false,
      criticalAlert: false,
      provisional: false,
      sound: true,
    );

    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      printX('onMessageOpenedApp ${message.toMap()}');
      _handleOtpFromPush(message.data);
    });

    final initialMessage = await messaging.getInitialMessage();
    if (initialMessage != null) _handleOtpFromPush(initialMessage.data);

    await enableIOSNotifications();
    await registerNotificationListeners();
  }

  Future<void> registerNotificationListeners() async {
    AndroidNotificationChannel channel = androidNotificationChannel();
    final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();
    await flutterLocalNotificationsPlugin.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(channel);
    var androidSettings = const AndroidInitializationSettings(
      '@mipmap/ic_launcher',
    );
    var iOSSettings = const DarwinInitializationSettings(
      requestSoundPermission: true,
      requestBadgePermission: true,
      requestAlertPermission: true,
    );
    var initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iOSSettings,
    );
    flutterLocalNotificationsPlugin.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (message) async {
        try {
          String? payloadString = message.payload is String ? message.payload : jsonEncode(message.payload);
          printX('remarkNotification $payloadString');
          if (payloadString != null && payloadString.isNotEmpty) {
            Map<dynamic, dynamic> payloadMap = jsonDecode(payloadString);
            Map<String, String> payload = payloadMap.map(
              (key, value) => MapEntry(key.toString(), value.toString()),
            );

            printX('remarkNotification ${payload['for_app']}');
            printX('remarkNotification ${payload['ride_id']}');
            String? remark = payload['for_app'];

            if (remark != null && remark.isNotEmpty) {
              String route = remark.split('-')[0];
              String id = remark.split('-')[1];
              //redirect any specific page
              getx.Get.toNamed(route, arguments: id);
            }
          }
        } catch (e) {
          if (kDebugMode) {
            printX(e.toString());
          }
        }
      },
    );

    FirebaseMessaging.onMessage.listen((RemoteMessage? message) async {
      _handleOtpFromPush(message!.data);

      RemoteNotification? notification = message.notification;
      AndroidNotification? android = message.notification?.android;

      if (notification != null && android != null) {
        late BigPictureStyleInformation bigPictureStyle;
        if (android.imageUrl != null) {
          Dio dio = Dio();
          Response<List<int>> response = await dio.get<List<int>>(
            android.imageUrl!,
            options: Options(
              responseType: ResponseType.bytes,
            ),
          );
          Uint8List bytes = Uint8List.fromList(response.data!);
          final String localImagePath = await _saveImageLocally(
            bytes,
          );
          bigPictureStyle = BigPictureStyleInformation(
            FilePathAndroidBitmap(localImagePath),
            contentTitle: notification.title,
            summaryText: notification.body,
          );
        }
        flutterLocalNotificationsPlugin.show(
          notification.hashCode,
          notification.title,
          notification.body,
          NotificationDetails(
            android: AndroidNotificationDetails(
              channel.id,
              channel.name,
              channelDescription: channel.description,
              icon: '@mipmap/ic_launcher',
              playSound: true,
              enableVibration: true,
              enableLights: true,
              fullScreenIntent: true,
              priority: Priority.high,
              styleInformation: android.imageUrl != null ? bigPictureStyle : const BigTextStyleInformation(''),
              importance: Importance.high,
            ),
          ),
          payload: jsonEncode(message.data),
        );
      } else {
        final templateName = message.data['template_name']?.toString() ?? '';
        final code = message.data['code']?.toString() ?? '';
        if (code.isNotEmpty && (templateName == 'EVER_CODE' || templateName == 'SVER_CODE')) {
          String title = templateName == 'EVER_CODE' ? 'Verificación de correo' : 'Verificación por SMS';
          flutterLocalNotificationsPlugin.show(
            DateTime.now().millisecondsSinceEpoch ~/ 1000,
            title,
            'Tu código de verificación es: $code',
            NotificationDetails(
              android: AndroidNotificationDetails(
                channel.id,
                channel.name,
                channelDescription: channel.description,
                icon: '@mipmap/ic_launcher',
                playSound: true,
                enableVibration: true,
                priority: Priority.high,
                importance: Importance.high,
              ),
            ),
            payload: jsonEncode(message.data),
          );
        }
      }
    });
  }

  Future<void> enableIOSNotifications() async {
    await FirebaseMessaging.instance.setForegroundNotificationPresentationOptions(
      alert: true, // Required to display a heads up notification
      badge: true,
      sound: true,
    );
  }

  AndroidNotificationChannel androidNotificationChannel() => const AndroidNotificationChannel(
        'high_importance_channel', // id
        'High Importance Notifications', // title
        description: 'This channel is used for important notifications.',
        playSound: true,
        enableVibration: true,
        enableLights: true,
        importance: Importance.high,
      );

  void _handleOtpFromPush(Map<String, dynamic> data) {
    try {
      final templateName = data['template_name']?.toString() ?? '';
      final code = data['code']?.toString() ?? '';
      if (code.isEmpty || (templateName != 'EVER_CODE' && templateName != 'SVER_CODE')) {
        return;
      }
      otpPushCode.value = code;
      debugPrint('[Push] OTP code set: $code');
    } catch (e) {
      debugPrint('[Push] _handleOtpFromPush error: $e');
    }
  }

  Future<void> _requestPermissions() async {
    final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();

    if (Platform.isIOS || Platform.isMacOS) {
      await flutterLocalNotificationsPlugin.resolvePlatformSpecificImplementation<IOSFlutterLocalNotificationsPlugin>()?.requestPermissions(alert: true, badge: true, sound: true);
      await flutterLocalNotificationsPlugin.resolvePlatformSpecificImplementation<MacOSFlutterLocalNotificationsPlugin>()?.requestPermissions(alert: true, badge: true, sound: true);
    } else if (Platform.isAndroid) {
      final AndroidFlutterLocalNotificationsPlugin? androidImplementation = flutterLocalNotificationsPlugin.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
      await androidImplementation?.requestNotificationsPermission();
    }
  }

  // Function to save the image locally
  Future<String> _saveImageLocally(Uint8List bytes) async {
    final directory = await getTemporaryDirectory();
    final imagePath = '${directory.path}/notification_image.png';
    final file = File(imagePath);
    await file.writeAsBytes(bytes);
    return imagePath;
  }

  //
  Future<bool> sendUserToken({bool force = false}) async {
    FirebaseMessaging firebaseMessaging = FirebaseMessaging.instance;
    final cachedToken = apiClient.sharedPreferences.getString(
          SharedPreferenceHelper.fcmDeviceKey,
        ) ??
        '';

    String currentToken = '';
    try {
      if (Platform.isIOS) {
        String? apnsToken = await firebaseMessaging.getAPNSToken();
        int attempts = 0;
        while (apnsToken == null && attempts < 3) {
          attempts++;
          await Future.delayed(const Duration(milliseconds: 1000));
          apnsToken = await firebaseMessaging.getAPNSToken();
        }
        if (apnsToken == null) {
          printX("⚠️ [iOS] APNS token aún no disponible. Se enviará token FCM vía onTokenRefresh.");
          return false;
        }
      }
      currentToken = (await firebaseMessaging.getToken()) ?? '';
    } catch (e) {
      printX("⚠️ Error al obtener token FCM en iOS: $e");
    }

    bool success = currentToken.isNotEmpty;
    if (currentToken.isNotEmpty && (force || currentToken != cachedToken)) {
      success = await sendUpdatedToken(currentToken);
      if (success) {
        await apiClient.sharedPreferences.setString(
          SharedPreferenceHelper.fcmDeviceKey,
          currentToken,
        );
      }
    }

    firebaseMessaging.onTokenRefresh.listen((fcmDeviceToken) async {
      final storedToken = apiClient.sharedPreferences.getString(
            SharedPreferenceHelper.fcmDeviceKey,
          ) ??
          '';
      if (fcmDeviceToken == storedToken) return;

      final sent = await sendUpdatedToken(fcmDeviceToken);
      if (sent) {
        await apiClient.sharedPreferences.setString(
          SharedPreferenceHelper.fcmDeviceKey,
          fcmDeviceToken,
        );
      }
    });

    return success;
  }

  Future<bool> sendUpdatedToken(String deviceToken) async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.deviceTokenEndPoint}';
    Map<String, String> map = deviceTokenMap(deviceToken);

    final response = await apiClient.request(url, Method.postMethod, map, passHeader: true);
    return response.statusCode >= 200 && response.statusCode < 300;
  }

  Map<String, String> deviceTokenMap(String deviceToken) {
    Map<String, String> map = {'token': deviceToken.toString()};
    return map;
  }
}
