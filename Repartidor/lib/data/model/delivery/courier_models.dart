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
  String? requestedAt;
  String? deliveredAt;
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

  // Phase 2: Dynamic fare, route optimization & batching
  double? distanceKm;
  double? durationMinutes;
  double? driverEarning;
  double? totalPayout;
  int? points;
  int? batchId;
  FareBreakdownModel? fareBreakdown;

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
    this.requestedAt,
    this.deliveredAt,
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
    this.distanceKm,
    this.durationMinutes,
    this.driverEarning,
    this.totalPayout,
    this.points,
    this.batchId,
    this.fareBreakdown,
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
        requestedAt: json["requested_at"]?.toString(),
        deliveredAt: json["delivered_at"]?.toString(),
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
        confirmationRequirements: json["confirmation_requirements"] is Map ? Map<String, dynamic>.from(json["confirmation_requirements"]) : null,
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
        // Phase 2: Dynamic fare, route optimization & batching
        distanceKm: _pDouble(json["distance_km"]),
        durationMinutes: _pDouble(json["duration_minutes"]),
        driverEarning: _pDouble(json["driver_earning"]),
        totalPayout: _pDouble(json["total_payout"]),
        points: _pInt(json["points"]),
        batchId: _pInt(json["batch_id"]),
        fareBreakdown: json["fare_breakdown"] != null && json["fare_breakdown"] is Map<String, dynamic>
            ? FareBreakdownModel.fromJson(Map<String, dynamic>.from(json["fare_breakdown"]))
            : null,
        // Return handling
        returnStatus: json["return_status"]?.toString(),
        returnReason: json["return_reason"]?.toString(),
        returnNotes: json["return_notes"]?.toString(),
        favorData: json["favor"] != null ? FavorModel.fromJson(json["favor"]) : null,
        deliveryData: json["delivery"] != null ? DeliveryOrderModel.fromJson(json["delivery"]) : null,
      );

  bool get isDelivery => type == 'delivery';
  bool get isFavor => type == 'favor';
  bool get isDigitalWallet => paymentWallet == 'yape' || paymentWallet == 'plin' || (paymentQrString != null && paymentQrString!.isNotEmpty) || (paymentMethodName != null && (paymentMethodName!.toLowerCase().contains('yape') || paymentMethodName!.toLowerCase().contains('plin') || paymentMethodName!.toLowerCase().contains('qr')));
  bool get isActive => status != null && status != 'delivered' && status != 'cancelled';
  bool get requiresPin => payerType == 'recipient';
  bool get hasReturn => returnStatus != null && returnStatus!.isNotEmpty;

  /// Fecha real en que se solicitó el pedido. Si el operador la registró
  /// manualmente se usa esa; de lo contrario se usa la fecha de creación.
  String? get requestedAtText => _formatJobDate(requestedAt ?? createdAt);

  /// Fecha de entrega confirmada
  String? get deliveredAtText => _formatJobDate(deliveredAt ?? (status == 'delivered' ? updatedAt : null));

  static String? _formatJobDate(String? raw) {
    if (raw == null || raw.isEmpty) return null;
    try {
      final dt = DateTime.tryParse(raw);
      if (dt == null) return raw;
      final local = dt.toLocal();
      final months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
      return '${local.day} ${months[local.month - 1]} ${local.year}  ${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return raw;
    }
  }

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
  double? earningBalance; // Real spendable balance after admin settlements
  double? totalPaid; // Total already paid/settled by admin
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
  int? totalCompletedJobs;
  double? baseCommissionPercent;
  double? minimumCommission;
  int? offersReceived;
  int? offersResponded;
  int? offersMissed;
  double? responseRate;
  bool? eligibleForReview;
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
    this.totalCompletedJobs,
    this.baseCommissionPercent,
    this.minimumCommission,
    this.offersReceived,
    this.offersResponded,
    this.offersMissed,
    this.responseRate,
    this.eligibleForReview,
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
        recentSettlements: json["recent_settlements"] != null ? (json["recent_settlements"] as List).map((x) => EarningSettlement.fromJson(x)).toList() : [],
        tierName: json["tier_name"]?.toString(),
        tierBadge: json["tier_badge"]?.toString(),
        effectivePercent: _pDouble(json["effective_percent"]),
        totalWeeklyJobs: _pInt(json["total_weekly_jobs"]),
        totalCompletedJobs: _pInt(json["total_completed_jobs"] ?? json["total_weekly_jobs"]),
        baseCommissionPercent: _pDouble(json["base_commission_percent"]),
        minimumCommission: _pDouble(json["minimum_commission"]),
        offersReceived: _pInt(json["offer_metrics"]?["received"]),
        offersResponded: _pInt(json["offer_metrics"]?["responded"]),
        offersMissed: _pInt(json["offer_metrics"]?["missed"]),
        responseRate: _pDouble(json["offer_metrics"]?["response_rate"]),
        eligibleForReview: json["offer_metrics"]?["eligible_for_review"] == true || json["offer_metrics"]?["eligible_for_review"] == 1,
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

