import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_models.dart';
import 'package:lizto_delivery/data/services/directions_service.dart';
import 'package:lizto_delivery/data/services/pusher_service.dart';
import 'package:lizto_delivery/environment.dart';

import '../../../data/model/delivery/delivery_models.dart';

class DeliveryTrackingScreen extends StatefulWidget {
  final int orderId;
  final DeliveryOrderModel order;
  const DeliveryTrackingScreen({super.key, required this.orderId, required this.order});

  @override
  State<DeliveryTrackingScreen> createState() => _DeliveryTrackingScreenState();
}

class _DeliveryTrackingScreenState extends State<DeliveryTrackingScreen> {
  GoogleMapController? _mapController;
  final Set<Marker> _markers = {};
  final Set<Polyline> _polylines = {};
  LatLng? _storeLocation;
  LatLng? _deliveryLocation;
  LatLng? _courierLocation;
  bool _routeLoaded = false;
  String _statusText = 'Buscando repartidor...';
  String _etaText = '';
  Timer? _refreshTimer;

  @override
  void initState() {
    super.initState();
    _parseLocations();
    _subscribeToUpdates();
    _startAutoRefresh();
  }

  void _startAutoRefresh() {
    _refreshTimer = Timer.periodic(const Duration(seconds: 8), (_) {
      _loadRoute();
    });
  }

  void _parseLocations() {
    final order = widget.order;
    final store = order.store;
    if (store != null && store.latitude != null && store.longitude != null) {
      _storeLocation = LatLng(store.latitude!, store.longitude!);
      _markers.add(Marker(
        markerId: const MarkerId('store'),
        position: _storeLocation!,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
        infoWindow: InfoWindow(title: 'Tienda', snippet: store.address ?? store.name ?? ''),
      ));
    }
    if (order.deliveryLat != null && order.deliveryLng != null) {
      _deliveryLocation = LatLng(order.deliveryLat!, order.deliveryLng!);
      _markers.add(Marker(
        markerId: const MarkerId('delivery'),
        position: _deliveryLocation!,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
        infoWindow: InfoWindow(title: 'Destino', snippet: order.deliveryAddress ?? ''),
      ));
    }
    if (order.status != null) {
      _updateStatusText(order.status!);
    }
  }

