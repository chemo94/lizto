class UserAddressModel {
  String? id;
  String? label; // Casa, Trabajo, Otro
  String? address;
  double? latitude;
  double? longitude;
  bool isDefault;

  UserAddressModel({
    this.id, this.label, this.address, this.latitude, this.longitude,
    this.isDefault = false,
  });

  factory UserAddressModel.fromJson(Map<String, dynamic> json) => UserAddressModel(
        id: json["id"]?.toString(),
        label: json["label"]?.toString(),
        address: json["address"]?.toString(),
        latitude: (json["latitude"] is num) ? (json["latitude"] as num).toDouble() : double.tryParse(json["latitude"]?.toString() ?? ''),
        longitude: (json["longitude"] is num) ? (json["longitude"] as num).toDouble() : double.tryParse(json["longitude"]?.toString() ?? ''),
        isDefault: json["is_default"] == 1 || json["is_default"] == true,
      );

  Map<String, dynamic> toJson() => {
        if (id != null) 'id': id,
        'label': label, 'address': address,
        'latitude': latitude, 'longitude': longitude,
        'is_default': isDefault ? 1 : 0,
      };
}
