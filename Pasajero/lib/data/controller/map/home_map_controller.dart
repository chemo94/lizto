import 'dart:async';
import 'dart:convert';
import 'dart:math';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/helper.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_images.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/data/model/home/nearby_drivers_response_model.dart';
import 'package:liztogo/data/repo/home/home_repo.dart';
import 'package:liztogo/data/services/pusher_service.dart';
import 'package:liztogo/environment.dart';
import 'package:liztogo/presentation/packages/flutter_polyline_points/flutter_polyline_points.dart';

class NearbyDriverMarker {
  LatLng latLng;
  LatLng targetLatLng;
  LatLng oldLatLng;
  double rotation;
  double targetRotation;
  double oldRotation;
  String? serviceName;

  NearbyDriverMarker({required this.latLng, this.rotation = 0, this.serviceName})
      : targetLatLng = latLng,
        oldLatLng = latLng,
        targetRotation = rotation,
        oldRotation = rotation;
}

class HomeMapController extends GetxController with GetSingleTickerProviderStateMixin {
  HomeRepo homeRepo;
  HomeController homeController;
  HomeMapController({required this.homeRepo, required this.homeController});

  GoogleMapController? mapController;
  final Map<String, NearbyDriverMarker> _driverMarkers = {};
  late final AnimationController _animationController;
  Timer? _pollTimer;
  Uint8List? carIcon;
  Uint8List? motoIcon;
  bool isMapReady = false;
  String? _nearbyChannel;
  List<NearbyDriver> nearbyDrivers = [];

  // ── Route polyline ─────────────────────────────────────────────────────
  Map<PolylineId, Polyline> homePolylines = {};
  List<LatLng> homePolylineCoords = [];
  bool isDrawingRoute = false;

  @override
  void onInit() {
    super.onInit();
    _animationController = AnimationController(vsync: this, duration: const Duration(seconds: 2));
    _loadIcons();
    _subscribeToNearbyDrivers();
  }

  void _subscribeToNearbyDrivers() {
    try {
      final userId = homeRepo.apiClient.getUserID();
      if (userId.isEmpty) return;
      _nearbyChannel = 'private-nearby-drivers';
      PusherManager().addListener(_onNearbyDriverEvent);
      PusherManager().checkAndInitIfNeeded(_nearbyChannel!);
      printX('HomeMapController: subscribed to $_nearbyChannel');
    } catch (e) {
      printE('HomeMapController subscribe error: $e');
    }
  }

  void _onNearbyDriverEvent(PusherEvent event) {
    if (event.channelName != _nearbyChannel) return;
    if (event.eventName != 'driver_location_updated' && event.eventName != 'nearby_driver_update') return;
    try {
      final data = jsonDecode(event.data);
      final driverId = data['driver_id']?.toString() ?? '';
      final lat = StringConverter.formatDouble(data['latitude']?.toString() ?? '0', precision: 10);
      final lng = StringConverter.formatDouble(data['longitude']?.toString() ?? '0', precision: 10);
      if (lat == 0 || lng == 0 || driverId.isEmpty) return;
      final latLng = LatLng(lat, lng);
      final bearing = StringConverter.formatDouble(data['bearing']?.toString() ?? '0', precision: 10);
      final serviceName = data['service_name']?.toString();

      if (_driverMarkers.containsKey(driverId)) {
        final existing = _driverMarkers[driverId]!;
        existing.oldLatLng = existing.latLng;
        existing.targetLatLng = latLng;
        existing.oldRotation = existing.rotation;
        double newRotation = existing.rotation;
        if (existing.oldLatLng.latitude != latLng.latitude ||
            existing.oldLatLng.longitude != latLng.longitude) {
          newRotation = _getRotation(
            existing.oldLatLng.latitude,
            existing.oldLatLng.longitude,
            latLng.latitude,
            latLng.longitude,
          );
        } else if (bearing != 0) {
          newRotation = bearing;
        }
        existing.targetRotation = newRotation;
        if (serviceName != null) existing.serviceName = serviceName;
      } else {
        _driverMarkers[driverId] = NearbyDriverMarker(
          latLng: latLng,
          rotation: bearing,
          serviceName: serviceName,
        );
      }
      _animateAllMarkers();
    } catch (e) {
      printE('HomeMapController event error: $e');
    }
  }

  Future<void> _loadIcons() async {
    carIcon = await Helper.getBytesFromAsset(MyImages.mapDriverMarker, 80);
    motoIcon = await Helper.getBytesFromAsset(MyImages.mapDriverMarkerMt, 80);
  }

  void onMapCreated(GoogleMapController controller) {
    mapController = controller;
    isMapReady = true;
    _startPolling();
    _retryAnimateToUser();
    update();
  }

