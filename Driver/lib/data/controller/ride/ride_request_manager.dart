import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/helper/shared_preference_helper.dart';
import 'package:liztogo_pro/core/helper/string_format_helper.dart';
import 'package:liztogo_pro/core/route/route.dart';
import 'package:liztogo_pro/core/utils/audio_utils.dart';
import 'package:liztogo_pro/core/utils/method.dart';
import 'package:liztogo_pro/core/utils/url_container.dart';
import 'package:liztogo_pro/data/controller/dashboard/dashboard_controller.dart';
import 'package:liztogo_pro/data/model/global/response_model/response_model.dart';
import 'package:liztogo_pro/data/model/global/ride/ride_model.dart';
import 'package:liztogo_pro/data/model/ride/ride_opportunity_model.dart';
import 'package:liztogo_pro/data/services/api_client.dart';
import 'package:liztogo_pro/data/services/pusher_service.dart';
import 'package:liztogo_pro/presentation/components/snack_bar/show_custom_snackbar.dart';
import 'package:vibration/vibration.dart';

class RideRequestManager extends GetxController with WidgetsBindingObserver {
  static const MethodChannel _intentChannel = MethodChannel('com.jsoft.proveedor/ride_intent');

  final ApiClient apiClient;

  RideRequestManager({required this.apiClient});

  static RideRequestManager get instance {
    if (Get.isRegistered<RideRequestManager>()) {
      return Get.find<RideRequestManager>();
    }
    return Get.put(RideRequestManager(apiClient: Get.find<ApiClient>()), permanent: true);
  }

  // Idempotency state
  final Map<String, DateTime> _processedRideMap = {};
  String? _currentDisplayingRideId;
  RideOpportunity? _currentOpportunity;

  AppLifecycleState _lifecycleState = AppLifecycleState.resumed;
  bool get isAppInForeground => _lifecycleState == AppLifecycleState.resumed;

  RideOpportunity? get currentOpportunity => _currentOpportunity;
  String? get currentDisplayingRideId => _currentDisplayingRideId;
  bool get hasActiveRequest => _currentDisplayingRideId != null && _currentOpportunity != null;

  bool isAccepting = false;

  @override
  void onInit() {
    super.onInit();
    WidgetsBinding.instance.addObserver(this);
    if (Platform.isAndroid) {
      _setupNativeIntentListener();
      _checkInitialIntent();
    }
  }

  @override
  void onClose() {
    WidgetsBinding.instance.removeObserver(this);
    super.onClose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _lifecycleState = state;
    printX('📱 RideRequestManager lifecycle changed to: $state');

    if (state == AppLifecycleState.resumed) {
      _onAppResumed();
    }
  }

  void _onAppResumed() {
    // 1. Check & Reconnect WebSocket if needed
    _ensureWebSocketConnection();

    // 2. Check for any pending ride intent from Android
    if (Platform.isAndroid) {
      _checkInitialIntent();
    }

    // 3. Sync driver state
    if (Get.isRegistered<DashBoardController>()) {
      Get.find<DashBoardController>().initialData(shouldLoad: false);
    }
  }

  Future<void> _ensureWebSocketConnection() async {
    try {
      final userId = apiClient.sharedPreferences.getString(SharedPreferenceHelper.userIdKey) ?? '';
      if (userId.isNotEmpty && !PusherManager().isConnected()) {
        printX('🔄 Reconnecting Pusher on app resume...');
        await PusherManager().checkAndInitIfNeeded("private-rider-driver-$userId");
      }
    } catch (e) {
      printE('Error checking websocket on resume: $e');
    }
  }

  void _setupNativeIntentListener() {
    _intentChannel.setMethodCallHandler((call) async {
      if (call.method == 'onNewRideIntent') {
        printX('📲 Native Intent received new ride: ${call.arguments}');
        final raw = call.arguments;
        _handleRawIncomingRide(raw, source: 'FULL_SCREEN_INTENT');
      }
    });
  }

