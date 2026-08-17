class PanelTableModel {
  int? id;
  String? name;
  int? areaId;
  String? areaName;
  String? status;
  int? capacity;
  int? posX;
  int? posY;
  String? shape;
  double? activeOrderTotal;
  int? activeOrderItemsCount;

  PanelTableModel({this.id, this.name, this.areaId, this.areaName, this.status, this.capacity, this.posX, this.posY, this.shape, this.activeOrderTotal, this.activeOrderItemsCount});

  factory PanelTableModel.fromJson(Map<String, dynamic> json) => PanelTableModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        name: json['name']?.toString(),
        areaId: json['pos_area_id'] is int ? json['pos_area_id'] : int.tryParse(json['pos_area_id']?.toString() ?? ''),
        areaName: json['area_name']?.toString(),
        status: json['status']?.toString() ?? 'free',
        capacity: json['capacity'] is int ? json['capacity'] : int.tryParse(json['capacity']?.toString() ?? '4'),
        posX: json['pos_x'] is int ? json['pos_x'] : int.tryParse(json['pos_x']?.toString() ?? '0'),
        posY: json['pos_y'] is int ? json['pos_y'] : int.tryParse(json['pos_y']?.toString() ?? '0'),
        shape: json['shape']?.toString() ?? 'square',
        activeOrderTotal: _pd(json['active_order_total']),
        activeOrderItemsCount: json['active_order_items_count'] is int ? json['active_order_items_count'] : int.tryParse(json['active_order_items_count']?.toString() ?? ''),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'pos_area_id': areaId,
        'capacity': capacity,
        'pos_x': posX,
        'pos_y': posY,
        'status': status,
        'shape': shape,
      };

  bool get isFree => status == 'free';
  bool get isOccupied => status == 'occupied';
}

class PanelAreaModel {
  int? id;
  String? name;
  List<PanelTableModel> tables = [];

  PanelAreaModel({this.id, this.name, this.tables = const []});

  factory PanelAreaModel.fromJson(Map<String, dynamic> json) => PanelAreaModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        name: json['name']?.toString(),
        tables: json['tables'] != null ? (json['tables'] as List).map((x) => PanelTableModel.fromJson(x)).toList() : [],
      );
}

class KitchenOrderModel {
  int? id;
  String? orderNo;
  String? orderType;
  String? table;
  String? customerName;
  String? status;
  String? kitchenNotes;
  double? total;
  String? createdAt;
  List<KitchenItemModel> items = [];

  KitchenOrderModel({this.id, this.orderNo, this.orderType, this.table, this.customerName, this.status, this.kitchenNotes, this.total, this.createdAt, this.items = const []});

  factory KitchenOrderModel.fromJson(Map<String, dynamic> json) => KitchenOrderModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        orderNo: json['order_no']?.toString(),
        orderType: json['order_type']?.toString(),
        table: json['table']?.toString(),
        customerName: json['customer_name']?.toString(),
        status: json['status']?.toString(),
        kitchenNotes: json['kitchen_notes']?.toString(),
        total: _pd(json['total']),
        createdAt: json['created_at']?.toString(),
        items: json['items'] != null ? (json['items'] as List).map((x) => KitchenItemModel.fromJson(x)).toList() : [],
      );
}

class KitchenItemModel {
  int? id;
  String? name;
  int? qty;
  String? status;

  KitchenItemModel({this.id, this.name, this.qty, this.status});

  factory KitchenItemModel.fromJson(Map<String, dynamic> json) => KitchenItemModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        name: json['name']?.toString(),
        qty: json['qty'] is int ? json['qty'] : int.tryParse(json['qty']?.toString() ?? '1'),
        status: json['status']?.toString() ?? 'pending',
      );
}

class PanelDashboardModel {
  int? posTodayCount;
  double? posTodaySales;
  int? delTodayCount;
  double? delTodaySales;
  int? posMonthCount;
  int? delMonthCount;
  int? kitchenPending;
  int? tablesOccupied;
  double? walletBalance;
  double? receivableBalance;

  PanelDashboardModel({this.posTodayCount, this.posTodaySales, this.delTodayCount, this.delTodaySales, this.posMonthCount, this.delMonthCount, this.kitchenPending, this.tablesOccupied, this.walletBalance, this.receivableBalance});