  void _retryAnimateToUser() {
    if (!isMapReady) return;
    Future.delayed(const Duration(milliseconds: 500), () {
      if (!isMapReady) return;
      if (homeController.currentPosition != null && homeController.currentPosition!.latitude != 0.0) {
        animateToCurrentLocation();
      } else {
        _retryAnimateToUser();
      }
    });
  }

  void _startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 15), (_) => _fetchNearbyDrivers());
    _fetchNearbyDrivers();
  }

  void fetchNearbyDrivers() {
    _fetchNearbyDrivers();
  }

  Future<void> _fetchNearbyDrivers() async {
    if (!isMapReady) return;
    try {
      final pos = homeController.currentPosition;
      if (pos == null || pos.latitude == 0.0) return;
      final model = await homeRepo.getNearbyDrivers(lat: pos.latitude, lng: pos.longitude);
      _updateDrivers(model.data?.drivers ?? []);
    } catch (e) {
      printE('HomeMapController fetchNearbyDrivers error: $e');
    }
  }

  void _updateDrivers(List<NearbyDriver> drivers) {
    nearbyDrivers = drivers;
    final currentIds = _driverMarkers.keys.toSet();
    final newIds = drivers.map((d) => d.id ?? '').toSet();

    for (final id in currentIds.difference(newIds)) {
      _driverMarkers.remove(id);
    }

    for (final driver in drivers) {
      final id = driver.id ?? '';
      final latLng = LatLng(driver.latitude, driver.longitude);
      if (_driverMarkers.containsKey(id)) {
        final existing = _driverMarkers[id]!;
        existing.oldLatLng = existing.latLng;
        existing.targetLatLng = latLng;
        existing.oldRotation = existing.rotation;
        double newRotation = existing.rotation;
        if (existing.oldLatLng.latitude != latLng.latitude ||
            existing.oldLatLng.longitude != latLng.longitude) {
          newRotation = _getRotation(
            existing.oldLatLng.latitude,
            existing.oldLatLng.longitude,
            latLng.latitude,
            latLng.longitude,
          );
        } else if (driver.bearing != 0) {
          newRotation = driver.bearing;
        }
        existing.targetRotation = newRotation;
        if (driver.serviceName != null) existing.serviceName = driver.serviceName;
      } else {
        _driverMarkers[id] = NearbyDriverMarker(
          latLng: latLng,
          rotation: driver.bearing,
          serviceName: driver.serviceName,
        );
      }
    }

    _animateAllMarkers();
  }

  void _animateAllMarkers() {
    _animationController.stop();
    _animationController.reset();

    final animation = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.linear),
    );

    void listener() {
      final val = animation.value;
      bool needsRebuild = false;
      for (var data in _driverMarkers.values) {
        if (data.oldLatLng.latitude != data.targetLatLng.latitude ||
            data.oldLatLng.longitude != data.targetLatLng.longitude) {
          final lat = data.oldLatLng.latitude +
              (data.targetLatLng.latitude - data.oldLatLng.latitude) * val;
          final lng = data.oldLatLng.longitude +
              (data.targetLatLng.longitude - data.oldLatLng.longitude) * val;
          data.latLng = LatLng(lat, lng);
          needsRebuild = true;
        }
        if (data.oldRotation != data.targetRotation) {
          data.rotation = _interpolateRotation(data.oldRotation, data.targetRotation, val);
          needsRebuild = true;
        }
      }
      if (needsRebuild) update();
    }

    _animationController.removeListener(() {});
    _animationController.addListener(listener);

    _animationController.forward().whenComplete(() {
      for (var data in _driverMarkers.values) {
        data.latLng = data.targetLatLng;
        data.oldLatLng = data.targetLatLng;
        data.rotation = data.targetRotation;
        data.oldRotation = data.targetRotation;
      }
      update();
      _animationController.removeListener(listener);
    });
  }

  double _toRadians(double degree) => degree * pi / 180.0;

  double _interpolateRotation(double from, double to, double val) {
    double diff = to - from;
    while (diff < -180) {
      diff += 360;
    }
    while (diff > 180) {
      diff -= 360;
    }
    return (from + diff * val) % 360;
  }

  double _getRotation(double lat1, double lon1, double lat2, double lon2) {
    final phi1 = _toRadians(lat1);
    final phi2 = _toRadians(lat2);
    final deltaLambda = _toRadians(lon2 - lon1);
    final y = sin(deltaLambda) * cos(phi2);
    final x = cos(phi1) * sin(phi2) - sin(phi1) * cos(phi2) * cos(deltaLambda);
    final bearing = atan2(y, x);
    return (bearing * 180.0 / pi + 360.0) % 360.0;
  }

  Set<Marker> getDriverMarkers() {
    return _driverMarkers.entries.map((entry) {
      final id = entry.key;
      final data = entry.value;
      final isMoto = data.serviceName?.toLowerCase().contains('mototaxi') ?? false;
      final icon = isMoto ? motoIcon : carIcon;
      return Marker(
        markerId: MarkerId('nearby_driver_$id'),
        position: data.latLng,
        rotation: data.rotation,
        anchor: const Offset(0.5, 0.5),
        icon: icon == null
            ? BitmapDescriptor.defaultMarker
            : BitmapDescriptor.bytes(
                icon,
                width: 30,
                height: 45,
              ),
      );
    }).toSet();
  }

  void animateToCurrentLocation() {
    final pos = homeController.currentPosition;
    if (pos != null && pos.latitude != 0.0 && mapController != null) {
      mapController!.animateCamera(
        CameraUpdate.newCameraPosition(
          CameraPosition(
            target: LatLng(pos.latitude - 0.0035, pos.longitude),
            zoom: Environment.mapDefaultZoom,
          ),
        ),
      );
      _fetchNearbyDrivers(); // Immediate poll when locating
    }
  }

  // ── Route polyline & Markers ───────────────────────────────────────────
  Map<MarkerId, Marker> routeMarkers = {};

  Future<void> drawRouteTo(LatLng destination) async {
    final pos = homeController.currentPosition;
    if (pos == null || pos.latitude == 0.0) return;
    if (!isMapReady || mapController == null) return;

    isDrawingRoute = true;
    update();

    try {
      final origin = LatLng(pos.latitude, pos.longitude);
      
      // Clear previous route polylines and markers first
      homePolylines.clear();
      homePolylineCoords.clear();
      routeMarkers.clear();

      final polylinePoints = PolylinePoints();
      final result = await polylinePoints.getRouteBetweenCoordinates(
        request: PolylineRequest(
          origin: PointLatLng(origin.latitude, origin.longitude),
          destination: PointLatLng(destination.latitude, destination.longitude),
          mode: TravelMode.driving,
        ),
        googleApiKey: Environment.mapKey,
      );

      homePolylineCoords = result.points
          .map((p) => LatLng(p.latitude, p.longitude))
          .toList();

      if (homePolylineCoords.isNotEmpty) {
        const polylineId = PolylineId('home_route');
        homePolylines = {
          polylineId: Polyline(
            polylineId: polylineId,
            color: MyColor.getPrimaryColor(),
            points: homePolylineCoords,
            width: 5,
            startCap: Cap.roundCap,
            endCap: Cap.roundCap,
            jointType: JointType.round,
          ),
        };

        // Add markers for origin and destination
        routeMarkers = {
          const MarkerId('route_origin'): Marker(
            markerId: const MarkerId('route_origin'),
            position: origin,
            icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
            infoWindow: const InfoWindow(title: 'Mi ubicación'),
          ),
          const MarkerId('route_destination'): Marker(
            markerId: const MarkerId('route_destination'),
            position: destination,
            icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
            infoWindow: const InfoWindow(title: 'Destino'),
          ),
        };

        // Fit camera to show full route
        _fitRouteBounds(origin, destination, homePolylineCoords);
      }
    } catch (e) {
      printE('drawRouteTo error: $e');
    } finally {
      isDrawingRoute = false;
      update();
    }
  }

  void clearRoute() {
    homePolylines.clear();
    homePolylineCoords.clear();
    routeMarkers.clear();
    update();
  }

  void _fitRouteBounds(LatLng origin, LatLng destination, List<LatLng> points) {
    if (mapController == null) return;
    final allPoints = [origin, destination, ...points];
    double minLat = allPoints.first.latitude;
    double maxLat = allPoints.first.latitude;
    double minLng = allPoints.first.longitude;
    double maxLng = allPoints.first.longitude;

    for (final p in allPoints) {
      if (p.latitude < minLat) minLat = p.latitude;
      if (p.latitude > maxLat) maxLat = p.latitude;
      if (p.longitude < minLng) minLng = p.longitude;
      if (p.longitude > maxLng) maxLng = p.longitude;
    }

    final bounds = LatLngBounds(
      southwest: LatLng(minLat, minLng),
      northeast: LatLng(maxLat, maxLng),
    );

    mapController!.animateCamera(
      CameraUpdate.newLatLngBounds(bounds, 80),
    );
  }

  @override
  void onClose() {
    _pollTimer?.cancel();
    _animationController.dispose();
    PusherManager().removeListener(_onNearbyDriverEvent);
    super.onClose();
  }
}
