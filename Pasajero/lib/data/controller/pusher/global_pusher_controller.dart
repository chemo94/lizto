import 'dart:async';
import 'package:liztogo/core/helper/shared_preference_helper.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'dart:convert';
import 'package:liztogo/core/route/route.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/controller/ride/ride_details/ride_details_controller.dart';
import 'package:liztogo/data/model/global/pusher/pusher_event_response_model.dart';
import 'package:liztogo/data/services/pusher_service.dart';
import 'package:liztogo/data/services/realtime_service.dart';
import 'package:get/get.dart';
import 'package:liztogo/data/services/api_client.dart';

class GlobalPusherController extends GetxController {
  ApiClient apiClient;
  StreamSubscription<Map<String, dynamic>>? _realtimeWsSub;
  String? _userTopic;
  GlobalPusherController({required this.apiClient});

  @override
  void onInit() {
    super.onInit();

    PusherManager().addListener(onEvent);
    _initRealtimeWs();
  }

  Future<void> _initRealtimeWs() async {
    final token = apiClient.sharedPreferences.getString(SharedPreferenceHelper.accessTokenKey) ?? '';
    final userId = apiClient.getUserID();
    if (token.isEmpty || userId.isEmpty) return;

    try {
      final connection = RealtimeManager().init(wsUrl: UrlContainer.wsUrl, token: token);
      _userTopic = 'user.$userId';
      _realtimeWsSub?.cancel();
      _realtimeWsSub = RealtimeManager().onTopic(_userTopic!).listen(_onRealtimeUserMessage);
      RealtimeManager().subscribe(_userTopic!);
      await connection;
      printX('GlobalRealtimeController: subscribed to $_userTopic');
    } catch (e) {
      printX('Error initializing passenger native realtime: $e');
    }
  }

  void _onRealtimeUserMessage(Map<String, dynamic> message) {
    final payload = message['payload'];
    if (payload is! Map) return;

    final event = Map<String, dynamic>.from(payload);
    final type = (event['type'] ?? event['event_type'])?.toString().toUpperCase();
    if (type != 'BID_ACCEPT' && type != 'NEW_BID' && type != 'NEW_RIDE') return;

    final ride = event['ride'];
    final bid = event['bid'];
    final rideData = ride is Map ? ride : const <String, dynamic>{};
    final bidData = bid is Map ? bid : const <String, dynamic>{};
    final rideId = (event['ride_id'] ?? event['rideId'] ?? rideData['id'] ?? bidData['ride_id'] ?? bidData['rideId'])?.toString();
    if (rideId == null || rideId.isEmpty || rideId == 'null') return;

    if (isRideDetailsPage()) {
      if (Get.isRegistered<RideDetailsController>()) {
        final rideController = Get.find<RideDetailsController>();
        if (rideController.ride.id?.toString() == rideId) {
          rideController.getRideDetails(rideId, shouldLoading: false);
        }
      }
      return;
    }

    Get.toNamed(RouteHelper.rideDetailsScreen, arguments: rideId);
  }

  List<String> activeEventList = ["ride_end", "pick_up", "cash_payment_received", "new_bid", "bid_accept"];

  void onEvent(PusherEvent event) {
    try {
      printD("Global pusher event: ${event.eventName}");
      printX("Global pusher data: ${event.data}");
      if (event.eventName == "" || event.data == "{}") return;

      final eventName = event.eventName.toLowerCase();

      final data = jsonDecode(event.data);
      final model = PusherResponseModel.fromJson(data);

      if (activeEventList.contains(eventName) && !isRideDetailsPage()) {
        final rideId = eventName == "new_bid" ? model.data?.bid?.rideId : model.data?.ride?.id;
        if (rideId != null) {
          Get.toNamed(RouteHelper.rideDetailsScreen, arguments: rideId);
        }
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
    PusherManager().removeListener(onEvent);
    _realtimeWsSub?.cancel();
    if (_userTopic != null) RealtimeManager().unsubscribe(_userTopic!);
    super.onClose();
  }

  Future<void> ensureConnection({String? channelName}) async {
    try {
      var userId = apiClient.sharedPreferences.getString(SharedPreferenceHelper.userIdKey) ?? '';
      await PusherManager().checkAndInitIfNeeded(channelName ?? "private-rider-user-$userId");
    } catch (e) {
      printX("Error ensuring connection: $e");
    }
  }
}
