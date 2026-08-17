class ShoppingListItem {
  int? id;
  int? favorId;
  String? name;
  int? quantity;
  double? unitPrice;
  double? totalPrice;
  String? notes;
  String? imageUrl;
  String? status;
  String? substituteName;
  double? substitutePrice;
  String? substituteImageUrl;
  String? substituteNotes;
  String? storeConfirmedAt;
  String? customerApprovedAt;
  bool? customerApproved;
  int? sortOrder;

  ShoppingListItem({
    this.id, this.favorId, this.name, this.quantity, this.unitPrice,
    this.totalPrice, this.notes, this.imageUrl, this.status,
    this.substituteName, this.substitutePrice, this.substituteImageUrl,
    this.substituteNotes, this.storeConfirmedAt, this.customerApprovedAt,
    this.customerApproved, this.sortOrder,
  });

  factory ShoppingListItem.fromJson(Map<String, dynamic> json) {
    return ShoppingListItem(
      id: _parseInt(json["id"]),
      favorId: _parseInt(json["favor_id"]),
      name: json["name"]?.toString(),
      quantity: _parseInt(json["quantity"]) ?? 1,
      unitPrice: _parseDouble(json["unit_price"]),
      totalPrice: _parseDouble(json["total_price"]),
      notes: json["notes"]?.toString(),
      imageUrl: json["image_url"]?.toString(),
      status: json["status"]?.toString(),
      substituteName: json["substitute_name"]?.toString(),
      substitutePrice: _parseDouble(json["substitute_price"]),
      substituteImageUrl: json["substitute_image_url"]?.toString(),
      substituteNotes: json["substitute_notes"]?.toString(),
      storeConfirmedAt: json["store_confirmed_at"]?.toString(),
      customerApprovedAt: json["customer_approved_at"]?.toString(),
      customerApproved: json["customer_approved"],
      sortOrder: _parseInt(json["sort_order"]),
    );
  }

  Map<String, dynamic> toJson() => {
    if (id != null) 'id': id,
    if (favorId != null) 'favor_id': favorId,
    'name': name,
    'quantity': quantity,
    if (unitPrice != null) 'unit_price': unitPrice,
    if (notes != null) 'notes': notes,
    'sort_order': sortOrder ?? 0,
  };

  String get statusLabel => switch (status) {
    'pending'     => 'Pendiente',
    'found'       => 'Encontrado',
    'not_found'   => 'No encontrado',
    'substituted' => 'Sustituido',
    'cancelled'   => 'Cancelado',
    _             => status ?? '',
  };

  bool get isPending => status == 'pending';
  bool get isFound => status == 'found';
  bool get isNotFound => status == 'not_found';
  bool get isSubstituted => status == 'substituted';
  bool get needsApproval => status == 'not_found' && substituteName != null && customerApprovedAt == null;

  static int? _parseInt(dynamic v) {
    if (v == null) return null;
    if (v is int) return v;
    return int.tryParse(v.toString());
  }

  static double? _parseDouble(dynamic v) {
    if (v == null) return null;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString());
  }
}

class ShoppingBudget {
  int? id;
  int? favorId;
  double? maxProductBudget;
  double? maxDeliveryFee;
  double? actualProductCost;
  double? actualDeliveryFee;
  String? paymentMethod;
  String? paymentStatus;

  ShoppingBudget({
    this.id, this.favorId, this.maxProductBudget, this.maxDeliveryFee,
    this.actualProductCost, this.actualDeliveryFee,
    this.paymentMethod, this.paymentStatus,
  });

  factory ShoppingBudget.fromJson(Map<String, dynamic> json) {
    return ShoppingBudget(
      id: _parseInt(json["id"]),
      favorId: _parseInt(json["favor_id"]),
      maxProductBudget: _parseDouble(json["max_product_budget"]),
      maxDeliveryFee: _parseDouble(json["max_delivery_fee"]),
      actualProductCost: _parseDouble(json["actual_product_cost"]),
      actualDeliveryFee: _parseDouble(json["actual_delivery_fee"]),
      paymentMethod: json["payment_method"]?.toString(),
      paymentStatus: json["payment_status"]?.toString(),
    );
  }

  bool get isOverBudget {
    if (actualProductCost == null || maxProductBudget == null) return false;
    return actualProductCost! > maxProductBudget!;
  }

  double get overageAmount {
    if (!isOverBudget) return 0;
    return actualProductCost! - maxProductBudget!;
  }

  double get estimatedTotal {
    return (actualProductCost ?? maxProductBudget ?? 0) +
           (actualDeliveryFee ?? maxDeliveryFee ?? 0);
  }

  static int? _parseInt(dynamic v) {
    if (v == null) return null;
    if (v is int) return v;
    return int.tryParse(v.toString());
  }

  static double? _parseDouble(dynamic v) {
    if (v == null) return null;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString());
  }
}

class ShoppingSummary {
  int? totalItems;
  int? found;
  int? notFound;
  int? substituted;
  int? pending;
  double? estimatedTotal;
  double? actualTotal;

  ShoppingSummary({
    this.totalItems, this.found, this.notFound,
    this.substituted, this.pending, this.estimatedTotal, this.actualTotal,
  });

  factory ShoppingSummary.fromJson(Map<String, dynamic> json) {
    return ShoppingSummary(
      totalItems: json["total_items"],
      found: json["found"],
      notFound: json["not_found"],
      substituted: json["substituted"],
      pending: json["pending"],
      estimatedTotal: _parseDouble(json["estimated_total"]),
      actualTotal: _parseDouble(json["actual_total"]),
    );
  }

  double get progressPercent {
    if (totalItems == null || totalItems == 0) return 0;
    final completed = (found ?? 0) + (substituted ?? 0);
    return (completed / totalItems!) * 100;
  }

  static double? _parseDouble(dynamic v) {
    if (v == null) return null;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString());
  }
}
