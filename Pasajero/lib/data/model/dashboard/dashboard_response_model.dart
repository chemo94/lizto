// To parse this JSON data, do
//
//     final dashBoardResponseModel = dashBoardResponseModelFromJson(jsonString);

import 'dart:convert';

import 'package:liztogo/data/model/global/app/app_payment_method.dart';
import 'package:liztogo/data/model/global/app/app_service_model.dart';
import 'package:liztogo/data/model/global/app/ride_model.dart';
import 'package:liztogo/data/model/global/user/global_user_model.dart';
import 'package:liztogo/data/model/home/banner_model.dart';
import 'package:liztogo/data/model/location/selected_location_info.dart';

DashBoardResponseModel dashBoardResponseModelFromJson(String str) => DashBoardResponseModel.fromJson(json.decode(str));

String dashBoardResponseModelToJson(DashBoardResponseModel data) => json.encode(data.toJson());

class DashBoardResponseModel {
  String? remark;
  String? status;
  Data? data;
  List<String>? message;

  DashBoardResponseModel({this.remark, this.status, this.data, this.message});

  factory DashBoardResponseModel.fromJson(Map<String, dynamic> json) => DashBoardResponseModel(
        remark: json["remark"],
        status: json["status"],
        message: json["message"] == null ? [] : List<String>.from(json["message"]!.map((x) => x.toString())),
        data: json["data"] == null ? null : Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "remark": remark,
        "status": status,
        "data": data?.toJson(),
        "message": message,
      };
}

class Data {
  List<AppPaymentMethod>? paymentMethod;
  List<AppService>? services;
  GlobalUser? userInfo;
  RideModel? runningRide;
  String? serviceImagePath;
  String? gatewayImagePath;
  String? userImagePath;
  List<BannerModel>? banners;
  List<BannerModel>? deliveryBanners;
  String? bannerImagePath;
  DashBoardCoupon? coupon;
  DashBoardCoupon? taxiCoupon;
  DashBoardCoupon? deliveryCoupon;
  List<SelectedLocationInfo>? recentDestinations;

  Data({
    this.paymentMethod,
    this.services,
    this.userInfo,
    this.runningRide,
    this.serviceImagePath,
    this.gatewayImagePath,
    this.userImagePath,
    this.banners,
    this.deliveryBanners,
    this.bannerImagePath,
    this.coupon,
    this.taxiCoupon,
    this.deliveryCoupon,
    this.recentDestinations,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        paymentMethod: json["payment_method"] == null
            ? []
            : List<AppPaymentMethod>.from(
                json["payment_method"]!.map((x) => AppPaymentMethod.fromJson(x)),
              ),
        services: json["services"] == null
            ? []
            : List<AppService>.from(
                json["services"]!.map((x) => AppService.fromJson(x)),
              ),
        userInfo: json["user"] == null ? null : GlobalUser.fromJson(json["user"]),
        runningRide: json["running_ride"] == null ? null : RideModel.fromJson(json["running_ride"]),
        gatewayImagePath: json["gateway_image_path"].toString(),
        serviceImagePath: json["service_image_path"].toString(),
        userImagePath: json["user_image_path"].toString(),
        banners: json["banners"] == null ? null : List<BannerModel>.from(json["banners"].map((x) => BannerModel.fromJson(x))),
        deliveryBanners: json["delivery_banners"] == null ? null : List<BannerModel>.from(json["delivery_banners"].map((x) => BannerModel.fromJson(x))),
        bannerImagePath: json["banner_image_path"]?.toString(),
        coupon: json["coupon"] == null ? null : DashBoardCoupon.fromJson(json["coupon"]),
        taxiCoupon: json["taxi_coupon"] == null ? null : DashBoardCoupon.fromJson(json["taxi_coupon"]),
        deliveryCoupon: json["delivery_coupon"] == null ? null : DashBoardCoupon.fromJson(json["delivery_coupon"]),
        recentDestinations: json["recent_destinations"] == null
            ? []
            : List<SelectedLocationInfo>.from(
                json["recent_destinations"].map((x) {
                  final dest = (x["destination_location"] ?? x["destination"])?.toString() ?? '';
                  return SelectedLocationInfo(
                    address: dest,
                    fullAddress: dest,
                    latitude: double.tryParse(x["destination_latitude"]?.toString() ?? '') ?? 0,
                    longitude: double.tryParse(x["destination_longitude"]?.toString() ?? '') ?? 0,
                    placeName: dest,
                  );
                }),
              ),
      );

  Map<String, dynamic> toJson() => {
        "payment_method": paymentMethod == null ? [] : List<dynamic>.from(paymentMethod!.map((x) => x.toJson())),
        "services": services == null ? [] : List<dynamic>.from(services!.map((x) => x.toJson())),
        "user": userInfo?.toJson(),
        "gateway_image_path": gatewayImagePath,
        "service_image_path": serviceImagePath,
        "user_image_path": userImagePath,
        "banners": banners?.map((x) => x.toJson()).toList(),
        "delivery_banners": deliveryBanners?.map((x) => x.toJson()).toList(),
         "banner_image_path": bannerImagePath,
        "coupon": coupon?.toJson(),
        "taxi_coupon": taxiCoupon?.toJson(),
        "delivery_coupon": deliveryCoupon?.toJson(),
        "recent_destinations": recentDestinations?.map((x) => x.toJson()).toList(),
      };
}

class DashBoardCoupon {
  int? id;
  String? code;
  String? name;
  String? type;
  double? value;
  DateTime? expiresAt;

  DashBoardCoupon({this.id, this.code, this.name, this.type, this.value, this.expiresAt});

  factory DashBoardCoupon.fromJson(Map<String, dynamic> json) => DashBoardCoupon(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        code: json["code"]?.toString(),
        name: json["name"]?.toString(),
        type: json["type"]?.toString(),
        value: double.tryParse(json["value"]?.toString() ?? ''),
        expiresAt: json["expires_at"] == null ? null : DateTime.parse(json["expires_at"].toString()),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "code": code,
        "name": name,
        "type": type,
        "value": value,
        "expires_at": expiresAt?.toIso8601String(),
      };
}