  Future<void> _checkInitialIntent() async {
    try {
      final initialData = await _intentChannel.invokeMethod<String>('getInitialRide');
      if (initialData != null && initialData.isNotEmpty) {
        printX('📲 Found initial ride intent on launch: $initialData');
        _handleRawIncomingRide(initialData, source: 'LAUNCH_INTENT');
      }
    } catch (_) {}
  }

  void _handleRawIncomingRide(dynamic raw, {required String source}) {
    if (raw == null) return;
    try {
      Map<String, dynamic> data;
      if (raw is String) {
        data = Map<String, dynamic>.from(jsonDecode(raw));
      } else if (raw is Map) {
        data = Map<String, dynamic>.from(raw);
      } else {
        return;
      }
      onNewRideReceived(data, source: source);
    } catch (e) {
      printE('Error parsing raw incoming ride: $e');
    }
  }

  /// Entry point for new ride opportunities from WebSocket, FCM or Native Intent
  void onNewRideReceived(dynamic rideData, {required String source}) {
    RideOpportunity opportunity;
    if (rideData is RideOpportunity) {
      opportunity = rideData;
    } else if (rideData is RideModel) {
      opportunity = RideOpportunity.fromRideModel(rideData, source: source);
    } else if (rideData is Map<String, dynamic>) {
      if (rideData['ride'] is Map<String, dynamic>) {
        final rideModel = RideModel.fromJson(Map<String, dynamic>.from(rideData['ride']));
        opportunity = RideOpportunity.fromRideModel(rideModel, source: source);
      } else {
        opportunity = RideOpportunity.fromMap(rideData, source: source);
      }
    } else {
      printE('Unsupported rideData type in onNewRideReceived: ${rideData.runtimeType}');
      return;
    }

    final rideId = opportunity.id;
    if (rideId.isEmpty || rideId == '-1') {
      printX('⚠️ Invalid ride ID received ($rideId). Ignoring.');
      return;
    }

    // IDEMPOTENCY / DEDUPLICATION CHECK
    if (!shouldProcessRide(rideId)) {
      printX('🛡️ Idempotency: Ride $rideId already active or processed recently. Ignoring duplicate from $source.');
      return;
    }

    // Check if driver is already busy with an active ride
    if (_isDriverBusy()) {
      printX('🚗 Driver is currently busy with another ride. Ignoring ride $rideId.');
      _processedRideMap[rideId] = DateTime.now();
      return;
    }

    printX('🚕 [RideRequestManager] Showing new ride opportunity $rideId from $source!');

    _currentDisplayingRideId = rideId;
    _currentOpportunity = opportunity;
    update();

    // Play alert sound & vibration
    _playAlertEffects();

    // Wake screen if device screen is locked/off
    _wakeScreen();

    // Navigate to Ride Request Screen
    _displayRideRequestScreen(opportunity);
  }

  bool shouldProcessRide(String rideId) {
    final now = DateTime.now();

    // Cleanup entries older than 5 minutes
    _processedRideMap.removeWhere((_, time) => now.difference(time).inMinutes > 5);

    // If currently displaying this ride
    if (_currentDisplayingRideId == rideId) {
      return false;
    }

    // If processed in the last 60 seconds
    if (_processedRideMap.containsKey(rideId)) {
      final lastTime = _processedRideMap[rideId]!;
      if (now.difference(lastTime).inSeconds < 60) {
        return false;
      }
    }

    _processedRideMap[rideId] = now;
    return true;
  }

  bool _isDriverBusy() {
    if (Get.currentRoute == RouteHelper.rideDetailsScreen) {
      return true;
    }
    if (Get.isRegistered<DashBoardController>()) {
      final dashboard = Get.find<DashBoardController>();
      if (dashboard.runningRide != null && dashboard.runningRide?.id != null && dashboard.runningRide?.id != '-1') {
        return true;
      }
    }
    return false;
  }

  void _playAlertEffects() {
    try {
      AudioUtils.playAudio(apiClient.getNotificationAudio());
    } catch (_) {}
    try {
      Vibration.hasVibrator().then((hasVib) {
        if (hasVib == true) {
          Vibration.vibrate(duration: 1000);
        }
      });
    } catch (_) {}
  }

