import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/helper/shared_preference_helper.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'dart:convert';
import 'package:liztogo/core/route/route.dart';
import 'package:liztogo/core/utils/audio_utils.dart';
import 'package:liztogo/core/utils/util.dart';
import 'package:liztogo/data/controller/ride/ride_details/ride_details_controller.dart';
import 'package:liztogo/data/model/general_setting/general_setting_response_model.dart';
import 'package:liztogo/data/model/global/pusher/pusher_event_response_model.dart';
import 'package:liztogo/data/services/pusher_service.dart';
import 'package:liztogo/presentation/components/dialog/show_custom_bid_dialog.dart';
import 'package:get/get.dart';
import 'package:liztogo/data/controller/ride/ride_meassage/ride_meassage_controller.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'dart:async';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/services/realtime_service.dart';

class PusherRideController extends GetxController {
  ApiClient apiClient;
  RideMessageController rideMessageController;
  RideDetailsController rideDetailsController;
  String rideID;
  StreamSubscription? _realtimeWsSub;
  String? _subscribedDriverTopic;

  PusherRideController({
    required this.apiClient,
    required this.rideMessageController,
    required this.rideDetailsController,
    required this.rideID,
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
      if (token.isNotEmpty && rideID.isNotEmpty) {
        await RealtimeManager().init(wsUrl: UrlContainer.wsUrl, token: token);
        RealtimeManager().subscribe('ride.$rideID');

        // Si ya hay conductor asignado, suscribirse a su posición
        final driverId = rideDetailsController.ride.driver?.id?.toString() ??
            rideDetailsController.ride.driverId?.toString();
        if (driverId != null && driverId.isNotEmpty && driverId != '0') {
          _subscribeDriverTopic(driverId);
        }

        _realtimeWsSub?.cancel();
        _realtimeWsSub = RealtimeManager().onBroadcast.listen(_handleRealtimeMessage);
      }
    } catch (e) {
      printX("Error init Realtime WS in Pasajero: $e");
    }
  }

  void _subscribeDriverTopic(String driverId) {
    if (_subscribedDriverTopic == 'driver.$driverId') return;
    if (_subscribedDriverTopic != null) {
      RealtimeManager().unsubscribe(_subscribedDriverTopic!);
    }
    _subscribedDriverTopic = 'driver.$driverId';
    RealtimeManager().subscribe(_subscribedDriverTopic!);
  }

  void _handleRealtimeMessage(Map<String, dynamic> msg) {
    try {
      final topic = msg['topic']?.toString() ?? '';
      final payload = msg['payload'];

      if (payload is Map<String, dynamic>) {
        // 1. Manejo de ubicación en vivo del taxista
        if (payload['type'] == 'driver_location') {
          final lat = (payload['lat'] as num?)?.toDouble() ?? 0.0;
          final lng = (payload['lng'] as num?)?.toDouble() ?? 0.0;
          final bearing = (payload['bearing'] as num?)?.toDouble() ?? 0.0;
          final driverId = payload['driver_id']?.toString() ??
              rideDetailsController.ride.driver?.id?.toString() ??
              'driver';

          if (lat != 0 && lng != 0) {
            rideDetailsController.mapController.updateDriverLocation(
              driverId: driverId,
              latLng: LatLng(lat, lng),
              isRunning: false,
              bearing: bearing,
              serviceName: rideDetailsController.ride.service?.name,
            );
          }
          return;
        }

        // 2. Manejo de estado de la carrera
        if (topic == 'ride.$rideID') {
          rideDetailsController.getRideDetails(rideID, shouldLoading: false);
        }
      }
    } catch (e) {
      printX("Error processing Realtime WS message in Pasajero: $e");
    }
  }

  PusherConfig pusherConfig = PusherConfig();