  factory PanelDashboardModel.fromJson(Map<String, dynamic> json) => PanelDashboardModel(
        posTodayCount: json['pos_today_count'] is int ? json['pos_today_count'] : int.tryParse(json['pos_today_count']?.toString() ?? ''),
        posTodaySales: _pd(json['pos_today_sales']),
        delTodayCount: json['del_today_count'] is int ? json['del_today_count'] : int.tryParse(json['del_today_count']?.toString() ?? ''),
        delTodaySales: _pd(json['del_today_sales']),
        posMonthCount: json['pos_month_count'] is int ? json['pos_month_count'] : int.tryParse(json['pos_month_count']?.toString() ?? ''),
        delMonthCount: json['del_month_count'] is int ? json['del_month_count'] : int.tryParse(json['del_month_count']?.toString() ?? ''),
        kitchenPending: json['kitchen_pending'] is int ? json['kitchen_pending'] : int.tryParse(json['kitchen_pending']?.toString() ?? ''),
        tablesOccupied: json['tables_occupied'] is int ? json['tables_occupied'] : int.tryParse(json['tables_occupied']?.toString() ?? ''),
        walletBalance: _pd(json['wallet']?['balance']),
        receivableBalance: _pd(json['stats']?['receivable_balance'] ?? json['receivable_balance']),
      );
}

class PanelCashModel {
  CashSessionModel? openSession;
  List<CashTransactionModel> transactions = [];
  List<CashSessionModel> sessions = [];

  PanelCashModel({this.openSession, this.transactions = const [], this.sessions = const []});

  factory PanelCashModel.fromJson(Map<String, dynamic> json) => PanelCashModel(
        openSession: json['openSession'] != null ? CashSessionModel.fromJson(json['openSession']) : null,
        transactions: json['transactions'] != null ? (json['transactions'] as List).map((x) => CashTransactionModel.fromJson(x)).toList() : [],
        sessions: json['sessions'] != null ? (json['sessions'] as List).map((x) => CashSessionModel.fromJson(x)).toList() : [],
      );
}

class CashSessionModel {
  int? id;
  double? openingBalance;
  double? totalSales;
  double? totalExpenses;
  double? closingBalance;
  String? status;
  String? openedAt;
  String? closedAt;

  CashSessionModel({this.id, this.openingBalance, this.totalSales, this.totalExpenses, this.closingBalance, this.status, this.openedAt, this.closedAt});

  factory CashSessionModel.fromJson(Map<String, dynamic> json) => CashSessionModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        openingBalance: _pd(json['opening_balance']),
        totalSales: _pd(json['total_sales']),
        totalExpenses: _pd(json['total_expenses']),
        closingBalance: _pd(json['closing_balance']),
        status: json['status']?.toString(),
        openedAt: json['opened_at']?.toString(),
        closedAt: json['closed_at']?.toString(),
      );
}

class CashTransactionModel {
  int? id;
  String? type;
  double? amount;
  String? description;
  String? createdAt;

  CashTransactionModel({this.id, this.type, this.amount, this.description, this.createdAt});

  factory CashTransactionModel.fromJson(Map<String, dynamic> json) => CashTransactionModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        type: json['type']?.toString(),
        amount: _pd(json['amount']),
        description: json['description']?.toString(),
        createdAt: json['created_at']?.toString(),
      );
}

class PanelBillingModel {
  bool? isCashOpen;
  List<BillingOrderModel> pending = [];
  List<BillingOrderModel> paid = [];
  List<InvoiceTypeModel> invoiceTypes = [];

  PanelBillingModel({this.isCashOpen, this.pending = const [], this.paid = const [], this.invoiceTypes = const []});

  factory PanelBillingModel.fromJson(Map<String, dynamic> json) => PanelBillingModel(
        isCashOpen: json['isCashOpen'] == true || json['is_cash_open'] == true,
        pending: json['pending'] != null ? (json['pending'] as List).map((x) => BillingOrderModel.fromJson(x)).toList() : [],
        paid: json['paid'] != null ? (json['paid'] as List).map((x) => BillingOrderModel.fromJson(x)).toList() : [],
        invoiceTypes: json['invoiceTypes'] != null ? (json['invoiceTypes'] as List).map((x) => InvoiceTypeModel.fromJson(x)).toList() : [],
      );
}

class BillingOrderModel {
  int? id;
  String? orderNo;
  double? total;
  String? customerName;
  String? table;
  String? invoice;
  String? paidAt;
  int? sunatInvoiceId;
  String? cdrStatus;

  BillingOrderModel({
    this.id,
    this.orderNo,
    this.total,
    this.customerName,
    this.table,
    this.invoice,
    this.paidAt,
    this.sunatInvoiceId,
    this.cdrStatus,
  });

  static String? _parseTable(dynamic tableJson) {
    if (tableJson == null) return null;
    if (tableJson is Map) {
      final name = tableJson['name']?.toString();
      if (name != null && name.isNotEmpty) return name;
      final number = tableJson['table_number']?.toString() ?? tableJson['number']?.toString();
      if (number != null && number.isNotEmpty) return 'Mesa $number';
      return null;
    }
    return tableJson.toString();
  }