/// Dynamic Fare Breakdown
class FareBreakdownModel {
  final double? baseFare;
  final double? distanceKm;
  final double? distanceRate;
  final double? distanceAmount;
  final double? timeMinutes;
  final double? timeRate;
  final double? timeAmount;
  final String? batchType;
  final double? batchBonus;
  final String? demandTier;
  final double? demandMultiplier;
  final double? demandIncentive;
  final double? driverEarning;
  final double? tip;
  final double? totalPayout;
  final int? points;
  final String? economicPolicy;
  final bool driverRetains100;

  FareBreakdownModel({
    this.baseFare,
    this.distanceKm,
    this.distanceRate,
    this.distanceAmount,
    this.timeMinutes,
    this.timeRate,
    this.timeAmount,
    this.batchType,
    this.batchBonus,
    this.demandTier,
    this.demandMultiplier,
    this.demandIncentive,
    this.driverEarning,
    this.tip,
    this.totalPayout,
    this.points,
    this.economicPolicy,
    this.driverRetains100 = true,
  });

  factory FareBreakdownModel.fromJson(Map<String, dynamic> json) => FareBreakdownModel(
        baseFare: _pDouble(json["base_fare"]),
        distanceKm: _pDouble(json["distance_km"]),
        distanceRate: _pDouble(json["distance_rate"]),
        distanceAmount: _pDouble(json["distance_amount"]),
        timeMinutes: _pDouble(json["time_minutes"]),
        timeRate: _pDouble(json["time_rate"]),
        timeAmount: _pDouble(json["time_amount"]),
        batchType: json["batch_type"]?.toString(),
        batchBonus: _pDouble(json["batch_bonus"]),
        demandTier: json["demand_tier"]?.toString(),
        demandMultiplier: _pDouble(json["demand_multiplier"]),
        demandIncentive: _pDouble(json["demand_incentive"]),
        driverEarning: _pDouble(json["driver_earning"]),
        tip: _pDouble(json["tip"]),
        totalPayout: _pDouble(json["total_payout"]),
        points: _pInt(json["points"]),
        economicPolicy: json["economic_policy"]?.toString(),
        driverRetains100: json["driver_retains_100"] == true || json["driver_retains_100"] == 1,
      );

  Map<String, dynamic> toJson() => {
        "base_fare": baseFare,
        "distance_km": distanceKm,
        "distance_rate": distanceRate,
        "distance_amount": distanceAmount,
        "time_minutes": timeMinutes,
        "time_rate": timeRate,
        "time_amount": timeAmount,
        "batch_type": batchType,
        "batch_bonus": batchBonus,
        "demand_tier": demandTier,
        "demand_multiplier": demandMultiplier,
        "demand_incentive": demandIncentive,
        "driver_earning": driverEarning,
        "tip": tip,
        "total_payout": totalPayout,
        "points": points,
        "economic_policy": economicPolicy,
        "driver_retains_100": driverRetains100,
      };
}

/// Optimized Multi-Stop Route Stop
class OptimizedStopModel {
  final int? stopNumber;
  final String? type; // pickup, dropoff
  final int? orderId;
  final String? orderType;
  final double? lat;
  final double? lng;
  final String? address;
  final String? contactName;
  final double? legDistanceKm;
  final double? legTimeMinutes;
  final double? cumulativeDistanceKm;
  final double? cumulativeTimeMinutes;
  final bool isCompleted;

  OptimizedStopModel({
    this.stopNumber,
    this.type,
    this.orderId,
    this.orderType,
    this.lat,
    this.lng,
    this.address,
    this.contactName,
    this.legDistanceKm,
    this.legTimeMinutes,
    this.cumulativeDistanceKm,
    this.cumulativeTimeMinutes,
    this.isCompleted = false,
  });

  factory OptimizedStopModel.fromJson(Map<String, dynamic> json) => OptimizedStopModel(
        stopNumber: _pInt(json["stop_number"]),
        type: json["type"]?.toString(),
        orderId: _pInt(json["order_id"]),
        orderType: json["order_type"]?.toString(),
        lat: _pDouble(json["lat"]),
        lng: _pDouble(json["lng"]),
        address: json["address"]?.toString(),
        contactName: json["contact_name"]?.toString(),
        legDistanceKm: _pDouble(json["leg_distance_km"]),
        legTimeMinutes: _pDouble(json["leg_time_minutes"]),
        cumulativeDistanceKm: _pDouble(json["cumulative_distance_km"]),
        cumulativeTimeMinutes: _pDouble(json["cumulative_time_minutes"]),
        isCompleted: json["is_completed"] == true || json["is_completed"] == 1,
      );

