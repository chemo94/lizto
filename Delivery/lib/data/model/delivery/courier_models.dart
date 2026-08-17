import 'package:flutter/material.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_models.dart';
import 'package:lizto_delivery/data/model/delivery/favor_models.dart';

class CourierJobModel {
  int? id;
  String? type; // delivery, favor
  String? orderNo;
  String? customerName;
  String? customerPhone;
  String? pickupAddress;
  double? pickupLat;
  double? pickupLng;
  String? deliveryAddress;
  double? deliveryLat;
  double? deliveryLng;
  double? amount;
  double? deliveryFee;
  double? totalEarning;
  String? status; // pending, accepted, on_way_to_pickup, at_pickup, on_way_to_delivery, delivered
  String? storeName;
  String? description;
  double? customerRating;
  String? createdAt;
  String? updatedAt;
  String? storeImagePath;
  String? customerImagePath;

  // Favor-specific
  FavorModel? favorData;

  // Delivery-specific
  DeliveryOrderModel? deliveryData;

  CourierJobModel({
    this.id, this.type, this.orderNo, this.customerName, this.customerPhone,
    this.pickupAddress, this.pickupLat, this.pickupLng, this.deliveryAddress,
    this.deliveryLat, this.deliveryLng, this.amount, this.deliveryFee,
    this.totalEarning, this.status, this.storeName, this.description,
    this.customerRating, this.createdAt, this.updatedAt,
    this.storeImagePath, this.customerImagePath, this.favorData, this.deliveryData,
  });

  factory CourierJobModel.fromJson(Map<String, dynamic> json) => CourierJobModel(
        id: _pInt(json["id"]),
        type: json["type"]?.toString(),
        orderNo: json["order_no"]?.toString(),
        customerName: json["customer_name"]?.toString(),
        customerPhone: json["customer_phone"]?.toString(),
        pickupAddress: json["pickup_address"]?.toString(),
        pickupLat: _pDouble(json["pickup_lat"]),
        pickupLng: _pDouble(json["pickup_lng"]),
        deliveryAddress: json["delivery_address"]?.toString(),
        deliveryLat: _pDouble(json["delivery_lat"]),
        deliveryLng: _pDouble(json["delivery_lng"]),
        amount: _pDouble(json["amount"]),
        deliveryFee: _pDouble(json["delivery_fee"]),
        totalEarning: _pDouble(json["total_earning"]),
        status: json["status"]?.toString(),
        storeName: json["store_name"]?.toString(),
        description: json["description"]?.toString(),
        customerRating: _pDouble(json["customer_rating"]),
        createdAt: json["created_at"]?.toString(),
        updatedAt: json["updated_at"]?.toString(),
        storeImagePath: json["store_image_path"]?.toString(),
        customerImagePath: json["customer_image_path"]?.toString(),
        favorData: json["favor"] != null ? FavorModel.fromJson(json["favor"]) : null,
        deliveryData: json["delivery"] != null ? DeliveryOrderModel.fromJson(json["delivery"]) : null,
      );

  bool get isDelivery => type == 'delivery';
  bool get isFavor => type == 'favor';
  bool get isActive => status != null && status != 'delivered' && status != 'cancelled';

  String get statusLabel {
    switch (status) {
      case 'pending': return 'Pendiente';
      case 'accepted': return 'Aceptado';
      case 'on_way_to_pickup': return 'Camino a recoger';
      case 'at_pickup': return 'En recogida';
      case 'on_way_to_delivery': return 'Camino a entregar';
      case 'delivered': return 'Entregado';
      case 'cancelled': return 'Cancelado';
      default: return status ?? '';
    }
  }

  Color get statusColor {
    switch (status) {
      case 'pending': return Colors.orange;
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
      case 'accepted': return Icons.check_circle_outline_rounded;
      case 'on_way_to_pickup': return Icons.directions_walk_rounded;
      case 'at_pickup': return Icons.inventory_2_rounded;
      case 'on_way_to_delivery': return Icons.delivery_dining_rounded;
      case 'delivered': return Icons.verified_rounded;
      case 'cancelled': return Icons.cancel_rounded;
      default: return Icons.help_outline_rounded;
    }
  }

  String get nextAction {
    switch (status) {
      case 'pending': return 'Aceptar';
      case 'accepted': return 'Llegué a recoger';
      case 'on_way_to_pickup': return 'Confirmar recogida';
      case 'at_pickup': return 'Iniciar entrega';
      case 'on_way_to_delivery': return 'Confirmar entrega';
      default: return '';
    }
  }

  String? get nextStatus {
    switch (status) {
      case 'pending': return 'accepted';
      case 'accepted': return 'on_way_to_pickup';
      case 'on_way_to_pickup': return 'at_pickup';
      case 'at_pickup': return 'on_way_to_delivery';
      case 'on_way_to_delivery': return 'delivered';
      default: return null;
    }
  }
}

class CourierEarningsModel {
  double? todayEarnings;
  double? weekEarnings;
  double? monthEarnings;
  double? totalEarnings;
  int? todayJobs;
  int? weekJobs;
  int? monthJobs;
  int? totalJobs;
  double? avgRating;

  CourierEarningsModel({
    this.todayEarnings, this.weekEarnings, this.monthEarnings,
    this.totalEarnings, this.todayJobs, this.weekJobs,
    this.monthJobs, this.totalJobs, this.avgRating,
  });

  factory CourierEarningsModel.fromJson(Map<String, dynamic> json) => CourierEarningsModel(
        todayEarnings: _pDouble(json["today_earnings"]),
        weekEarnings: _pDouble(json["week_earnings"]),
        monthEarnings: _pDouble(json["month_earnings"]),
        totalEarnings: _pDouble(json["total_earnings"]),
        todayJobs: _pInt(json["today_jobs"]),
        weekJobs: _pInt(json["week_jobs"]),
        monthJobs: _pInt(json["month_jobs"]),
        totalJobs: _pInt(json["total_jobs"]),
        avgRating: _pDouble(json["avg_rating"]),
      );
}

int? _pInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  return int.tryParse(value.toString());
}

double? _pDouble(dynamic value) {
  if (value == null) return null;
  if (value is double) return value;
  if (value is int) return value.toDouble();
  return double.tryParse(value.toString());
}
