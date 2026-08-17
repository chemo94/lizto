import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/data/model/delivery/delivery_models.dart';
import 'package:liztogo/data/services/directions_service.dart';
import 'package:liztogo/data/services/pusher_service.dart';
import 'package:liztogo/environment.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';
import 'package:liztogo/presentation/screens/delivery/delivery_review_screen.dart';
import 'package:liztogo/presentation/screens/delivery/delivery_tracking_screen.dart';
import 'package:liztogo/presentation/screens/delivery/delivery_chat_screen.dart';
import 'package:liztogo/presentation/screens/web_view/web_view_screen.dart';
import 'package:liztogo/data/model/webview/webview_model.dart';
import 'package:liztogo/presentation/screens/delivery/mercadopago_checkout_screen.dart';

import '../../../core/helper/string_format_helper.dart';

class OrderDetailScreen extends StatefulWidget {
  final int orderId;
  const OrderDetailScreen({super.key, required this.orderId});

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> with SingleTickerProviderStateMixin {
  final List<double> _tipOptions = [2, 5, 10, 20];
  GoogleMapController? _trackingMapController;
  final Set<Marker> _trackingMarkers = {};
  final Set<Polyline> _trackingPolylines = {};
  bool _routeLoaded = false;

  late final AnimationController _pulseCtrl;
  late final Animation<double> _pulseAnim;

  @override
  void initState() {
    super.initState();
    _pulseCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 1200))..repeat(reverse: true);
    _pulseAnim = Tween<double>(begin: 0.7, end: 1.0).animate(CurvedAnimation(parent: _pulseCtrl, curve: Curves.easeInOut));

    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<DeliveryController>().loadOrderDetail(widget.orderId);
    });

    _subscribeToUpdates();
  }

  void _subscribeToUpdates() {
    try {
      final pm = PusherManager();
      pm.addListener(_onPusherEvent);
      final channel = 'private-tracking.${widget.orderId}';
      pm.checkAndInitIfNeeded(channel);
    } catch (_) {}
  }

  void _onPusherEvent(PusherEvent event) {
    try {
      printX('OrderDetail received: ${event.eventName} on ${event.channelName} data=${event.data}');
      final data = jsonDecode(event.data);
      final orderId = data['order_id'] is int ? data['order_id'] : int.tryParse(data['order_id']?.toString() ?? '');
      if (orderId == widget.orderId) {
        if (event.eventName == 'delivery_order_status_updated') {
          printX('OrderDetail: reloading order ${widget.orderId}');
          Get.find<DeliveryController>().loadOrderDetail(widget.orderId);
        } else if (event.eventName == 'location_update') {
          final lat = double.tryParse(data['latitude']?.toString() ?? '');
          final lng = double.tryParse(data['longitude']?.toString() ?? '');
          if (lat != null && lng != null) {
            final c = Get.find<DeliveryController>();
            c.courierLat = lat;
            c.courierLng = lng;
            c.update();
            if (c.selectedOrder != null) {
              _updateTrackingMap(c.selectedOrder!);
            }
          }
        }
      }
    } catch (_) {}
  }

  @override
  void dispose() {
    PusherManager().removeListener(_onPusherEvent);
    _pulseCtrl.dispose();
    _trackingMapController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final top = MediaQuery.of(context).padding.top;

    return GetBuilder<DeliveryController>(
      builder: (c) {
        final order = c.selectedOrder;

        return Scaffold(
          backgroundColor: const Color(0xFFF7F8FA),
          body: Column(
            children: [
              // ── Premium Header ──
              Container(
                padding: EdgeInsets.fromLTRB(Dimensions.space16, top + Dimensions.space12, Dimensions.space16, Dimensions.space20),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(bottom: Radius.circular(24)),
                ),
                child: Row(
                  children: [
                    GestureDetector(
                      onTap: Get.back,
                      child: Container(
                        height: 40,
                        width: 40,
                        decoration: BoxDecoration(
                          color: const Color(0xFFF2F4F7),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.arrow_back_ios_new_rounded, size: 18, color: Color(0xFF101828)),
                      ),
                    ),
                    const SizedBox(width: Dimensions.space12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Detalle del pedido', style: boldLarge.copyWith(fontSize: 20, color: MyColor.primaryTextColor)),
                          if (order != null)
                            Text('# ${order.orderNo ?? ''}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                        ],
                      ),
                    ),
                    if (order != null)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.space10, vertical: Dimensions.space6),
                        decoration: BoxDecoration(
                          color: order.statusColor.withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            FadeTransition(
                              opacity: _pulseAnim,
                              child: Container(
                                height: 8,
                                width: 8,
                                decoration: BoxDecoration(color: order.statusColor, shape: BoxShape.circle),
                              ),
                            ),
                            const SizedBox(width: 6),
                            Text(order.statusLabel, style: semiBoldSmall.copyWith(color: order.statusColor, fontSize: 12)),
                          ],
                        ),
                      ),
                  ],
                ),
              ),

              // ── Body ──
              Expanded(
                child: c.isLoading
                    ? const Center(child: CircularProgressIndicator())
                    : order == null
                        ? const Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.receipt_long_rounded, size: 64, color: Color(0xFFD1D5DB)),
                                SizedBox(height: 16),
                                Text('Pedido no encontrado', style: TextStyle(color: Color(0xFF6B7280))),
                              ],
                            ),
                          )
                        : RefreshIndicator(
                            color: MyColor.primaryColor,
                            onRefresh: () => c.loadOrderDetail(widget.orderId),
                            child: ListView(
                              padding: const EdgeInsets.all(Dimensions.space16),
                              physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                              children: [
                                // ── Animated Status Stepper ──
                                _StatusStepper(order: order),
                                const SizedBox(height: Dimensions.space16),

                                // ── Live Tracking Map ──
                                if (order.status != 'pending' && order.status != 'cancelled')
                                  _TrackingMapCard(
                                    order: order,
                                    markers: _trackingMarkers,
                                    polylines: _trackingPolylines,
                                    onMapCreated: (ctrl) {
                                      _trackingMapController = ctrl;
                                      _updateTrackingMap(order);
                                    },
                                  ),
                                if (order.status != 'pending' && order.status != 'cancelled')
                                  const SizedBox(height: Dimensions.space16),

                                // ── Driver Card (if assigned) ──
                                if (order.driver != null) ...[
                                  _DriverCard(order: order, imagePath: c.orderDriverImagePath),
                                  const SizedBox(height: Dimensions.space16),
                                ],

                                // ── Store Info ──
                                _InfoCard(
                                  icon: Icons.storefront_rounded,
                                  color: MyColor.primaryColor,
                                  title: 'Tienda',
                                  child: Row(
                                    children: [
                                      const Icon(Icons.store_rounded, size: 16, color: Color(0xFF6B7280)),
                                      const SizedBox(width: Dimensions.space8),
                                      Text(order.store?.name ?? 'Tienda', style: regularDefault.copyWith(color: MyColor.primaryTextColor)),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: Dimensions.space12),

                                // ── Order Items + Totals ──
                                _InfoCard(
                                  icon: Icons.receipt_rounded,
                                  color: const Color(0xFF8B5CF6),
                                  title: 'Productos',
                                  child: Column(
                                    children: [
                                      ...?order.items?.map((item) => _OrderItemTile(item: item, controller: c)),
                                      const Divider(height: 20),
                                      _TotalRow(label: 'Subtotal', value: 'S/ ${order.subtotal?.toStringAsFixed(2) ?? "0.00"}'),
                                      const SizedBox(height: 4),
                                      _TotalRow(label: 'Delivery', value: 'S/ ${order.deliveryFee?.toStringAsFixed(2) ?? "0.00"}'),
                                      if ((order.tip ?? 0) > 0) ...[
                                        const SizedBox(height: 4),
                                        _TotalRow(label: 'Propina', value: 'S/ ${order.tip?.toStringAsFixed(2) ?? "0.00"}', valueColor: const Color(0xFF10B981)),
                                      ],
                                      if ((order.discount ?? 0) > 0) ...[
                                        const SizedBox(height: 4),
                                        _TotalRow(label: 'Descuento', value: '- S/ ${order.discount?.toStringAsFixed(2) ?? "0.00"}', valueColor: const Color(0xFF10B981)),
                                      ],
                                      const Divider(height: 20),
                                      Row(
                                        children: [
                                          Text('Total', style: boldDefault.copyWith(fontSize: 16)),
                                          const Spacer(),
                                          Text('S/ ${order.total?.toStringAsFixed(2) ?? "0.00"}', style: boldDefault.copyWith(fontSize: 18, color: MyColor.primaryColor)),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: Dimensions.space12),

                                // ── Delivery Address ──
                                _InfoCard(
                                  icon: Icons.location_on_rounded,
                                  color: const Color(0xFF00C9A7),
                                  title: 'Dirección de entrega',
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      _AddressRow(icon: Icons.home_rounded, text: order.deliveryAddress ?? 'No disponible'),
                                      if (order.contactName?.isNotEmpty == true)
                                        _AddressRow(icon: Icons.person_rounded, text: order.contactName!),
                                      if (order.contactPhone?.isNotEmpty == true)
                                        _AddressRow(icon: Icons.phone_rounded, text: order.contactPhone!),
                                      if (order.notes?.isNotEmpty == true)
                                        _AddressRow(icon: Icons.note_rounded, text: order.notes!),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: Dimensions.space12),

                                // ── Payment ──
                                _InfoCard(
                                  icon: Icons.payment_rounded,
                                  color: const Color(0xFF6C63FF),
                                  title: 'Pago',
                                  child: Row(
                                    children: [
                                      Container(
                                        height: 36,
                                        width: 36,
                                        decoration: BoxDecoration(
                                          color: const Color(0xFF6C63FF).withValues(alpha: 0.1),
                                          borderRadius: BorderRadius.circular(10),
                                        ),
                                        child: const Icon(Icons.credit_card_rounded, color: Color(0xFF6C63FF), size: 18),
                                      ),
                                      const SizedBox(width: Dimensions.space12),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(order.paymentMethodName ?? 'No especificado', style: boldDefault.copyWith(fontSize: 14)),
                                            Text(
                                              order.paymentStatus == 1 ? 'Pagado' : 'Pendiente de pago',
                                              style: regularSmall.copyWith(
                                                color: order.paymentStatus == 1 ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                ),

                                // ── Add Tip ──
                                if ((order.tip ?? 0) == 0 && order.status != 'cancelled' && order.status != 'delivered') ...[
                                  const SizedBox(height: Dimensions.space12),
                                  _AddTipCard(tipOptions: _tipOptions, controller: c, orderId: order.id ?? 0),
                                ],

                                // ── Report Problem ──
                                const SizedBox(height: Dimensions.space12),
                                _ActionButton(
                                  label: 'Reportar Problema',
                                  icon: Icons.report_problem_outlined,
                                  color: const Color(0xFFF59E0B),
                                  outlined: true,
                                  onTap: () => _reportProblem(c, order.id ?? 0),
                                ),

                                // ── Actions ──
                                const SizedBox(height: Dimensions.space20),
                                if (order.status != 'delivered' && order.status != 'cancelled') ...[
                                  Row(
                                    children: [
                                      Expanded(
                                        child: _ActionButton(
                                          label: 'Chat',
                                          icon: Icons.chat_rounded,
                                          color: MyColor.primaryColor,
                                          outlined: true,
                                          onTap: () => Get.to(() => DeliveryChatScreen(
                                            orderId: order.id ?? 0,
                                            orderTitle: 'Pedido ${order.orderNo ?? ""}',
                                            courierName: order.driver?['name']?.toString() ?? 'Repartidor',
                                            courierImage: order.driver?['image']?.toString(),
                                          )),
                                        ),
                                      ),
                                      SizedBox(width: Dimensions.space12),
                                      Expanded(
                                        child: _ActionButton(
                                          label: 'Tracking',
                                          icon: Icons.location_on_rounded,
                                          color: MyColor.primaryColor,
                                          outlined: false,
                                          onTap: () => Get.to(() => DeliveryTrackingScreen(
                                            orderId: order.id ?? 0,
                                            order: order,
                                          )),
                                        ),
                                      ),
                                    ],
                                  ),
                                  SizedBox(height: Dimensions.space12),
                                ],
                                if (order.status == 'pending_payment') ...[
                                  const SizedBox(height: Dimensions.space12),
                                  Container(
                                    width: double.infinity,
                                    padding: EdgeInsets.all(Dimensions.space14),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFEF4444).withValues(alpha: 0.08),
                                      borderRadius: BorderRadius.circular(Dimensions.mediumRadius),
                                      border: Border.all(color: const Color(0xFFEF4444).withValues(alpha: 0.2)),
                                    ),
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Icon(Icons.info_rounded, color: const Color(0xFFEF4444), size: 20),
                                        SizedBox(width: Dimensions.space8),
                                        Expanded(
                                          child: Text(
                                            'Tu pedido aún no ha sido generado. Debes completar el pago o cambiar el método de pago para que el pedido sea procesado.',
                                            style: regularSmall.copyWith(color: const Color(0xFFEF4444)),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(height: Dimensions.space12),
                                  _ActionButton(
                                    label: 'Pagar ahora',
                                    icon: Icons.payment_rounded,
                                    color: const Color(0xFF10B981),
                                    outlined: false,
                                    onTap: () => _payOrder(c, order.id ?? 0),
                                  ),
                                  const SizedBox(height: Dimensions.space12),
                                ],
                                if (order.status == 'pending' || order.status == 'confirmed' || order.status == 'pending_payment')
                                  _ActionButton(
                                    label: 'Cancelar pedido',
                                    icon: Icons.cancel_outlined,
                                    color: MyColor.redCancelTextColor,
                                    outlined: true,
                                    onTap: () => _cancelOrder(c, order.id ?? 0),
                                  ),
                                if (order.status == 'delivered' || order.status == 'cancelled') ...[
                                  _ActionButton(
                                    label: 'Solicitar reembolso',
                                    icon: Icons.replay_rounded,
                                    color: MyColor.primaryColor,
                                    outlined: true,
                                    onTap: () => _requestRefund(c, order.id ?? 0),
                                  ),
                                ],
                                if (order.status == 'delivered') ...[
                                  const SizedBox(height: Dimensions.space12),
                                  _ActionButton(
                                    label: 'Calificar servicio',
                                    icon: Icons.star_rounded,
                                    color: const Color(0xFFF59E0B),
                                    outlined: false,
                                    onTap: () => Get.to(() => DeliveryReviewScreen(
                                          orderId: order.id ?? 0,
                                          orderNo: order.orderNo ?? '',
                                          courierName: order.driver?['name']?.toString(),
                                          courierImage: order.driver?['image']?.toString(),
                                          imagePath: c.orderDriverImagePath,
                                        )),
                                  ),
                                ],
                                const SizedBox(height: Dimensions.space32),
                              ],
                            ),
                          ),
              ),
            ],
          ),
        );
      },
    );
  }

  // ── Map updates ──
  void _updateTrackingMap(DeliveryOrderModel order) {
    if (_trackingMapController == null) return;
    _trackingMarkers.clear();

    final storeLat = order.store?.latitude;
    final storeLng = order.store?.longitude;
    final deliveryLat = order.deliveryLat;
    final deliveryLng = order.deliveryLng;

    if (storeLat != null && storeLng != null) {
      _trackingMarkers.add(Marker(
        markerId: const MarkerId('store'),
        position: LatLng(storeLat, storeLng),
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
        infoWindow: InfoWindow(title: order.store?.name ?? 'Tienda'),
      ));
      _trackingMapController!.animateCamera(CameraUpdate.newLatLng(LatLng(storeLat, storeLng)));
    }

    if (deliveryLat != null && deliveryLng != null) {
      _trackingMarkers.add(Marker(
        markerId: const MarkerId('delivery'),
        position: LatLng(deliveryLat, deliveryLng),
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
        infoWindow: const InfoWindow(title: 'Entrega'),
      ));
    }

    final c = Get.find<DeliveryController>();
    final driverLat = c.courierLat ?? (order.driver?['latitude'] != null ? double.tryParse(order.driver!['latitude']!.toString()) : null);
    final driverLng = c.courierLng ?? (order.driver?['longitude'] != null ? double.tryParse(order.driver!['longitude']!.toString()) : null);
    if (driverLat != null && driverLng != null) {
      _trackingMarkers.add(Marker(
        markerId: const MarkerId('driver'),
        position: LatLng(driverLat, driverLng),
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
        infoWindow: InfoWindow(title: order.driver?['name'] ?? 'Repartidor'),
      ));
      _trackingMapController!.animateCamera(CameraUpdate.newLatLng(LatLng(driverLat, driverLng)));
    }

    if (!_routeLoaded && storeLat != null && storeLng != null && deliveryLat != null && deliveryLng != null) {
      _routeLoaded = true;
      DirectionsService.getDirections(
        originLat: storeLat, originLng: storeLng,
        destLat: deliveryLat, destLng: deliveryLng,
        apiKey: Environment.mapKey,
      ).then((result) {
        if (result != null && mounted) {
          setState(() {
            _trackingPolylines.add(Polyline(
              polylineId: const PolylineId('route'),
              points: result.polylinePoints,
              color: MyColor.primaryColor,
              width: 4,
            ));
          });
        }
      });
    }
  }

  void _payOrder(DeliveryController c, int orderId) async {
    if (c.gateways.isEmpty) {
      await c.loadGateways();
    }
    final gateways = c.gateways.toList();
    if (gateways.isEmpty) {
      Get.snackbar('Error', 'No hay métodos de pago disponibles', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
      return;
    }
    Get.bottomSheet(
      Container(
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Seleccionar método de pago', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
            const SizedBox(height: 16),
            ...gateways.map((gw) => ListTile(
              leading: gw.image != null ? ClipRRect(borderRadius: BorderRadius.circular(8), child: Image.network(gw.image!, width: 40, height: 40, fit: BoxFit.contain)) : Icon(gw.isCash ? Icons.payments_rounded : Icons.credit_card_rounded, color: MyColor.primaryColor),
              title: Text(gw.name ?? 'Pago'),
              subtitle: Text(gw.isCash ? 'Pago en efectivo' : (gw.currency ?? 'PEN')),
              trailing: const Icon(Icons.chevron_right_rounded),
              onTap: () async {
                Get.back();
                final errorMsg = await c.payPendingOrder(orderId, gw.code ?? 0);
                if (errorMsg == null && mounted) {
                  if (c.mpCheckoutData != null) {
                    final mpData = c.mpCheckoutData!;
                    final resolvedOrderId = c.pendingOrderId ?? orderId;
                    c.mpCheckoutData = null;
                    c.pendingOrderId = null;
                    await Get.to(() => DeliveryMercadoPagoCheckoutScreen(mpData: mpData, orderId: resolvedOrderId));
                    c.loadOrderDetail(widget.orderId);
                  } else if (c.paymentRedirectUrl != null) {
                    final url = c.paymentRedirectUrl!;
                    c.paymentRedirectUrl = null;
                    await Get.to(() => MyWebViewScreen(model: WebviewModel(url: url, rideId: '')));
                    c.loadOrderDetail(widget.orderId);
                  } else {
                    Get.snackbar('Metodo actualizado', 'El pedido sera procesado con ${gw.name}', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
                    c.loadOrderDetail(widget.orderId);
                  }
                } else if (mounted) {
                  Get.snackbar('Error', errorMsg ?? 'No se pudo iniciar el pago', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
                }
              },
            )),
            const SizedBox(height: 12),
          ],
        ),
      ),
      isScrollControlled: true,
      backgroundColor: Colors.white,
    );
  }

  void _cancelOrder(DeliveryController c, int orderId) {
    Get.defaultDialog(
      title: 'Cancelar pedido',
      middleText: '¿Estás seguro de cancelar este pedido?',
      textConfirm: 'Sí, cancelar',
      textCancel: 'No',
      confirmTextColor: Colors.white,
      buttonColor: MyColor.redCancelTextColor,
      onConfirm: () async {
        Get.back();
        bool ok = await c.cancelOrder(orderId);
        if (ok) {
          Get.snackbar('Cancelado', 'Pedido cancelado correctamente', backgroundColor: MyColor.primaryColor, colorText: Colors.white);
        }
      },
    );
  }

  void _requestRefund(DeliveryController c, int orderId) {
    final reasonCtrl = TextEditingController();
    Get.defaultDialog(
      title: 'Solicitar reembolso',
      content: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8),
        child: TextField(
          controller: reasonCtrl,
          decoration: InputDecoration(
            hintText: 'Describe el motivo del reembolso',
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
          ),
          maxLines: 3,
        ),
      ),
      textConfirm: 'Enviar solicitud',
      textCancel: 'Cancelar',
      confirmTextColor: Colors.white,
      buttonColor: MyColor.primaryColor,
      onConfirm: () async {
        if (reasonCtrl.text.trim().isEmpty) {
          Get.snackbar('Error', 'Por favor describe el motivo', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
          return;
        }
        Get.back();
        await c.requestRefund(orderId, reasonCtrl.text.trim());
      },
    );
  }

  void _reportProblem(DeliveryController c, int orderId) {
    final subjectCtrl = TextEditingController();
    final descCtrl = TextEditingController();
    Get.defaultDialog(
      title: 'Reportar Problema',
      content: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 8),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: subjectCtrl,
              decoration: InputDecoration(
                hintText: 'Asunto (ej. Pedido incompleto)',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: Dimensions.space12),
            TextField(
              controller: descCtrl,
              decoration: InputDecoration(
                hintText: 'Describe el problema',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
              maxLines: 3,
            ),
          ],
        ),
      ),
      textConfirm: 'Enviar reporte',
      textCancel: 'Cancelar',
      confirmTextColor: Colors.white,
      buttonColor: MyColor.primaryColor,
      onConfirm: () async {
        if (subjectCtrl.text.trim().isEmpty) {
          Get.snackbar('Error', 'Por favor ingresa un asunto', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
          return;
        }
        Get.back();
        await c.reportOrderProblem(orderId, subjectCtrl.text.trim(), descCtrl.text.trim());
      },
    );
  }
}

// ── Status Stepper ──

class _StatusStepper extends StatelessWidget {
  final DeliveryOrderModel order;

  const _StatusStepper({required this.order});

  static const List<Map<String, dynamic>> _steps = [
    {'key': 'pending', 'label': 'Pedido\nrecibido', 'icon': Icons.receipt_long_rounded},
    {'key': 'confirmed', 'label': 'Pedido\nconfirmado', 'icon': Icons.check_circle_outline_rounded},
    {'key': 'preparing', 'label': 'En\npreparación', 'icon': Icons.restaurant_rounded},
    {'key': 'on_way', 'label': 'En\ncamino', 'icon': Icons.delivery_dining_rounded},
    {'key': 'delivered', 'label': 'Entregado', 'icon': Icons.verified_rounded},
  ];

  int _activeStep() {
    const statusOrder = ['pending', 'confirmed', 'preparing', 'ready', 'on_way', 'delivered'];
    final idx = statusOrder.indexOf(order.status ?? '');
    if (idx < 0) return 0;
    // Map to our 5-step display
    if (idx <= 0) return 0;
    if (idx == 1) return 1;
    if (idx == 2 || idx == 3) return 2;
    if (idx == 4) return 3;
    return 4;
  }

  @override
  Widget build(BuildContext context) {
    if (order.status == 'cancelled') {
      return Container(
        padding: const EdgeInsets.all(Dimensions.space16),
        decoration: BoxDecoration(
          color: MyColor.redCancelTextColor.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: MyColor.redCancelTextColor.withValues(alpha: 0.2)),
        ),
        child: Row(
          children: [
            Container(
              height: 48,
              width: 48,
              decoration: BoxDecoration(
                color: MyColor.redCancelTextColor.withValues(alpha: 0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.cancel_rounded, color: MyColor.redCancelTextColor, size: 26),
            ),
            const SizedBox(width: Dimensions.space16),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Pedido cancelado', style: boldLarge.copyWith(color: MyColor.redCancelTextColor)),
                Text('# ${order.orderNo ?? ''}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
              ],
            ),
          ],
        ),
      );
    }

    final active = _activeStep();

    return Container(
      padding: const EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                height: 34,
                width: 34,
                decoration: BoxDecoration(
                  color: order.statusColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(order.statusIcon, color: order.statusColor, size: 18),
              ),
              const SizedBox(width: Dimensions.space10),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(order.statusLabel, style: boldDefault.copyWith(color: order.statusColor, fontSize: 16)),
                  Text('Pedido # ${order.orderNo ?? ''}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                ],
              ),
            ],
          ),
          const SizedBox(height: Dimensions.space20),
          // Step indicators
          Row(
            children: List.generate(_steps.length, (i) {
              final isDone = i < active;
              final isCurrent = i == active;
              final stepColor = isDone || isCurrent ? order.statusColor : const Color(0xFFE5E7EB);
              return Expanded(
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        children: [
                          AnimatedContainer(
                            duration: const Duration(milliseconds: 400),
                            height: 36,
                            width: 36,
                            decoration: BoxDecoration(
                              color: isCurrent ? order.statusColor : (isDone ? order.statusColor.withValues(alpha: 0.15) : const Color(0xFFF3F4F6)),
                              shape: BoxShape.circle,
                              border: Border.all(
                                color: isCurrent ? order.statusColor : (isDone ? order.statusColor : const Color(0xFFE5E7EB)),
                                width: isCurrent ? 2.5 : 1,
                              ),
                            ),
                            child: Icon(
                              _steps[i]['icon'] as IconData,
                              size: 16,
                              color: isCurrent ? Colors.white : (isDone ? order.statusColor : const Color(0xFFD1D5DB)),
                            ),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            _steps[i]['label'] as String,
                            textAlign: TextAlign.center,
                            style: regularSmall.copyWith(
                              fontSize: 9,
                              color: isCurrent ? order.statusColor : (isDone ? MyColor.primaryTextColor : MyColor.bodyMutedTextColor),
                              fontWeight: isCurrent ? FontWeight.w700 : FontWeight.normal,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (i < _steps.length - 1)
                      Expanded(
                        child: Container(
                          height: 2,
                          margin: const EdgeInsets.only(bottom: 22),
                          color: stepColor,
                        ),
                      ),
                  ],
                ),
              );
            }),
          ),
        ],
      ),
    );
  }
}