  factory BillingOrderModel.fromJson(Map<String, dynamic> json) {
    final sunatInv = json['sunat_invoice'];
    int? sId;
    String? cStatus;
    if (sunatInv is Map) {
      sId = sunatInv['id'] is int ? sunatInv['id'] : int.tryParse(sunatInv['id']?.toString() ?? '');
      cStatus = sunatInv['cdr_status']?.toString();
    }

    String? invNum = json['invoice']?.toString();
    if ((invNum == null || invNum.isEmpty) && sunatInv is Map) {
      final serie = sunatInv['serie']?.toString() ?? '';
      final corr = sunatInv['correlativo']?.toString() ?? '';
      if (serie.isNotEmpty && corr.isNotEmpty) {
        invNum = '$serie-${corr.padLeft(8, '0')}';
      }
    }

    return BillingOrderModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
      orderNo: json['order_no']?.toString(),
      total: _pd(json['total']),
      customerName: json['customer_name']?.toString(),
      table: _parseTable(json['table']),
      invoice: invNum,
      paidAt: json['paid_at']?.toString(),
      sunatInvoiceId: sId,
      cdrStatus: cStatus,
    );
  }
}

class PanelReportModel {
  String? from;
  String? to;
  int? posCount;
  double? posSales;
  int? delCount;
  double? delSales;
  int? totalOrders;
  double? totalSales;

  PanelReportModel({this.from, this.to, this.posCount, this.posSales, this.delCount, this.delSales, this.totalOrders, this.totalSales});

  factory PanelReportModel.fromJson(Map<String, dynamic> json) => PanelReportModel(
        from: json['from']?.toString(),
        to: json['to']?.toString(),
        posCount: json['pos_count'] is int ? json['pos_count'] : int.tryParse(json['pos_count']?.toString() ?? ''),
        posSales: _pd(json['pos_sales']),
        delCount: json['del_count'] is int ? json['del_count'] : int.tryParse(json['del_count']?.toString() ?? ''),
        delSales: _pd(json['del_sales']),
        totalOrders: json['total_orders'] is int ? json['total_orders'] : int.tryParse(json['total_orders']?.toString() ?? ''),
        totalSales: _pd(json['total_sales']),
      );
}

class PanelCustomerModel {
  String? customerName;
  String? customerPhone;
  int? totalOrders;
  double? totalSpent;
  String? lastOrder;

  PanelCustomerModel({this.customerName, this.customerPhone, this.totalOrders, this.totalSpent, this.lastOrder});

  factory PanelCustomerModel.fromJson(Map<String, dynamic> json) => PanelCustomerModel(
        customerName: json['customer_name']?.toString(),
        customerPhone: json['customer_phone']?.toString(),
        totalOrders: json['total_orders'] is int ? json['total_orders'] : int.tryParse(json['total_orders']?.toString() ?? ''),
        totalSpent: _pd(json['total_spent']),
        lastOrder: json['last_order']?.toString(),
      );
}

class InvoiceSeriesModel {
  int? id;
  int? invoiceTypeId;
  String? series;
  int? currentNumber;

  InvoiceSeriesModel({this.id, this.invoiceTypeId, this.series, this.currentNumber});

  factory InvoiceSeriesModel.fromJson(Map<String, dynamic> json) => InvoiceSeriesModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        invoiceTypeId: json['invoice_type_id'] is int ? json['invoice_type_id'] : int.tryParse(json['invoice_type_id']?.toString() ?? ''),
        series: json['series']?.toString(),
        currentNumber: json['current_number'] is int ? json['current_number'] : int.tryParse(json['current_number']?.toString() ?? ''),
      );
}

class InvoiceTypeModel {
  int? id;
  String? name;
  String? code;
  String? sunatCode;
  List<InvoiceSeriesModel> series = [];

  InvoiceTypeModel({this.id, this.name, this.code, this.sunatCode, this.series = const []});

  factory InvoiceTypeModel.fromJson(Map<String, dynamic> json) => InvoiceTypeModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        name: json['name']?.toString(),
        code: json['code']?.toString(),
        sunatCode: json['sunat_code']?.toString(),
        series: json['series'] != null ? (json['series'] as List).map((x) => InvoiceSeriesModel.fromJson(x)).toList() : [],
      );
}

class ExpenseModel {
  int? id;
  String? category;
  double? amount;
  String? description;
  String? provider;
  String? invoiceNumber;
  String? paymentMethod;
  String? notes;
  String? expenseDate;

  ExpenseModel({
    this.id,
    this.category,
    this.amount,
    this.description,
    this.provider,
    this.invoiceNumber,
    this.paymentMethod,
    this.notes,
    this.expenseDate,
  });