  /// Handle incoming Pusher events
  void onEvent(PusherEvent event) {
    try {

      if (event.eventName.startsWith('pusher:')) {
        return;
      }

      printD('Pusher Channel: ${event.channelName}');
      printD('Pusher Event: ${event.eventName}');

      // Handle driver_location_updated (flat data from DriverLocationUpdated event)
      if (event.eventName == 'driver_location_updated') {
        final locData = jsonDecode(event.data);
        final rideId = locData['ride_id']?.toString() ?? '';
        if (rideId != rideID) return;
        final lat = StringConverter.formatDouble(locData['latitude']?.toString() ?? '0', precision: 10);
        final lng = StringConverter.formatDouble(locData['longitude']?.toString() ?? '0', precision: 10);
        final bearing = StringConverter.formatDouble(locData['bearing']?.toString() ?? '0', precision: 10);
        if (lat != 0 && lng != 0) {
          rideDetailsController.mapController.updateDriverLocation(
            driverId: locData['driver_id']?.toString() ?? 'unknown',
            latLng: LatLng(lat, lng),
            isRunning: false,
            bearing: bearing,
            serviceName: rideDetailsController.ride.service?.name,
          );
        }
        return;
      }

      if (event.eventName == 'DRIVER_VIEWING') {
        final viewData = jsonDecode(event.data);
        final driverData = viewData['driver'];
        if (driverData != null) {
          final driverId = driverData['id']?.toString() ?? '';
          final firstname = driverData['firstname']?.toString() ?? '';
          final lastname = driverData['lastname']?.toString() ?? '';
          final image = driverData['image']?.toString() ?? '';
          
          if (driverId.isNotEmpty) {
            rideDetailsController.addViewingDriver(driverId, firstname, lastname, image);
          }
        }
        return;
      }

      final data = jsonDecode(event.data);
      final model = PusherResponseModel.fromJson(data);

      final modifiedEvent = PusherResponseModel(
        eventName: event.eventName,
        channelName: event.channelName,
        data: model.data,
      );

      updateEvent(modifiedEvent);

    } catch (e) {
      printX('onEvent error: $e');
    }
  }

  /// Update UI or state based on event name
  void updateEvent(PusherResponseModel event) {
    final eventName = event.eventName?.toLowerCase();
    printX('Handling event: $eventName');

    switch (eventName) {
      case 'online_payment_received':
        _handleOnlinePayment(event);
        break;

      case 'message_received':
        _handleMessageReceived(event);
        break;

      case 'live_location':
        _handleLiveLocation(event);
        break;

      case 'new_bid':
        _handleNewBid(event);
        break;

      case 'bid_reject':
        rideDetailsController.updateBidCount(true);
        break;

      case 'cash_payment_received':
        _handleCashPayment(event);
        break;

      case 'pick_up':
      case 'ride_end':
      case 'bid_accept':
        _updateRideIfAvailable(event);
        break;

      default:
        _updateRideIfAvailable(event);
        break;
    }
  }

  /// Handlers for each event type

  void _handleOnlinePayment(PusherResponseModel event) {
    printX('Online payment received for ride: ${event.data?.rideId}');
    Get.offAndToNamed(
      RouteHelper.rideReviewScreen,
      arguments: event.data?.rideId ?? '',
    );
  }

  void _handleMessageReceived(PusherResponseModel eventResponse) {
    if (eventResponse.data?.message != null) {
      if (eventResponse.data!.ride != null && eventResponse.data!.ride!.id != rideID) {
        printX('Message for different ride: ${eventResponse.data!.ride!.id}, current ride: $rideID');
        return;
      }
      if (isRideDetailsPage()) {
        if (rideDetailsController.repo.apiClient.isNotificationAudioEnable()) {
          MyUtils.vibrate();
        }
      }

      rideMessageController.addEventMessage(eventResponse.data!.message!);
    }
  }

  void _handleLiveLocation(PusherResponseModel eventResponse) {
    if (eventResponse.data!.ride != null && eventResponse.data!.ride!.id != rideID) {
      printX('Message for different ride: ${eventResponse.data!.ride!.id}, current ride: $rideID');
      return;
    }

    final lat = StringConverter.formatDouble(eventResponse.data?.driverLatitude ?? '0', precision: 10);
    final lng = StringConverter.formatDouble(eventResponse.data?.driverLongitude ?? '0', precision: 10);
    final driverId = eventResponse.data?.driverId ?? 'unknown';
    final serviceName = eventResponse.data?.service?.name ?? eventResponse.data?.ride?.service?.name ?? rideDetailsController.ride.service?.name;

    if (lat != 0 && lng != 0) {
      rideDetailsController.mapController.updateDriverLocation(
        driverId: driverId,
        latLng: LatLng(lat, lng),
        isRunning: false,
        serviceName: serviceName,
      );
    }
  }