  bool get isPickup => type == 'pickup';
  bool get isDropoff => type == 'dropoff';
}

/// Multi-Order Batch Model (Single, Double, Triplet, Quadruple)
class CourierBatchModel {
  final int? id;
  final String? batchNo;
  final String? batchType; // SINGLE, DOUBLE, TRIPLET, QUADRUPLE
  final String? status; // pending, offered, accepted, in_progress, completed, cancelled
  final int? totalOrders;
  final double? totalDistanceKm;
  final double? totalDurationMinutes;
  final double? baseEarning;
  final double? distanceEarning;
  final double? timeEarning;
  final double? batchBonus;
  final double? demandIncentive;
  final double? driverEarning;
  final double? totalTips;
  final double? totalPayout;
  final int? totalPoints;
  final String? demandTier;
  final double? demandMultiplier;
  final List<OptimizedStopModel> optimizedStops;
  final FareBreakdownModel? fareBreakdown;
  final List<CourierBatchOrderModel> orders;
  final String? createdAt;

  CourierBatchModel({
    this.id,
    this.batchNo,
    this.batchType,
    this.status,
    this.totalOrders,
    this.totalDistanceKm,
    this.totalDurationMinutes,
    this.baseEarning,
    this.distanceEarning,
    this.timeEarning,
    this.batchBonus,
    this.demandIncentive,
    this.driverEarning,
    this.totalTips,
    this.totalPayout,
    this.totalPoints,
    this.demandTier,
    this.demandMultiplier,
    this.optimizedStops = const [],
    this.fareBreakdown,
    this.orders = const [],
    this.createdAt,
  });

  factory CourierBatchModel.fromJson(Map<String, dynamic> json) => CourierBatchModel(
        id: _pInt(json["id"]),
        batchNo: json["batch_no"]?.toString(),
        batchType: json["batch_type"]?.toString(),
        status: json["status"]?.toString(),
        totalOrders: _pInt(json["total_orders"]),
        totalDistanceKm: _pDouble(json["total_distance_km"]),
        totalDurationMinutes: _pDouble(json["total_duration_minutes"]),
        baseEarning: _pDouble(json["base_earning"]),
        distanceEarning: _pDouble(json["distance_earning"]),
        timeEarning: _pDouble(json["time_earning"]),
        batchBonus: _pDouble(json["batch_bonus"]),
        demandIncentive: _pDouble(json["demand_incentive"]),
        driverEarning: _pDouble(json["driver_earning"]),
        totalTips: _pDouble(json["total_tips"]),
        totalPayout: _pDouble(json["total_payout"]),
        totalPoints: _pInt(json["total_points"]),
        demandTier: json["demand_tier"]?.toString(),
        demandMultiplier: _pDouble(json["demand_multiplier"]),
        optimizedStops: json["optimized_stops"] is List
            ? (json["optimized_stops"] as List)
                .map((x) => OptimizedStopModel.fromJson(Map<String, dynamic>.from(x)))
                .toList()
            : [],
        fareBreakdown: json["fare_breakdown"] != null && json["fare_breakdown"] is Map<String, dynamic>
            ? FareBreakdownModel.fromJson(Map<String, dynamic>.from(json["fare_breakdown"]))
            : null,
        orders: json["orders"] is List
            ? (json["orders"] as List)
                .map((x) => CourierBatchOrderModel.fromJson(Map<String, dynamic>.from(x)))
                .toList()
            : [],
        createdAt: json["created_at"]?.toString(),
      );

  bool get isSingle => batchType == 'SINGLE';
  bool get isDouble => batchType == 'DOUBLE';
  bool get isTriplet => batchType == 'TRIPLET';
  bool get isQuadruple => batchType == 'QUADRUPLE';
  bool get isActive => status == 'accepted' || status == 'in_progress';
}

/// Order Entry inside a Batch
class CourierBatchOrderModel {
  final int? id;
  final int? orderId;
  final String? orderType;
  final int? sequenceOrder;
  final int? pickupStopNo;
  final int? dropoffStopNo;
  final String? status; // pending, picked_up, delivered, cancelled
  final double? individualEarning;
  final double? tip;
  final int? points;
  final CourierJobModel? orderDetail;

  CourierBatchOrderModel({
    this.id,
    this.orderId,
    this.orderType,
    this.sequenceOrder,
    this.pickupStopNo,
    this.dropoffStopNo,
    this.status,
    this.individualEarning,
    this.tip,
    this.points,
    this.orderDetail,
  });

