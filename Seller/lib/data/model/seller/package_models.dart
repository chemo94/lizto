import 'dart:convert';

class SellerPackageModel {
  int? id;
  String? name;
  String? type;
  String? description;
  double? price;
  String? icon;
  int? durationDays;
  List<String>? features;

  SellerPackageModel({
    this.id, this.name, this.type, this.description, this.price, this.icon, this.durationDays, this.features,
  });

  factory SellerPackageModel.fromJson(Map<String, dynamic> json) => SellerPackageModel(
    id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
    name: json['name']?.toString(),
    type: json['type']?.toString(),
    description: json['description']?.toString(),
    price: _p(json['price']),
    icon: json['icon']?.toString(),
    durationDays: json['duration_days'] is int ? json['duration_days'] : int.tryParse(json['duration_days']?.toString() ?? ''),
    features: _parseFeatures(json['features']),
  );
}

List<String>? _parseFeatures(dynamic raw) {
  if (raw == null) return null;
  if (raw is List) {
    return List<String>.from(raw.map((x) => x.toString()));
  }
  if (raw is String) {
    final trimmed = raw.trim();
    if (trimmed.isEmpty) return null;
    if (trimmed.startsWith('[')) {
      try {
        final decoded = jsonDecode(trimmed);
        if (decoded is List) return List<String>.from(decoded.map((x) => x.toString()));
      } catch (_) {}
    }
    return trimmed.split(',').map((x) => x.trim()).where((x) => x.isNotEmpty).toList();
  }
  return null;
}

class SellerSubscriptionModel {
  int? id;
  int? storeId;
  SellerPackageModel? package;
  String? status;
  String? purchasedAt;
  String? expiresAt;
  String? packageName;

  SellerSubscriptionModel({
    this.id, this.storeId, this.package, this.status, this.purchasedAt, this.expiresAt, this.packageName,
  });

  DateTime? get expiresAtDate {
    final s = expiresAt;
    if (s == null || s.isEmpty) return null;
    return DateTime.tryParse(s) ?? DateTime.tryParse(s.replaceFirst(' ', 'T'));
  }

  factory SellerSubscriptionModel.fromJson(Map<String, dynamic> json) => SellerSubscriptionModel(
    id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
    storeId: json['store_id'] is int ? json['store_id'] : int.tryParse(json['store_id']?.toString() ?? ''),
    package: json['package'] != null ? SellerPackageModel.fromJson(json['package']) : null,
    status: json['status']?.toString(),
    purchasedAt: json['purchased_at']?.toString(),
    expiresAt: json['expires_at']?.toString(),
    packageName: json['package_name']?.toString() ?? json['package']?['name']?.toString(),
  );
}

class PurchaseResponseModel {
  SellerSubscriptionModel? subscription;
  String? redirectUrl;

  PurchaseResponseModel({this.subscription, this.redirectUrl});

  factory PurchaseResponseModel.fromJson(Map<String, dynamic> json) => PurchaseResponseModel(
    subscription: json['subscription'] != null ? SellerSubscriptionModel.fromJson(json['subscription']) : null,
    redirectUrl: json['redirect_url']?.toString(),
  );
}

class StoreAnalyticsModel {
  int? totalOrders;
  int? completedOrders;
  double? totalRevenue;
  double? avgOrderValue;
  List<DailyRevenueModel> daily = [];
  List<TopProductModel> topProducts = [];

  StoreAnalyticsModel({this.totalOrders, this.completedOrders, this.totalRevenue, this.avgOrderValue, this.daily = const [], this.topProducts = const []});

  factory StoreAnalyticsModel.fromJson(Map<String, dynamic> json) => StoreAnalyticsModel(
    totalOrders: json['summary']?['total_orders'] is int ? json['summary']['total_orders'] : int.tryParse(json['summary']?['total_orders']?.toString() ?? ''),
    completedOrders: json['summary']?['completed_orders'] is int ? json['summary']['completed_orders'] : int.tryParse(json['summary']?['completed_orders']?.toString() ?? ''),
    totalRevenue: _p(json['summary']?['total_revenue']),
    avgOrderValue: _p(json['summary']?['avg_order_value']),
    daily: json['daily'] != null ? (json['daily'] as List).map((x) => DailyRevenueModel.fromJson(x)).toList() : [],
    topProducts: json['top_products'] != null ? (json['top_products'] as List).map((x) => TopProductModel.fromJson(x)).toList() : [],
  );
}

class DailyRevenueModel {
  String? date;
  int? count;
  double? revenue;

  DailyRevenueModel({this.date, this.count, this.revenue});

  factory DailyRevenueModel.fromJson(Map<String, dynamic> json) => DailyRevenueModel(
    date: json['date']?.toString(),
    count: json['count'] is int ? json['count'] : int.tryParse(json['count']?.toString() ?? ''),
    revenue: _p(json['revenue']),
  );
}

class TopProductModel {
  String? productName;
  int? totalQty;
  double? totalSales;

  TopProductModel({this.productName, this.totalQty, this.totalSales});

  factory TopProductModel.fromJson(Map<String, dynamic> json) => TopProductModel(
    productName: json['product_name']?.toString(),
    totalQty: json['total_qty'] is int ? json['total_qty'] : int.tryParse(json['total_qty']?.toString() ?? ''),
    totalSales: _p(json['total_sales']),
  );
}

class StoreQrModel {
  String? qrUrl;

  StoreQrModel({this.qrUrl});

  factory StoreQrModel.fromJson(Map<String, dynamic> json) => StoreQrModel(
    qrUrl: json['qr_url']?.toString(),
  );
}

class NotifyResponseModel {
  int? recipients;

  NotifyResponseModel({this.recipients});

  factory NotifyResponseModel.fromJson(Map<String, dynamic> json) => NotifyResponseModel(
    recipients: json['recipients'] is int ? json['recipients'] : int.tryParse(json['recipients']?.toString() ?? ''),
  );
}

double? _p(dynamic v) {
  if (v == null) return null;
  if (v is double) return v;
  if (v is int) return v.toDouble();
  return double.tryParse(v.toString());
}
