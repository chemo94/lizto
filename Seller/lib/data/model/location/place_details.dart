class PlaceDetails {
  final PlaceResult? result;
  PlaceDetails({this.result});
  factory PlaceDetails.fromJson(Map<String, dynamic> json) => PlaceDetails(
    result: json['result'] != null ? PlaceResult.fromJson(json['result']) : null,
  );
}

class PlaceResult {
  final PlaceGeometry? geometry;
  final String? formattedAddress;
  PlaceResult({this.geometry, this.formattedAddress});
  factory PlaceResult.fromJson(Map<String, dynamic> json) => PlaceResult(
    geometry: json['geometry'] != null ? PlaceGeometry.fromJson(json['geometry']) : null,
    formattedAddress: json['formatted_address']?.toString(),
  );
}

class PlaceGeometry {
  final PlaceLocation? location;
  PlaceGeometry({this.location});
  factory PlaceGeometry.fromJson(Map<String, dynamic> json) => PlaceGeometry(
    location: json['location'] != null ? PlaceLocation.fromJson(json['location']) : null,
  );
}

class PlaceLocation {
  final double? lat;
  final double? lng;
  PlaceLocation({this.lat, this.lng});
  factory PlaceLocation.fromJson(Map<String, dynamic> json) => PlaceLocation(
    lat: (json['lat'] as num?)?.toDouble(),
    lng: (json['lng'] as num?)?.toDouble(),
  );
}
