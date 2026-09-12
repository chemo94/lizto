import 'package:flutter/material.dart';

class GeneralCategoryModel {
  int? id;
  String? name;
  String? image;
  String? slug;
  int? status;
  int? sortOrder;

  GeneralCategoryModel({this.id, this.name, this.image, this.slug, this.status, this.sortOrder});

  factory GeneralCategoryModel.fromJson(Map<String, dynamic> json) => GeneralCategoryModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        name: json["name"]?.toString(),
        image: json["image"]?.toString(),
        slug: json["slug"]?.toString(),
        status: json["status"] is int ? json["status"] : int.tryParse(json["status"]?.toString() ?? ''),
        sortOrder: json["sort_order"] is int ? json["sort_order"] : int.tryParse(json["sort_order"]?.toString() ?? ''),
      );

  bool get isFavorCategory => slug == 'servicio-de-favores' || (name?.toLowerCase().contains('favor') ?? false);
}

class SubCategoryModel {
  int? id;
  int? generalCategoryId;
  String? name;
  String? image;
  int? status;
  int? sortOrder;

  SubCategoryModel({this.id, this.generalCategoryId, this.name, this.image, this.status, this.sortOrder});

  factory SubCategoryModel.fromJson(Map<String, dynamic> json) => SubCategoryModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        generalCategoryId: json["general_category_id"] is int ? json["general_category_id"] : int.tryParse(json["general_category_id"]?.toString() ?? ''),
        name: json["name"]?.toString(),
        image: json["image"]?.toString(),
        status: json["status"] is int ? json["status"] : int.tryParse(json["status"]?.toString() ?? ''),
        sortOrder: json["sort_order"] is int ? json["sort_order"] : int.tryParse(json["sort_order"]?.toString() ?? ''),
      );
}

class StoreModel {
  int? id;
  int? sellerId;
  int? subCategoryId;
  String? name;
  String? image;
  String? coverImage;
  String? description;
  String? address;
  double? deliveryFee;
  double? minOrderAmount;
  bool? isOpen;
  String? openingTime;
  String? closingTime;
  double? latitude;
  double? longitude;
  int? preparationTime;
  double? distance;
  double? rating;
  int? totalOrders;
  bool? isPremium;
  bool? isFeatured;
  List<dynamic>? activePackages;

  StoreModel({
    this.id,
    this.sellerId,
    this.subCategoryId,
    this.name,
    this.image,
    this.coverImage,
    this.description,
    this.address,
    this.deliveryFee,
    this.minOrderAmount,
    this.isOpen,
    this.openingTime,
    this.closingTime,
    this.latitude,
    this.longitude,
    this.preparationTime,
    this.distance,
    this.rating,
    this.totalOrders,
    this.isPremium,
    this.isFeatured,
    this.activePackages,
  });