// ── Tracking Map Card ──

class _TrackingMapCard extends StatelessWidget {
  final DeliveryOrderModel order;
  final Set<Marker> markers;
  final Set<Polyline> polylines;
  final void Function(GoogleMapController) onMapCreated;

  const _TrackingMapCard({
    required this.order,
    required this.markers,
    required this.polylines,
    required this.onMapCreated,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 200,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 12, offset: const Offset(0, 4))],
      ),
      clipBehavior: Clip.antiAlias,
      child: Stack(
        children: [
          GoogleMap(
            initialCameraPosition: CameraPosition(
              target: LatLng(
                order.store?.latitude ?? -12.0464,
                order.store?.longitude ?? -77.0428,
              ),
              zoom: 14,
            ),
            markers: markers,
            polylines: polylines,
            onMapCreated: onMapCreated,
            myLocationEnabled: true,
            zoomControlsEnabled: false,
            mapToolbarEnabled: false,
          ),
          // Map label overlay
          Positioned(
            top: Dimensions.space10,
            left: Dimensions.space10,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: Dimensions.space10, vertical: Dimensions.space6),
              decoration: BoxDecoration(
                color: Colors.black.withValues(alpha: 0.6),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.location_on_rounded, color: Colors.white, size: 13),
                  const SizedBox(width: 4),
                  Text('Seguimiento en vivo', style: regularSmall.copyWith(color: Colors.white, fontSize: 11)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Driver Card ──

class _DriverCard extends StatelessWidget {
  final DeliveryOrderModel order;
  final String imagePath;

  const _DriverCard({required this.order, required this.imagePath});

  @override
  Widget build(BuildContext context) {
    final driver = order.driver!;
    return Container(
      padding: const EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [MyColor.primaryColor, MyColor.primaryColor.withValues(alpha: 0.75)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: MyColor.primaryColor.withValues(alpha: 0.3), blurRadius: 16, offset: const Offset(0, 6))],
      ),
      child: Row(
        children: [
          // Driver avatar
          Container(
            height: 52,
            width: 52,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white, width: 2),
            ),
            clipBehavior: Clip.antiAlias,
            child: driver['image'] != null
                ? MyImageWidget(
                    imageUrl: '$imagePath/${driver['image']}',
                    height: 52,
                    width: 52,
                    boxFit: BoxFit.cover,
                  )
                : const Icon(Icons.person_rounded, color: Colors.white, size: 28),
          ),
          const SizedBox(width: Dimensions.space14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Tu repartidor', style: regularSmall.copyWith(color: Colors.white70, fontSize: 11)),
                Text(driver['name']?.toString() ?? 'Repartidor', style: boldDefault.copyWith(color: Colors.white, fontSize: 16)),
              ],
            ),
          ),
          Container(
            height: 40,
            width: 40,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.delivery_dining_rounded, color: Colors.white, size: 20),
          ),
        ],
      ),
    );
  }
}

