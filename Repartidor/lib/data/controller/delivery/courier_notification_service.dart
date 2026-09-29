import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/shared_preference_helper.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/utils/audio_utils.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/url_container.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:liztogo_repartidor/data/services/pusher_service.dart';
import 'package:liztogo_repartidor/data/services/realtime_service.dart';

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
  StreamSubscription? _realtimeWsSub;

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
      final token = apiClient.sharedPreferences.getString(SharedPreferenceHelper.accessTokenKey) ?? '';

      PusherManager().checkAndInitIfNeeded('private-nearby-couriers');
      if (userId.isNotEmpty) {
        PusherManager().checkAndInitIfNeeded('private-courier.$userId');
      }

      PusherManager().addListener(_onPusherEvent);

      // WebSocket nativo
      if (token.isNotEmpty) {
        RealtimeManager().init(wsUrl: UrlContainer.wsUrl, token: token).then((_) {
          if (userId.isNotEmpty) {
            RealtimeManager().subscribe('driver.$userId');
          }
          _realtimeWsSub?.cancel();
          _realtimeWsSub = RealtimeManager().onBroadcast.listen(_onRealtimeBroadcast);
        }).catchError((e) {
          printX('RealtimeManager init error: $e');
        });
      }

      _subscribed = true;
    } catch (e) {
      printX('CourierNotificationService init error: $e');
    }
  }

  void _onRealtimeBroadcast(Map<String, dynamic> msg) {
    try {
      final topic = msg['topic']?.toString() ?? '';
      final payload = msg['payload'];
      printX('Realtime WS Courier broadcast: $topic -> $payload');

      if (payload is Map<String, dynamic>) {
        final title = payload['title']?.toString() ?? 'Nuevo Pedido';
        final message = payload['message']?.toString() ?? 'Tienes una nueva actualización';
        _refreshPendingJobs();
        _addLiveNotification(
          title: title,
          message: message,
          icon: Icons.delivery_dining,
          iconColor: MyColor.primaryColor,
          type: payload['type']?.toString(),
        );
      }
    } catch (e) {
      printX('Error processing Realtime WS in Courier: $e');
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
          showIncomingOrderAlertFromData(data);
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

  static int? _activeAlertJobId;

  static void showIncomingOrderAlertFromData(Map<dynamic, dynamic> data) {
    try {
      final rawJobId = data['job_id'] ?? data['favor_id'] ?? data['order_id'];
      final jobId = rawJobId is int ? rawJobId : int.tryParse(rawJobId?.toString() ?? '');
      if (jobId == null || jobId == 0) return;

      if (_activeAlertJobId == jobId && Get.isDialogOpen == true) {
        return; // already open
      }
      _activeAlertJobId = jobId;

      final type = data['job_type']?.toString() ?? data['type']?.toString() ?? 'favor';
      final orderNo = data['order_no']?.toString() ?? '#$jobId';
      final storeName = data['store_name']?.toString() ?? data['seller_name']?.toString() ?? 'Lizto';
      final pickupAddress = data['pickup_address']?.toString() ?? 'Punto de recogida';
      final deliveryAddress = data['delivery_address']?.toString() ?? 'Punto de entrega';
      final rawFee = data['delivery_fee'] ?? data['total_earning'] ?? data['total'];
      final fee = rawFee is num ? rawFee.toDouble() : (double.tryParse(rawFee?.toString() ?? '0.0') ?? 0.0);
      final description = data['description']?.toString() ?? '';

      AudioUtils.playNotificationSound();

      Get.dialog(
        Dialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
          elevation: 16,
          backgroundColor: Colors.transparent,
          insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
          child: Builder(
            builder: (context) {
              final isDark = Theme.of(context).brightness == Brightness.dark;
              return Container(
                decoration: BoxDecoration(
                  color: isDark ? const Color(0xFF1E293B) : Colors.white,
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: MyColor.primaryColor.withValues(alpha: 0.25),
                      blurRadius: 20,
                      spreadRadius: 2,
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Glowing Top Banner
                    Container(
                      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
                      decoration: const BoxDecoration(
                        gradient: LinearGradient(
                          colors: [MyColor.primaryColor, Color(0xFF059669)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.2),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.bolt_rounded, color: Colors.white, size: 24),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  '¡NUEVO PEDIDO ENTRANTE!',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 13,
                                    letterSpacing: 0.5,
                                  ),
                                ),
                                Text(
                                  'Solicitud $orderNo',
                                  style: TextStyle(
                                    color: Colors.white.withValues(alpha: 0.85),
                                    fontSize: 12,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Text(
                              'S/ ${fee.toStringAsFixed(2)}',
                              style: const TextStyle(
                                color: MyColor.primaryColor,
                                fontWeight: FontWeight.bold,
                                fontSize: 16,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                    // Body details
                    Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        children: [
                          // Store / Description
                          if (storeName.isNotEmpty)
                            Row(
                              children: [
                                const Icon(Icons.storefront_rounded, color: MyColor.primaryColor, size: 20),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    storeName,
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 15,
                                      color: isDark ? Colors.white : const Color(0xFF0F172A),
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          const SizedBox(height: 12),

                          // Route Box (Pickup -> Delivery)
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: isDark ? const Color(0xFF0F172A) : const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color: isDark ? Colors.white10 : const Color(0xFFE2E8F0),
                              ),
                            ),
                            child: Column(
                              children: [
                                Row(
                                  children: [
                                    const Icon(Icons.circle, color: Color(0xFF10B981), size: 10),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Text(
                                        pickupAddress,
                                        style: TextStyle(
                                          fontSize: 12,
                                          color: isDark ? Colors.grey[300] : const Color(0xFF475569),
                                        ),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ],
                                ),
                                const Padding(
                                  padding: EdgeInsets.only(left: 4, top: 4, bottom: 4),
                                  child: Align(
                                    alignment: Alignment.centerLeft,
                                    child: SizedBox(
                                      height: 12,
                                      child: VerticalDivider(color: Colors.grey, width: 1, thickness: 1),
                                    ),
                                  ),
                                ),
                                Row(
                                  children: [
                                    const Icon(Icons.location_on_rounded, color: Colors.redAccent, size: 14),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Text(
                                        deliveryAddress,
                                        style: TextStyle(
                                          fontSize: 13,
                                          fontWeight: FontWeight.w600,
                                          color: isDark ? Colors.white : const Color(0xFF0F172A),
                                        ),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),

                          if (description.isNotEmpty) ...[
                            const SizedBox(height: 10),
                            Align(
                              alignment: Alignment.centerLeft,
                              child: Text(
                                'Nota: $description',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontStyle: FontStyle.italic,
                                  color: isDark ? Colors.grey[400] : Colors.grey[600],
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],

                          const SizedBox(height: 20),

                          // Action Buttons: Aceptar & Rechazar
                          Row(
                            children: [
                              Expanded(
                                flex: 1,
                                child: OutlinedButton(
                                  style: OutlinedButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(vertical: 14),
                                    side: BorderSide(
                                      color: isDark ? Colors.grey[700]! : Colors.grey[300]!,
                                    ),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                  ),
                                  onPressed: () {
                                    AudioUtils.stop();
                                    Get.back();
                                  },
                                  child: Text(
                                    'Rechazar',
                                    style: TextStyle(
                                      color: isDark ? Colors.grey[400] : Colors.grey[700],
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                flex: 2,
                                child: ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: MyColor.primaryColor,
                                    padding: const EdgeInsets.symmetric(vertical: 14),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    elevation: 4,
                                  ),
                                  onPressed: () async {
                                    AudioUtils.stop();
                                    Get.back();
                                    if (Get.isRegistered<CourierController>()) {
                                      final courierController = Get.find<CourierController>();
                                      bool ok = await courierController.acceptJob(jobId, type);
                                      if (ok) {
                                        Get.snackbar(
                                          '¡Pedido Aceptado!',
                                          'El pedido $orderNo ya está en tus pedidos activos.',
                                          backgroundColor: const Color(0xFF10B981),
                                          colorText: MyColor.colorWhite,
                                          icon: const Icon(Icons.check_circle_rounded, color: Colors.white),
                                          duration: const Duration(seconds: 4),
                                        );
                                      }
                                    }
                                  },
                                  child: const Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Icons.check_circle_rounded, color: Colors.white, size: 20),
                                      SizedBox(width: 6),
                                      Text(
                                        'Aceptar Pedido',
                                        style: TextStyle(
                                          color: Colors.white,
                                          fontWeight: FontWeight.bold,
                                          fontSize: 15,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ),
        barrierDismissible: true,
      );
    } catch (e) {
      printX('showIncomingOrderAlertFromData error: $e');
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
    _realtimeWsSub?.cancel();
    PusherManager().removeListener(_onPusherEvent);
    super.onClose();
  }
}