  factory StoreModel.fromJson(Map<String, dynamic> json) => StoreModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        sellerId: json["seller_id"] is int ? json["seller_id"] : int.tryParse(json["seller_id"]?.toString() ?? ''),
        subCategoryId: json["sub_category_id"] is int ? json["sub_category_id"] : int.tryParse(json["sub_category_id"]?.toString() ?? ''),
        name: json["name"]?.toString(),
        image: json["image"]?.toString(),
        coverImage: json["cover_image"]?.toString(),
        description: json["description"]?.toString(),
        address: json["address"]?.toString(),
        deliveryFee: (json["delivery_fee"] is num) ? (json["delivery_fee"] as num).toDouble() : double.tryParse(json["delivery_fee"]?.toString() ?? '0'),
        minOrderAmount: (json["min_order_amount"] is num) ? (json["min_order_amount"] as num).toDouble() : double.tryParse(json["min_order_amount"]?.toString() ?? '0'),
        isOpen: json["is_open"] == 1 || json["is_open"] == true || json["is_open_now"] == true,
        openingTime: json["opening_time"]?.toString() ?? '08:00',
        closingTime: json["closing_time"]?.toString() ?? '22:00',
        latitude: (json["latitude"] is num) ? (json["latitude"] as num).toDouble() : double.tryParse(json["latitude"]?.toString() ?? ''),
        longitude: (json["longitude"] is num) ? (json["longitude"] as num).toDouble() : double.tryParse(json["longitude"]?.toString() ?? ''),
        preparationTime: json["preparation_time"] is int ? json["preparation_time"] : int.tryParse(json["preparation_time"]?.toString() ?? ''),
        distance: (json["distance"] is num) ? (json["distance"] as num).toDouble() : double.tryParse(json["distance"]?.toString() ?? ''),
        rating: (json["rating"] is num) ? (json["rating"] as num).toDouble() : double.tryParse(json["rating"]?.toString() ?? ''),
        totalOrders: json["total_orders"] is int ? json["total_orders"] : int.tryParse(json["total_orders"]?.toString() ?? ''),
        isPremium: json["is_premium"] == true || json["is_premium"] == 1,
        isFeatured: json["is_featured"] == true || json["is_featured"] == 1,
        activePackages: json["active_packages"] as List<dynamic>?,
      );

  bool get isOpenNow {
    if (openingTime == null || closingTime == null) return isOpen ?? true;
    try {
      var now = DateTime.now();
      var open = DateTime.parse('2000-01-01 $openingTime');
      var close = DateTime.parse('2000-01-01 $closingTime');
      var current = DateTime(2000, 1, 1, now.hour, now.minute);
      if (close.isBefore(open)) {
        return current.isAfter(open) || current.isBefore(close);
      }
      return current.isAfter(open) && current.isBefore(close);
    } catch (_) {
      return isOpen ?? true;
    }
  }

  String? get distanceFormatted {
    if (distance == null) return null;
    if (distance! < 1) return '${(distance! * 1000).toStringAsFixed(0)} m';
    return '${distance!.toStringAsFixed(1)} km';
  }

  Map<String, dynamic> toJson() => {
        "id": id,
        "seller_id": sellerId,
        "sub_category_id": subCategoryId,
        "name": name,
        "image": image,
        "cover_image": coverImage,
        "description": description,
        "address": address,
        "delivery_fee": deliveryFee,
        "min_order_amount": minOrderAmount,
        "is_open": isOpen,
        "opening_time": openingTime,
        "closing_time": closingTime,
        "latitude": latitude,
        "longitude": longitude,
        "preparation_time": preparationTime,
        "distance": distance,
      };
}

class StoreCategoryModel {
  int? id;
  int? storeId;
  String? name;
  String? image;
  int? sortOrder;
  List<ProductModel>? products;

  StoreCategoryModel({this.id, this.storeId, this.name, this.image, this.sortOrder, this.products});

  factory StoreCategoryModel.fromJson(Map<String, dynamic> json) => StoreCategoryModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        storeId: json["store_id"] is int ? json["store_id"] : int.tryParse(json["store_id"]?.toString() ?? ''),
        name: json["name"]?.toString(),
        image: json["image"]?.toString(),
        sortOrder: json["sort_order"] is int ? json["sort_order"] : int.tryParse(json["sort_order"]?.toString() ?? ''),
        products: json["products"] != null ? (json["products"] as List).map((x) => ProductModel.fromJson(x)).toList() : null,
      );
}

class ProductVariationModel {
  int? id;
  int? productId;
  String? name;
  double? price;
  int? status;

  ProductVariationModel({this.id, this.productId, this.name, this.price, this.status});

  factory ProductVariationModel.fromJson(Map<String, dynamic> json) => ProductVariationModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        productId: json["product_id"] is int ? json["product_id"] : int.tryParse(json["product_id"]?.toString() ?? ''),
        name: json["name"]?.toString(),
        price: (json["price"] is num) ? (json["price"] as num).toDouble() : double.tryParse(json["price"]?.toString() ?? '0'),
        status: json["status"] is int ? json["status"] : int.tryParse(json["status"]?.toString() ?? ''),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "product_id": productId,
        "name": name,
        "price": price,
        "status": status,
      };
}

class ProductAddonModel {
  int? id;
  int? productId;
  String? name;
  double? price;
  int? status;

  ProductAddonModel({this.id, this.productId, this.name, this.price, this.status});

  factory ProductAddonModel.fromJson(Map<String, dynamic> json) => ProductAddonModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        productId: json["product_id"] is int ? json["product_id"] : int.tryParse(json["product_id"]?.toString() ?? ''),
        name: json["name"]?.toString(),
        price: (json["price"] is num) ? (json["price"] as num).toDouble() : double.tryParse(json["price"]?.toString() ?? '0'),
        status: json["status"] is int ? json["status"] : int.tryParse(json["status"]?.toString() ?? ''),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "product_id": productId,
        "name": name,
        "price": price,
        "status": status,
      };
}

