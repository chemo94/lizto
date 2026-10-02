import 'dart:convert';
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:get/get.dart' hide Response;
import 'package:liztogo_pro/core/helper/string_format_helper.dart';
import 'package:liztogo_pro/data/controller/dashboard/dashboard_controller.dart';
import 'package:path_provider/path_provider.dart';
import 'package:liztogo_pro/data/controller/ride/ride_request_manager.dart';
import '../../core/helper/shared_preference_helper.dart';
import '../../core/utils/method.dart';
import '../../core/utils/url_container.dart';
import '../../data/model/global/response_model/response_model.dart';
import '../../firebase_options.dart';
import 'api_client.dart';
import 'otp_auto_fill_service.dart';
import 'package:get/get.dart' as getx;

@pragma('vm:entry-point')
Future<void> _messageHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);

  final data = message.data;
  final isRide = data['template_name'] == 'NEW_RIDE' || data['type'] == 'new_ride' || (data['pickup_location'] != null && data['destination'] != null);

  if (isRide) {
    try {
      final FlutterLocalNotificationsPlugin fln = FlutterLocalNotificationsPlugin();
      const AndroidInitializationSettings androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
      const InitializationSettings initSettings = InitializationSettings(android: androidSettings);
      await fln.initialize(initSettings);

      const AndroidNotificationChannel rideChannel = AndroidNotificationChannel(
        'ride_requests_channel',
        'Solicitudes de Carrera',
        description: 'Notificaciones urgentes de nuevas carreras',
        importance: Importance.max,
        playSound: true,
        enableVibration: true,
        enableLights: true,
      );
      await fln.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(rideChannel);

      final rideId = (data['ride_id'] ?? data['id'] ?? '0').toString();
      final pickup = (data['pickup_location'] ?? 'Recojo').toString();
      final destination = (data['destination'] ?? 'Destino').toString();
      final amount = (data['amount'] ?? '').toString();
      final distance = (data['distance'] ?? '').toString();

      final title = amount.isNotEmpty ? '🚕 ¡Nueva Carrera! S/ $amount' : '🚕 ¡Nueva Carrera Disponible!';
      final body = distance.isNotEmpty ? '$pickup ➔ $destination ($distance km)' : '$pickup ➔ $destination';

      await fln.show(
        int.tryParse(rideId) ?? (DateTime.now().millisecondsSinceEpoch ~/ 1000),
        title,
        body,
        NotificationDetails(
          android: AndroidNotificationDetails(
            rideChannel.id,
            rideChannel.name,
            channelDescription: rideChannel.description,
            icon: '@mipmap/ic_launcher',
            importance: Importance.max,
            priority: Priority.max,
            playSound: true,
            enableVibration: true,
            enableLights: true,
            fullScreenIntent: true,
            category: AndroidNotificationCategory.call,
            visibility: NotificationVisibility.public,
            ongoing: true,
            autoCancel: true,
            styleInformation: BigTextStyleInformation(
              '$pickup\n➔ $destination\nTarifa: S/ $amount | Distancia: $distance',
              contentTitle: title,
              summaryText: 'Nueva carrera asignable',
            ),
          ),
          iOS: const DarwinNotificationDetails(
            presentAlert: true,
            presentBadge: true,
            presentSound: true,
            interruptionLevel: InterruptionLevel.timeSensitive,
          ),
        ),
        payload: jsonEncode(data),
      );
    } catch (e) {
      printX('Error showing fullScreen notification in background: $e');
    }
  }
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

    // Request permission only once via Firebase Messaging
    await messaging.requestPermission(
      alert: true,
      announcement: false,
      badge: true,
      carPlay: false,
      criticalAlert: false,
      provisional: false,
      sound: true,
    );
    await _requestPermissions();

    FirebaseMessaging.onMessageOpenedApp.listen((RemoteMessage message) {
      printX('onMessageOpenedApp ${message.toMap()}');
      _handleOtpFromPush(message.data);

      final isRide = message.data['template_name'] == 'NEW_RIDE' || message.data['type'] == 'new_ride' || (message.data['pickup_location'] != null && message.data['destination'] != null);
      if (isRide) {
        RideRequestManager.instance.onNewRideReceived(message.data, source: 'FCM_OPEN');
      }

      try {
        if (Get.isRegistered<DashBoardController>()) {
          Get.find<DashBoardController>().initialData(shouldLoad: false);
        }
      } catch (_) {}
    });

    final initialMessage = await messaging.getInitialMessage();
    if (initialMessage != null) {
      _handleOtpFromPush(initialMessage.data);

      final isRide = initialMessage.data['template_name'] == 'NEW_RIDE' || initialMessage.data['type'] == 'new_ride' || (initialMessage.data['pickup_location'] != null && initialMessage.data['destination'] != null);
      if (isRide) {
        RideRequestManager.instance.onNewRideReceived(initialMessage.data, source: 'FCM_INITIAL');
      }

      try {
        if (Get.isRegistered<DashBoardController>()) {
          Get.find<DashBoardController>().initialData(shouldLoad: false);
        }
      } catch (_) {}
    }

    await enableIOSNotifications();
    await registerNotificationListeners();
  }

  Future<void> registerNotificationListeners() async {
    AndroidNotificationChannel channel = androidNotificationChannel();
    AndroidNotificationChannel rideChannel = rideRequestsNotificationChannel();
    final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();
    await flutterLocalNotificationsPlugin.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(channel);
    await flutterLocalNotificationsPlugin.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(rideChannel);
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
            Map<String, dynamic> payload = payloadMap.map(
              (key, value) => MapEntry(key.toString(), value),
            );

            // Handle Urgent Ride Notification Taps
            if (payload['template_name'] == 'NEW_RIDE' || payload['type'] == 'new_ride' || (payload['pickup_location'] != null && payload['destination'] != null)) {
              RideRequestManager.instance.onNewRideReceived(payload, source: 'NOTIFICATION_RESPONSE');
              return;
            }

            printX('remarkNotification ${payload['for_app']}');
            printX('remarkNotification ${payload['ride_id']}');
            String? remark = payload['for_app']?.toString();

            if (remark != null && remark.isNotEmpty && remark.contains('-')) {
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
      if (message == null) return;
      _handleOtpFromPush(message.data);

      final isRide = message.data['template_name'] == 'NEW_RIDE' || message.data['type'] == 'new_ride' || (message.data['pickup_location'] != null && message.data['destination'] != null);
      if (isRide) {
        // Foreground: RideRequestManager displays the RideRequestScreen directly without duplicating notifications
        RideRequestManager.instance.onNewRideReceived(message.data, source: 'FCM_FOREGROUND');
        return;
      }

      RemoteNotification? notification = message.notification;
      AndroidNotification? android = message.notification?.android;
      printX(">>>>>> ${message.notification?.toMap()}");
      printX(">>>>>> ${android?.imageUrl}");
      if (notification != null && android != null) {
        late BigPictureStyleInformation bigPictureStyle;
        if (android.imageUrl != null) {
          Dio dio = Dio();
          Response<List<int>> response = await dio.get<List<int>>(
            android.imageUrl!,
            options: Options(responseType: ResponseType.bytes),
          );
          Uint8List bytes = Uint8List.fromList(response.data!);
          final String localImagePath = await _saveImageLocally(bytes);
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
            iOS: const DarwinNotificationDetails(
              presentAlert: true,
              presentBadge: true,
              presentSound: true,
              interruptionLevel: InterruptionLevel.timeSensitive,
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
              iOS: const DarwinNotificationDetails(
                presentAlert: true,
                presentBadge: true,
                presentSound: true,
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

  AndroidNotificationChannel rideRequestsNotificationChannel() => const AndroidNotificationChannel(
        'ride_requests_channel', // id
        'Solicitudes de Carrera', // title
        description: 'Notificaciones urgentes de nuevas carreras para conductores.',
        playSound: true,
        enableVibration: true,
        enableLights: true,
        importance: Importance.max,
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

    ResponseModel response = await apiClient.request(url, Method.postMethod, map, passHeader: true);
    return response.statusCode == 200;
  }

  Map<String, String> deviceTokenMap(String deviceToken) {
    Map<String, String> map = {
      'token': deviceToken.toString(),
      'device_token': deviceToken.toString(),
      'fcm_token': deviceToken.toString(),
    };
    return map;
  }
}
