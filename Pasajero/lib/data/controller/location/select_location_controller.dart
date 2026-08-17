import 'package:flutter/material.dart';
import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/route/route.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/util.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/model/location/selected_location_info.dart';
import 'package:liztogo/environment.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';
import 'package:liztogo/presentation/packages/flutter_polyline_points/flutter_polyline_points.dart';
import 'package:liztogo/presentation/packages/polyline_animation/polyline_animation_v1.dart';

import '../../model/location/place_details.dart';
import '../../model/location/prediction.dart';
import '../../repo/location/location_search_repo.dart';
import '../home/home_controller.dart';

/// Controller responsible for handling location selection functionality
class SelectLocationController extends GetxController {
  // Dependencies
  final LocationSearchRepo locationSearchRepo;
  int selectedLocationIndex;

  SelectLocationController({
    required this.locationSearchRepo,
    required this.selectedLocationIndex,
  });

  // ── Mapa de controladores desechados ──────────────────────────────────
  // Rastrea si cada GoogleMapController ya fue dispuesto para evitar
  // llamar animateCamera sobre un widget desmontado.
  bool _mapControllerDisposed = false;
  bool _editMapControllerDisposed = false;

  void changeIndex(int i) {
    selectedLocationIndex = i;
    update();
  }

  // Location coordinates
  LatLng pickupLatlong = const LatLng(0, 0);
  LatLng destinationLatlong = const LatLng(0, 0);

  Position? currentPosition;
  final currentAddress = "".obs;
  double selectedLatitude = 0.0;
  double selectedLongitude = 0.0;

  bool isLoading = false;
  bool isLoadingFirstTime = false;

  final HomeController homeController = Get.find();
  final TextEditingController searchLocationController = TextEditingController();
  final TextEditingController valueOfLocation = TextEditingController();
  final TextEditingController destinationController = TextEditingController();
  final TextEditingController pickUpController = TextEditingController();
  final FocusNode searchFocus = FocusNode();

  final PolylineAnimator animator = PolylineAnimator();

  GoogleMapController? _mapController;
  GoogleMapController? _editMapController;

  // ── Setters públicos llamados desde el widget ─────────────────────────
  // Al asignar un nuevo controlador se resetea el flag de disposed.
  set mapController(GoogleMapController? c) {
    _mapControllerDisposed = false;
    _mapController = c;
  }

  set editMapController(GoogleMapController? c) {
    _editMapControllerDisposed = false;
    _editMapController = c;
  }

  // Getters para acceso de solo lectura externo si se necesita
  GoogleMapController? get mapController => _mapController;
  GoogleMapController? get editMapController => _editMapController;

  // ── Método seguro para llamar animateCamera ───────────────────────────
  /// Ejecuta [action] sobre [controller] solo si no fue dispuesto.
  /// Captura StateError por seguridad extra.
  Future<void> _safeAnimate(
    GoogleMapController? controller,
    bool disposed,
    Future<void> Function(GoogleMapController) action,
  ) async {
    if (controller == null || disposed) return;
    try {
      await action(controller);
    } on StateError catch (e) {
      // El widget ya fue desmontado; ignoramos silenciosamente.
      printX('safeAnimate: map already disposed — $e');
    } catch (e) {
      printX('safeAnimate: unexpected error — $e');
    }
  }

  // ── Notificar que un mapa fue desmontado ──────────────────────────────
  /// Llama esto desde el `onMapCreated` / dispose del widget si es necesario,
  /// o simplemente deja que el setter lo maneje al reasignar.
  void disposeMapController() {
    _mapControllerDisposed = true;
    _mapController = null;
  }

  void disposeEditMapController() {
    _editMapControllerDisposed = true;
    _editMapController = null;
  }

  Map<PolylineId, Polyline> polylines = {};
  List<LatLng> polylineCoordinates = [];

  bool isSearched = false;
  List<Prediction> allPredictions = [];
  String selectedAddressFromSearch = '';

  void clearTextFiled(int index) {
    if (index == 0) {
      pickUpController.text = '';
    } else {
      destinationController.text = '';
    }
  }

  void initialize() async {
    printD("homeController.selectedLocations.length ${homeController.selectedLocations.length}");

    if (homeController.selectedLocations.isNotEmpty) {
      final pickupInfo = homeController.getSelectedLocationInfoAtIndex(0);
      if (pickupInfo != null) {
        pickupLatlong = LatLng(
          pickupInfo.latitude ?? 0,
          pickupInfo.longitude ?? 0,
        );
        pickUpController.text = pickupInfo.getFullAddress(showFull: true);
      }
    }

    // Clear destination, always starts empty
    destinationController.clear();
    destinationLatlong = const LatLng(0, 0);

    // If pickup is empty, auto-populate with current location
    if (pickupLatlong.latitude == 0) {
      selectedLocationIndex = 0;
      await getCurrentPosition(
        isLoading1stTime: true,
        pickupLocationForIndex: 0,
      );
    } else {
      // Respect the selected location index (e.g. 1 if editing/selecting destination)
      await getCurrentPosition(
        isLoading1stTime: true,
        pickupLocationForIndex: selectedLocationIndex,
      );
    }

    update();
  }