// ── Add Tip Card ──

class _AddTipCard extends StatelessWidget {
  final List<double> tipOptions;
  final DeliveryController controller;
  final int orderId;

  const _AddTipCard({required this.tipOptions, required this.controller, required this.orderId});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: const Color(0xFF10B981).withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.volunteer_activism_rounded, color: Color(0xFF10B981), size: 20),
              const SizedBox(width: Dimensions.space8),
              Text('Añadir propina al repartidor', style: boldDefault.copyWith(color: const Color(0xFF065F46))),
            ],
          ),
          const SizedBox(height: Dimensions.space4),
          Text('¡Un pequeño gesto hace la diferencia!', style: regularSmall.copyWith(color: const Color(0xFF059669))),
          const SizedBox(height: Dimensions.space12),
          Row(
            children: tipOptions.map((t) {
              return Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 3),
                  child: GestureDetector(
                    onTap: () => controller.addTip(orderId, t),
                    child: Container(
                      padding: const EdgeInsets.symmetric(vertical: Dimensions.space10),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFF10B981)),
                        boxShadow: [BoxShadow(color: const Color(0xFF10B981).withValues(alpha: 0.15), blurRadius: 6, offset: const Offset(0, 2))],
                      ),
                      child: Text(
                        'S/ ${t.toStringAsFixed(0)}',
                        textAlign: TextAlign.center,
                        style: semiBoldSmall.copyWith(color: const Color(0xFF059669), fontSize: 14),
                      ),
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }
}

