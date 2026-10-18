class AppService {
  String? id;
  String? name;
  String? code;
  String? image;
  String? imageWithPath;
  String? vehicleType;
  String? minVehicleCapacity;

  AppService({this.id, this.name, this.code, this.image, this.imageWithPath, this.vehicleType, this.minVehicleCapacity});

  factory AppService.fromJson(Map<String, dynamic> json) => AppService(
        id: json["id"].toString(),
        name: json["name"],
        code: json["code"],
        image: json["image"],
        imageWithPath: json["image_with_path"],
        vehicleType: json["vehicle_type"]?.toString(),
        minVehicleCapacity: json["min_vehicle_capacity"]?.toString(),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "code": code,
        "image": image,
        "image_with_path": imageWithPath,
        "vehicle_type": vehicleType,
        "min_vehicle_capacity": minVehicleCapacity,
      };
}