  Future<void> _generateRoutePolyline() async {
    final points = await getPolylinePoints();
    polylineCoordinates = points;
    generatePolyLineFromPoints(points);
    fitPolylineInTopHalf(points);
  }

  Future<bool> handleLocationPermission() async {
    bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      await Geolocator.openLocationSettings();
      CustomSnackBar.error(errorList: [MyStrings.locationServiceDisableMsg]);
      return false;
    }

    printX("serviceEnabled $serviceEnabled");

    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        CustomSnackBar.error(errorList: [MyStrings.locationPermissionDenied]);
        return false;
      }
    }

    if (permission == LocationPermission.deniedForever) {
      await Geolocator.openAppSettings();
      CustomSnackBar.error(
        errorList: [MyStrings.locationPermissionPermanentDenied],
      );
      return false;
    }

    return true;
  }

  Future<void> getCurrentPosition({
    bool isLoading1stTime = false,
    int pickupLocationForIndex = -1,
    bool isFromEdit = false,
    bool forceFreshLocation = false,
  }) async {
    isLoadingFirstTime = isLoading1stTime;
    isLoading = true;
    update();

    final hasPermission = await handleLocationPermission();
    if (!hasPermission) {
      _endLoading();
      return;
    }

    final getSelectLocationData = homeController.getSelectedLocationInfoAtIndex(pickupLocationForIndex);
    final effectiveIndex = forceFreshLocation || getSelectLocationData == null ? -1 : pickupLocationForIndex;

    if (effectiveIndex == -1) {
      // 1. Try last known position first
      try {
        final lastKnown = await Geolocator.getLastKnownPosition();
        if (lastKnown != null) {
          currentPosition = lastKnown;
          update();
        }
      } catch (_) {}

      // 2. Try fresh position with timeout
      try {
        final fresh = await Geolocator.getCurrentPosition(
          locationSettings: AndroidSettings(accuracy: LocationAccuracy.high),
        ).timeout(const Duration(seconds: 5));
        currentPosition = fresh;
      } catch (e) {
        printX('SelectLocationController: getCurrentPosition timed out/failed. Fallback to default or lastKnown.');
        if (currentPosition == null) {
          currentPosition = MyUtils.getDefaultPosition();
        }
      }
    }

    if (currentPosition != null && (forceFreshLocation || getSelectLocationData == null)) {
      changeCurrentLatLongBasedOnCameraMove(
        currentPosition!.latitude,
        currentPosition!.longitude,
      );
      update();
      await animateMapCameraPosition(isFromEdit: isFromEdit);
      if (selectedLocationIndex == 0) {
        await pickLocation();
      }
    } else if (getSelectLocationData != null) {
      changeCurrentLatLongBasedOnCameraMove(
        getSelectLocationData.latitude!,
        getSelectLocationData.longitude!,
      );
      update();
      await animateMapCameraPosition(isFromEdit: isFromEdit);
    }

    _endLoading();
  }

  void _endLoading() {
    isLoading = false;
    isLoadingFirstTime = false;
    update();
  }

  LatLng getInitialTargetLocationForMap({int pickupLocationForIndex = -1}) {
    final getSelectLocationData = homeController.getSelectedLocationInfoAtIndex(pickupLocationForIndex);

    if (getSelectLocationData == null) {
      return currentPosition != null ? LatLng(currentPosition!.latitude, currentPosition!.longitude) : const LatLng(37.0902, 95.7129);
    } else {
      return LatLng(
        getSelectLocationData.latitude!,
        getSelectLocationData.longitude!,
      );
    }
  }

  Future<void> pickLocation() async {
    await openMap(selectedLatitude, selectedLongitude);
  }

  Future<void> openMap(double latitude, double longitude) async {
    try {
      String address = '';

      if (Environment.addressPickerFromGoogleMapApi) {
        address = await locationSearchRepo.getActualAddress(latitude, longitude) ?? '';
      } else {
        final placemarks = await placemarkFromCoordinates(latitude, longitude);
        if (placemarks.isNotEmpty) {
          address = _formatAddress(placemarks.first);
        }
      }

      currentAddress.value = address;
      update();

      final bool useSearchedAddress = selectedAddressFromSearch.isNotEmpty && Get.currentRoute != RouteHelper.editLocationPickUpScreen;
      final String displayAddress = useSearchedAddress ? selectedAddressFromSearch : currentAddress.value;

      if (selectedLocationIndex == 0) {
        pickUpController.text = displayAddress;
        pickupLatlong = LatLng(latitude, longitude);
      } else {
        destinationController.text = displayAddress;
        destinationLatlong = LatLng(latitude, longitude);
      }

      homeController.addLocationAtIndex(
        SelectedLocationInfo(
          latitude: latitude,
          longitude: longitude,
          fullAddress: displayAddress,
        ),
        selectedLocationIndex,
      );

      if (pickupLatlong.latitude != 0 && destinationLatlong.latitude != 0) {
        await _generateRoutePolyline();
      }
    } catch (e) {
      printX("Error getting address: ${e.toString()}");
      final isFromEdit = Get.currentRoute == RouteHelper.editLocationPickUpScreen;
      await animateMapCameraPosition(isFromEdit: isFromEdit);
    }
  }

  String _formatAddress(Placemark placemark) {
    final number = placemark.subThoroughfare ?? '';
    final streetName = placemark.street ?? '';
    final streetFull = (number.isNotEmpty && streetName.isNotEmpty && !streetName.contains(number)) ? '$streetName $number' : (streetName.isNotEmpty ? streetName : number);
    final subLocality = placemark.subLocality ?? '';
    final locality = placemark.locality ?? '';
    final country = placemark.country ?? '';
    return [streetFull, subLocality, locality, country].where((p) => p.isNotEmpty).join(', ');
  }

  void changeCurrentLatLongBasedOnCameraMove(double latitude, double longitude) {
    selectedLatitude = latitude;
    selectedLongitude = longitude;
    update();
  }

  // ── animateMapCameraPosition — ahora async y seguro ───────────────────
  Future<void> animateMapCameraPosition({bool isFromEdit = false}) async {
    final cameraUpdate = CameraUpdate.newCameraPosition(
      CameraPosition(
        target: LatLng(selectedLatitude, selectedLongitude),
        zoom: 18,
      ),
    );

    if (isFromEdit) {
      await _safeAnimate(
        _editMapController,
        _editMapControllerDisposed,
        (c) => c.animateCamera(cameraUpdate),
      );
    } else {
      await _safeAnimate(
        _mapController,
        _mapControllerDisposed,
        (c) => c.animateCamera(cameraUpdate),
      );
    }
  }

  void clearSearchField() {
    allPredictions = [];
    searchLocationController.clear();
    update();
  }

  void updateSelectedAddressFromSearch(String address) {
    selectedAddressFromSearch = address;
    update();
  }

  Future<void> searchYourAddress({
    required String locationName,
    void Function()? onSuccessCallback,
  }) async {
    if (locationName.isEmpty) {
      allPredictions.clear();
      update();
      return;
    }

    isSearched = true;
    update();

    try {
      Position? position;
      try {
        position = await MyUtils.getCurrentPosition();
      } catch (e) {
        printE('Location fetch failed: $e');
      }

      final response = await locationSearchRepo.searchAddressByLocationName(
        text: locationName,
        position: position,
      );

      if (response != null) {
        final json = response.responseJson;
        final predictions = PlacesAutocompleteResponse.fromJson(json).predictions ?? [];
        allPredictions = predictions;
        onSuccessCallback?.call();
      }
    } catch (e) {
      printX('Search error: $e');
    } finally {
      isSearched = false;
      update();
    }
  }

  Future<LatLng?> getLangAndLatFromMap(Prediction prediction) async {
    try {
      final ResponseModel response = await locationSearchRepo.getPlaceDetailsFromPlaceId(prediction);
      final placeDetails = PlaceDetails.fromJson(response.responseJson);

      if (placeDetails.result == null) return null;

      final lat = placeDetails.result!.geometry!.location!.lat ?? 0.0;
      final lng = placeDetails.result!.geometry!.location!.lng ?? 0.0;

      prediction.lat = lat.toString();
      prediction.lng = lng.toString();

      changeCurrentLatLongBasedOnCameraMove(lat, lng);

      // ✅ Seguro: si el mapa fue dispuesto entre tanto, no lanza error
      await _safeAnimate(
        _mapController,
        _mapControllerDisposed,
        (c) => c.animateCamera(
          CameraUpdate.newCameraPosition(
            CameraPosition(target: LatLng(lat, lng), zoom: 15),
          ),
        ),
      );

      allPredictions = [];
      update();

      return LatLng(lat, lng);
    } catch (e) {
      printX("Error getting place details: ${e.toString()}");
      return null;
    }
  }

  Future<List<LatLng>> getPolylinePoints() async {
    List<LatLng> points = [];

    try {
      final PolylinePoints polylinePoints = PolylinePoints();
      final PolylineResult result = await polylinePoints.getRouteBetweenCoordinates(
        request: PolylineRequest(
          origin: PointLatLng(pickupLatlong.latitude, pickupLatlong.longitude),
          destination: PointLatLng(
            destinationLatlong.latitude,
            destinationLatlong.longitude,
          ),
          mode: TravelMode.driving,
        ),
        googleApiKey: Environment.mapKey,
      );

      if (result.points.isNotEmpty) {
        for (var point in result.points) {
          points.add(LatLng(point.latitude, point.longitude));
        }
      } else {
        printX("Polyline error: ${result.errorMessage}");
      }
    } catch (e) {
      printX("Error getting polyline: ${e.toString()}");
    }

    return points;
  }

  void generatePolyLineFromPoints(List<LatLng> coordinates) {
    if (coordinates.isEmpty) return;

    isLoading = true;
    update();

    const PolylineId id = PolylineId("poly");
    final Polyline polyline = Polyline(
      polylineId: id,
      color: MyColor.getPrimaryColor(),
      points: coordinates,
      width: 5,
    );

    polylines[id] = polyline;
    isLoading = false;
    update();
  }

  void fitPolylineBounds(
    List<LatLng> coords, {
    double bottomSheetExtent = 0.4,
  }) {
    if (coords.isEmpty) return;
    final bounds = _createLatLngBounds(coords, bottomSheetExtent);
    // ✅ Seguro
    _safeAnimate(
      _mapController,
      _mapControllerDisposed,
      (c) => c.animateCamera(CameraUpdate.newLatLngBounds(bounds, 100)),
    );
  }

  LatLngBounds _createLatLngBounds(
    List<LatLng> coords,
    double bottomSheetExtent,
  ) {
    if (coords.isEmpty) {
      return LatLngBounds(
        southwest: const LatLng(0, 0),
        northeast: const LatLng(1, 1),
      );
    }

    double minLat = coords.first.latitude;
    double maxLat = coords.first.latitude;
    double minLng = coords.first.longitude;
    double maxLng = coords.first.longitude;

    for (var latLng in coords) {
      if (latLng.latitude < minLat) minLat = latLng.latitude;
      if (latLng.latitude > maxLat) maxLat = latLng.latitude;
      if (latLng.longitude < minLng) minLng = latLng.longitude;
      if (latLng.longitude > maxLng) maxLng = latLng.longitude;
    }

    final latPaddingRatio = bottomSheetExtent.clamp(0.0, 1.0);
    final latSpan = maxLat - minLat;
    final extraPadding = latSpan * latPaddingRatio;

    return LatLngBounds(
      southwest: LatLng(minLat + extraPadding, minLng),
      northeast: LatLng(maxLat, maxLng),
    );
  }

  void fitPolylineInTopHalf(List<LatLng> polylinePoints) {
    if (polylinePoints.isEmpty) return;
    final bounds = _calculateBounds(polylinePoints);
    // ✅ Seguro
    _safeAnimate(
      _mapController,
      _mapControllerDisposed,
      (c) => c.animateCamera(CameraUpdate.newLatLngBounds(bounds, 50.0)),
    );
  }

  LatLngBounds _calculateBounds(List<LatLng> points) {
    double x0 = points.first.latitude;
    double x1 = points.first.latitude;
    double y0 = points.first.longitude;
    double y1 = points.first.longitude;

    for (LatLng latLng in points) {
      if (latLng.latitude > x1) x1 = latLng.latitude;
      if (latLng.latitude < x0) x0 = latLng.latitude;
      if (latLng.longitude > y1) y1 = latLng.longitude;
      if (latLng.longitude < y0) y0 = latLng.longitude;
    }

    return LatLngBounds(
      southwest: LatLng(x0, y0),
      northeast: LatLng(x1, y1),
    );
  }

  @override
  void onClose() {
    // ── Marcar controladores como dispuestos ANTES de liberar recursos ───
    _mapControllerDisposed = true;
    _editMapControllerDisposed = true;

    searchLocationController.dispose();
    valueOfLocation.dispose();
    destinationController.dispose();
    pickUpController.dispose();
    searchFocus.dispose();

    // No llamar dispose() sobre los GoogleMapController aquí:
    // el widget GoogleMap los gestiona internamente. Solo los anulamos.
    _mapController = null;
    _editMapController = null;

    super.onClose();
  }
}