  factory ExpenseModel.fromJson(Map<String, dynamic> json) => ExpenseModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        category: json['category']?.toString(),
        amount: _pd(json['amount']),
        description: json['description']?.toString(),
        provider: json['provider']?.toString(),
        invoiceNumber: json['invoice_number']?.toString(),
        paymentMethod: json['payment_method']?.toString(),
        notes: json['notes']?.toString(),
        expenseDate: json['expense_date']?.toString() ?? json['created_at']?.toString(),
      );
}

double? _pd(dynamic v) {
  if (v == null) return null;
  if (v is double) return v;
  if (v is int) return v.toDouble();
  return double.tryParse(v.toString());
}

// ── Mozo Ordering Models ──

class PanelProductModel {
  int? id;
  String? name;
  double? price;
  String? image;
  String? barcode;
  int? categoryId;
  String? category;
  List<PanelProductVariationModel> variations;
  List<PanelProductAddonModel> addons;

  PanelProductModel({
    this.id,
    this.name,
    this.price,
    this.image,
    this.barcode,
    this.categoryId,
    this.category,
    this.variations = const [],
    this.addons = const [],
  });

  factory PanelProductModel.fromJson(Map<String, dynamic> json) => PanelProductModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        name: json['name']?.toString(),
        price: _pd(json['price']),
        image: json['image']?.toString(),
        barcode: json['barcode']?.toString(),
        categoryId: json['category_id'] is int ? json['category_id'] : int.tryParse(json['category_id']?.toString() ?? ''),
        category: json['category']?.toString(),
        variations: json['variations'] != null
            ? (json['variations'] as List).map((x) => PanelProductVariationModel.fromJson(x)).toList()
            : [],
        addons: json['addons'] != null
            ? (json['addons'] as List).map((x) => PanelProductAddonModel.fromJson(x)).toList()
            : [],
      );
}

class PanelProductVariationModel {
  int? id;
  String? name;
  double? price;

  PanelProductVariationModel({this.id, this.name, this.price});

  factory PanelProductVariationModel.fromJson(Map<String, dynamic> json) => PanelProductVariationModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        name: json['name']?.toString(),
        price: _pd(json['price']),
      );
}

class PanelProductAddonModel {
  int? id;
  String? name;
  double? price;

  PanelProductAddonModel({this.id, this.name, this.price});

  factory PanelProductAddonModel.fromJson(Map<String, dynamic> json) => PanelProductAddonModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        name: json['name']?.toString(),
        price: _pd(json['price']),
      );
}

class MozoCartItem {
  final int productId;
  final String name;
  final double price;
  int quantity;
  String? notes;
  final int? variationId;
  final String? variationName;
  final List<int> addonIds;
  final List<String> addonNames;
  bool isTakeaway;
  bool isCourtesy;

  MozoCartItem({
    required this.productId,
    required this.name,
    required this.price,
    this.quantity = 1,
    this.notes,
    this.variationId,
    this.variationName,
    this.addonIds = const [],
    this.addonNames = const [],
    this.isTakeaway = false,
    this.isCourtesy = false,
  });

  double get total => isCourtesy ? 0 : price * quantity;

  Map<String, dynamic> toJson() => {
        'product_id': productId,
        'name': name,
        'price': isCourtesy ? 0 : price,
        'quantity': quantity,
        'notes': notes,
        'is_takeaway': isTakeaway,
      };
}

class MozoOrderModel {
  int? id;
  String? orderNo;
  String? status;
  double? total;
  String? table;
  int? tableId;
  List<MozoOrderItemModel> items;

  MozoOrderModel({this.id, this.orderNo, this.status, this.total, this.table, this.tableId, this.items = const []});

  factory MozoOrderModel.fromJson(Map<String, dynamic> json) => MozoOrderModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        orderNo: json['order_no']?.toString(),
        status: json['status']?.toString(),
        total: _pd(json['total']),
        table: json['table']?.toString(),
        tableId: json['pos_table_id'] is int ? json['pos_table_id'] : int.tryParse(json['pos_table_id']?.toString() ?? ''),
        items: json['items'] != null
            ? (json['items'] as List).map((x) => MozoOrderItemModel.fromJson(x)).toList()
            : [],
      );
}

class MozoOrderItemModel {
  int? id;
  String? productName;
  int? quantity;
  double? unitPrice;
  double? totalPrice;
  String? status;
  String? notes;

  MozoOrderItemModel({this.id, this.productName, this.quantity, this.unitPrice, this.totalPrice, this.status, this.notes});

  factory MozoOrderItemModel.fromJson(Map<String, dynamic> json) => MozoOrderItemModel(
        id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
        productName: json['product_name']?.toString(),
        quantity: json['quantity'] is int ? json['quantity'] : int.tryParse(json['quantity']?.toString() ?? '1'),
        unitPrice: _pd(json['unit_price']),
        totalPrice: _pd(json['total_price']),
        status: json['status']?.toString() ?? 'pending',
        notes: json['notes']?.toString(),
      );
}
