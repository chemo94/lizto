import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/favor_controller.dart';
import 'package:liztogo/data/repo/delivery/favor_repo.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'package:liztogo/data/model/delivery/favor_models.dart';
import 'package:liztogo/data/services/directions_service.dart';
import 'package:liztogo/data/services/pusher_service.dart';
import 'package:liztogo/environment.dart';
import 'package:liztogo/presentation/screens/delivery/favor_chat_screen.dart';
import 'package:liztogo/presentation/screens/delivery/favor_review_screen.dart';
import 'package:liztogo/data/model/delivery/shopping_models.dart';
import 'package:liztogo/data/repo/delivery/shopping_repo.dart';

class FavorTrackingScreen extends StatefulWidget {
  final int favorId;
  const FavorTrackingScreen({super.key, required this.favorId});

  @override
  State<FavorTrackingScreen> createState() => _FavorTrackingScreenState();
}

class _FavorTrackingScreenState extends State<FavorTrackingScreen> {
  GoogleMapController? _mapController;
  final Set<Marker> _markers = {};
  final Set<Polyline> _polylines = {};
  int? _lastFavorId;
  String? _lastStatus;
  LatLng? _lastCourierPos;
  bool _routeLoaded = false;
  String? _routeDuration;
  String? _jobChannel;
  String? _favorChannel;
  static const _initialLat = -12.0464;
  static const _initialLng = -77.0428;