  Future<void> _wakeScreen() async {
    if (!Platform.isAndroid) return;
    try {
      await _intentChannel.invokeMethod('wakeScreen');
    } catch (_) {}
  }

  void _displayRideRequestScreen(RideOpportunity opportunity) {
    if (Get.currentRoute == RouteHelper.rideRequestScreen) {
      // Replace with latest opportunity if different
      Get.offNamed(RouteHelper.rideRequestScreen, arguments: opportunity);
    } else {
      Get.toNamed(RouteHelper.rideRequestScreen, arguments: opportunity);
    }
  }

  /// Accept Ride
  Future<bool> acceptRide(RideOpportunity opportunity, {double? customAmount}) async {
    if (isAccepting) return false;
    isAccepting = true;
    update();

    try {
      final url = '${UrlContainer.baseUrl}driver/rides/accept/${opportunity.id}';
      final params = <String, String>{
        'amount': (customAmount ?? opportunity.fare).toStringAsFixed(2),
      };

      printX('⚡ [RideRequestManager] Sending accept request to: $url with params: $params');
      final ResponseModel response = await apiClient.request(url, Method.postMethod, params, passHeader: true);

      isAccepting = false;
      update();

      if (response.statusCode == 200) {
        final data = response.responseJson;
        final status = data['status']?.toString();

        if (status == 'success') {
          printX('✅ [RideRequestManager] Ride ${opportunity.id} accepted successfully!');
          _currentDisplayingRideId = null;
          _currentOpportunity = null;

          // Update dashboard state
          if (Get.isRegistered<DashBoardController>()) {
            Get.find<DashBoardController>().initialData(shouldLoad: false);
          }

          // Close ride request screen and navigate to active ride details
          if (Get.currentRoute == RouteHelper.rideRequestScreen) {
            Get.offNamed(RouteHelper.rideDetailsScreen, arguments: opportunity.id);
          } else {
            Get.toNamed(RouteHelper.rideDetailsScreen, arguments: opportunity.id);
          }

          CustomSnackBar.success(successList: ['¡Carrera aceptada exitosamente!']);
          return true;
        } else {
          // Backend rejection reason
          final messageList = data['message'];
          String errorMsg = 'No fue posible aceptar la carrera';
          if (messageList is Map && messageList['error'] is List && (messageList['error'] as List).isNotEmpty) {
            errorMsg = (messageList['error'] as List).first.toString();
          } else if (messageList is List && messageList.isNotEmpty) {
            errorMsg = messageList.first.toString();
          }
          _handleAcceptanceFailed(opportunity, errorMsg);
          return false;
        }
      } else {
        _handleAcceptanceFailed(opportunity, response.message.isNotEmpty ? response.message : 'Error al conectar con el servidor');
        return false;
      }
    } catch (e) {
      isAccepting = false;
      update();
      printE('Error accepting ride: $e');
      _handleAcceptanceFailed(opportunity, 'Error inesperado al aceptar la carrera');
      return false;
    }
  }

  void _handleAcceptanceFailed(RideOpportunity opportunity, String reason) {
    CustomSnackBar.error(errorList: [reason]);
    dismissCurrentRequest();
  }

  /// Reject Ride
  Future<void> rejectRide(RideOpportunity opportunity) async {
    printX('🚫 [RideRequestManager] Driver rejected ride ${opportunity.id}');
    dismissCurrentRequest();

    try {
      final url = '${UrlContainer.baseUrl}driver/rides/reject/${opportunity.id}';
      await apiClient.request(url, Method.postMethod, {}, passHeader: true);
    } catch (_) {}
  }

  /// Handle Ride Expiration
  void onRideExpired(RideOpportunity opportunity) {
    if (_currentDisplayingRideId == opportunity.id) {
      printX('⌛ [RideRequestManager] Ride ${opportunity.id} expired!');
      dismissCurrentRequest();
    }
  }

  void dismissCurrentRequest() {
    _currentDisplayingRideId = null;
    _currentOpportunity = null;
    update();

    if (Get.currentRoute == RouteHelper.rideRequestScreen) {
      Get.back();
    }
  }
}