  factory CourierBatchOrderModel.fromJson(Map<String, dynamic> json) => CourierBatchOrderModel(
        id: _pInt(json["id"]),
        orderId: _pInt(json["order_id"]),
        orderType: json["order_type"]?.toString(),
        sequenceOrder: _pInt(json["sequence_order"]),
        pickupStopNo: _pInt(json["pickup_stop_no"]),
        dropoffStopNo: _pInt(json["dropoff_stop_no"]),
        status: json["status"]?.toString(),
        individualEarning: _pDouble(json["individual_earning"]),
        tip: _pDouble(json["tip"]),
        points: _pInt(json["points"]),
        orderDetail: json["order_detail"] != null && json["order_detail"] is Map<String, dynamic>
            ? CourierJobModel.fromJson(Map<String, dynamic>.from(json["order_detail"]))
            : null,
      );
}

/// Real-time Demand Tier Status
class DemandStatusModel {
  final String? tier;
  final double? multiplier;
  final double? incentiveAmount;
  final int? points;
  final double? demandRatio;
  final int? pendingOrders;
  final int? availableDrivers;
  final double? radiusKm;

  DemandStatusModel({
    this.tier,
    this.multiplier,
    this.incentiveAmount,
    this.points,
    this.demandRatio,
    this.pendingOrders,
    this.availableDrivers,
    this.radiusKm,
  });

  factory DemandStatusModel.fromJson(Map<String, dynamic> json) => DemandStatusModel(
        tier: json["tier"]?.toString(),
        multiplier: _pDouble(json["multiplier"]),
        incentiveAmount: _pDouble(json["incentive_amount"]),
        points: _pInt(json["points"]),
        demandRatio: _pDouble(json["demand_ratio"]),
        pendingOrders: _pInt(json["pending_orders"]),
        availableDrivers: _pInt(json["available_drivers"]),
        radiusKm: _pDouble(json["radius_km"]),
      );

  bool get isHighDemand => tier == 'HIGH' || tier == 'VERY_HIGH';
}

/// Auto-Acceptance Settings Configuration
class AutoAcceptSettingsModel {
  final bool autoAcceptEnabled;
  final double minEarning;
  final double maxDistance;

  AutoAcceptSettingsModel({
    this.autoAcceptEnabled = false,
    this.minEarning = 0.0,
    this.maxDistance = 10.0,
  });

  factory AutoAcceptSettingsModel.fromJson(Map<String, dynamic> json) => AutoAcceptSettingsModel(
        autoAcceptEnabled: json["auto_accept_enabled"] == true || json["auto_accept_enabled"] == 1,
        minEarning: _pDouble(json["auto_accept_min_earning"]) ?? 0.0,
        maxDistance: _pDouble(json["auto_accept_max_distance"]) ?? 10.0,
      );

  Map<String, dynamic> toJson() => {
        "auto_accept_enabled": autoAcceptEnabled,
        "auto_accept_min_earning": minEarning,
        "auto_accept_max_distance": maxDistance,
      };
}

/// 15-Second Expiring Offer Model
class TargetedCourierOfferModel {
  final int? id;
  final int? driverId;
  final String? jobType;
  final int? jobId;
  final int? batchId;
  final String? source;
  final String? status;
  final String? offeredAt;
  final String? expiresAt;
  final int remainingSeconds;
  final CourierJobModel? jobDetail;
  final CourierBatchModel? batchDetail;

  TargetedCourierOfferModel({
    this.id,
    this.driverId,
    this.jobType,
    this.jobId,
    this.batchId,
    this.source,
    this.status,
    this.offeredAt,
    this.expiresAt,
    this.remainingSeconds = 15,
    this.jobDetail,
    this.batchDetail,
  });

  factory TargetedCourierOfferModel.fromJson(Map<String, dynamic> json) {
    CourierJobModel? job;
    CourierBatchModel? batch;

    if (json["job_detail"] is Map<String, dynamic>) {
      final detail = Map<String, dynamic>.from(json["job_detail"]);
      if (json["batch_id"] != null || json["job_type"]?.toString().contains('Batch') == true) {
        batch = CourierBatchModel.fromJson(detail);
      } else {
        job = CourierJobModel.fromJson(detail);
      }
    }

    return TargetedCourierOfferModel(
      id: _pInt(json["id"]),
      driverId: _pInt(json["driver_id"]),
      jobType: json["job_type"]?.toString(),
      jobId: _pInt(json["job_id"]),
      batchId: _pInt(json["batch_id"]),
      source: json["source"]?.toString(),
      status: json["status"]?.toString(),
      offeredAt: json["offered_at"]?.toString(),
      expiresAt: json["expires_at"]?.toString(),
      remainingSeconds: _pInt(json["remaining_seconds"]) ?? 15,
      jobDetail: job,
      batchDetail: batch,
    );
  }

  bool get isExpired => remainingSeconds <= 0 || status == 'expired';
  bool get isBatch => batchId != null || batchDetail != null;
}

