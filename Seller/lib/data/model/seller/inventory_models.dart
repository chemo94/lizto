double? _parseD(dynamic v) {
  if (v == null) return null;
  if (v is num) return v.toDouble();
  return double.tryParse(v.toString());
}

int? _parseI(dynamic v) {
  if (v == null) return null;
  if (v is int) return v;
  if (v is num) return v.toInt();
  return int.tryParse(v.toString());
}

class InvItemModel {
  int? id;
  int? sellerId;
  String? name;
  String? category;
  String? unit;
  double? minStock;
  double? cost;
  double? lastCost;
  double? salePrice;
  double? stock;
  String? itemType;
  String? taxType;
  bool isBarItem;
  String? barCategory;
  String? sunatCode;
  String? status;

  InvItemModel({
    this.id,
    this.sellerId,
    this.name,
    this.category,
    this.unit,
    this.minStock,
    this.cost,
    this.lastCost,
    this.salePrice,
    this.stock,
    this.itemType,
    this.taxType,
    this.isBarItem = false,
    this.barCategory,
    this.sunatCode,
    this.status,
  });

  factory InvItemModel.fromJson(Map<String, dynamic> json) => InvItemModel(
        id: _parseI(json['id']),
        sellerId: _parseI(json['seller_id']),
        name: json['name']?.toString(),
        category: json['category']?.toString(),
        unit: json['unit']?.toString() ?? 'UNIDAD',
        minStock: _parseD(json['min_stock']) ?? 0.0,
        cost: _parseD(json['cost']) ?? 0.0,
        lastCost: _parseD(json['last_cost']) ?? 0.0,
        salePrice: _parseD(json['sale_price']),
        stock: _parseD(json['stock']) ?? 0.0,
        itemType: json['item_type']?.toString() ?? 'insumo',
        taxType: json['tax_type']?.toString() ?? 'gravado',
        isBarItem: json['is_bar_item'] == true || json['is_bar_item'] == 1 || json['is_bar_item']?.toString() == '1',
        barCategory: json['bar_category']?.toString(),
        sunatCode: json['sunat_code']?.toString(),
        status: json['status']?.toString() ?? 'active',
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'category': category,
        'unit': unit,
        'min_stock': minStock,
        'cost': cost,
        'sale_price': salePrice,
        'tax_type': taxType,
        'is_bar_item': isBarItem ? 1 : 0,
        'bar_category': barCategory,
        'sunat_code': sunatCode,
      };

  bool get isOutOfStock => (stock ?? 0) <= 0;
  bool get isLowStock => (stock ?? 0) <= (minStock ?? 0) && (minStock ?? 0) > 0;
  double get totalValuation => (stock ?? 0) * (cost ?? 0);
}

class InvRecipeItemModel {
  int? id;
  int? recipeId;
  int? itemId;
  double? quantityGross;
  double? wastePct;
  double? quantityNet;
  String? unit;
  String? notes;
  InvItemModel? item;

  InvRecipeItemModel({
    this.id,
    this.recipeId,
    this.itemId,
    this.quantityGross,
    this.wastePct,
    this.quantityNet,
    this.unit,
    this.notes,
    this.item,
  });

  factory InvRecipeItemModel.fromJson(Map<String, dynamic> json) => InvRecipeItemModel(
        id: _parseI(json['id']),
        recipeId: _parseI(json['recipe_id']),
        itemId: _parseI(json['item_id']),
        quantityGross: _parseD(json['quantity_gross']) ?? 0.0,
        wastePct: _parseD(json['waste_pct']) ?? 0.0,
        quantityNet: _parseD(json['quantity_net']) ?? 0.0,
        unit: json['unit']?.toString() ?? 'UNIDAD',
        notes: json['notes']?.toString(),
        item: json['item'] != null ? InvItemModel.fromJson(json['item']) : null,
      );

  Map<String, dynamic> toJson() => {
        'item_id': itemId,
        'quantity_gross': quantityGross,
        'waste_pct': wastePct,
        'quantity_net': quantityNet,
        'unit': unit,
        'notes': notes,
      };

  double get ingredientCost => (quantityNet ?? quantityGross ?? 0) * (item?.cost ?? 0);
}

class InvRecipeModel {
  int? id;
  int? sellerId;
  int? productId;
  String? name;
  String? recipeType; // kitchen, bar
  double? portions;
  String? unitProduced;
  String? notes;
  String? status;
  double? totalCost;
  double? costPerPortion;
  double? productPrice;
  double? margin;
  double? suggestedPrice;
  String? productName;
  List<InvRecipeItemModel> items;

