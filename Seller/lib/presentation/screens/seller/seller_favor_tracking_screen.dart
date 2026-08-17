import 'dart:async';
import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/services/directions_service.dart';
import 'package:lizto_store/environment.dart';

class SellerFavorTrackingScreen extends StatefulWidget {
  final int favorId;
  final String orderNo;
  const SellerFavorTrackingScreen({super.key, required this.favorId, required this.orderNo});

  @override
  State<SellerFavorTrackingScreen> createState() => _SellerFavorTrackingScreenState();
}

class _SellerFavorTrackingScreenState extends State<SellerFavorTrackingScreen> {
  GoogleMapController? _mapController;
  Timer? _poller;
  Timer? _movementTimer;
  LatLng? _pickup;
  LatLng? _destination;
  LatLng? _driver;
  List<LatLng> _deliveryRoute = [];
  String _status = 'accepted';
  String _courierName = 'Repartidor';
  double _bearing = 0;
  bool _firstMapFit = true;
  int _movementVersion = 0;
  BitmapDescriptor? _courierIcon;

  @override
  void initState() {
    super.initState();
    _loadCourierIcon();
    _refresh();
    _poller = Timer.periodic(const Duration(seconds: 3), (_) => _refresh());
  }

  Future<void> _loadCourierIcon() async {
    try {
      final icon = await BitmapDescriptor.fromAssetImage(const ImageConfiguration(size: Size(58, 58)), 'assets/images/map/driver.png');
      if (mounted) setState(() => _courierIcon = icon);
    } catch (_) {}
  }

  @override
  void dispose() {
    _poller?.cancel();
    _movementTimer?.cancel();
    _mapController?.dispose();
    super.dispose();
  }

  double? _number(dynamic value) => value is num ? value.toDouble() : double.tryParse(value?.toString() ?? '');

  Future<void> _refresh() async {
    final data = await Get.find<SellerController>().getStoreFavorDetail(widget.favorId);
    if (!mounted || data == null) return;
    final favor = data['favor'] as Map?;
    if (favor == null) return;
    final pickupLat = _number(favor['pickup_lat']);
    final pickupLng = _number(favor['pickup_lng']);
    final destinationLat = _number(favor['delivery_lat']);
    final destinationLng = _number(favor['delivery_lng']);
    final courier = favor['courier'] as Map?;
    final tracking = data['tracking'] as Map?;
    final trackingCourier = tracking?['courier'] as Map?;
    final driverLat = _number(trackingCourier?['latitude'] ?? courier?['current_lat'] ?? courier?['latitude']);
    final driverLng = _number(trackingCourier?['longitude'] ?? courier?['current_lot'] ?? courier?['current_lng'] ?? courier?['longitude']);
    final nextPickup = pickupLat != null && pickupLng != null ? LatLng(pickupLat, pickupLng) : null;
    final nextDestination = destinationLat != null && destinationLng != null ? LatLng(destinationLat, destinationLng) : null;
    final nextDriver = driverLat != null && driverLng != null ? LatLng(driverLat, driverLng) : null;

    setState(() {
      _pickup = nextPickup;
      _destination = nextDestination;
      _status = tracking?['status']?.toString() ?? favor['status']?.toString() ?? _status;
      _courierName = trackingCourier?['name']?.toString() ?? courier?['fullname']?.toString() ?? courier?['name']?.toString() ?? _courierName;
    });
    if (_deliveryRoute.isEmpty && nextPickup != null && nextDestination != null) _loadDeliveryRoute(nextPickup, nextDestination);
    if (nextDriver != null) _animateDriverTo(nextDriver);
    if (_firstMapFit && _pickup != null && _destination != null) _fitMap();
    if (['delivered', 'cancelled'].contains(_status)) _poller?.cancel();
  }

  Future<void> _loadDeliveryRoute(LatLng pickup, LatLng destination) async {
    final result = await DirectionsService.getDirections(
      originLat: pickup.latitude,
      originLng: pickup.longitude,
      destLat: destination.latitude,
      destLng: destination.longitude,
      apiKey: Environment.mapKey,
    );
    if (mounted && result != null) setState(() => _deliveryRoute = result.polylinePoints);
  }

  Future<void> _animateDriverTo(LatLng target) async {
    if (_driver == null) {
      setState(() => _driver = target);
      return;
    }
    if (_distance(_driver!, target) < 2) return;
    final version = ++_movementVersion;
    final start = _driver!;
    final route = await DirectionsService.getDirections(
      originLat: start.latitude,
      originLng: start.longitude,
      destLat: target.latitude,
      destLng: target.longitude,
      apiKey: Environment.mapKey,
    );
    if (!mounted || version != _movementVersion) return;
    final points = route?.polylinePoints.isNotEmpty == true ? route!.polylinePoints : [start, target];
    _movementTimer?.cancel();
    final startedAt = DateTime.now();
    const duration = Duration(milliseconds: 2600);
    _movementTimer = Timer.periodic(const Duration(milliseconds: 16), (timer) {
      if (!mounted || version != _movementVersion) {
        timer.cancel();
        return;
      }
      final progress = DateTime.now().difference(startedAt).inMilliseconds / duration.inMilliseconds;
      if (progress >= 1) {
        setState(() => _driver = target);
        timer.cancel();
        return;
      }
      final position = _pointAtProgress(points, progress);
      final ahead = _pointAtProgress(points, math.min(1, progress + .015));
      setState(() {
        _driver = position;
        _bearing = _bearingBetween(position, ahead);
      });
    });
  }

