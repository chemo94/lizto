import 'package:flutter/material.dart';

class FavorType {
  static const String buy = 'buy';
  static const String send = 'send';
}

class FavorMessageModel {
  int? id;
  int? favorId;
  int? senderId;
  String? senderName;
  String? senderRole; // customer, courier, admin
  String? message;
  String? image;
  String? createdAt;

  FavorMessageModel({
    this.id, this.favorId, this.senderId, this.senderName,
    this.senderRole, this.message, this.image, this.createdAt,
  });

  factory FavorMessageModel.fromJson(Map<String, dynamic> json) => FavorMessageModel(
        id: _parseInt(json["id"]),
        favorId: _parseInt(json["favor_id"]),
        senderId: _parseInt(json["sender_id"]),
        senderName: json["sender_name"]?.toString(),
        senderRole: json["sender_role"]?.toString(),
        message: json["message"]?.toString(),
        image: json["image"]?.toString(),
        createdAt: json["created_at"]?.toString(),
      );

  bool get isFromCourier => senderRole == 'courier';
  bool get isFromCustomer => senderRole == 'customer';
}

class FavorCourierModel {
  int? id;
  String? firstname;
  String? lastname;
  String? mobile;
  String? image;
  double? latitude;
  double? longitude;
  double? bearing;
  double? distanceKm;
  double? rating;

  FavorCourierModel({
    this.id, this.firstname, this.lastname, this.mobile, this.image,
    this.latitude, this.longitude, this.bearing, this.distanceKm, this.rating,
  });

  factory FavorCourierModel.fromJson(Map<String, dynamic> json) => FavorCourierModel(
        id: _parseInt(json["id"]),
        firstname: json["firstname"]?.toString(),
        lastname: json["lastname"]?.toString(),
        mobile: json["mobile"]?.toString(),
        image: json["image"]?.toString(),
        latitude: _parseDouble(json["latitude"]),
        longitude: _parseDouble(json["longitude"]),
        bearing: _parseDouble(json["bearing"]),
        distanceKm: _parseDouble(json["distance_km"]),
        rating: _parseDouble(json["rating"]),
      );

  String get fullName => '${firstname ?? ""} ${lastname ?? ""}'.trim();
}

class FavorModel {
  int? id;
  String? orderNo;
  String? type; // buy, send
  String? description;
  String? storeName;
  String? storeAddress;
  double? estimatedAmount;
  String? pickupAddress;
  double? pickupLat;
  double? pickupLng;
  String? deliveryAddress;
  double? deliveryLat;
  double? deliveryLng;
  String? recipientName;
  String? recipientPhone;
  double? deliveryFee;
  double? total;
  String? status; // pending, searching_courier, accepted, on_way_to_pickup, at_pickup, on_way_to_delivery, delivered, cancelled
  String? paymentMethodCode;
  String? paymentMethodName;
  String? paymentStatus;
  String? createdAt;
  String? updatedAt;
  FavorCourierModel? courier;
  String? courierImagePath;
  String? chatChannel;

  FavorModel({
    this.id, this.orderNo, this.type, this.description, this.storeName,
    this.storeAddress, this.estimatedAmount, this.pickupAddress,
    this.pickupLat, this.pickupLng, this.deliveryAddress,
    this.deliveryLat, this.deliveryLng, this.recipientName,
    this.recipientPhone, this.deliveryFee, this.total, this.status,
    this.paymentMethodCode, this.paymentMethodName, this.paymentStatus,
    this.createdAt, this.updatedAt, this.courier, this.courierImagePath,
    this.chatChannel,
  });