  InvRecipeModel({
    this.id,
    this.sellerId,
    this.productId,
    this.name,
    this.recipeType,
    this.portions,
    this.unitProduced,
    this.notes,
    this.status,
    this.totalCost,
    this.costPerPortion,
    this.productPrice,
    this.margin,
    this.suggestedPrice,
    this.productName,
    this.items = const [],
  });

  factory InvRecipeModel.fromJson(Map<String, dynamic> json) {
    List<InvRecipeItemModel> parsedItems = [];
    if (json['items'] != null && json['items'] is List) {
      parsedItems = (json['items'] as List)
          .map((e) => InvRecipeItemModel.fromJson(e as Map<String, dynamic>))
          .toList();
    }

    String? pName;
    if (json['product'] != null && json['product'] is Map) {
      pName = json['product']['name']?.toString();
    }

    return InvRecipeModel(
      id: _parseI(json['id']),
      sellerId: _parseI(json['seller_id']),
      productId: _parseI(json['product_id']),
      name: json['name']?.toString(),
      recipeType: json['recipe_type']?.toString() ?? 'kitchen',
      portions: _parseD(json['portions']) ?? 1.0,
      unitProduced: json['unit_produced']?.toString() ?? 'porcion',
      notes: json['notes']?.toString(),
      status: json['status']?.toString() ?? 'active',
      totalCost: _parseD(json['total_cost']) ?? 0.0,
      costPerPortion: _parseD(json['cost_per_portion']) ?? 0.0,
      productPrice: _parseD(json['product_price']) ?? 0.0,
      margin: _parseD(json['margin']) ?? 0.0,
      suggestedPrice: _parseD(json['suggested_price']) ?? 0.0,
      productName: pName,
      items: parsedItems,
    );
  }

  bool get isKitchen => recipeType == 'kitchen';
  bool get isBar => recipeType == 'bar';

  double get calculatedTotalCost {
    if (totalCost != null && totalCost! > 0) return totalCost!;
    double sum = 0;
    for (var it in items) {
      sum += it.ingredientCost;
    }
    return sum;
  }

  double get calculatedCostPerPortion {
    if (costPerPortion != null && costPerPortion! > 0) return costPerPortion!;
    final p = portions ?? 1.0;
    return p > 0 ? calculatedTotalCost / p : 0.0;
  }

  double calculatedMargin([double? price]) {
    final pr = price ?? productPrice ?? 0.0;
    if (pr <= 0) return 0.0;
    return ((pr - calculatedCostPerPortion) / pr) * 100;
  }

  double calculatedSuggestedPrice([double targetMarginPct = 35.0]) {
    final target = targetMarginPct / 100;
    if (target >= 1.0 || calculatedCostPerPortion <= 0) return 0.0;
    return calculatedCostPerPortion / (1 - target);
  }
}

class InvProductionModel {
  int? id;
  int? sellerId;
  int? recipeId;
  int? productId;
  double? portionsProduced;
  String? producedAt;
  String? notes;
  String? status;
  String? recipeName;
  String? unitProduced;
  String? productName;

  InvProductionModel({
    this.id,
    this.sellerId,
    this.recipeId,
    this.productId,
    this.portionsProduced,
    this.producedAt,
    this.notes,
    this.status,
    this.recipeName,
    this.unitProduced,
    this.productName,
  });

  factory InvProductionModel.fromJson(Map<String, dynamic> json) => InvProductionModel(
        id: _parseI(json['id']),
        sellerId: _parseI(json['seller_id']),
        recipeId: _parseI(json['recipe_id']),
        productId: _parseI(json['product_id']),
        portionsProduced: _parseD(json['portions_produced']) ?? 0.0,
        producedAt: json['produced_at']?.toString() ?? json['created_at']?.toString(),
        notes: json['notes']?.toString(),
        status: json['status']?.toString() ?? 'completed',
        recipeName: json['recipe'] != null ? json['recipe']['name']?.toString() : null,
        unitProduced: json['recipe'] != null ? json['recipe']['unit_produced']?.toString() : 'porcion',
        productName: json['product'] != null ? json['product']['name']?.toString() : null,
      );

  bool get isCompleted => status == 'completed';
  bool get isVoided => status == 'voided';
}

class InvSupplierModel {
  int? id;
  int? sellerId;
  String? name;
  String? documentType;
  String? documentNumber;
  String? phone;
  String? email;
  String? contactName;
  String? address;
  String? notes;
  String? status;

  InvSupplierModel({
    this.id,
    this.sellerId,
    this.name,
    this.documentType,
    this.documentNumber,
    this.phone,
    this.email,
    this.contactName,
    this.address,
    this.notes,
    this.status,
  });

