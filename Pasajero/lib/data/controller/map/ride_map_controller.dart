import 'dart:math';
import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:liztogo/core/utils/app_status.dart';
import 'package:liztogo/core/utils/my_icons.dart';
import 'package:liztogo/data/controller/ride/ride_details/ride_details_controller.dart';
import 'package:liztogo/presentation/packages/flutter_polyline_points/flutter_polyline_points.dart';
import 'package:geocoding/geocoding.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/helper.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_images.dart';
import 'package:liztogo/environment.dart';
import 'package:liztogo/presentation/packages/polyline_animation/polyline_animation_v1.dart';

class DriverMarkerData {
  LatLng latLng;
  LatLng targetLatLng;
  LatLng oldLatLng;
  double rotation;
  double targetRotation;
  double oldRotation;
  String? serviceName;
  DriverMarkerData({required this.latLng, this.rotation = 0, this.serviceName, LatLng? targetLatLng})
      : targetLatLng = targetLatLng ?? latLng,
        oldLatLng = latLng,
        targetRotation = rotation,
        oldRotation = rotation;
}

class RideMapController extends GetxController with GetSingleTickerProviderStateMixin {
  bool isLoading = false;
  final PolylineAnimator animator = PolylineAnimator();

  LatLng pickupLatLng = const LatLng(0, 0);
  LatLng destinationLatLng = const LatLng(0, 0);

  LatLng? _previousDriverLatLng;
  LatLng? driverLatLng;

  /// rotation for driver marker in degrees
  double driverRotation = 0.0;

  Map<PolylineId, Polyline> polylines = {};

  // Map controller used by UI to set controller reference
  GoogleMapController? mapController;

  // Animation controller for interpolating marker movement
  late final AnimationController _animationController;

  Map<String, DriverMarkerData> drivers = {};

  @override
  void onInit() {
    super.onInit();
    _animationController = AnimationController(vsync: this, duration: const Duration(seconds: 2));
  }

  @override
  void onClose() {
    _animationController.dispose();
    super.onClose();
  }

  /// Public method to receive driver location updates
  void updateDriverLocation({
    required String driverId,
    required LatLng latLng,
    required bool isRunning,
    double? bearing,
    String? serviceName,
  }) {
    printX('ride map update $driverId, $latLng, $isRunning');

    if (drivers.containsKey(driverId)) {
      final oldPos = drivers[driverId]!.latLng;
      drivers[driverId]!.oldLatLng = oldPos;
      drivers[driverId]!.targetLatLng = latLng;
      drivers[driverId]!.oldRotation = drivers[driverId]!.rotation;
      double newRotation = drivers[driverId]!.rotation;
      if (oldPos.latitude != latLng.latitude || oldPos.longitude != latLng.longitude) {
        newRotation = _getRotation(
          oldPos.latitude,
          oldPos.longitude,
          latLng.latitude,
          latLng.longitude,
        );
      } else if (bearing != null && bearing != 0) {
        newRotation = bearing;
      }
      drivers[driverId]!.targetRotation = newRotation;
      if (serviceName != null) {
        drivers[driverId]!.serviceName = serviceName;
      }
    } else {
      drivers[driverId] = DriverMarkerData(
        latLng: latLng,
        rotation: bearing ?? 0,
        serviceName: serviceName,
      );
    }

    String? acceptedDriverId;
    if (Get.isRegistered<RideDetailsController>()) {
      acceptedDriverId = Get.find<RideDetailsController>().ride.driverId;
    }

    bool isAcceptedDriver = driverId == acceptedDriverId && acceptedDriverId != null && acceptedDriverId != 'null' && acceptedDriverId != '-1';

    if (isAcceptedDriver) {
      if (driverLatLng == null) {
        _previousDriverLatLng = latLng;
        driverLatLng = latLng;
        getCurrentDriverAddress();
        _updateRouteIfNeeded();
        update();
      } else {
        _previousDriverLatLng = driverLatLng;
        driverLatLng = latLng;
        _animateAllMarkers();
        getCurrentDriverAddress();
        _updateRouteIfNeeded();
      }
    } else {
      _animateAllMarkers();
    }
  }