// ── Shared Components ──

class _InfoCard extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String title;
  final Widget child;

  const _InfoCard({required this.icon, required this.color, required this.title, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space16, Dimensions.space16, Dimensions.space12),
            child: Row(
              children: [
                Container(
                  height: 32,
                  width: 32,
                  decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(9)),
                  child: Icon(icon, color: color, size: 16),
                ),
                const SizedBox(width: Dimensions.space10),
                Text(title, style: boldDefault.copyWith(fontSize: 14, color: MyColor.primaryTextColor)),
              ],
            ),
          ),
          Divider(height: 1, color: MyColor.neutral200),
          Padding(
            padding: const EdgeInsets.all(Dimensions.space16),
            child: child,
          ),
        ],
      ),
    );
  }
}

class _OrderItemTile extends StatelessWidget {
  final dynamic item;
  final DeliveryController controller;

  const _OrderItemTile({required this.item, required this.controller});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: Dimensions.space12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: MyImageWidget(
              imageUrl: '${controller.orderProductImagePath}/${item.productImage}',
              height: 46,
              width: 46,
              boxFit: BoxFit.cover,
            ),
          ),
          const SizedBox(width: Dimensions.space10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item.productName ?? '', style: boldDefault.copyWith(fontSize: 14)),
                Text('x${item.quantity ?? 0}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                if (item.variation != null)
                  Text('• ${item.variation.variationName}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                if (item.addons != null && item.addons.isNotEmpty)
                  ...item.addons.map<Widget>((a) => Text('+ ${a.addonName}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))),
              ],
            ),
          ),
          Text('S/ ${item.totalPrice?.toStringAsFixed(2) ?? "0.00"}', style: semiBoldSmall.copyWith(color: MyColor.primaryColor)),
        ],
      ),
    );
  }
}