class ProductModel {
  int? id;
  int? storeId;
  int? storeCategoryId;
  String? name;
  String? image;
  String? description;
  double? price;
  double? discountPrice;
  int? status;
  List<ProductVariationModel>? variations;
  List<ProductAddonModel>? addons;

  ProductModel({
    this.id,
    this.storeId,
    this.storeCategoryId,
    this.name,
    this.image,
    this.description,
    this.price,
    this.discountPrice,
    this.status,
    this.variations,
    this.addons,
  });

  factory ProductModel.fromJson(Map<String, dynamic> json) => ProductModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        storeId: json["store_id"] is int ? json["store_id"] : int.tryParse(json["store_id"]?.toString() ?? ''),
        storeCategoryId: json["store_category_id"] is int ? json["store_category_id"] : int.tryParse(json["store_category_id"]?.toString() ?? ''),
        name: json["name"]?.toString(),
        image: json["image"]?.toString(),
        description: json["description"]?.toString(),
        price: (json["price"] is num) ? (json["price"] as num).toDouble() : double.tryParse(json["price"]?.toString() ?? '0'),
        discountPrice: (json["discount_price"] is num) ? (json["discount_price"] as num).toDouble() : double.tryParse(json["discount_price"]?.toString() ?? ''),
        status: json["status"] is int ? json["status"] : int.tryParse(json["status"]?.toString() ?? ''),
        variations: json["variations"] != null ? (json["variations"] as List).map((x) => ProductVariationModel.fromJson(x)).toList() : null,
        addons: json["addons"] != null ? (json["addons"] as List).map((x) => ProductAddonModel.fromJson(x)).toList() : null,
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "store_id": storeId,
        "store_category_id": storeCategoryId,
        "name": name,
        "image": image,
        "description": description,
        "price": price,
        "discount_price": discountPrice,
        "status": status,
        "variations": variations?.map((x) => x.toJson()).toList(),
        "addons": addons?.map((x) => x.toJson()).toList(),
      };

  double get finalPrice => discountPrice ?? price ?? 0;
}

class CartItemModel {
  ProductModel product;
  int quantity;
  ProductVariationModel? selectedVariation;
  List<ProductAddonModel> selectedAddons;
  StoreModel? store;

  CartItemModel({
    required this.product,
    this.quantity = 1,
    this.selectedVariation,
    this.selectedAddons = const [],
    this.store,
  });

  factory CartItemModel.fromJson(Map<String, dynamic> json) => CartItemModel(
        product: ProductModel.fromJson(json["product"]),
        quantity: json["quantity"] is int ? json["quantity"] : int.tryParse(json["quantity"]?.toString() ?? '') ?? 1,
        selectedVariation: json["selectedVariation"] != null ? ProductVariationModel.fromJson(json["selectedVariation"]) : null,
        selectedAddons: json["selectedAddons"] != null ? (json["selectedAddons"] as List).map((x) => ProductAddonModel.fromJson(x)).toList() : [],
        store: json["store"] != null ? StoreModel.fromJson(json["store"]) : null,
      );

  Map<String, dynamic> toJson() => {
        "product": product.toJson(),
        "quantity": quantity,
        "selectedVariation": selectedVariation?.toJson(),
        "selectedAddons": selectedAddons.map((x) => x.toJson()).toList(),
        if (store != null) "store": store!.toJson(),
      };

  double get unitPrice {
    double base = (selectedVariation != null && (selectedVariation!.price ?? 0) > 0)
        ? selectedVariation!.price!
        : product.finalPrice;
    for (var a in selectedAddons) {
      base += a.price ?? 0;
    }
    return base;
  }

  double get totalPrice => unitPrice * quantity;

  Map<String, dynamic> toOrderJson() => {
        'product_id': product.id,
        'quantity': quantity,
        'variation_id': selectedVariation?.id,
        'addon_ids': selectedAddons.map((a) => a.id).toList(),
      };
}

class DeliveryOrderItemVariationModel {
  String? variationName;
  double? variationPrice;