  void _animateAllMarkers() {
    _animationController.stop();
    _animationController.reset();

    for (var data in drivers.values) {
       data.oldLatLng = data.latLng;
       data.oldRotation = data.rotation;
    }

    final animation = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.linear),
    );

    void listener() {
      final val = animation.value;
      bool needsRebuild = false;

      for (var data in drivers.values) {
        if (data.oldLatLng.latitude != data.targetLatLng.latitude || data.oldLatLng.longitude != data.targetLatLng.longitude) {
           final lat = data.oldLatLng.latitude + (data.targetLatLng.latitude - data.oldLatLng.latitude) * val;
           final lng = data.oldLatLng.longitude + (data.targetLatLng.longitude - data.oldLatLng.longitude) * val;
           data.latLng = LatLng(lat, lng);
           needsRebuild = true;
        }
        if (data.oldRotation != data.targetRotation) {
           data.rotation = _interpolateRotation(data.oldRotation, data.targetRotation, val);
           needsRebuild = true;
        }
      }

      // Also animate accepted driver
      if (_previousDriverLatLng != null && driverLatLng != null && driverLatLng != _previousDriverLatLng) {
          final lat = _previousDriverLatLng!.latitude + (driverLatLng!.latitude - _previousDriverLatLng!.latitude) * val;
          final lng = _previousDriverLatLng!.longitude + (driverLatLng!.longitude - _previousDriverLatLng!.longitude) * val;
          
          driverRotation = _getRotation(
            _previousDriverLatLng!.latitude,
            _previousDriverLatLng!.longitude,
            lat,
            lng,
          );
          
          needsRebuild = true;
      }

      if (needsRebuild) update();
    }

    _animationController.removeListener(() {});
    _animationController.addListener(listener);

    _animationController.forward().whenComplete(() {
      for (var data in drivers.values) {
         data.latLng = data.targetLatLng;
         data.oldLatLng = data.targetLatLng;
         data.rotation = data.targetRotation;
         data.oldRotation = data.targetRotation;
      }
      if (_previousDriverLatLng != null && driverLatLng != null) {
          driverRotation = _getRotation(
            _previousDriverLatLng!.latitude,
            _previousDriverLatLng!.longitude,
            driverLatLng!.latitude,
            driverLatLng!.longitude,
          );
          _previousDriverLatLng = driverLatLng;
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

  /// Calculates bearing (degrees) from (lat1, lon1) to (lat2, lon2)
  double _getRotation(double lat1, double lon1, double lat2, double lon2) {
    // convert to radians
    final phi1 = _toRadians(lat1);
    final phi2 = _toRadians(lat2);
    final deltaLambda = _toRadians(lon2 - lon1);

    final y = sin(deltaLambda) * cos(phi2);
    final x = cos(phi1) * sin(phi2) - sin(phi1) * cos(phi2) * cos(deltaLambda);
    final bearing = atan2(y, x);
    var bearingDegrees = (bearing * 180.0 / pi + 360.0) % 360.0; // normalize 0-360

    return bearingDegrees;
  }

  void loadMap({
    required LatLng pickup,
    required LatLng destination,
    bool? isRunning = false,
    String? serviceName,
  }) async {
    pickupLatLng = pickup;
    destinationLatLng = destination;
    update();

    getPolyLinePoints().then((data) {
      polylineCoordinates = data;
      generatePolyLineFromPoints(data);
      fitPolylineBounds(data);
      if (Get.isRegistered<RideDetailsController>()) {
        if (![AppStatus.RIDE_RUNNING, AppStatus.RIDE_ACTIVE, AppStatus.RIDE_COMPLETED].contains(Get.find<RideDetailsController>().ride.status)) {
          // animator.animatePolyline(
          //   data,
          //   'polyline_id',
          //   MyColor.colorOrange,
          //   MyColor.primaryColor,
          //   polylines,
          //   () {
          //     if (Get.isRegistered<RideDetailsController>()) {
          //       if (![AppStatus.RIDE_RUNNING, AppStatus.RIDE_ACTIVE, AppStatus.RIDE_COMPLETED].contains(Get.find<RideDetailsController>().ride.status)) {
          //         update();
          //       }
          //     }
          //   },
          // );
        }
      }
    });

    await setCustomMarkerIcon(serviceName: serviceName);
  }

  void animateMapCameraPosition() {
    mapController?.animateCamera(
      CameraUpdate.newCameraPosition(
        CameraPosition(
          target: LatLng(pickupLatLng.latitude, pickupLatLng.longitude),
          zoom: Environment.mapDefaultZoom,
        ),
      ),
    );
  }

  void generatePolyLineFromPoints(List<LatLng> polylineCoordinates) async {
    isLoading = true;
    update();
    PolylineId id = const PolylineId("poly");
    Polyline polyline = Polyline(
      polylineId: id,
      color: MyColor.getPrimaryColor(),
      points: polylineCoordinates,
      width: 5,
    );
    polylines[id] = polyline;
    isLoading = false;
    update();
  }

  void _updateRouteIfNeeded() {
    if (Get.isRegistered<RideDetailsController>()) {
      final ride = Get.find<RideDetailsController>().ride;
      if (ride.status == AppStatus.RIDE_RUNNING || ride.status == AppStatus.RIDE_ACTIVE) {
        getPolyLinePoints().then((data) {
          if (data.isNotEmpty) {
            polylineCoordinates = data;
            generatePolyLineFromPoints(data);
          }
        });
      }
    }
  }

  List<LatLng> polylineCoordinates = [];
  Future<List<LatLng>> getPolyLinePoints() async {
    List<LatLng> polylineCoordinates = [];
    PolylinePoints polylinePoints = PolylinePoints();

    LatLng origin = pickupLatLng;
    LatLng dest = destinationLatLng;

    if (Get.isRegistered<RideDetailsController>()) {
      final ride = Get.find<RideDetailsController>().ride;
      if (ride.status == AppStatus.RIDE_ACTIVE && driverLatLng != null) {
        origin = driverLatLng!;
        dest = pickupLatLng;
      } else if (ride.status == AppStatus.RIDE_RUNNING && driverLatLng != null) {
        origin = driverLatLng!;
        dest = destinationLatLng;
      }
    }

    PolylineResult result = await polylinePoints.getRouteBetweenCoordinates(
      request: PolylineRequest(
        origin: PointLatLng(origin.latitude, origin.longitude),
        destination: PointLatLng(
          dest.latitude,
          dest.longitude,
        ),
        mode: TravelMode.driving,
      ),
      googleApiKey: Environment.mapKey,
    );
    if (result.points.isNotEmpty) {
      for (var point in result.points) {
        polylineCoordinates.add(LatLng(point.latitude, point.longitude));
      }
    } else {
      printX(result.errorMessage);
    }
    return polylineCoordinates;
  }

  // icons
  Uint8List? pickupIcon;
  Uint8List? destinationIcon;
  Uint8List? driverIcon;
  Uint8List? carIcon;
  Uint8List? motoIcon;

  Set<Marker> getMarkers({
    required LatLng pickup,
    required LatLng destination,
    LatLng? maybeDriverLatLng,
  }) {
    final markers = <Marker>{};

    String? acceptedDriverId;
    String? rideStatus;

    if (Get.isRegistered<RideDetailsController>()) {
      final ride = Get.find<RideDetailsController>().ride;
      acceptedDriverId = ride.driverId;
      rideStatus = ride.status;
    }

    bool isAccepted = rideStatus != null && rideStatus != AppStatus.RIDE_PENDING && acceptedDriverId != null && acceptedDriverId != 'null' && acceptedDriverId != '-1';

    drivers.forEach((id, data) {
      // If a driver is accepted, only show that driver. 
      // If ride is pending, show all nearby drivers.
      if (isAccepted && id != acceptedDriverId) {
        return;
      }

      bool isMoto = data.serviceName?.toLowerCase().contains('mototaxi') ?? false;
      Uint8List? icon = isMoto ? motoIcon : carIcon;

      // Use interpolated position/rotation for the accepted driver
      double rotation = (id == acceptedDriverId) ? driverRotation : data.rotation;
      LatLng position = (id == acceptedDriverId && driverLatLng != null) ? driverLatLng! : data.latLng;

      markers.add(
        Marker(
          markerId: MarkerId('driver_$id'),
          position: position,
          rotation: rotation,
          anchor: const Offset(0.5, 0.5),
          icon: icon == null
              ? BitmapDescriptor.defaultMarker
              : BitmapDescriptor.bytes(
                  icon,
                  width: 30,
                  height: 45,
                  bitmapScaling: MapBitmapScaling.auto,
                ),
          infoWindow: InfoWindow(title: id == acceptedDriverId ? driverAddress : data.serviceName ?? 'Driver', onTap: () {}),
          onTap: () async {
            if (id == acceptedDriverId) {
              getCurrentDriverAddress();
            }
            printX('Driver $id current position ${data.latLng}');
          },
        ),
      );
    });

    // Fallback for single driver logic if drivers map is empty
    if (markers.isEmpty) {
      final mkDriverLatLng = maybeDriverLatLng ?? driverLatLng;
      if (mkDriverLatLng != null) {
        String? activeServiceName;
        if (Get.isRegistered<RideDetailsController>()) {
          activeServiceName = Get.find<RideDetailsController>().ride.service?.name;
        }
        bool isMoto = activeServiceName?.toLowerCase().contains('mototaxi') ?? false;
        Uint8List? icon = isMoto ? motoIcon : carIcon;
        icon ??= driverIcon;

        markers.add(
          Marker(
            markerId: const MarkerId('driver_marker_id'),
            position: mkDriverLatLng,
            rotation: driverRotation,
            anchor: const Offset(0.5, 0.5),
            icon: icon == null
                ? BitmapDescriptor.defaultMarker
                : BitmapDescriptor.bytes(
                    icon,
                    width: 30,
                    height: 45,
                    bitmapScaling: MapBitmapScaling.auto,
                  ),
            infoWindow: InfoWindow(title: driverAddress, onTap: () {}),
            onTap: () async {
              getCurrentDriverAddress();
              printX('Driver current position $mkDriverLatLng');
              printX('Driver current address $driverAddress');
            },
          ),
        );
      }
    }

    // pickup
    markers.add(
      Marker(
        markerId: const MarkerId('pickup_marker_id'),
        position: LatLng(pickup.latitude, pickup.longitude),
        icon: pickupIcon == null
            ? BitmapDescriptor.defaultMarker
            : BitmapDescriptor.bytes(
                pickupIcon!,
                height: 45,
                width: 45,
                bitmapScaling: MapBitmapScaling.auto,
              ),
        onTap: () async {
          mapController?.animateCamera(
            CameraUpdate.newCameraPosition(
              CameraPosition(
                target: LatLng(pickupLatLng.latitude, pickupLatLng.longitude),
                zoom: Environment.mapDefaultZoom,
              ),
            ),
          );
        },
      ),
    );

    // destination
    markers.add(
      Marker(
        markerId: const MarkerId('destination_marker_id'),
        position: LatLng(destination.latitude, destination.longitude),
        icon: destinationIcon == null
            ? BitmapDescriptor.defaultMarker
            : BitmapDescriptor.bytes(
                destinationIcon!,
                height: 45,
                width: 45,
                bitmapScaling: MapBitmapScaling.auto,
              ),
        onTap: () async {
          mapController?.animateCamera(
            CameraUpdate.newCameraPosition(
              CameraPosition(
                target: LatLng(destination.latitude, destination.longitude),
                zoom: Environment.mapDefaultZoom,
              ),
            ),
          );
        },
      ),
    );

    return markers;
  }

  Future<void> setCustomMarkerIcon({String? serviceName}) async {
    pickupIcon = await Helper.getBytesFromAsset(MyIcons.mapMarkerPickUpIcon, 150);
    destinationIcon = await Helper.getBytesFromAsset(MyIcons.mapMarkerIcon, 150);
    
    carIcon = await Helper.getBytesFromAsset(MyImages.mapDriverMarker, 80);
    motoIcon = await Helper.getBytesFromAsset(MyImages.mapDriverMarkerMt, 80);

    String driverMarker = MyImages.mapDriverMarker;
    if (serviceName?.toLowerCase().contains('mototaxi') ?? false) {
      driverMarker = MyImages.mapDriverMarkerMt;
    }
    driverIcon = await Helper.getBytesFromAsset(driverMarker, 80);
    update();
  }

  String driverAddress = 'Loading...';

  Future<void> getCurrentDriverAddress() async {
    if (driverLatLng == null) return;
    try {
      final List<Placemark> placeMark = await placemarkFromCoordinates(
        driverLatLng!.latitude,
        driverLatLng!.longitude,
      );
      driverAddress = "";
      driverAddress = "${placeMark[0].street} ${placeMark[0].subThoroughfare} ${placeMark[0].thoroughfare},${placeMark[0].subLocality},${placeMark[0].locality},${placeMark[0].country}";
      update();
      printX('appLocations position $driverAddress');
    } catch (e) {
      printX('Error in getting position: $e');
    }
  }

  void fitPolylineBounds(List<LatLng> coords) {
    if (coords.isEmpty) return;

    setMapFitToTour(Set<Polyline>.of(polylines.values));
  }

  void setMapFitToTour(Set<Polyline> p) {
    if (p.isEmpty) return;

    double minLat = p.first.points.first.latitude;
    double minLong = p.first.points.first.longitude;
    double maxLat = p.first.points.first.latitude;
    double maxLong = p.first.points.first.longitude;
    for (var poly in p) {
      for (var point in poly.points) {
        if (point.latitude < minLat) minLat = point.latitude;
        if (point.latitude > maxLat) maxLat = point.latitude;
        if (point.longitude < minLong) minLong = point.longitude;
        if (point.longitude > maxLong) maxLong = point.longitude;
      }
    }
    mapController?.animateCamera(
      CameraUpdate.newLatLngBounds(
        LatLngBounds(southwest: LatLng(minLat, minLong), northeast: LatLng(maxLat, maxLong)),
        30,
      ),
    );
  }
}
