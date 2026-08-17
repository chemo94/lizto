import 'package:flutter/material.dart';
import 'package:liztogo_repartidor/data/model/delivery/delivery_models.dart';
import 'package:liztogo_repartidor/data/model/delivery/favor_models.dart';

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
  double? commission;
  String? status; // pending, accepted, on_way_to_pickup, at_pickup, on_way_to_delivery, delivered
  String? storeName;
  String? description;
  double? customerRating;
  String? createdAt;
  String? updatedAt;
  String? storeImagePath;
  String? customerImagePath;
  String? paymentMethodCode;
  String? paymentMethodName;
  int? paymentStatus;
  bool requiresPaymentCollection;
  String? paymentWallet;
  String? paymentQrString;
  String? payerType; // sender or recipient

  // Sprint 1: Delivery confirmation
  String? pinCode;
  int? estimatedMinutes;
  Map<String, dynamic>? eta;
  Map<String, dynamic>? confirmationRequirements;

  // Sprint 2: Package details
  double? packageWeightKg;
  String? packageDimensions;
  bool isFragile;
  double? itemValue;
  String? scheduledAt;
  String? timeSlot;

  // Sprint 3: Express/Priority
  bool isExpress;
  int priorityLevel;

  // Sprint 4: Enterprise fields
  String? shipmentType;
  String? evidenceType;
  double? codAmount;
  bool isHeavy;
  bool isTemperatureControlled;

  // Return handling
  String? returnStatus;
  String? returnReason;
  String? returnNotes;

  // Favor-specific
  FavorModel? favorData;

  // Delivery-specific
  DeliveryOrderModel? deliveryData;

  CourierJobModel({
    this.id,
    this.type,
    this.orderNo,
    this.customerName,
    this.customerPhone,
    this.pickupAddress,
    this.pickupLat,
    this.pickupLng,
    this.deliveryAddress,
    this.deliveryLat,
    this.deliveryLng,
    this.amount,
    this.deliveryFee,
    this.totalEarning,
    this.commission,
    this.status,
    this.storeName,
    this.description,
    this.customerRating,
    this.createdAt,
    this.updatedAt,
    this.storeImagePath,
    this.customerImagePath,
    this.paymentMethodCode,
    this.paymentMethodName,
    this.paymentStatus,
    this.requiresPaymentCollection = false,
    this.paymentWallet,
    this.paymentQrString,
    this.payerType,
    this.pinCode,
    this.estimatedMinutes,
    this.eta,
    this.confirmationRequirements,
    this.packageWeightKg,
    this.packageDimensions,
    this.isFragile = false,
    this.itemValue,
    this.scheduledAt,
    this.timeSlot,
    this.isExpress = false,
    this.priorityLevel = 0,
    this.shipmentType,
    this.evidenceType,
    this.codAmount,
    this.isHeavy = false,
    this.isTemperatureControlled = false,
    this.returnStatus,
    this.returnReason,
    this.returnNotes,
    this.favorData,
    this.deliveryData,
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
        commission: _pDouble(json["commission"]),
        status: json["status"]?.toString(),
        storeName: json["store_name"]?.toString(),
        description: json["description"]?.toString(),
        customerRating: _pDouble(json["customer_rating"]),
        createdAt: json["created_at"]?.toString(),
        updatedAt: json["updated_at"]?.toString(),
        storeImagePath: json["store_image_path"]?.toString(),
        customerImagePath: json["customer_image_path"]?.toString(),
        paymentMethodCode: json["payment_method_code"]?.toString(),
        paymentMethodName: json["payment_method_name"]?.toString(),
        paymentStatus: _pInt(json["payment_status"]),
        requiresPaymentCollection: json["requires_payment_collection"] == true || json["requires_payment_collection"] == 1,
        paymentWallet: json["payment_wallet"]?.toString(),
        paymentQrString: json["payment_qr_string"]?.toString(),
        payerType: json["payer_type"]?.toString(),
        // Sprint 1: Delivery confirmation
        pinCode: json["pin_code"]?.toString(),
        estimatedMinutes: _pInt(json["estimated_minutes"]),
        eta: json["eta"] is Map ? Map<String, dynamic>.from(json["eta"]) : null,
        confirmationRequirements: json["confirmation_requirements"] is Map
            ? Map<String, dynamic>.from(json["confirmation_requirements"])
            : null,
        // Sprint 2: Package details
        packageWeightKg: _pDouble(json["package_weight_kg"]),
        packageDimensions: json["package_dimensions"]?.toString(),
        isFragile: json["is_fragile"] == true || json["is_fragile"] == 1,
        itemValue: _pDouble(json["item_value"]),
        scheduledAt: json["scheduled_at"]?.toString(),
        timeSlot: json["time_slot"]?.toString(),
        // Sprint 3: Express
        isExpress: json["is_express"] == true || json["is_express"] == 1,
        priorityLevel: _pInt(json["priority_level"]) ?? 0,
        // Sprint 4: Enterprise fields
        shipmentType: json["shipment_type"]?.toString(),
        evidenceType: json["evidence_type"]?.toString(),
        codAmount: _pDouble(json["cod_amount"]),
        isHeavy: json["is_heavy"] == true || json["is_heavy"] == 1,
        isTemperatureControlled: json["is_temperature_controlled"] == true || json["is_temperature_controlled"] == 1,
        // Return handling
        returnStatus: json["return_status"]?.toString(),
        returnReason: json["return_reason"]?.toString(),
        returnNotes: json["return_notes"]?.toString(),
        favorData: json["favor"] != null ? FavorModel.fromJson(json["favor"]) : null,
        deliveryData: json["delivery"] != null ? DeliveryOrderModel.fromJson(json["delivery"]) : null,
      );

  bool get isDelivery => type == 'delivery';
  bool get isFavor => type == 'favor';
  bool get isDigitalWallet =>
      paymentWallet == 'yape' ||
      paymentWallet == 'plin' ||
      (paymentQrString != null && paymentQrString!.isNotEmpty) ||
      (paymentMethodName != null &&
          (paymentMethodName!.toLowerCase().contains('yape') ||
              paymentMethodName!.toLowerCase().contains('plin') ||
              paymentMethodName!.toLowerCase().contains('qr')));
  bool get isActive => status != null && status != 'delivered' && status != 'cancelled';
  bool get requiresPin => payerType == 'recipient';
  bool get hasReturn => returnStatus != null && returnStatus!.isNotEmpty;

  String get statusLabel {
    switch (status ?? '') {
      case 'pending':
        return 'Pendiente';
      case 'accepted':
        return 'Aceptado';
      case 'on_way':
      case 'on_way_to_pickup':
        return 'Camino a recoger';
      case 'at_pickup':
        return 'En recogida';
      case 'on_way_to_delivery':
        return 'Camino a entregar';
      case 'delivered':
        return 'Entregado';
      case 'cancelled':
        return 'Cancelado';
      default:
        return status?.replaceAll('_', ' ') ?? '';
    }
  }

  String get returnStatusLabel {
    switch (returnStatus ?? '') {
      case 'return_requested':
        return 'Devolución solicitada';
      case 'return_assigned':
        return 'Devolución asignada';
      case 'return_in_transit':
        return 'Devolución en tránsito';
      case 'return_completed':
        return 'Devolución completada';
      default:
        return '';
    }
  }

  Color get statusColor {
    switch (status ?? '') {
      case 'pending':
        return Colors.orange;
      case 'accepted':
        return const Color(0xFF8B5CF6);
      case 'on_way':
      case 'on_way_to_pickup':
        return const Color(0xFF3B82F6);
      case 'at_pickup':
        return const Color(0xFF10B981);
      case 'on_way_to_delivery':
        return const Color(0xFF06B6D4);
      case 'delivered':
        return const Color(0xFF10B981);
      case 'cancelled':
        return Colors.red;
      default:
        return const Color(0xFF6B7280);
    }
  }

  IconData get statusIcon {
    switch (status ?? '') {
      case 'pending':
        return Icons.hourglass_empty_rounded;
      case 'accepted':
        return Icons.check_circle_outline_rounded;
      case 'on_way':
      case 'on_way_to_pickup':
        return Icons.directions_walk_rounded;
      case 'at_pickup':
        return Icons.inventory_2_rounded;
      case 'on_way_to_delivery':
        return Icons.delivery_dining_rounded;
      case 'delivered':
        return Icons.verified_rounded;
      case 'cancelled':
        return Icons.cancel_rounded;
      default:
        return Icons.help_outline_rounded;
    }
  }

  String get nextAction {
    // Return handling actions
    if (returnStatus == 'return_requested' || returnStatus == 'return_assigned') {
      return 'Recoger devolución';
    }
    if (returnStatus == 'return_in_transit') {
      return 'Completar devolución';
    }

    switch (status ?? '') {
      case 'pending':
        return 'Aceptar';
      case 'accepted':
        return 'Llegue a recoger';
      case 'on_way':
      case 'on_way_to_pickup':
        return 'Confirmar recogida';
      case 'at_pickup':
        return 'Iniciar entrega';
      case 'on_way_to_delivery':
        return 'Confirmar entrega';
      default:
        return '';
    }
  }

  String? get nextStatus {
    // Return handling statuses
    if (returnStatus == 'return_requested' || returnStatus == 'return_assigned') {
      return 'return_picked_up';
    }
    if (returnStatus == 'return_in_transit') {
      return 'return_completed';
    }

    switch (status ?? '') {
      case 'pending':
        return 'accepted';
      case 'accepted':
        return 'on_way_to_pickup';
      case 'on_way':
      case 'on_way_to_pickup':
        return 'at_pickup';
      case 'at_pickup':
        return 'on_way_to_delivery';
      case 'on_way_to_delivery':
        return 'delivered';
      default:
        return null;
    }
  }

  String? get etaText {
    if (eta != null && eta!['duration_text'] != null) {
      return eta!['duration_text'];
    }
    if (estimatedMinutes != null) {
      return '~$estimatedMinutes min';
    }
    return null;
  }

  String? get etaDistanceText {
    if (eta != null && eta!['distance_text'] != null) {
      return eta!['distance_text'];
    }
    return null;
  }
}