  factory FavorModel.fromJson(Map<String, dynamic> json) => FavorModel(
        id: _parseInt(json["id"]),
        orderNo: json["order_no"]?.toString(),
        type: json["type"]?.toString(),
        description: json["description"]?.toString(),
        storeName: json["store_name"]?.toString(),
        storeAddress: json["store_address"]?.toString(),
        estimatedAmount: _parseDouble(json["estimated_amount"]),
        pickupAddress: json["pickup_address"]?.toString(),
        pickupLat: _parseDouble(json["pickup_lat"]),
        pickupLng: _parseDouble(json["pickup_lng"]),
        deliveryAddress: json["delivery_address"]?.toString(),
        deliveryLat: _parseDouble(json["delivery_lat"]),
        deliveryLng: _parseDouble(json["delivery_lng"]),
        recipientName: json["recipient_name"]?.toString(),
        recipientPhone: json["recipient_phone"]?.toString(),
        deliveryFee: _parseDouble(json["delivery_fee"]),
        total: _parseDouble(json["total"]),
        status: json["status"]?.toString(),
        paymentMethodCode: json["payment_method_code"]?.toString(),
        paymentMethodName: json["payment_method_name"]?.toString(),
        paymentStatus: json["payment_status"]?.toString(),
        createdAt: json["created_at"]?.toString(),
        updatedAt: json["updated_at"]?.toString(),
        courier: json["courier"] != null ? FavorCourierModel.fromJson(json["courier"]) : null,
        courierImagePath: json["courier_image_path"]?.toString(),
        chatChannel: json["chat_channel"]?.toString(),
      );

  String get statusLabel {
    switch (status) {
      case 'pending': return 'Pendiente';
      case 'searching_courier': return 'Buscando repartidor...';
      case 'accepted': return 'Aceptado';
      case 'on_way_to_pickup': return 'Camino a recoger';
      case 'at_pickup': return 'En punto de recogida';
      case 'on_way_to_delivery': return 'Camino a entregar';
      case 'delivered': return 'Entregado';
      case 'cancelled': return 'Cancelado';
      default: return status ?? 'Desconocido';
    }
  }

  Color get statusColor {
    switch (status) {
      case 'pending': return Colors.orange;
      case 'searching_courier': return Colors.blue;
      case 'accepted': return const Color(0xFF8B5CF6);
      case 'on_way_to_pickup': return const Color(0xFF3B82F6);
      case 'at_pickup': return const Color(0xFF10B981);
      case 'on_way_to_delivery': return const Color(0xFF06B6D4);
      case 'delivered': return const Color(0xFF10B981);
      case 'cancelled': return Colors.red;
      default: return const Color(0xFF6B7280);
    }
  }

  IconData get statusIcon {
    switch (status) {
      case 'pending': return Icons.hourglass_empty_rounded;
      case 'searching_courier': return Icons.person_search_rounded;
      case 'accepted': return Icons.check_circle_outline_rounded;
      case 'on_way_to_pickup': return Icons.directions_walk_rounded;
      case 'at_pickup': return Icons.inventory_2_rounded;
      case 'on_way_to_delivery': return Icons.delivery_dining_rounded;
      case 'delivered': return Icons.verified_rounded;
      case 'cancelled': return Icons.cancel_rounded;
      default: return Icons.help_outline_rounded;
    }
  }

  String get typeLabel => type == 'send' ? 'Envío' : 'Compra';
  IconData get typeIcon => type == 'send' ? Icons.send_rounded : Icons.shopping_bag_rounded;

  bool get isActive => status != null && status != 'delivered' && status != 'cancelled';
}

class FavorBidModel {
  int? id;
  int? favorId;
  int? courierId;
  String? courierName;
  String? courierImage;
  String? courierImagePath;
  double? bidAmount;
  String? message;
  double? courierRating;
  double? courierDistanceKm;
  String? status;
  String? createdAt;

  FavorBidModel({
    this.id, this.favorId, this.courierId, this.courierName,
    this.courierImage, this.courierImagePath, this.bidAmount,
    this.message, this.courierRating, this.courierDistanceKm,
    this.status, this.createdAt,
  });

  factory FavorBidModel.fromJson(Map<String, dynamic> json) => FavorBidModel(
        id: _parseInt(json["id"]),
        favorId: _parseInt(json["favor_id"]),
        courierId: _parseInt(json["courier_id"]),
        courierName: json["courier_name"]?.toString(),
        courierImage: json["courier_image"]?.toString(),
        courierImagePath: json["courier_image_path"]?.toString(),
        bidAmount: _parseDouble(json["bid_amount"]),
        message: json["message"]?.toString(),
        courierRating: _parseDouble(json["courier_rating"]),
        courierDistanceKm: _parseDouble(json["courier_distance_km"]),
        status: json["status"]?.toString(),
        createdAt: json["created_at"]?.toString(),
      );
}

int? _parseInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  return int.tryParse(value.toString());
}

double? _parseDouble(dynamic value) {
  if (value == null) return null;
  if (value is double) return value;
  if (value is int) return value.toDouble();
  return double.tryParse(value.toString());
}
