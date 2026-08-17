import 'dart:convert';
import 'dart:ui';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/data/services/pusher_service.dart';

import '../../../presentation/screens/seller/seller_orders_screen.dart';

class SellerNotificationService extends GetxController {
  int unreadCount = 0;
  final List<Map<String, dynamic>> pendingOrders = [];

  void subscribe(int sellerId) {
    final channel = 'private-seller.$sellerId';
    PusherManager().checkAndInitIfNeeded(channel);
    PusherManager().addListener((event) {
      if (event.channelName != channel) return;
      if (event.eventName == 'new_delivery_order') {
        _handleNewOrder(event.data);
      }
    });
  }

  void _handleNewOrder(String rawData) {
    try {
      final data = jsonDecode(rawData);
      unreadCount++;
      pendingOrders.insert(0, data);
      update();

      Get.snackbar(
        '🔔 Nuevo Pedido',
        '${data['order_no']} - ${data['customer_name']}\n${data['store_name']} - S/ ${data['total']}',
        backgroundColor: const Color(0xFF6C63FF),
        colorText: MyColor.colorWhite,
        duration: const Duration(seconds: 8),
        snackPosition: SnackPosition.TOP,
        isDismissible: true,
        margin: const EdgeInsets.all(12),
        borderRadius: 12,
        mainButton: TextButton(
          onPressed: () {
            Get.back();
            Get.to(() => const SellerOrdersScreen());
          },
          child: Text('Ver', style: TextStyle(color: MyColor.colorWhite, fontWeight: FontWeight.w600)),
        ),
      );
    } catch (_) {}
  }

  void markAllRead() {
    unreadCount = 0;
    update();
  }
}
