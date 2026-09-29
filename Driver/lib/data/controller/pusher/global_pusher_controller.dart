import 'dart:async';
import 'dart:convert';

import 'package:get/get.dart';
import 'package:liztogo_pro/core/helper/shared_preference_helper.dart';
import 'package:liztogo_pro/core/route/route.dart';
import 'package:liztogo_pro/core/utils/audio_utils.dart';
import 'package:liztogo_pro/data/controller/dashboard/dashboard_controller.dart';
import 'package:liztogo_pro/data/controller/dashboard/ride_queue_manager.dart';
import 'package:liztogo_pro/data/model/global/pusher/pusher_event_response_model.dart';
import 'package:liztogo_pro/data/model/global/ride/ride_model.dart';
import 'package:liztogo_pro/data/services/pusher_service.dart';

import '../../../core/helper/string_format_helper.dart';
import '../../services/api_client.dart';

import '../../../core/utils/url_container.dart';
import '../../services/realtime_service.dart';

class GlobalPusherController extends GetxController {
  ApiClient apiClient;
  DashBoardController dashBoardController;
  StreamSubscription? _realtimeSub;

  GlobalPusherController({
    required this.apiClient,
    required this.dashBoardController,
  });

  @override
  void onInit() {
    super.onInit();

    PusherManager().addListener(onEvent);
    _initRealtimeWs();
  }

  void _initRealtimeWs() async {
    try {
      final token = apiClient.sharedPreferences.getString(SharedPreferenceHelper.accessTokenKey) ?? '';
      final userId = apiClient.sharedPreferences.getString(SharedPreferenceHelper.userIdKey) ?? '';
      if (token.isNotEmpty) {
        await RealtimeManager().init(wsUrl: UrlContainer.wsUrl, token: token);
        if (userId.isNotEmpty) {
          RealtimeManager().subscribe('driver.$userId');
        }
        _realtimeSub?.cancel();
        _realtimeSub = RealtimeManager().onBroadcast.listen(_handleRealtimeBroadcast);
      }
    } catch (e) {
      printX("Error init RealtimeManager in GlobalPusherController: $e");
    }
  }

  void _handleRealtimeBroadcast(Map<String, dynamic> msg) {
    try {
      final topic = msg['topic']?.toString() ?? '';
      final payload = msg['payload'];
      printX("Realtime WS Broadcast Driver: $topic -> $payload");

      if (payload is Map<String, dynamic>) {
        final eventType = payload['type']?.toString().toLowerCase() ?? '';
        final eventName = (payload['event'] ?? eventType).toString().toLowerCase();

        if (eventName == "new_ride" && !isRideDetailsPage()) {
          AudioUtils.playAudio(apiClient.getNotificationAudio());
          dashBoardController.initialData(shouldLoad: false);
        } else if (eventName == "bid_reject" && !isRideDetailsPage()) {
          dashBoardController.initialData(shouldLoad: false);
        } else if (activeEventList.contains(eventName) && !isRideDetailsPage()) {
          final rideId = payload['ride_id']?.toString() ?? payload['ride']?['id']?.toString();
          if (rideId != null) {
            Get.toNamed(RouteHelper.rideDetailsScreen, arguments: rideId);
          }
        }
      }
    } catch (e) {
      printE("Error handling Realtime WS event: $e");
    }
  }

  List<String> activeEventList = [
    "bid_accept",
    "cash_payment_request",
    "online_payment_received",
  ];

  void onEvent(PusherEvent event) {
    try {
      printX("Global pusher event: ${event.eventName}");
      if (event.eventName == "" || event.data == "{}") return;

      final eventName = event.eventName.toLowerCase();

      //Dashbaod New Ride Popup and Rides Management
      if (eventName == "new_ride" && !isRideDetailsPage()) {
        AudioUtils.playAudio(apiClient.getNotificationAudio());
        PusherResponseModel model = PusherResponseModel.fromJson(
          jsonDecode(event.data),
        );
        final modifyData = PusherResponseModel(
          eventName: eventName,
          channelName: event.channelName,
          data: model.data,
        );

        dashBoardController.updateMainAmount(
          double.tryParse(modifyData.data?.ride?.amount.toString() ?? "0.00") ?? 0,
        );

        // Get or create RideQueueManager
        final queueManager = Get.isRegistered<RideQueueManager>() ? Get.find<RideQueueManager>() : Get.put(RideQueueManager());

        // Add ride to queue
        queueManager.addRideToQueue(
          RideQueueItem(
            ride: modifyData.data?.ride ?? RideModel(id: "-1"),
            currency: Get.find<ApiClient>().getCurrency(),
            currencySym: Get.find<ApiClient>().getCurrency(isSymbol: true),
            dashboardController: dashBoardController,
          ),
        );
        dashBoardController.initialData(shouldLoad: false);
      }
      //Check Customer reject my bid
      if (eventName == "bid_reject" && !isRideDetailsPage()) {
        dashBoardController.initialData(shouldLoad: false);
      }
      //Go to Ride Details Page Payment Complete
      if (activeEventList.contains(eventName) && !isRideDetailsPage()) {
        PusherResponseModel model = PusherResponseModel.fromJson(
          jsonDecode(event.data),
        );
        final pusherData = PusherResponseModel(
          eventName: eventName,
          channelName: event.channelName,
          data: model.data,
        );

        Get.toNamed(
          RouteHelper.rideDetailsScreen,
          arguments: pusherData.data?.ride?.id,
        );
      }
    } catch (e) {
      printE("Error handling event ${event.eventName}: $e");
    }
  }

  bool isRideDetailsPage() {
    return Get.currentRoute == RouteHelper.rideDetailsScreen;
  }

  @override
  void onClose() {
    _realtimeSub?.cancel();
    PusherManager().removeListener(onEvent);
    super.onClose();
  }

  Future<void> ensureConnection({String? channelName}) async {
    try {
      var userId = apiClient.sharedPreferences.getString(
            SharedPreferenceHelper.userIdKey,
          ) ??
          '';
      await PusherManager().checkAndInitIfNeeded(
        channelName ?? "private-rider-driver-$userId",
      );
    } catch (e) {
      printX("Error ensuring connection: $e");
    }
  }
}
