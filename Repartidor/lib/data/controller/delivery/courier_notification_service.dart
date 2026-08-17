import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/utils/audio_utils.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:liztogo_repartidor/data/services/pusher_service.dart';

class CourierNotificationItem {
  final String id;
  final String title;
  final String message;
  final String timestamp;
  final IconData icon;
  final Color iconColor;
  final String? type;

  CourierNotificationItem({
    required this.id,
    required this.title,
    required this.message,
    required this.timestamp,
    required this.icon,
    required this.iconColor,
    this.type,
  });
}

class CourierNotificationService extends GetxController {
  bool _subscribed = false;
  final RxList<CourierNotificationItem> liveNotifications = <CourierNotificationItem>[].obs;
  final Set<String> _handledEventIds = <String>{};

  static void init() {
    if (!Get.isRegistered<CourierNotificationService>()) {
      Get.put(CourierNotificationService(), permanent: true);
    }
  }

  void subscribeAll() {
    if (_subscribed) return;

    try {
      final apiClient = Get.find<ApiClient>();
      final userId = apiClient.getUserID();

      PusherManager().checkAndInitIfNeeded('private-nearby-couriers');
      if (userId.isNotEmpty) {
        PusherManager().checkAndInitIfNeeded('private-courier.$userId');
      }

      PusherManager().addListener(_onPusherEvent);
      _subscribed = true;
    } catch (e) {
      printX('CourierNotificationService init error: $e');
    }
  }

  void _onPusherEvent(PusherEvent event) {
    if (event.channelName != 'private-nearby-couriers' && !event.channelName.startsWith('private-courier.')) {
      return;
    }

    try {
      final decoded = event.data.isEmpty ? <String, dynamic>{} : jsonDecode(event.data);
      final data = decoded is Map ? decoded : <String, dynamic>{};
      if (_isDuplicate(data)) return;
      final message = data['message']?.toString();

      switch (event.eventName) {
        case 'new_delivery_order':
        case 'store_favor_requested':
        case 'new_job_available':
          _refreshPendingJobs();
          AudioUtils.playNotificationSound();
          _addLiveNotification(
            title: '¡Nuevo Pedido Disponible!',
            message: message ?? 'Hay un pedido disponible en tu zona para tomar.',
            icon: Icons.delivery_dining_rounded,
            iconColor: MyColor.primaryColor,
            type: 'new_job',
          );
          Get.snackbar(
            'Nuevo reparto',
            message ?? 'Hay un pedido disponible para tomar',
            backgroundColor: MyColor.primaryColor,
            colorText: MyColor.colorWhite,
            duration: const Duration(seconds: 4),
          );
          break;

        case 'delivery_order_status_updated':
        case 'favor_status_updated':
          _refreshCourierJobs(data);
          AudioUtils.playNotificationSound();
          _addLiveNotification(
            title: 'Actualización de Reparto',
            message: message ?? 'Se actualizó el estado del pedido.',
            icon: Icons.refresh_rounded,
            iconColor: const Color(0xFF3B82F6),
            type: 'status_update',
          );
          Get.snackbar(
            'Reparto actualizado',
            message ?? 'Se actualizo el estado del pedido',
            backgroundColor: const Color(0xFF3B82F6),
            colorText: MyColor.colorWhite,
            duration: const Duration(seconds: 3),
          );
          break;

        case 'eta_update':
          _updateEta(data);
          break;

        case 'return_requested':
          _handleReturnRequested(data);
          break;

        case 'favor_taken':
          _refreshPendingJobs();
          break;
      }
    } catch (e) {
      printX('Courier notification event error: $e');
    }
  }

  void _addLiveNotification({
    required String title,
    required String message,
    required IconData icon,
    required Color iconColor,
    String? type,
  }) {
    final item = CourierNotificationItem(
      id: DateTime.now().millisecondsSinceEpoch.toString(),
      title: title,
      message: message,
      timestamp: 'Ahora',
      icon: icon,
      iconColor: iconColor,
      type: type,
    );
    liveNotifications.insert(0, item);
    update();
  }

  void _refreshPendingJobs() {
    if (!Get.isRegistered<CourierController>()) return;

    final controller = Get.find<CourierController>();
    if (controller.isOnline) {
      controller.loadPendingJobs();
    }
  }

  void _refreshCourierJobs(Map<dynamic, dynamic> data) {
    if (!Get.isRegistered<CourierController>()) return;

    final controller = Get.find<CourierController>();
    controller.loadPendingJobs();
    controller.loadActiveJobs();

    final rawJobId = data['job_id'] ?? data['order_id'] ?? data['favor_id'];
    final jobId = rawJobId is int ? rawJobId : int.tryParse(rawJobId?.toString() ?? '');
    if (jobId != null && controller.selectedJob?.id == jobId) {
      controller.loadJobDetail(jobId, type: controller.selectedJob?.type);
    }
  }

  void _updateEta(Map<dynamic, dynamic> data) {
    if (!Get.isRegistered<CourierController>()) return;

    final controller = Get.find<CourierController>();
    final rawJobId = data['favor_id'] ?? data['order_id'];
    final jobId = rawJobId is int ? rawJobId : int.tryParse(rawJobId?.toString() ?? '');

    if (jobId != null && controller.selectedJob?.id == jobId) {
      final job = controller.selectedJob;
      if (job != null) {
        job.eta = {
          'duration_text': data['duration_text'],
          'duration_min': data['duration_min'],
          'distance_text': data['distance_text'],
          'source': data['source'],
        };
        controller.update();
      }
    }
  }

  void _handleReturnRequested(Map<dynamic, dynamic> data) {
    if (!Get.isRegistered<CourierController>()) return;

    _refreshPendingJobs();
    _refreshCourierJobs(data);

    AudioUtils.playNotificationSound();
    _addLiveNotification(
      title: 'Devolución Solicitada',
      message: 'Un envío requiere devolución inmediata.',
      icon: Icons.assignment_return_rounded,
      iconColor: Colors.orange,
      type: 'return_requested',
    );

    Get.snackbar(
      'Devolución solicitada',
      'Un envío requiere devolución',
      backgroundColor: Colors.orange,
      colorText: MyColor.colorWhite,
      duration: const Duration(seconds: 5),
    );
  }

  bool _isDuplicate(Map<dynamic, dynamic> data) {
    final eventId = data['event_id']?.toString();
    if (eventId == null || eventId.isEmpty) return false;
    if (_handledEventIds.contains(eventId)) return true;
    _handledEventIds.add(eventId);
    if (_handledEventIds.length > 200) _handledEventIds.remove(_handledEventIds.first);
    return false;
  }

  @override
  void onClose() {
    PusherManager().removeListener(_onPusherEvent);
    super.onClose();
  }
}