  LatLng _pointAtProgress(List<LatLng> points, double progress) {
    if (points.length < 2) return points.first;
    final lengths = <double>[];
    var total = 0.0;
    for (var i = 0; i < points.length - 1; i++) {
      final length = _distance(points[i], points[i + 1]);
      lengths.add(length);
      total += length;
    }
    var remaining = total * progress;
    for (var i = 0; i < lengths.length; i++) {
      if (remaining <= lengths[i]) {
        final ratio = lengths[i] == 0 ? 0 : remaining / lengths[i];
        return LatLng(points[i].latitude + (points[i + 1].latitude - points[i].latitude) * ratio, points[i].longitude + (points[i + 1].longitude - points[i].longitude) * ratio);
      }
      remaining -= lengths[i];
    }
    return points.last;
  }

  double _distance(LatLng a, LatLng b) {
    const earth = 6371000.0;
    final dLat = _rad(b.latitude - a.latitude);
    final dLng = _rad(b.longitude - a.longitude);
    final h = math.sin(dLat / 2) * math.sin(dLat / 2) + math.cos(_rad(a.latitude)) * math.cos(_rad(b.latitude)) * math.sin(dLng / 2) * math.sin(dLng / 2);
    return earth * 2 * math.atan2(math.sqrt(h), math.sqrt(1 - h));
  }

  double _bearingBetween(LatLng a, LatLng b) {
    final dLng = _rad(b.longitude - a.longitude);
    final y = math.sin(dLng) * math.cos(_rad(b.latitude));
    final x = math.cos(_rad(a.latitude)) * math.sin(_rad(b.latitude)) - math.sin(_rad(a.latitude)) * math.cos(_rad(b.latitude)) * math.cos(dLng);
    return (math.atan2(y, x) * 180 / math.pi + 360) % 360;
  }

  double _rad(double value) => value * math.pi / 180;

  Future<void> _fitMap() async {
    if (_mapController == null || _pickup == null || _destination == null) return;
    _firstMapFit = false;
    final points = [_pickup!, _destination!, if (_driver != null) _driver!];
    final minLat = points.map((p) => p.latitude).reduce(math.min);
    final maxLat = points.map((p) => p.latitude).reduce(math.max);
    final minLng = points.map((p) => p.longitude).reduce(math.min);
    final maxLng = points.map((p) => p.longitude).reduce(math.max);
    await _mapController!.animateCamera(CameraUpdate.newLatLngBounds(LatLngBounds(southwest: LatLng(minLat, minLng), northeast: LatLng(maxLat, maxLng)), 72));
  }

  String get _statusText {
    switch (_status) {
      case 'on_way_to_pickup':
        return 'El repartidor va rumbo al recojo';
      case 'at_pickup':
        return 'El repartidor esta en el punto de recojo';
      case 'on_way_to_delivery':
        return 'El repartidor va rumbo a la entrega';
      case 'delivered':
        return 'Pedido entregado';
      default:
        return 'Repartidor asignado';
    }
  }

  @override
  Widget build(BuildContext context) {
    final markers = <Marker>{
      if (_pickup != null) Marker(markerId: const MarkerId('pickup'), position: _pickup!, icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen), infoWindow: const InfoWindow(title: 'Recogida')),
      if (_destination != null) Marker(markerId: const MarkerId('destination'), position: _destination!, icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed), infoWindow: const InfoWindow(title: 'Destino')),
      if (_driver != null) Marker(markerId: const MarkerId('courier'), position: _driver!, flat: true, rotation: _bearing, anchor: const Offset(.5, .5), icon: _courierIcon ?? BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure), infoWindow: InfoWindow(title: _courierName)),
    };
    return Scaffold(
      appBar: AppBar(backgroundColor: MyColor.primaryColor, title: Text('Seguimiento en tiempo real', style: boldLarge.copyWith(color: Colors.white))),
      body: Stack(children: [
        GoogleMap(
          initialCameraPosition: CameraPosition(target: _pickup ?? const LatLng(-12.0464, -77.0428), zoom: 14),
          onMapCreated: (controller) {
            _mapController = controller;
            _fitMap();
          },
          markers: markers,
          polylines: {
            if (_deliveryRoute.isNotEmpty) Polyline(polylineId: const PolylineId('delivery_route'), points: _deliveryRoute, color: MyColor.primaryColor, width: 5),
          },
          myLocationButtonEnabled: false,
        ),
        Positioned(
          left: 16,
          right: 16,
          bottom: 22,
          child: SafeArea(
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: const [BoxShadow(color: Color(0x22000000), blurRadius: 12)]),
              child: Row(children: [
                Container(width: 44, height: 44, decoration: BoxDecoration(color: MyColor.primaryColor.withValues(alpha: .12), shape: BoxShape.circle), child: const Icon(Icons.two_wheeler_rounded, color: MyColor.primaryColor)),
                const SizedBox(width: 12),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(_statusText, style: boldLarge.copyWith(fontSize: 15)), const SizedBox(height: 3), Text('$_courierName - solicitud ${widget.orderNo}', style: regularSmall.copyWith(color: MyColor.bodyTextColor))])),
              ]),
            ),
          ),
        ),
      ]),
    );
  }
}
