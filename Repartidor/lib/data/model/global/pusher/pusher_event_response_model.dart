import 'dart:convert';

PusherResponseModel pusherResponseModelFromJson(String str) => PusherResponseModel.fromJson(json.decode(str));

class PusherResponseModel {
  String? channelName;
  String? eventName;
  EventData? data;
  PusherResponseModel({this.channelName, this.eventName, this.data});
  PusherResponseModel copyWith({
    String? channelName,
    String? eventName,
    EventData? data,
  }) =>
      PusherResponseModel(
        channelName: channelName.toString(),
        eventName: eventName.toString(),
        data: data,
      );
  factory PusherResponseModel.fromJson(Map<String, dynamic> json) {
    return PusherResponseModel(
      channelName: json["channelName"].toString(),
      eventName: json["eventName"].toString(),
      data: EventData.fromJson(json["data"]),
    );
  }
}

class EventData {
  String? remark;
  String? userId;
  String? driverId;
  String? rideId;
  String? driverLatitude;
  String? driverLongitude;
  EventData({
    this.remark,
    this.userId,
    this.driverId,
    this.rideId,
    this.driverLatitude,
    this.driverLongitude,
  });
  EventData copyWith({
    String? channelName,
    String? eventName,
    String? remark,
    String? userId,
    String? driverId,
    String? rideId,
    String? driverLatitude,
    String? driverLongitude,
  }) =>
      EventData(
        remark: remark.toString(),
        userId: userId.toString(),
        driverId: driverId.toString(),
        rideId: rideId.toString(),
        driverLatitude: driverLatitude ?? '',
        driverLongitude: driverLongitude ?? '',
      );
  factory EventData.fromJson(Map<String, dynamic> json) {
    return EventData(
      remark: json["remark"].toString(),
      userId: json["userId"].toString(),
      driverId: json["driverId"].toString(),
      rideId: json["rideId"].toString(),
      driverLatitude: json["latitude"].toString(),
      driverLongitude: json["longitude"].toString(),
    );
  }
}