  DeliveryOrderItemVariationModel({this.variationName, this.variationPrice});

  factory DeliveryOrderItemVariationModel.fromJson(Map<String, dynamic> json) => DeliveryOrderItemVariationModel(
        variationName: json["variation_name"]?.toString(),
        variationPrice: (json["variation_price"] is num) ? (json["variation_price"] as num).toDouble() : double.tryParse(json["variation_price"]?.toString() ?? '0'),
      );
}

class DeliveryOrderItemAddonModel {
  String? addonName;
  double? addonPrice;

  DeliveryOrderItemAddonModel({this.addonName, this.addonPrice});

  factory DeliveryOrderItemAddonModel.fromJson(Map<String, dynamic> json) => DeliveryOrderItemAddonModel(
        addonName: json["addon_name"]?.toString(),
        addonPrice: (json["addon_price"] is num) ? (json["addon_price"] as num).toDouble() : double.tryParse(json["addon_price"]?.toString() ?? '0'),
      );
}

class DeliveryOrderItemModel {
  int? id;
  int? productId;
  String? productName;
  String? productImage;
  int? quantity;
  double? unitPrice;
  double? totalPrice;
  DeliveryOrderItemVariationModel? variation;
  List<DeliveryOrderItemAddonModel>? addons;

  DeliveryOrderItemModel({
    this.id,
    this.productId,
    this.productName,
    this.productImage,
    this.quantity,
    this.unitPrice,
    this.totalPrice,
    this.variation,
    this.addons,
  });

  factory DeliveryOrderItemModel.fromJson(Map<String, dynamic> json) => DeliveryOrderItemModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        productId: json["product_id"] is int ? json["product_id"] : int.tryParse(json["product_id"]?.toString() ?? ''),
        productName: json["product_name"]?.toString(),
        productImage: json["product_image"]?.toString(),
        quantity: json["quantity"] is int ? json["quantity"] : int.tryParse(json["quantity"]?.toString() ?? ''),
        unitPrice: (json["unit_price"] is num) ? (json["unit_price"] as num).toDouble() : double.tryParse(json["unit_price"]?.toString() ?? '0'),
        totalPrice: (json["total_price"] is num) ? (json["total_price"] as num).toDouble() : double.tryParse(json["total_price"]?.toString() ?? '0'),
        variation: json["variation"] != null ? DeliveryOrderItemVariationModel.fromJson(json["variation"]) : null,
        addons: json["addons"] != null ? (json["addons"] as List).map((x) => DeliveryOrderItemAddonModel.fromJson(x)).toList() : null,
      );
}

class GatewayModel {
  int? id;
  int? code;
  String? name;
  String? image;
  String? currency;
  String? symbol;
  bool isCash;
  double? percentCharge;
  double? fixedCharge;

  GatewayModel({this.id, this.code, this.name, this.image, this.currency, this.symbol, this.isCash = false, this.percentCharge, this.fixedCharge});

  factory GatewayModel.fromJson(Map<String, dynamic> json) => GatewayModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        code: json["code"] is int ? json["code"] : int.tryParse(json["code"]?.toString() ?? ''),
        name: json["name"]?.toString(),
        image: json["image"]?.toString(),
        currency: json["currency"]?.toString(),
        symbol: json["symbol"]?.toString(),
        isCash: json["is_cash"] == true || json["is_cash"] == 1 || json["code"]?.toString() == '0',
        percentCharge: json["percent_charge"] != null ? double.tryParse(json["percent_charge"].toString()) : null,
        fixedCharge: json["fixed_charge"] != null ? double.tryParse(json["fixed_charge"].toString()) : null,
      );
}

class DeliveryOrderModel {
  int? id;
  String? orderNo;
  int? userId;
  int? storeId;
  int? driverId;
  double? subtotal;
  double? deliveryFee;
  double? discount;
  double? tip;
  double? total;
  String? status;
  String? deliveryAddress;
  double? deliveryLat;
  double? deliveryLng;
  String? contactPhone;
  String? contactName;
  String? notes;
  String? cancelReason;
  double? paymentMethodCode;
  String? paymentMethodName;
  int? paymentStatus;
  String? createdAt;
  List<DeliveryOrderItemModel>? items;
  StoreModel? store;
  Map<String, dynamic>? driver;

