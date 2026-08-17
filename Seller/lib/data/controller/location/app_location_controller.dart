import 'package:geolocator/geolocator.dart';
import 'package:get/get.dart';
import 'package:lizto_store/data/repo/location/location_search_repo.dart';

class AppLocationController extends GetxController {
  LocationSearchRepo locationSearchRepo = LocationSearchRepo(apiClient: Get.find());
  Position currentPosition = Position(
    latitude: -12.0464, longitude: -77.0428,
    timestamp: DateTime.now(), accuracy: 0, altitude: 0,
    altitudeAccuracy: 0, heading: 0, headingAccuracy: 0,
    speed: 0, speedAccuracy: 0,
  );
  String currentAddress = 'Cargando...';
}
