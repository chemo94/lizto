import 'dart:convert';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:http/http.dart' as http;

class DirectionsService {
  static const _baseUrl = 'https://maps.googleapis.com/maps/api/directions/json';

  static Future<DirectionsResult?> getDirections({
    required double originLat,
    required double originLng,
    required double destLat,
    required double destLng,
    String? apiKey,
  }) async {
    if (apiKey == null || apiKey.isEmpty) return null;
    final url = Uri.parse('$_baseUrl?'
        'origin=$originLat,$originLng'
        '&destination=$destLat,$destLng'
        '&key=$apiKey'
        '&mode=driving');

    try {
      final response = await http.get(url);
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == 'OK') {
          final route = data['routes'][0];
          final leg = route['legs'][0];
          return DirectionsResult(
            polylinePoints: _decodePolyline(route['overview_polyline']['points']),
            distanceText: leg['distance']['text'],
            distanceMeters: leg['distance']['value'],
            durationText: leg['duration']['text'],
            durationSeconds: leg['duration']['value'],
          );
        }
      }
    } catch (_) {}
    return null;
  }

  static List<LatLng> _decodePolyline(String encoded) {
    List<LatLng> points = [];
    int index = 0, len = encoded.length;
    int lat = 0, lng = 0;
    while (index < len) {
      int shift = 0, result = 0;
      int b;
      do {
        b = encoded.codeUnitAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      int dlat = (result & 1) != 0 ? ~(result >> 1) : (result >> 1);
      lat += dlat;
      shift = 0; result = 0;
      do {
        b = encoded.codeUnitAt(index++) - 63;
        result |= (b & 0x1f) << shift;
        shift += 5;
      } while (b >= 0x20);
      int dlng = (result & 1) != 0 ? ~(result >> 1) : (result >> 1);
      lng += dlng;
      points.add(LatLng(lat / 1e5, lng / 1e5));
    }
    return points;
  }
}

class DirectionsResult {
  final List<LatLng> polylinePoints;
  final String distanceText;
  final int distanceMeters;
  final String durationText;
  final int durationSeconds;

  DirectionsResult({
    required this.polylinePoints,
    required this.distanceText,
    required this.distanceMeters,
    required this.durationText,
    required this.durationSeconds,
  });
}