  DeliveryOrderModel({
    this.id,
    this.orderNo,
    this.userId,
    this.storeId,
    this.driverId,
    this.subtotal,
    this.deliveryFee,
    this.discount,
    this.tip,
    this.total,
    this.status,
    this.deliveryAddress,
    this.deliveryLat,
    this.deliveryLng,
    this.contactPhone,
    this.contactName,
    this.notes,
    this.cancelReason,
    this.paymentMethodCode,
    this.paymentMethodName,
    this.paymentStatus,
    this.createdAt,
    this.items,
    this.store,
    this.driver,
  });

  factory DeliveryOrderModel.fromJson(Map<String, dynamic> json) => DeliveryOrderModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        orderNo: json["order_no"]?.toString(),
        userId: json["user_id"] is int ? json["user_id"] : int.tryParse(json["user_id"]?.toString() ?? ''),
        storeId: json["store_id"] is int ? json["store_id"] : int.tryParse(json["store_id"]?.toString() ?? ''),
        driverId: json["driver_id"] is int ? json["driver_id"] : int.tryParse(json["driver_id"]?.toString() ?? ''),
        subtotal: (json["subtotal"] is num) ? (json["subtotal"] as num).toDouble() : double.tryParse(json["subtotal"]?.toString() ?? '0'),
        deliveryFee: (json["delivery_fee"] is num) ? (json["delivery_fee"] as num).toDouble() : double.tryParse(json["delivery_fee"]?.toString() ?? '0'),
        discount: (json["discount"] is num) ? (json["discount"] as num).toDouble() : double.tryParse(json["discount"]?.toString() ?? '0'),
        tip: (json["tip"] is num) ? (json["tip"] as num).toDouble() : double.tryParse(json["tip"]?.toString() ?? '0'),
        total: (json["total"] is num) ? (json["total"] as num).toDouble() : double.tryParse(json["total"]?.toString() ?? '0'),
        status: json["status"]?.toString(),
        deliveryAddress: json["delivery_address"]?.toString(),
        deliveryLat: (json["delivery_lat"] is num) ? (json["delivery_lat"] as num).toDouble() : double.tryParse(json["delivery_lat"]?.toString() ?? ''),
        deliveryLng: (json["delivery_lng"] is num) ? (json["delivery_lng"] as num).toDouble() : double.tryParse(json["delivery_lng"]?.toString() ?? ''),
        contactPhone: json["contact_phone"]?.toString(),
        contactName: json["contact_name"]?.toString(),
        notes: json["notes"]?.toString(),
        cancelReason: json["cancel_reason"]?.toString(),
        paymentMethodCode: (json["payment_method_code"] is num) ? (json["payment_method_code"] as num).toDouble() : double.tryParse(json["payment_method_code"]?.toString() ?? ''),
        paymentMethodName: json["payment_method_name"]?.toString(),
        paymentStatus: json["payment_status"] is int ? json["payment_status"] : int.tryParse(json["payment_status"]?.toString() ?? ''),
        createdAt: json["created_at"]?.toString(),
        items: json["items"] != null ? (json["items"] as List).map((x) => DeliveryOrderItemModel.fromJson(x)).toList() : null,
        store: json["store"] != null ? StoreModel.fromJson(json["store"]) : null,
        driver: json["driver"],
      );

  String get statusLabel {
    switch (status) {
      case 'pending':
        return 'Pendiente';
      case 'pending_payment':
        return 'Pago pendiente';
      case 'confirmed':
        return 'Confirmado';
      case 'preparing':
        return 'Preparando';
      case 'ready':
        return 'Listo';
      case 'on_way':
        return 'En camino';
      case 'delivered':
        return 'Entregado';
      case 'cancelled':
        return 'Cancelado';
      default:
        return status ?? '';
    }
  }

  Color get statusColor {
    switch (status) {
      case 'pending':
        return const Color(0xFFF59E0B);
      case 'pending_payment':
        return const Color(0xFFEF4444);
      case 'confirmed':
        return const Color(0xFF3B82F6);
      case 'preparing':
        return const Color(0xFF8B5CF6);
      case 'ready':
        return const Color(0xFF10B981);
      case 'on_way':
        return const Color(0xFF06B6D4);
      case 'delivered':
        return const Color(0xFF059669);
      case 'cancelled':
        return const Color(0xFFEF4444);
      default:
        return const Color(0xFF6B7280);
    }
  }

  IconData get statusIcon {
    switch (status) {
      case 'pending':
        return Icons.hourglass_empty_rounded;
      case 'pending_payment':
        return Icons.payment_rounded;
      case 'confirmed':
        return Icons.check_circle_outline_rounded;
      case 'preparing':
        return Icons.restaurant_rounded;
      case 'ready':
        return Icons.inventory_2_rounded;
      case 'on_way':
        return Icons.delivery_dining_rounded;
      case 'delivered':
        return Icons.verified_rounded;
      case 'cancelled':
        return Icons.cancel_rounded;
      default:
        return Icons.help_outline_rounded;
    }
  }
}

