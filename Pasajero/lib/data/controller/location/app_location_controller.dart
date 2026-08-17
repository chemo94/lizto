import 'package:geocoding/geocoding.dart';
import 'package:geolocator/geolocator.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/util.dart';
import 'package:liztogo/data/repo/location/location_search_repo.dart';
import 'package:liztogo/environment.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';

class AppLocationController extends GetxController {
  LocationSearchRepo locationSearchRepo = LocationSearchRepo(apiClient: Get.find());
  Position currentPosition = MyUtils.getDefaultPosition();
  String currentAddress = "${MyStrings.loading.tr}...";
  Position? position;
  Future<Position?> getCurrentPosition() async {
    try {
      final geolocator = GeolocatorPlatform.instance;

      // 1. Try to get last known position first for instant response
      Position? lastKnown;
      try {
        lastKnown = await geolocator.getLastKnownPosition();
      } catch (_) {}

      if (lastKnown != null) {
        position = lastKnown;
        currentPosition = lastKnown;
        update();
      }

      // 2. Fetch fresh position with a safety timeout of 6 seconds
      Position freshPosition;
      try {
        freshPosition = await geolocator.getCurrentPosition(
          locationSettings: const LocationSettings(
            accuracy: LocationAccuracy.high,
          ),
        ).timeout(const Duration(seconds: 6));
      } catch (_) {
        if (lastKnown != null) {
          freshPosition = lastKnown;
        } else {
          rethrow;
        }
      }

      position = freshPosition;

      if (Environment.addressPickerFromGoogleMapApi) {
        currentAddress = await locationSearchRepo.getActualAddress(freshPosition.latitude, freshPosition.longitude) ?? 'Unknown location..';
      } else {
        // Use local reverse geocoding
        final placemarks = await placemarkFromCoordinates(freshPosition.latitude, freshPosition.longitude);
        if (placemarks.isNotEmpty) {
          currentAddress = _formatAddress(placemarks.first);
        } else {
          currentAddress = 'Unknown location..';
        }
      }

      currentPosition = freshPosition;
      update();

      printX('appLocations position: $currentAddress');
      return freshPosition;
    } catch (e) {
      // Avoid spamming error dialog if we already have a location
      if (currentPosition.latitude == 0.0) {
        CustomSnackBar.error(errorList: [MyStrings.locationPermissionNeedMSG.tr]);
      }
    }

    return null;
  }

  /// Format address from placemark components
  String _formatAddress(Placemark placemark) {
    // Safely format address components, checking for nulls
    final number = placemark.subThoroughfare ?? '';
    final streetName = placemark.street ?? '';
    final streetFull = (number.isNotEmpty && streetName.isNotEmpty && !streetName.contains(number)) ? '$streetName $number' : (streetName.isNotEmpty ? streetName : number);
    final subLocality = placemark.subLocality ?? '';
    final locality = placemark.locality ?? '';
    // final subAdministrativeArea = placemark.subAdministrativeArea ?? '';
    // final administrativeArea = placemark.administrativeArea ?? '';
    final country = placemark.country ?? '';

    // return [streetFull, subLocality, locality, subAdministrativeArea, administrativeArea, country].where((part) => part.isNotEmpty).join(', ');
    return [streetFull, subLocality, locality, country].where((part) => part.isNotEmpty).join(', ');
  }
}
