import 'dart:convert';

import 'package:liztogo_repartidor/data/model/global/user/global_driver_model.dart';

DashBoardRideResponseModel newRideResponseModelFromJson(String str) => DashBoardRideResponseModel.fromJson(json.decode(str));

class DashBoardRideResponseModel {
  String? remark;
  String? status;
  List<String>? message;
  Data? data;

  DashBoardRideResponseModel({
    this.remark,
    this.status,
    this.message,
    this.data,
  });

  factory DashBoardRideResponseModel.fromJson(Map<String, dynamic> json) => DashBoardRideResponseModel(
        remark: json["remark"],
        status: json["status"],
        message: json["message"] == null ? [] : List<String>.from(json["message"]!.map((x) => x)),
        data: json["data"] == null ? null : Data.fromJson(json["data"]),
      );
}

class Data {
  GlobalDriverInfoModel? driverInfo;
  String? driverImagePath;
  String? userImagePath;

  Data({
    this.driverInfo,
    this.driverImagePath,
    this.userImagePath,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        driverInfo: json["driver"] == null ? null : GlobalDriverInfoModel.fromJson(json["driver"]),
        driverImagePath: json["driver_image_path"].toString(),
        userImagePath: json["user_image_path"].toString(),
      );

  Map<String, dynamic> toJson() => {
        "driver_info": driverInfo?.toJson(),
      };
}