class RefundModel {
  int? id;
  int? orderId;
  String? orderNo;
  double? amount;
  String? reason;
  String? status;
  String? adminRemark;
  String? createdAt;
  String? updatedAt;

  RefundModel({
    this.id,
    this.orderId,
    this.orderNo,
    this.amount,
    this.reason,
    this.status,
    this.adminRemark,
    this.createdAt,
    this.updatedAt,
  });

  factory RefundModel.fromJson(Map<String, dynamic> json) => RefundModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        orderId: json["order_id"] is int ? json["order_id"] : int.tryParse(json["order_id"]?.toString() ?? ''),
        orderNo: json["order_no"]?.toString(),
        amount: (json["amount"] is num) ? (json["amount"] as num).toDouble() : double.tryParse(json["amount"]?.toString() ?? ''),
        reason: json["reason"]?.toString(),
        status: json["status"]?.toString(),
        adminRemark: json["admin_remark"]?.toString(),
        createdAt: json["created_at"]?.toString(),
        updatedAt: json["updated_at"]?.toString(),
      );

  String get statusLabel {
    switch (status) {
      case 'pending':
        return 'Pendiente';
      case 'approved':
        return 'Aprobado';
      case 'rejected':
        return 'Rechazado';
      case 'processed':
        return 'Procesado';
      default:
        return status ?? 'Desconocido';
    }
  }

  Color get statusColor {
    switch (status) {
      case 'pending':
        return Colors.orange;
      case 'approved':
        return const Color(0xFF10B981);
      case 'rejected':
        return Colors.red;
      case 'processed':
        return const Color(0xFF10B981);
      default:
        return const Color(0xFF6B7280);
    }
  }
}

class DeliveryPaymentModel {
  int? id;
  int? orderId;
  String? orderNo;
  String? gatewayName;
  String? gatewayCode;
  double? amount;
  String? status;
  String? transactionId;
  String? createdAt;

  DeliveryPaymentModel({
    this.id,
    this.orderId,
    this.orderNo,
    this.gatewayName,
    this.gatewayCode,
    this.amount,
    this.status,
    this.transactionId,
    this.createdAt,
  });

  factory DeliveryPaymentModel.fromJson(Map<String, dynamic> json) => DeliveryPaymentModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        orderId: json["order_id"] is int ? json["order_id"] : int.tryParse(json["order_id"]?.toString() ?? ''),
        orderNo: json["order_no"]?.toString(),
        gatewayName: json["gateway_name"]?.toString(),
        gatewayCode: json["gateway_code"]?.toString(),
        amount: (json["amount"] is num) ? (json["amount"] as num).toDouble() : double.tryParse(json["amount"]?.toString() ?? ''),
        status: json["status"]?.toString(),
        transactionId: json["transaction_id"]?.toString(),
        createdAt: json["created_at"]?.toString(),
      );

  String get statusLabel {
    switch (status) {
      case 'pending':
        return 'Pendiente';
      case 'completed':
        return 'Completado';
      case 'failed':
        return 'Fallido';
      case 'refunded':
        return 'Reembolsado';
      default:
        return status ?? 'Desconocido';
    }
  }

  Color get statusColor {
    switch (status) {
      case 'pending':
        return Colors.orange;
      case 'completed':
        return const Color(0xFF10B981);
      case 'failed':
        return Colors.red;
      case 'refunded':
        return Colors.blue;
      default:
        return const Color(0xFF6B7280);
    }
  }
}