  // Shopping state
  String? _shoppingStatus;
  double _shoppingProgress = 0;
  bool _needsSubstitutionApproval = false;
  bool _receiptUploaded = false;
  List<ShoppingListItem> _pendingSubstitutions = [];
  double? _actualTotal;
  String? _receiptUrl;
  String? _storePhotoUrl;
  ShoppingBudget? _shoppingBudget;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<FavorController>()) {
      Get.put(FavorController(favorRepo: FavorRepo(apiClient: Get.find<ApiClient>())));
    }
    _subscribeToJobChannel();
    _subscribeToFavorChannel();
    _loadShoppingStatus();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<FavorController>();
      c.loadFavorDetail(widget.favorId);
    });
  }

  void _subscribeToJobChannel() {
    _jobChannel = 'private-job-${widget.favorId}';
    PusherManager().addListener(_onJobEvent);
    PusherManager().checkAndInitIfNeeded(_jobChannel!);
  }

  void _subscribeToFavorChannel() {
    _favorChannel = 'private-favor.${widget.favorId}';
    PusherManager().addListener(_onFavorEvent);
    PusherManager().checkAndInitIfNeeded(_favorChannel!);
  }

  void _onFavorEvent(PusherEvent event) {
    if (event.channelName != _favorChannel) return;
    try {
      final data = jsonDecode(event.data);
      if (event.eventName == 'shopping_item_updated' || event.eventName == 'shopping_substitution_proposed' || event.eventName == 'shopping_receipt_uploaded') {
        _loadShoppingStatus();
      }
    } catch (_) {}
  }

  void _onJobEvent(PusherEvent event) {
    if (event.channelName != _jobChannel) return;
    try {
      final data = jsonDecode(event.data);
      if (event.eventName == 'location_update') {
        final lat = double.tryParse(data['latitude']?.toString() ?? '');
        final lng = double.tryParse(data['longitude']?.toString() ?? '');
        if (lat != null && lng != null && mounted) {
          setState(() {
            _markers.removeWhere((m) => m.markerId == const MarkerId('courier'));
            _markers.add(Marker(
              markerId: const MarkerId('courier'),
              position: LatLng(lat, lng),
              icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
              rotation: double.tryParse(data['bearing']?.toString() ?? '0') ?? 0,
              infoWindow: InfoWindow(title: data['courier_name']?.toString() ?? 'Repartidor'),
            ));
          });
        }
      } else if (event.eventName == 'job_message_received') {
        try {
          final c = Get.find<FavorController>();
          c.addEventMessage(data);
        } catch (_) {}
      }
    } catch (_) {}
  }

  Future<void> _loadShoppingStatus() async {
    try {
      final repo = ShoppingRepo(apiClient: Get.find<ApiClient>());
      final res = await repo.getShoppingStatus(widget.favorId);
      if (res.statusCode == 200 && res.responseJson != null) {
        final data = res.responseJson['data'];
        if (data != null && mounted) {
          setState(() {
            _shoppingStatus = data['shopping_status']?.toString();
            _shoppingProgress = ((data['progress'] as num?)?.toDouble() ?? 0) / 100;
            _needsSubstitutionApproval = data['needs_approval'] == true;
            _actualTotal = data['actual_total'] != null ? double.tryParse(data['actual_total'].toString()) : null;
            _receiptUrl = data['receipt_url']?.toString();
            _storePhotoUrl = data['store_photo_url']?.toString();
            if (data['budget'] != null) _shoppingBudget = ShoppingBudget.fromJson(data['budget']);
            if (data['items'] != null) {
              _pendingSubstitutions = (data['items'] as List).map((e) => ShoppingListItem.fromJson(e)).where((item) => item.needsApproval).toList();
            }
            _receiptUploaded = _shoppingStatus == 'purchased';
          });
        }
      }
    } catch (_) {}
  }

  @override
  void dispose() {
    PusherManager().removeListener(_onJobEvent);
    if (_favorChannel != null) {
      PusherManager().removeListener(_onFavorEvent);
    }
    _mapController?.dispose();
    super.dispose();
  }

  void _updateMap(FavorModel? favor) {
    if (favor == null || _mapController == null) return;
    final courierLat = favor.courier?.latitude;
    final courierLng = favor.courier?.longitude;
    bool courierMoved = (courierLat != null && courierLng != null) && (_lastCourierPos == null || _lastCourierPos!.latitude != courierLat || _lastCourierPos!.longitude != courierLng);
    if (courierMoved) _lastCourierPos = LatLng(courierLat!, courierLng!);
    bool changed = _lastFavorId != favor.id || _lastStatus != favor.status || courierMoved;
    _lastFavorId = favor.id;
    _lastStatus = favor.status;
    if (!changed) return;
    _markers.clear();
    _polylines.clear();

    // Pickup marker
    if (favor.pickupLat != null && favor.pickupLng != null) {
      final pickup = LatLng(favor.pickupLat!, favor.pickupLng!);
      _markers.add(Marker(
        markerId: const MarkerId('pickup'),
        position: pickup,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
        infoWindow: InfoWindow(title: 'Recogida', snippet: favor.pickupAddress),
      ));
    }

    // Delivery marker
    if (favor.deliveryLat != null && favor.deliveryLng != null) {
      final delivery = LatLng(favor.deliveryLat!, favor.deliveryLng!);
      _markers.add(Marker(
        markerId: const MarkerId('delivery'),
        position: delivery,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
        infoWindow: InfoWindow(title: 'Entrega', snippet: favor.deliveryAddress),
      ));
    }

    // Courier marker
    if (favor.courier?.latitude != null && favor.courier?.longitude != null) {
      final courierPos = LatLng(favor.courier!.latitude!, favor.courier!.longitude!);
      _markers.add(Marker(
        markerId: const MarkerId('courier'),
        position: courierPos,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
        rotation: favor.courier!.bearing ?? 0,
        infoWindow: InfoWindow(title: favor.courier!.fullName, snippet: 'Repartidor'),
      ));
      _mapController?.animateCamera(CameraUpdate.newLatLng(courierPos));
    }

    // Polyline from pickup to delivery with real route
    if (favor.pickupLat != null && favor.pickupLng != null && favor.deliveryLat != null && favor.deliveryLng != null) {
      if (!_routeLoaded) {
        _loadRealRoute(favor.pickupLat!, favor.pickupLng!, favor.deliveryLat!, favor.deliveryLng!);
      }
      if (_polylines.isEmpty) {
        _polylines.add(Polyline(
          polylineId: const PolylineId('route'),
          points: [LatLng(favor.pickupLat!, favor.pickupLng!), LatLng(favor.deliveryLat!, favor.deliveryLng!)],
          color: MyColor.primaryColor.withValues(alpha: 0.4),
          width: 4,
          patterns: [PatternItem.dash(10), PatternItem.gap(6)],
        ));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<FavorController>(
      builder: (c) {
        final favor = c.selectedFavor;
        WidgetsBinding.instance.addPostFrameCallback((_) {
          _updateMap(favor);
        });

        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text(favor?.orderNo ?? 'Seguimiento', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            actions: [
              if (favor != null)
                IconButton(
                  icon: Icon(Icons.chat_rounded, color: MyColor.colorWhite),
                  onPressed: () => Get.to(() => FavorChatScreen(favorId: favor.id ?? 0)),
                ),
            ],
          ),
          body: c.isLoading
              ? const Center(child: CircularProgressIndicator())
              : favor == null
                  ? const Center(child: Text('Favor no encontrado'))
                  : Column(
                      children: [
                        // ── Map ──
                        SizedBox(
                          height: MediaQuery.of(context).size.height * 0.35,
                          child: GoogleMap(
                            initialCameraPosition: CameraPosition(
                              target: LatLng(favor.pickupLat ?? _initialLat, favor.pickupLng ?? _initialLng),
                              zoom: 15,
                            ),
                            markers: _markers,
                            polylines: _polylines,
                            onMapCreated: (controller) {
                              _mapController = controller;
                              _updateMap(favor);
                            },
                            myLocationEnabled: true,
                            zoomControlsEnabled: false,
                          ),
                        ),
                        // ── Status & Info ──
                        Expanded(
                          child: ListView(
                            padding: EdgeInsets.all(Dimensions.space16),
                            children: [
                              _buildCourierCard(c, favor),
                              SizedBox(height: Dimensions.space16),
                              _buildStatusTimeline(favor),
                              SizedBox(height: Dimensions.space16),
                              if (favor.status == 'searching_courier') ...[
                                _buildBidsSection(c, favor),
                                SizedBox(height: Dimensions.space16),
                              ],
                              if (_isShoppingType(favor)) ...[
                                _buildShoppingProgressCard(favor),
                                SizedBox(height: Dimensions.space16),
                              ],
                              _buildDetailCard(favor),
                              if (favor.status == 'delivered') ...[
                                SizedBox(height: Dimensions.space12),
                                SizedBox(
                                  width: double.infinity,
                                  child: ElevatedButton.icon(
                                    onPressed: () => Get.to(() => FavorReviewScreen(
                                          favorId: favor.id ?? 0,
                                          orderNo: favor.orderNo ?? '',
                                          courierName: favor.courier?.fullName,
                                        )),
                                    icon: Icon(Icons.star_rounded, color: const Color(0xFFF59E0B)),
                                    label: Text('Calificar servicio', style: boldDefault.copyWith(color: MyColor.colorWhite)),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: const Color(0xFFF59E0B),
                                      padding: EdgeInsets.all(Dimensions.space12),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                                    ),
                                  ),
                                ),
                              ],
                              if (favor.isActive) ...[
                                SizedBox(height: Dimensions.space20),
                                SizedBox(
                                  width: double.infinity,
                                  child: OutlinedButton.icon(
                                    onPressed: () => _cancelFavor(c, favor.id ?? 0),
                                    icon: Icon(Icons.cancel_outlined, color: MyColor.redCancelTextColor),
                                    label: Text('Cancelar solicitud', style: boldDefault.copyWith(color: MyColor.redCancelTextColor)),
                                    style: OutlinedButton.styleFrom(
                                      side: BorderSide(color: MyColor.redCancelTextColor),
                                      padding: EdgeInsets.all(Dimensions.space12),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ],
                    ),
          // ── Bottom chat bar ──
          bottomNavigationBar: favor != null && favor.isActive
              ? Container(
                  padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: Dimensions.space10),
                  decoration: BoxDecoration(
                    color: MyColor.colorWhite,
                    boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 8, offset: const Offset(0, -2))],
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: GestureDetector(
                          onTap: () => Get.to(() => FavorChatScreen(favorId: favor.id ?? 0)),
                          child: Container(
                            padding: EdgeInsets.symmetric(vertical: Dimensions.space12),
                            decoration: BoxDecoration(
                              color: MyColor.primaryColor.withValues(alpha: 0.06),
                              borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.chat_bubble_outline_rounded, color: MyColor.primaryColor, size: 20),
                                SizedBox(width: 8),
                                Text('Chatear con el repartidor', style: regularDefault.copyWith(color: MyColor.primaryColor)),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                )
              : null,
        );
      },
    );
  }

  Widget _buildCourierCard(dynamic c, FavorModel favor) {
    final courier = favor.courier;
    if (courier == null) {
      return Container(
        padding: EdgeInsets.all(Dimensions.space16),
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
        ),
        child: Row(
          children: [
            Container(
              padding: EdgeInsets.all(Dimensions.space12),
              decoration: BoxDecoration(
                color: Colors.blue.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
              ),
              child: Icon(Icons.person_search_rounded, color: Colors.blue, size: 28),
            ),
            SizedBox(width: Dimensions.space12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Buscando repartidor cercano...', style: boldDefault),
                SizedBox(height: 2),
                Text('Te notificaremos cuando alguien acepte tu solicitud.', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
              ]),
            ),
            SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)),
          ],
        ),
      );
    }

    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 24,
            backgroundColor: MyColor.primaryColor.withValues(alpha: 0.1),
            child: Icon(Icons.person, color: MyColor.primaryColor, size: 28),
          ),
          SizedBox(width: Dimensions.space12),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(courier.fullName, style: boldDefault),
              SizedBox(height: 2),
              if (courier.rating != null)
                Row(children: [
                  Icon(Icons.star_rounded, color: const Color(0xFFF59E0B), size: 16),
                  SizedBox(width: 2),
                  Text(courier.rating!.toStringAsFixed(1), style: regularSmall.copyWith(color: const Color(0xFFF59E0B))),
                ]),
              if (courier.distanceKm != null) Text('A ${courier.distanceKm!.toStringAsFixed(1)} km', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
            ]),
          ),
          Container(
            padding: EdgeInsets.all(Dimensions.space8),
            decoration: BoxDecoration(
              color: const Color(0xFF10B981).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(30),
            ),
            child: Icon(Icons.call_rounded, color: const Color(0xFF10B981), size: 20),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusTimeline(FavorModel favor) {
    final isShopping = _isShoppingType(favor);
    final statuses = isShopping ? ['accepted', 'on_way_to_pickup', 'at_pickup', 'shopping', 'awaiting_approval', 'purchasing', 'purchased', 'on_way_to_delivery', 'delivered'] : ['pending', 'searching_courier', 'accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery', 'delivered'];
    final labels = isShopping ? ['Aceptado', 'Yendo a tienda', 'En tienda', 'Comprando', 'Esperando aprobación', 'Comprando', 'Comprado', 'Entregando', 'Entregado'] : ['Pendiente', 'Buscando', 'Aceptado', 'Recogiendo', 'Recogido', 'Entregando', 'Entregado'];
    final currentIdx = statuses.indexOf(favor.status ?? 'pending');

    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('Estado del servicio', style: boldLarge),
        SizedBox(height: Dimensions.space12),
        ...List.generate(statuses.length, (i) {
          bool done = i <= currentIdx && currentIdx >= 0;
          bool active = i == currentIdx;
          Color color = done ? favor.statusColor : MyColor.borderColor;
          return Padding(
            padding: EdgeInsets.only(bottom: i < statuses.length - 1 ? 0 : Dimensions.space4),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Column(
                  children: [
                    Container(
                      width: 24,
                      height: 24,
                      decoration: BoxDecoration(
                        color: done ? color : Colors.transparent,
                        border: Border.all(color: color, width: 2),
                        shape: BoxShape.circle,
                      ),
                      child: done ? Icon(Icons.check_rounded, size: 16, color: MyColor.colorWhite) : null,
                    ),
                    if (i < statuses.length - 1) Container(width: 2, height: 30, color: done ? color : MyColor.borderColor),
                  ],
                ),
                SizedBox(width: Dimensions.space12),
                Padding(
                  padding: EdgeInsets.only(top: 2),
                  child: Text(labels[i], style: active ? boldDefault.copyWith(color: color) : regularDefault.copyWith(color: done ? color : MyColor.bodyMutedTextColor)),
                ),
              ],
            ),
          );
        }),
      ]),
    );
  }

  Widget _buildDetailCard(FavorModel favor) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('Detalles', style: boldLarge),
        SizedBox(height: Dimensions.space8),
        _row('Tipo', favor.typeLabel),
        if (favor.storeName != null) _row('Tienda', favor.storeName),
        _row('Descripción', favor.description ?? ''),
        _row('Recogida', favor.pickupAddress ?? ''),
        _row('Entrega', favor.deliveryAddress ?? ''),
        if (favor.estimatedAmount != null) _row('Monto est.', 'S/ ${favor.estimatedAmount!.toStringAsFixed(2)}'),
        if (favor.deliveryFee != null) _row('Delivery', 'S/ ${favor.deliveryFee!.toStringAsFixed(2)}'),
        if (favor.total != null) _row('Total', 'S/ ${favor.total!.toStringAsFixed(2)}', bold: true, color: MyColor.primaryColor),
        if (favor.recipientName != null) _row('Recibe', '${favor.recipientName}${favor.recipientPhone != null ? " - ${favor.recipientPhone}" : ""}'),
      ]),
    );
  }

  Widget _row(String label, String? value, {bool bold = false, Color? color}) {
    if (value == null) return const SizedBox.shrink();
    return Padding(
      padding: EdgeInsets.only(bottom: Dimensions.space4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(width: 90, child: Text(label, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))),
          Expanded(child: Text(value, style: (bold ? boldDefault : regularDefault).copyWith(color: color))),
        ],
      ),
    );
  }

  void _cancelFavor(dynamic c, int favorId) {
    Get.defaultDialog(
      title: 'Cancelar solicitud',
      middleText: '¿Estás seguro de cancelar este servicio?',
      textConfirm: 'Sí, cancelar',
      textCancel: 'No',
      confirmTextColor: MyColor.colorWhite,
      onConfirm: () async {
        Get.back();
        bool ok = await c.cancelFavor(favorId);
        if (ok) {
          Get.snackbar('Cancelado', 'Servicio cancelado correctamente', backgroundColor: MyColor.primaryColor, colorText: MyColor.colorWhite);
          Get.back();
        }
      },
    );
  }

  Future<void> _loadRealRoute(double fromLat, double fromLng, double toLat, double toLng) async {
    _routeLoaded = true;
    final result = await DirectionsService.getDirections(
      originLat: fromLat,
      originLng: fromLng,
      destLat: toLat,
      destLng: toLng,
      apiKey: Environment.mapKey,
    );
    if (result != null && mounted) {
      setState(() {
        _routeDuration = result.durationText;
        _polylines.clear();
        _polylines.add(Polyline(
          polylineId: const PolylineId('route'),
          points: result.polylinePoints,
          color: MyColor.primaryColor,
          width: 5,
        ));
      });
    }
  }

  Widget _buildBidsSection(dynamic c, dynamic favor) {
    c.loadBids(favor.id ?? 0);
    return GetBuilder<FavorController>(
      builder: (controller) {
        if (controller.loadingBids) {
          return Container(
            padding: EdgeInsets.all(Dimensions.space16),
            decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
            child: const Center(child: CircularProgressIndicator()),
          );
        }
        if (controller.bids.isEmpty) {
          return Container(
            padding: EdgeInsets.all(Dimensions.space12),
            decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
            child: Row(children: [
              SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)),
              SizedBox(width: Dimensions.space8),
              Text('Esperando ofertas de repartidores...', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
            ]),
          );
        }
        return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Ofertas recibidas (${controller.bids.length})', style: boldLarge),
          SizedBox(height: Dimensions.space8),
          ...controller.bids.map((bid) => Container(
                margin: EdgeInsets.only(bottom: Dimensions.space8),
                padding: EdgeInsets.all(Dimensions.space12),
                decoration: BoxDecoration(
                  color: MyColor.colorWhite,
                  borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                  boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
                  border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.3)),
                ),
                child: Row(children: [
                  CircleAvatar(
                    radius: 20,
                    backgroundColor: MyColor.primaryColor.withValues(alpha: 0.1),
                    child: Icon(Icons.person, color: MyColor.primaryColor, size: 20),
                  ),
                  SizedBox(width: Dimensions.space12),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(bid.courierName ?? 'Repartidor', style: boldDefault),
                      if (bid.courierRating != null)
                        Row(children: [
                          Icon(Icons.star_rounded, color: const Color(0xFFF59E0B), size: 14),
                          Text(bid.courierRating!.toStringAsFixed(1), style: regularSmall.copyWith(color: const Color(0xFFF59E0B))),
                          if (bid.courierDistanceKm != null) ...[
                            SizedBox(width: 8),
                            Icon(Icons.near_me_rounded, size: 12, color: MyColor.bodyMutedTextColor),
                            Text('${bid.courierDistanceKm!.toStringAsFixed(1)} km', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                          ],
                        ]),
                      if (bid.message != null) Text(bid.message!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 1, overflow: TextOverflow.ellipsis),
                    ]),
                  ),
                  Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                    Text('S/ ${bid.bidAmount?.toStringAsFixed(2) ?? "0.00"}', style: boldLarge.copyWith(color: const Color(0xFF10B981))),
                    SizedBox(height: 4),
                    ElevatedButton(
                      onPressed: () async {
                        bool ok = await (c as FavorController).acceptBid(favor.id ?? 0, bid.id ?? 0);
                        if (ok) {
                          Get.snackbar('Aceptado', 'Repartidor asignado correctamente', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF10B981),
                        foregroundColor: MyColor.colorWhite,
                        padding: EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space6),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                        minimumSize: Size.zero,
                      ),
                      child: Text('Aceptar', style: regularSmall.copyWith(fontWeight: FontWeight.w600)),
                    ),
                  ]),
                ]),
              )),
        ]);
      },
    );
  }

  bool _isShoppingType(FavorModel favor) {
    return favor.type?.toLowerCase() == 'buy';
  }

  Widget _buildShoppingProgressCard(FavorModel favor) {
    final statusLabels = {
      'preparing': 'Preparando lista...',
      'submitted': 'Lista enviada',
      'shopping': 'En proceso de compra...',
      'awaiting_approval': 'Esperando aprobación del cliente',
      'purchasing': 'Comprando productos...',
      'purchased': 'Compra completada',
      'delivering': 'Entregando...',
      'delivered': 'Entregado',
    };

    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Icon(Icons.shopping_cart_rounded, color: MyColor.primaryColor, size: 20),
          SizedBox(width: Dimensions.space8),
          Text('Compra en tienda', style: boldDefault),
        ]),
        SizedBox(height: Dimensions.space12),
        Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            Text(statusLabels[_shoppingStatus] ?? 'Estado desconocido', style: regularSmall),
            Text('${(_shoppingProgress * 100).toInt()}%', style: boldSmall.copyWith(color: MyColor.primaryColor)),
          ]),
          SizedBox(height: Dimensions.space4),
          ClipRRect(
            borderRadius: BorderRadius.circular(4),
            child: LinearProgressIndicator(
              value: _shoppingProgress,
              backgroundColor: MyColor.borderColor.withValues(alpha: 0.3),
              color: MyColor.primaryColor,
              minHeight: 6,
            ),
          ),
        ]),
        SizedBox(height: Dimensions.space12),
        if (_shoppingBudget != null) ...[
          Row(children: [
            Icon(Icons.account_balance_wallet_rounded, size: 14, color: MyColor.bodyMutedTextColor),
            SizedBox(width: Dimensions.space4),
            Text('Presupuesto: S/ ${_shoppingBudget!.maxProductBudget?.toStringAsFixed(2) ?? "0.00"}', style: regularSmall),
          ]),
          SizedBox(height: Dimensions.space4),
        ],
        if (_actualTotal != null) ...[
          Row(children: [
            Icon(Icons.receipt_rounded, size: 14, color: MyColor.bodyMutedTextColor),
            SizedBox(width: Dimensions.space4),
            Text('Total: S/ ${_actualTotal!.toStringAsFixed(2)}', style: regularSmall.copyWith(fontWeight: FontWeight.w600)),
          ]),
          SizedBox(height: Dimensions.space8),
        ],
        if (_needsSubstitutionApproval) ...[
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => _openSubstitutionReview(),
              icon: Icon(Icons.swap_horiz_rounded, size: 18),
              label: Text('Revisar sustitutos (${_pendingSubstitutions.length})', style: regularSmall.copyWith(color: MyColor.colorWhite)),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.orange,
                padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
            ),
          ),
          SizedBox(height: Dimensions.space8),
        ],
        if (_receiptUploaded && _actualTotal != null) ...[
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () => _openReceiptReview(),
              icon: Icon(Icons.receipt_long_rounded, size: 18),
              label: Text('Revisar comprobante', style: regularSmall.copyWith(color: MyColor.colorWhite)),
              style: ElevatedButton.styleFrom(
                backgroundColor: MyColor.primaryColor,
                padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
            ),
          ),
        ],
      ]),
    );
  }

  void _openSubstitutionReview() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.6,
        minChildSize: 0.4,
        maxChildSize: 0.92,
        expand: false,
        builder: (ctx, scrollController) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          padding: EdgeInsets.all(Dimensions.space12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(children: [
                Icon(Icons.swap_horiz_rounded, color: Colors.orange, size: 22),
                SizedBox(width: Dimensions.space8),
                Text('Revisar sustitutos', style: boldDefault),
                const Spacer(),
                IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
              ]),
              SizedBox(height: Dimensions.space8),
              Expanded(
                child: _pendingSubstitutions.isEmpty
                    ? Center(child: Text('No hay sustitutos pendientes de revisión.', style: regularSmall))
                    : ListView.separated(
                        controller: scrollController,
                        itemCount: _pendingSubstitutions.length,
                        separatorBuilder: (_, __) => Divider(color: MyColor.borderColor),
                        itemBuilder: (_, i) {
                          final item = _pendingSubstitutions[i];
                          return Padding(
                            padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(item.name ?? 'Producto', style: boldDefault),
                                SizedBox(height: Dimensions.space4),
                                Row(children: [
                                  Icon(Icons.arrow_forward_rounded, size: 16, color: Colors.orange),
                                  SizedBox(width: Dimensions.space4),
                                  Expanded(
                                    child: Text(
                                      '${item.substituteName ?? "—"} · S/ ${item.substitutePrice?.toStringAsFixed(2) ?? "?"}',
                                      style: regularSmall.copyWith(fontWeight: FontWeight.w600),
                                    ),
                                  ),
                                ]),
                                if (item.substituteNotes != null && item.substituteNotes!.isNotEmpty) ...[
                                  SizedBox(height: Dimensions.space4),
                                  Text(item.substituteNotes!, style: regularSmall.copyWith(color: Colors.black54)),
                                ],
                              ],
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _openReceiptReview() {
    final hasReceipt = _receiptUrl != null && _receiptUrl!.isNotEmpty;
    final hasStorePhoto = _storePhotoUrl != null && _storePhotoUrl!.isNotEmpty;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        padding: EdgeInsets.all(Dimensions.space12),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(children: [
                Icon(Icons.receipt_long_rounded, color: MyColor.primaryColor, size: 22),
                SizedBox(width: Dimensions.space8),
                Text('Comprobante de compra', style: boldDefault),
                const Spacer(),
                IconButton(onPressed: () => Navigator.pop(ctx), icon: const Icon(Icons.close)),
              ]),
              SizedBox(height: Dimensions.space8),
              if (_actualTotal != null) ...[
                Text('Total: S/ ${_actualTotal!.toStringAsFixed(2)}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
                SizedBox(height: Dimensions.space12),
              ],
              if (hasReceipt) ...[
                Text('Comprobante', style: regularSmall.copyWith(color: Colors.black54)),
                SizedBox(height: Dimensions.space4),
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(_receiptUrl!, fit: BoxFit.contain, errorBuilder: (_, __, ___) => _imgError()),
                ),
                SizedBox(height: Dimensions.space12),
              ],
              if (hasStorePhoto) ...[
                Text('Foto en tienda', style: regularSmall.copyWith(color: Colors.black54)),
                SizedBox(height: Dimensions.space4),
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: Image.network(_storePhotoUrl!, fit: BoxFit.contain, errorBuilder: (_, __, ___) => _imgError()),
                ),
                SizedBox(height: Dimensions.space12),
              ],
              if (!hasReceipt && !hasStorePhoto)
                Padding(
                  padding: EdgeInsets.symmetric(vertical: Dimensions.space12),
                  child: Center(child: Text('No hay comprobante disponible.', style: regularSmall)),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _imgError() => Container(
        height: 120,
        alignment: Alignment.center,
        color: MyColor.borderColor.withValues(alpha: 0.2),
        child: Icon(Icons.broken_image_rounded, color: Colors.black38, size: 36),
      );
}