class _TotalRow extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;

  const _TotalRow({required this.label, required this.value, this.valueColor});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(label, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
        const Spacer(),
        Text(value, style: regularDefault.copyWith(color: valueColor ?? MyColor.primaryTextColor)),
      ],
    );
  }
}

class _AddressRow extends StatelessWidget {
  final IconData icon;
  final String text;

  const _AddressRow({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: Dimensions.space8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 15, color: MyColor.bodyMutedTextColor),
          const SizedBox(width: Dimensions.space8),
          Expanded(child: Text(text, style: regularDefault.copyWith(color: MyColor.primaryTextColor, height: 1.3))),
        ],
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final String label;
  final IconData icon;
  final Color color;
  final bool outlined;
  final VoidCallback onTap;

  const _ActionButton({
    required this.label,
    required this.icon,
    required this.color,
    required this.outlined,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(vertical: Dimensions.space14),
        decoration: BoxDecoration(
          color: outlined ? Colors.transparent : color,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color, width: outlined ? 1.5 : 0),
          boxShadow: outlined
              ? []
              : [BoxShadow(color: color.withValues(alpha: 0.35), blurRadius: 12, offset: const Offset(0, 4))],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: outlined ? color : Colors.white, size: 20),
            const SizedBox(width: Dimensions.space8),
            Text(label, style: boldDefault.copyWith(color: outlined ? color : Colors.white, fontSize: 15)),
          ],
        ),
      ),
    );
  }
}
