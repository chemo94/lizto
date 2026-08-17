import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/helper/shared_preference_helper.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'package:liztogo/data/services/pusher_service.dart';
import 'package:liztogo/data/controller/delivery/notification_inbox_controller.dart';

class DeliveryNotificationService extends GetxController {
  bool _subscribed = false;

  static void init() {
    if (!Get.isRegistered<DeliveryNotificationService>()) {
      Get.put(DeliveryNotificationService(), permanent: true);
    }
  }

  void subscribeAll() {
    printX('DeliveryNotificationService.subscribeAll called, _subscribed=$_subscribed');
    if (_subscribed) {
      printX('DeliveryNotificationService: already subscribed, skipping');
      return;
    }

    try {
      final apiClient = Get.find<ApiClient>();
      final userId = apiClient.getUserID();
      printX('DeliveryNotificationService: userId=$userId, subscribing...');
      if (userId.isEmpty) {
        printX('DeliveryNotificationService: userId empty, skipping subscribe');
        return;
      }

      // Add listener BEFORE subscribing to channels so it's ready when events arrive
      PusherManager().addListener(_onPusherEvent);

      PusherManager().checkAndInitIfNeeded('private-favor-customer.$userId');
      PusherManager().checkAndInitIfNeeded('private-delivery-order.$userId');
      _subscribed = true;
      printX('DeliveryNotificationService: subscribed to private-delivery-order.$userId');
    } catch (e) {
      printX('DeliveryNotificationService init error: $e');
    }
  }

  void _onPusherEvent(PusherEvent event) {
    printX('DeliveryNotificationService: event=${event.eventName} channel=${event.channelName}');
    final isFavor = event.channelName.startsWith('private-favor-customer.');
    final isOrder = event.channelName.startsWith('private-delivery-order.');
    if (!isFavor && !isOrder) return;

    try {
      final data = jsonDecode(event.data);
      if (Get.isRegistered<NotificationInboxController>()) {
        Get.find<NotificationInboxController>().add(
          AppNotification(
            id: DateTime.now().millisecondsSinceEpoch.toString(),
            title: event.eventName.replaceAll('_', ' ').capitalizeFirst ?? 'Notificación',
            body: data['message'] ?? 'Actualización del servicio',
            type: 'delivery',
          ),
        );
      }
      switch (event.eventName) {
        case 'job_accepted':
        case 'courier_accepted':
          Get.snackbar(
            'Aceptado',
            data['message'] ?? 'Pedido aceptado',
            backgroundColor: const Color(0xFF10B981),
            colorText: MyColor.colorWhite,
          );
          break;
        case 'courier_on_way':
          Get.snackbar(
            'En camino',
            data['message'] ?? 'El repartidor está en camino',
            backgroundColor: const Color(0xFF3B82F6),
            colorText: MyColor.colorWhite,
          );
          break;
        case 'favor_delivered':
        case 'delivery_delivered':
          Get.snackbar(
            'Entregado',
            data['message'] ?? 'Servicio completado',
            backgroundColor: const Color(0xFF10B981),
            colorText: MyColor.colorWhite,
          );
          break;
        case 'favor_status_updated':
        case 'delivery_order_status_updated':
          Get.snackbar(
            'Pedido actualizado',
            data['message'] ?? 'El estado del pedido cambió',
            backgroundColor: const Color(0xFF3B82F6),
            colorText: MyColor.colorWhite,
          );
          break;
      }
    } catch (_) {}
  }

  @override
  void onClose() {
    PusherManager().removeListener(_onPusherEvent);
    super.onClose();
  }
}
