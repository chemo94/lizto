import 'package:lizto_delivery/data/model/home/banner_model.dart';

class DashBoardResponseModel {
  String? remark;
  String? status;
  DashBoardData? data;
  List<String>? message;

  DashBoardResponseModel({this.remark, this.status, this.data, this.message});

  factory DashBoardResponseModel.fromJson(Map<String, dynamic> json) {
    return DashBoardResponseModel(
      remark: json["remark"],
      status: json["status"],
      message: json["message"] == null ? [] : List<String>.from(json["message"]!.map((x) => x.toString())),
      data: json["data"] == null ? null : DashBoardData.fromJson(json["data"]),
    );
  }
}

class DashBoardData {
  List<BannerModel>? banners;
  List<BannerModel>? deliveryBanners;
  String? bannerImagePath;

  DashBoardData({this.banners, this.deliveryBanners, this.bannerImagePath});

  factory DashBoardData.fromJson(Map<String, dynamic> json) {
    return DashBoardData(
      banners: json["banners"] == null ? null : List<BannerModel>.from(json["banners"].map((x) => BannerModel.fromJson(x))),
      deliveryBanners: json["delivery_banners"] == null ? null : List<BannerModel>.from(json["delivery_banners"].map((x) => BannerModel.fromJson(x))),
      bannerImagePath: json["banner_image_path"]?.toString(),
    );
  }
}
