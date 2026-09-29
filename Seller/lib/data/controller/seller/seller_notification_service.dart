import 'dart:async';
import 'dart:convert';
import 'dart:ui';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/helper/string_format_helper.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/data/services/pusher_service.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';

import '../../../presentation/screens/seller/seller_orders_screen.dart';
import 'package:lizto_store/core/helper/shared_preference_helper.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/data/services/api_client.dart';
import 'package:lizto_store/data/services/realtime_service.dart';

class SellerNotificationService extends GetxController {
  int unreadCount = 0;
  final List<Map<String, dynamic>> pendingOrders = [];
  final Set<String> _handledEventIds = <String>{};
  String _currentChannel = '';
  void Function(PusherEvent)? _currentListener;
  StreamSubscription? _sellerWsSub;
  String? _currentTopic;

  void subscribe(int sellerId) {
    if (sellerId <= 0) {
      printE("SellerNotificationService: sellerId inválido ($sellerId), no se suscribe");
      return;
    }

    unsubscribe();

    _currentChannel = 'private-seller.$sellerId';
    _currentListener = (event) {
      if (event.channelName != _currentChannel) return;
      if (event.eventName == 'new_delivery_order') {
        _handleNewOrder(event.data);
      } else if (event.eventName == 'delivery_order_status_updated') {
        _handleStatusUpdate(event.data);
      }
    };

    PusherManager().checkAndInitIfNeeded(_currentChannel);
    PusherManager().addListener(_currentListener!);
    printX("SellerNotificationService: suscrito a $_currentChannel");

    // WebSocket nativo
    try {
      if (Get.isRegistered<ApiClient>()) {
        final apiClient = Get.find<ApiClient>();
        final token = apiClient.sharedPreferences.getString(SharedPreferenceHelper.accessTokenKey) ?? '';
        if (token.isNotEmpty) {
          RealtimeManager().init(wsUrl: UrlContainer.wsUrl, token: token).then((_) {
            _currentTopic = 'seller.$sellerId';
            RealtimeManager().subscribe(_currentTopic!);
            _sellerWsSub?.cancel();
            _sellerWsSub = RealtimeManager().onBroadcast.listen((msg) {
              final payload = msg['payload'];
              if (payload is Map<String, dynamic>) {
                final eventName = (payload['event'] ?? payload['type'] ?? '').toString();
                if (eventName == 'new_delivery_order') {
                  _handleNewOrder(jsonEncode(payload));
                } else if (eventName == 'delivery_order_status_updated' || eventName == 'order_status') {
                  _handleStatusUpdate(jsonEncode(payload));
                }
              }
            });
            printX("SellerNotificationService: WS nativo suscrito a $_currentTopic");
          }).catchError((e) {
            printE("SellerNotificationService: error WS nativo: $e");
          });
        }
      }
    } catch (_) {}
  }

  void unsubscribe() {
    if (_currentListener != null) {
      PusherManager().removeListener(_currentListener!);
      _currentListener = null;
      printX("SellerNotificationService: desuscrito de $_currentChannel");
    }
    _currentChannel = '';

    if (_currentTopic != null) {
      RealtimeManager().unsubscribe(_currentTopic!);
      _currentTopic = null;
    }
    _sellerWsSub?.cancel();
  }

  void _handleStatusUpdate(String rawData) {
    try {
      final data = jsonDecode(rawData);
      if (_isDuplicate(data)) return;
      printX("SellerNotificationService: status_updated → ${data['message']}");
      Get.snackbar(
        'Pedido actualizado',
        data['message']?.toString() ?? 'El estado del pedido cambió',
        backgroundColor: const Color(0xFF3B82F6),
        colorText: MyColor.colorWhite,
        duration: const Duration(seconds: 4),
        snackPosition: SnackPosition.TOP,
      );
      if (Get.isRegistered<SellerController>()) {
        Get.find<SellerController>().loadOrders();
      }
      if (Get.isRegistered<SellerPanelController>()) {
        Get.find<SellerPanelController>().loadKitchen();
      }
    } catch (e) {
      printE("SellerNotificationService: error en status_update: $e");
    }
  }

  void _handleNewOrder(String rawData) {
    try {
      final data = jsonDecode(rawData);
      if (_isDuplicate(data)) return;
      printX("SellerNotificationService: new_order → ${data['order_no']}");
      unreadCount++;
      pendingOrders.insert(0, data);
      update();

      if (Get.isRegistered<SellerPanelController>()) {
        Get.find<SellerPanelController>().loadKitchen();
      }

      Get.snackbar(
        'Nuevo Pedido',
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
    } catch (e) {
      printE("SellerNotificationService: error en new_order: $e");
    }
  }

  void markAllRead() {
    unreadCount = 0;
    update();
  }

  bool _isDuplicate(dynamic data) {
    if (data is! Map) return false;
    final eventId = data['event_id']?.toString();
    if (eventId == null || eventId.isEmpty) return false;
    if (_handledEventIds.contains(eventId)) return true;
    _handledEventIds.add(eventId);
    if (_handledEventIds.length > 200) _handledEventIds.remove(_handledEventIds.first);
    return false;
  }
}