  void _updateStatusText(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        _statusText = 'Buscando repartidor...';
      case 'confirmed':
        _statusText = 'Repartidor asignado';
      case 'preparing':
        _statusText = 'Preparando pedido';
      case 'ready':
        _statusText = 'Listo para recoger';
      case 'on_way':
        _statusText = 'En camino a la entrega';
      case 'delivered':
        _statusText = 'Entregado';
      default:
        _statusText = 'Actualizando...';
    }
    if (mounted) setState(() {});
  }

  void _subscribeToUpdates() {
    try {
      final pm = PusherManager();
      pm.addListener(_onPusherEvent);
      final channel = 'private-tracking.${widget.order.id ?? widget.orderId}';
      pm.checkAndInitIfNeeded(channel);
    } catch (_) {}
  }

  void _onPusherEvent(PusherEvent event) {
    try {
      final data = jsonDecode(event.data);
      if (event.eventName == 'location_update') {
        final lat = double.tryParse(data['latitude']?.toString() ?? '');
        final lng = double.tryParse(data['longitude']?.toString() ?? '');
        if (lat != null && lng != null) {
          setState(() {
            _courierLocation = LatLng(lat, lng);
            _markers.removeWhere((m) => m.markerId == const MarkerId('courier'));
            _markers.add(Marker(
              markerId: const MarkerId('courier'),
              position: _courierLocation!,
              icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure),
              infoWindow: const InfoWindow(title: 'Repartidor'),
              rotation: double.tryParse(data['bearing']?.toString() ?? '0') ?? 0,
            ));
            if (_storeLocation != null && !_routeLoaded) {
              _loadRoute();
            }
          });
        }
      } else if (event.eventName == 'delivery_order_status_updated') {
        final status = data['status'] as String? ?? '';
        _updateStatusText(status);
      }
    } catch (_) {}
  }

  Future<void> _loadRoute() async {
    final origin = _courierLocation ?? _storeLocation;
    if (origin == null || _deliveryLocation == null) return;
    final apiKey = Environment.mapKey;
    if (apiKey == null || apiKey.isEmpty) return;
    _routeLoaded = true;
    try {
      final directions = await DirectionsService.getDirections(
        originLat: origin.latitude,
        originLng: origin.longitude,
        destLat: _deliveryLocation!.latitude,
        destLng: _deliveryLocation!.longitude,
        apiKey: apiKey,
      );
      if (directions != null && mounted) {
        setState(() {
          _polylines.clear();
          _polylines.add(Polyline(
            polylineId: const PolylineId('route'),
            color: MyColor.primaryColor,
            width: 5,
            points: directions.polylinePoints,
          ));
          if (directions.durationText.isNotEmpty) {
            _etaText = 'Tiempo estimado: ${directions.durationText}';
          }
        });
        _fitBounds();
      }
    } catch (_) {}
  }

  void _fitBounds() {
    if (_mapController == null) return;
    final bounds = _buildBounds();
    if (bounds != null) {
      _mapController?.animateCamera(CameraUpdate.newLatLngBounds(bounds, 80));
    }
  }

  LatLngBounds? _buildBounds() {
    final points = <LatLng>[];
    if (_storeLocation != null) points.add(_storeLocation!);
    if (_courierLocation != null) points.add(_courierLocation!);
    if (_deliveryLocation != null) points.add(_deliveryLocation!);
    if (points.isEmpty) return null;
    double minLat = points.first.latitude;
    double maxLat = points.first.latitude;
    double minLng = points.first.longitude;
    double maxLng = points.first.longitude;
    for (final p in points) {
      if (p.latitude < minLat) minLat = p.latitude;
      if (p.latitude > maxLat) maxLat = p.latitude;
      if (p.longitude < minLng) minLng = p.longitude;
      if (p.longitude > maxLng) maxLng = p.longitude;
    }
    return LatLngBounds(
      southwest: LatLng(minLat - 0.005, minLng - 0.005),
      northeast: LatLng(maxLat + 0.005, maxLng + 0.005),
    );
  }

  @override
  void dispose() {
    PusherManager().removeListener(_onPusherEvent);
    _refreshTimer?.cancel();
    _mapController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Seguimiento en vivo', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: MyColor.colorWhite),
          onPressed: () => Get.back(),
        ),
      ),
      body: Stack(
        children: [
          GoogleMap(
            initialCameraPosition: CameraPosition(
              target: _storeLocation ?? _deliveryLocation ?? const LatLng(-12.046374, -77.042793),
              zoom: 15,
            ),
            markers: _markers,
            polylines: _polylines,
            onMapCreated: (controller) {
              _mapController = controller;
              if (_storeLocation != null && _deliveryLocation != null) {
                _loadRoute();
              }
            },
            myLocationEnabled: true,
            myLocationButtonEnabled: false,
            zoomControlsEnabled: true,
          ),
          Positioned(
            top: Dimensions.space10,
            left: Dimensions.space16,
            right: Dimensions.space16,
            child: Container(
              padding: EdgeInsets.all(Dimensions.space14),
              decoration: BoxDecoration(
                color: MyColor.colorWhite,
                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                boxShadow: [
                  BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 12, offset: const Offset(0, 4)),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 12,
                        height: 12,
                        decoration: BoxDecoration(
                          color: _orderActive ? MyColor.greenSuccessColor : MyColor.colorOrange,
                          shape: BoxShape.circle,
                        ),
                      ),
                      SizedBox(width: Dimensions.space10),
                      Expanded(
                        child: Text(_statusText, style: boldDefault.copyWith(fontSize: Dimensions.fontLarge)),
                      ),
                    ],
                  ),
                  if (_etaText.isNotEmpty) ...[
                    SizedBox(height: Dimensions.space8),
                    Row(
                      children: [
                        Icon(Icons.access_time, size: 16, color: MyColor.bodyMutedTextColor),
                        SizedBox(width: Dimensions.space6),
                        Text(_etaText, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  bool get _orderActive {
    final s = widget.order.status?.toLowerCase() ?? '';
    return s != 'delivered' && s != 'cancelled' && s != 'canceled';
  }
}
