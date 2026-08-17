class DeliveryPinVerification {
  final int orderId;
  final String pinCode;
  final String? courierName;

  DeliveryPinVerification({
    required this.orderId,
    required this.pinCode,
    this.courierName,
  });

  factory DeliveryPinVerification.fromJson(Map<String, dynamic> json) => DeliveryPinVerification(
        orderId: json["order_id"] is int ? json["order_id"] : int.parse(json["order_id"].toString()),
        pinCode: json["pin_code"].toString(),
        courierName: json["courier_name"]?.toString(),
      );
}