class CourierEarningsModel {
  double? todayEarnings;
  double? weekEarnings;
  double? monthEarnings;
  double? totalEarnings;
  double? earningBalance;   // Real spendable balance after admin settlements
  double? totalPaid;        // Total already paid/settled by admin
  int? todayJobs;
  int? weekJobs;
  int? monthJobs;
  int? totalJobs;
  double? avgRating;
  List<EarningSettlement> recentSettlements;
  String? tierName;
  String? tierBadge;
  double? effectivePercent;
  int? totalWeeklyJobs;
  int? nextTierNeeded;
  String? nextTierName;

  CourierEarningsModel({
    this.todayEarnings,
    this.weekEarnings,
    this.monthEarnings,
    this.totalEarnings,
    this.earningBalance,
    this.totalPaid,
    this.todayJobs,
    this.weekJobs,
    this.monthJobs,
    this.totalJobs,
    this.avgRating,
    this.recentSettlements = const [],
    this.tierName,
    this.tierBadge,
    this.effectivePercent,
    this.totalWeeklyJobs,
    this.nextTierNeeded,
    this.nextTierName,
  });

  factory CourierEarningsModel.fromJson(Map<String, dynamic> json) => CourierEarningsModel(
        todayEarnings: _pDouble(json["today_earnings"]),
        weekEarnings: _pDouble(json["week_earnings"]),
        monthEarnings: _pDouble(json["month_earnings"]),
        totalEarnings: _pDouble(json["total_earnings"]),
        earningBalance: _pDouble(json["earning_balance"]),
        totalPaid: _pDouble(json["total_paid"]),
        todayJobs: _pInt(json["today_jobs"]),
        weekJobs: _pInt(json["week_jobs"]),
        monthJobs: _pInt(json["month_jobs"]),
        totalJobs: _pInt(json["total_jobs"]),
        avgRating: _pDouble(json["avg_rating"]),
        recentSettlements: json["recent_settlements"] != null
            ? (json["recent_settlements"] as List).map((x) => EarningSettlement.fromJson(x)).toList()
            : [],
        tierName: json["tier_name"]?.toString(),
        tierBadge: json["tier_badge"]?.toString(),
        effectivePercent: _pDouble(json["effective_percent"]),
        totalWeeklyJobs: _pInt(json["total_weekly_jobs"]),
        nextTierNeeded: _pInt(json["next_tier_needed"]),
        nextTierName: json["next_tier_name"]?.toString(),
      );
}

class EarningSettlement {
  final int? id;
  final String? type;
  final String? trxType;
  final double? amount;
  final double? postBalance;
  final String? settlementMethod;
  final String? notes;
  final String? date;

  EarningSettlement({
    this.id,
    this.type,
    this.trxType,
    this.amount,
    this.postBalance,
    this.settlementMethod,
    this.notes,
    this.date,
  });

  factory EarningSettlement.fromJson(Map<String, dynamic> json) => EarningSettlement(
        id: _pInt(json["id"]),
        type: json["type"]?.toString(),
        trxType: json["trx_type"]?.toString(),
        amount: _pDouble(json["amount"]),
        postBalance: _pDouble(json["post_balance"]),
        settlementMethod: json["settlement_method"]?.toString(),
        notes: json["notes"]?.toString(),
        date: json["date"]?.toString(),
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