  void _handleNewBid(PusherResponseModel eventResponse) {
    if (eventResponse.data!.bid != null && eventResponse.data!.bid!.rideId != rideID) {
      printX('Message for different ride: ${eventResponse.data!.bid!.rideId}, current ride: $rideID');
      return;
    }
    final bid = eventResponse.data?.bid;
    if (bid != null) {
      AudioUtils.playAudio(apiClient.getNotificationAudio());
      if (rideDetailsController.repo.apiClient.isNotificationAudioEnable()) {
        MyUtils.vibrate();
      }

      // Force refresh of the active bids list in real-time
      rideDetailsController.getRideBidList(rideID);

      CustomBidDialog.newBid(
        bid: bid,
        currency: rideDetailsController.currencySym,
        driverImagePath: '${rideDetailsController.driverImagePath}/${bid.driver?.avatar}',
        serviceImagePath: '${rideDetailsController.serviceImagePath}/${eventResponse.data?.service?.image}',
        totalRideCompleted: eventResponse.data?.driverTotalRide ?? '0',
      );
    }
    
    final lat = StringConverter.formatDouble(eventResponse.data?.driverLatitude ?? '0', precision: 10);
    final lng = StringConverter.formatDouble(eventResponse.data?.driverLongitude ?? '0', precision: 10);
    final driverId = eventResponse.data?.bid?.driverId?.toString() ?? eventResponse.data?.driverId ?? 'unknown';
    final serviceName = eventResponse.data?.service?.name ?? eventResponse.data?.ride?.service?.name ?? rideDetailsController.ride.service?.name;

    if (lat != 0 && lng != 0) {
      rideDetailsController.mapController.updateDriverLocation(
        driverId: driverId,
        latLng: LatLng(lat, lng),
        isRunning: false,
        serviceName: serviceName,
      );
    }
    
    rideDetailsController.updateBidCount(false);
  }

  void _handleCashPayment(PusherResponseModel event) {
    rideDetailsController.updatePaymentRequested(isRequested: false);
    _updateRideIfAvailable(event);
  }

  void _updateRideIfAvailable(PusherResponseModel eventResponse) {
    if (eventResponse.data!.ride != null && eventResponse.data!.ride!.id != rideID) {
      printX('Message for different ride: ${eventResponse.data!.ride!.id}, current ride: $rideID');
      return;
    }
    final ride = eventResponse.data?.ride;
    if (ride != null) {
      rideDetailsController.updateRide(ride);
    }
    // Also trigger full ride details reload to make sure status, driver, and loader states are refreshed
    rideDetailsController.getRideDetails(rideID, shouldLoading: false);
  }

  /// Utility
  bool isRideDetailsPage() => Get.currentRoute == RouteHelper.rideDetailsScreen;

  @override
  void onClose() {
    _realtimeWsSub?.cancel();
    if (rideID.isNotEmpty) {
      RealtimeManager().unsubscribe('ride.$rideID');
    }
    if (_subscribedDriverTopic != null) {
      RealtimeManager().unsubscribe(_subscribedDriverTopic!);
    }
    PusherManager().removeListener(onEvent);
    super.onClose();
  }

  Future<void> ensureConnection({String? channelName}) async {
    try {
      var userId = apiClient.sharedPreferences.getString(SharedPreferenceHelper.userIdKey) ?? '';
      await PusherManager().checkAndInitIfNeeded(channelName ?? "private-rider-user-$userId");
      await PusherManager().checkAndInitIfNeeded("private-ride-location-$rideID");
    } catch (e) {
      printX("Error ensuring connection: $e");
    }
  }
}
