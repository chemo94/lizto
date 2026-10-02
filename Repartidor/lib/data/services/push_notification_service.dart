import 'dart:convert';
import 'dart:io';
import 'package:dio/dio.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:path_provider/path_provider.dart';
import 'package:get/get.dart' as getx;
import '../../core/helper/shared_preference_helper.dart';
import '../../core/utils/audio_utils.dart';
import '../../core/utils/method.dart';
import '../../core/utils/url_container.dart';
import '../../data/controller/dashboard/dashboard_controller.dart';
import '../../data/controller/delivery/courier_controller.dart';
import '../../data/controller/delivery/courier_notification_service.dart';
import '../../firebase_options.dart';
import '../../presentation/screens/delivery/courier_job_detail_screen.dart';
import 'api_client.dart';
import 'otp_auto_fill_service.dart';

@pragma('vm:entry-point')
Future<void> _messageHandler(RemoteMessage message) async {
  await Firebase.initializeApp(options: DefaultFirebaseOptions.currentPlatform);
  final data = message.data;
  final orderId = data['order_id']?.toString() ?? data['job_id']?.toString();
  final favorId = data['favor_id']?.toString();
  final offerId = data['offer_id']?.toString();
  final batchId = data['batch_id']?.toString();

  final isUrgentDelivery = (orderId != null && orderId.isNotEmpty) ||
      (favorId != null && favorId.isNotEmpty) ||
      (offerId != null && offerId.isNotEmpty) ||
      (batchId != null && batchId.isNotEmpty) ||
      data['type'] == 'courier_offer' ||
      data['type'] == 'new_batch' ||
      data['template_name'] == 'COURIER_OFFER';

  if (isUrgentDelivery) {
    try {
      final FlutterLocalNotificationsPlugin fln = FlutterLocalNotificationsPlugin();
      const AndroidInitializationSettings androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
      const InitializationSettings initSettings = InitializationSettings(android: androidSettings);
      await fln.initialize(initSettings);

      const AndroidNotificationChannel rideChannel = AndroidNotificationChannel(
        'ride_requests_channel',
        'Ofertas de Reparto',
        description: 'Notificaciones prioritarias de nuevos repartos y pedidos',
        importance: Importance.max,
        playSound: true,
        enableVibration: true,
        enableLights: true,
      );
      await fln.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()?.createNotificationChannel(rideChannel);

      final title = data['title']?.toString() ?? '🛵 ¡Nuevo Pedido Disponible!';
      final body = data['body']?.toString() ?? data['message']?.toString() ?? 'Tienes una nueva oferta de reparto lista para aceptar';
      final notifId = int.tryParse(orderId ?? offerId ?? batchId ?? '0') ?? (DateTime.now().millisecondsSinceEpoch ~/ 1000);

      await fln.show(
        notifId,
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
            styleInformation: BigTextStyleInformation(body),
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
      printX('Error showing fullScreen notification in background Repartidor: $e');
    }
    try {
      await AudioUtils.playNotificationSound();
    } catch (_) {}
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
      AudioUtils.stop();
      _handlePushData(message.data);
      _refreshControllers();
    });

    final initialMessage = await messaging.getInitialMessage();
    if (initialMessage != null) {
      _handleOtpFromPush(initialMessage.data);
      _handlePushData(initialMessage.data);
      _refreshControllers();
    }

    await enableIOSNotifications();
    await registerNotificationListeners();
  }

  Future<void> registerNotificationListeners() async {
    AndroidNotificationChannel channel = androidNotificationChannel();
    AndroidNotificationChannel rideChannel = rideRequestsNotificationChannel();
    final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();
    final androidImpl = flutterLocalNotificationsPlugin.resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
    await androidImpl?.createNotificationChannel(channel);
    await androidImpl?.createNotificationChannel(rideChannel);
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
        AudioUtils.stop();
        try {
          String? payloadString = message.payload is String ? message.payload : jsonEncode(message.payload);
          printX('remarkNotification $payloadString');
          if (payloadString != null && payloadString.isNotEmpty) {
            Map<dynamic, dynamic> payloadMap = jsonDecode(payloadString);
            Map<String, String> payload = payloadMap.map(
              (key, value) => MapEntry(key.toString(), value.toString()),
            );

            printX('remarkNotification ${payload['for_app']}');
            _handlePushData(payload);
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

      final data = message.data;
      final orderId = data['order_id']?.toString() ?? data['job_id']?.toString();
      final favorId = data['favor_id']?.toString();
      if ((orderId != null && orderId.isNotEmpty) || (favorId != null && favorId.isNotEmpty)) {
        await AudioUtils.playNotificationSound();
      }

      _refreshControllers();

      final type = data['type']?.toString() ?? '';
      if (type == 'new_delivery_request' ||
          type == 'new_job' ||
          type == 'targeted_delivery_request' ||
          type == 'new_favor' ||
          (favorId != null && favorId.isNotEmpty) ||
          (orderId != null && orderId.isNotEmpty)) {
        CourierNotificationService.showIncomingOrderAlertFromData(data);
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
        'ride_requests_channel',
        'Ofertas de Reparto',
        description: 'Notificaciones prioritarias de nuevos repartos y pedidos',
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
      printX("⚠️ Error fetching FCM token (likely APNS token not set yet): $e");
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
    Map<String, dynamic> map = deviceTokenMap(deviceToken);

    final response = await apiClient.request(url, Method.postMethod, map, passHeader: true);
    return response.statusCode >= 200 && response.statusCode < 300;
  }

  Map<String, dynamic> deviceTokenMap(String deviceToken) {
    Map<String, dynamic> map = {'token': deviceToken.toString()};
    return map;
  }

  void _refreshControllers() {
    try {
      if (getx.Get.isRegistered<CourierController>()) {
        getx.Get.find<CourierController>()
          ..loadPendingJobs()
          ..loadActiveJobs();
      }
      if (getx.Get.isRegistered<DashBoardController>()) {
        getx.Get.find<DashBoardController>().initialData(shouldLoad: false);
      }
    } catch (_) {}
  }

  void _handlePushData(Map<String, dynamic> data) {
    final orderId = data['order_id']?.toString() ?? data['job_id']?.toString();
    final favorId = data['favor_id']?.toString();
    final id = orderId ?? favorId;
    final type = data['type']?.toString() ?? (favorId != null ? 'favor' : 'delivery');
    AudioUtils.stop();
    if (id != null && id.isNotEmpty) {
      getx.Get.to(() => CourierJobDetailScreen(jobId: int.tryParse(id) ?? 0, jobType: type));
    }
  }
}
