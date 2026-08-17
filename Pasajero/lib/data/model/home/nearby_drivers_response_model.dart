class NearbyDriversResponseModel {
  String? remark;
  String? status;
  List<String>? message;
  NearbyDriversData? data;

  NearbyDriversResponseModel({this.remark, this.status, this.message, this.data});

  factory NearbyDriversResponseModel.fromJson(Map<String, dynamic> json) {
    return NearbyDriversResponseModel(
      remark: json["remark"]?.toString(),
      status: json["status"]?.toString(),
      message: json["message"] != null ? List<String>.from(json["message"]) : null,
      data: json["data"] != null ? NearbyDriversData.fromJson(json["data"]) : null,
    );
  }
}

class NearbyDriversData {
  String? driverImagePath;
  List<NearbyDriver>? drivers;

  NearbyDriversData({this.driverImagePath, this.drivers});

  factory NearbyDriversData.fromJson(Map<String, dynamic> json) {
    return NearbyDriversData(
      driverImagePath: json["driver_image_path"]?.toString(),
      drivers: json["drivers"] != null
          ? List<NearbyDriver>.from(json["drivers"].map((x) => NearbyDriver.fromJson(x)))
          : null,
    );
  }
}

class NearbyDriver {
  String? id;
  String? firstname;
  String? lastname;
  double latitude;
  double longitude;
  double bearing;
  String? serviceName;
  double distanceKm;
  String? image;

  NearbyDriver({
    this.id,
    this.firstname,
    this.lastname,
    this.latitude = 0,
    this.longitude = 0,
    this.bearing = 0,
    this.serviceName,
    this.distanceKm = 0,
    this.image,
  });

  factory NearbyDriver.fromJson(Map<String, dynamic> json) {
    return NearbyDriver(
      id: json["id"]?.toString(),
      firstname: json["firstname"]?.toString(),
      lastname: json["lastname"]?.toString(),
      latitude: double.tryParse(json["latitude"]?.toString() ?? '0') ?? 0,
      longitude: double.tryParse(json["longitude"]?.toString() ?? '0') ?? 0,
      bearing: double.tryParse(json["bearing"]?.toString() ?? '0') ?? 0,
      serviceName: json["service_name"]?.toString(),
      distanceKm: double.tryParse(json["distance_km"]?.toString() ?? '0') ?? 0,
      image: json["image"]?.toString(),
    );
  }
}