  factory InvSupplierModel.fromJson(Map<String, dynamic> json) => InvSupplierModel(
        id: _parseI(json['id']),
        sellerId: _parseI(json['seller_id']),
        name: json['name']?.toString(),
        documentType: json['document_type']?.toString() ?? '6',
        documentNumber: json['document_number']?.toString(),
        phone: json['phone']?.toString(),
        email: json['email']?.toString(),
        contactName: json['contact_name']?.toString(),
        address: json['address']?.toString(),
        notes: json['notes']?.toString(),
        status: json['status']?.toString() ?? 'active',
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'document_type': documentType,
        'document_number': documentNumber,
        'phone': phone,
        'email': email,
        'contact_name': contactName,
        'address': address,
        'notes': notes,
      };
}

class InvPurchaseItemModel {
  int? id;
  int? purchaseId;
  int? itemId;
  double? quantity;
  double? unitCost;
  double? total;
  InvItemModel? item;

  InvPurchaseItemModel({
    this.id,
    this.purchaseId,
    this.itemId,
    this.quantity,
    this.unitCost,
    this.total,
    this.item,
  });

  factory InvPurchaseItemModel.fromJson(Map<String, dynamic> json) => InvPurchaseItemModel(
        id: _parseI(json['id']),
        purchaseId: _parseI(json['purchase_id']),
        itemId: _parseI(json['item_id']),
        quantity: _parseD(json['quantity']) ?? 0.0,
        unitCost: _parseD(json['unit_cost']) ?? 0.0,
        total: _parseD(json['total']) ?? 0.0,
        item: json['item'] != null ? InvItemModel.fromJson(json['item']) : null,
      );
}

class InvPurchaseModel {
  int? id;
  int? sellerId;
  int? supplierId;
  String? documentType;
  String? documentSeries;
  String? documentNumber;
  String? documentDate;
  double? subtotal;
  double? igv;
  double? total;
  String? paymentMethod;
  String? notes;
  InvSupplierModel? supplier;
  List<InvPurchaseItemModel> items;

  InvPurchaseModel({
    this.id,
    this.sellerId,
    this.supplierId,
    this.documentType,
    this.documentSeries,
    this.documentNumber,
    this.documentDate,
    this.subtotal,
    this.igv,
    this.total,
    this.paymentMethod,
    this.notes,
    this.supplier,
    this.items = const [],
  });

  factory InvPurchaseModel.fromJson(Map<String, dynamic> json) {
    List<InvPurchaseItemModel> parsedItems = [];
    if (json['items'] != null && json['items'] is List) {
      parsedItems = (json['items'] as List)
          .map((e) => InvPurchaseItemModel.fromJson(e as Map<String, dynamic>))
          .toList();
    }

    return InvPurchaseModel(
      id: _parseI(json['id']),
      sellerId: _parseI(json['seller_id']),
      supplierId: _parseI(json['supplier_id']),
      documentType: json['document_type']?.toString() ?? '01',
      documentSeries: json['document_series']?.toString(),
      documentNumber: json['document_number']?.toString(),
      documentDate: json['document_date']?.toString(),
      subtotal: _parseD(json['subtotal']) ?? 0.0,
      igv: _parseD(json['igv']) ?? 0.0,
      total: _parseD(json['total']) ?? 0.0,
      paymentMethod: json['payment_method']?.toString() ?? 'cash',
      notes: json['notes']?.toString(),
      supplier: json['supplier'] != null ? InvSupplierModel.fromJson(json['supplier']) : null,
      items: parsedItems,
    );
  }

  String get fullDocNumber => [documentSeries, documentNumber].where((e) => e != null && e.isNotEmpty).join('-');
  String get documentName => documentType == '01' ? 'Factura' : (documentType == '03' ? 'Boleta' : 'Comprobante');
}

class InvWasteModel {
  int? id;
  int? sellerId;
  int? itemId;
  double? quantity;
  String? unit;
  String? reason;
  String? wasteDate;
  String? notes;
  InvItemModel? item;

  InvWasteModel({
    this.id,
    this.sellerId,
    this.itemId,
    this.quantity,
    this.unit,
    this.reason,
    this.wasteDate,
    this.notes,
    this.item,
  });

  factory InvWasteModel.fromJson(Map<String, dynamic> json) => InvWasteModel(
        id: _parseI(json['id']),
        sellerId: _parseI(json['seller_id']),
        itemId: _parseI(json['item_id']),
        quantity: _parseD(json['quantity']) ?? 0.0,
        unit: json['unit']?.toString() ?? 'UNIDAD',
        reason: json['reason']?.toString(),
        wasteDate: json['waste_date']?.toString(),
        notes: json['notes']?.toString(),
        item: json['item'] != null ? InvItemModel.fromJson(json['item']) : null,
      );

