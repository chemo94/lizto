class PlacesAutocompleteResponse {
  final List<Prediction>? predictions;
  PlacesAutocompleteResponse({this.predictions});
  factory PlacesAutocompleteResponse.fromJson(Map<String, dynamic> json) => PlacesAutocompleteResponse(
    predictions: json['predictions'] != null ? (json['predictions'] as List).map((x) => Prediction.fromJson(x)).toList() : null,
  );
}

class Prediction {
  final String? description;
  final String? placeId;
  Prediction({this.description, this.placeId});
  factory Prediction.fromJson(Map<String, dynamic> json) => Prediction(
    description: json['description']?.toString(),
    placeId: json['place_id']?.toString(),
  );
}
