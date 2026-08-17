import 'package:geolocator/geolocator.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/core/utils/method.dart';
import 'package:lizto_store/data/services/api_client.dart';
import 'package:lizto_store/environment.dart';

class LocationSearchRepo {
  final ApiClient apiClient;
  LocationSearchRepo({required this.apiClient});

  Future<String?> getActualAddress(double lat, double lng) async {
    String url = '${UrlContainer.googleMapLocationSearch}/geocode/json?latlng=$lat,$lng&key=${Environment.mapKey}';
    var response = await apiClient.request(url, Method.getMethod, null);
    if (response.statusCode == 200 && response.responseJson['status'] == 'OK') {
      return response.responseJson['results'][0]['formatted_address'];
    }
    return '$lat, $lng';
  }

  Future<String?> detectCountryCode(Position position) async {
    return 'pe';
  }

  Future<dynamic> searchAddressByLocationName({String? text, Position? position}) async {
    String query = text ?? '';
    String url = '${UrlContainer.googleMapLocationSearch}/place/autocomplete/json?input=$query&key=${Environment.mapKey}&language=es&components=country:pe';
    return await apiClient.request(url, Method.getMethod, null);
  }

  Future<dynamic> getPlaceDetailsFromPlaceId(dynamic prediction) async {
    String placeId = prediction is String ? prediction : prediction.placeId ?? '';
    String url = '${UrlContainer.googleMapLocationSearch}/place/details/json?place_id=$placeId&key=${Environment.mapKey}&fields=geometry,formatted_address';
    return await apiClient.request(url, Method.getMethod, null);
  }
}