  double get estimatedCostLoss => (quantity ?? 0) * (item?.cost ?? 0);
}

class InvKardexModel {
  int? id;
  int? sellerId;
  int? itemId;
  int? warehouseId;
  String? type; // entrada, salida
  String? referenceType;
  int? referenceId;
  double? quantity;
  double? unitCost;
  double? totalCost;
  double? balanceStock;
  String? description;
  String? createdAt;
  InvItemModel? item;

  InvKardexModel({
    this.id,
    this.sellerId,
    this.itemId,
    this.warehouseId,
    this.type,
    this.referenceType,
    this.referenceId,
    this.quantity,
    this.unitCost,
    this.totalCost,
    this.balanceStock,
    this.description,
    this.createdAt,
    this.item,
  });

  factory InvKardexModel.fromJson(Map<String, dynamic> json) => InvKardexModel(
        id: _parseI(json['id']),
        sellerId: _parseI(json['seller_id']),
        itemId: _parseI(json['item_id']),
        warehouseId: _parseI(json['warehouse_id']),
        type: json['type']?.toString() ?? 'entrada',
        referenceType: json['reference_type']?.toString(),
        referenceId: _parseI(json['reference_id']),
        quantity: _parseD(json['quantity']) ?? 0.0,
        unitCost: _parseD(json['unit_cost']) ?? 0.0,
        totalCost: _parseD(json['total_cost']) ?? 0.0,
        balanceStock: _parseD(json['balance_stock']) ?? 0.0,
        description: json['description']?.toString(),
        createdAt: json['created_at']?.toString(),
        item: json['item'] != null ? InvItemModel.fromJson(json['item']) : null,
      );

  bool get isEntrada => type == 'entrada';
  bool get isSalida => type == 'salida';
}

class InventoryOptionModel {
  final String code;
  final String name;

  InventoryOptionModel({required this.code, required this.name});

  factory InventoryOptionModel.fromJson(Map<String, dynamic> json) => InventoryOptionModel(
        code: json['code']?.toString() ?? '',
        name: json['name']?.toString() ?? '',
      );

  Map<String, dynamic> toJson() => {
        'code': code,
        'name': name,
      };
}

class InventoryMetadataModel {
  List<InventoryOptionModel> units;
  List<InventoryOptionModel> taxTypes;
  List<InventoryOptionModel> documentTypes;
  List<InventoryOptionModel> paymentMethods;
  List<String> wasteReasons;
  String currencySymbol;
  String currencyText;
  double taxRatePercent;
  double targetMarginPercent;
  String defaultTaxType;
  bool hasBar;

  InventoryMetadataModel({
    this.units = const [],
    this.taxTypes = const [],
    this.documentTypes = const [],
    this.paymentMethods = const [],
    this.wasteReasons = const [],
    this.currencySymbol = 'S/',
    this.currencyText = 'PEN',
    this.taxRatePercent = 18.0,
    this.targetMarginPercent = 35.0,
    this.defaultTaxType = 'gravado',
    this.hasBar = true,
  });

  factory InventoryMetadataModel.fromJson(Map<String, dynamic> json) {
    return InventoryMetadataModel(
      units: (json['units'] as List?)
              ?.map((e) => InventoryOptionModel.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList() ??
          [],
      taxTypes: (json['tax_types'] as List?)
              ?.map((e) => InventoryOptionModel.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList() ??
          [],
      documentTypes: (json['document_types'] as List?)
              ?.map((e) => InventoryOptionModel.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList() ??
          [],
      paymentMethods: (json['payment_methods'] as List?)
              ?.map((e) => InventoryOptionModel.fromJson(Map<String, dynamic>.from(e as Map)))
              .toList() ??
          [],
      wasteReasons: (json['waste_reasons'] as List?)?.map((e) => e.toString()).toList() ?? [],
      currencySymbol: json['currency_symbol']?.toString() ?? 'S/',
      currencyText: json['currency_text']?.toString() ?? 'PEN',
      taxRatePercent: _parseD(json['tax_rate_percent']) ?? 18.0,
      targetMarginPercent: _parseD(json['target_margin_percent']) ?? 35.0,
      defaultTaxType: json['default_tax_type']?.toString() ?? 'gravado',
      hasBar: json['has_bar'] == true || json['has_bar']?.toString() == '1',
    );
  }
}

