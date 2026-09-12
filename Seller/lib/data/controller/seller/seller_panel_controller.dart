import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/helper/string_format_helper.dart';
import 'package:lizto_store/core/utils/my_strings.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/model/seller/panel_models.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';

class SellerPanelController extends GetxController {
  final SellerPanelRepo repo;
  SellerPanelController({required this.repo});

  PanelDashboardModel? dashboard;
  List<PanelAreaModel> areas = [];
  List<KitchenOrderModel> kitchenOrders = [];
  PanelCashModel? cashData;
  PanelBillingModel? billingData;
  PanelReportModel? report;
  List<PanelCustomerModel> customers = [];
  List<dynamic> panelOrders = [];
  List<InvoiceTypeModel> invoiceTypes = [];
  List<ExpenseModel> expensesList = [];

  bool loadingDashboard = false;
  bool loadingTables = false;
  bool loadingKitchen = false;
  bool loadingKitchenSilent = false; // background silent poll — no spinner
  bool loadingCash = false;
  bool loadingBilling = false;
  bool loadingReports = false;
  bool loadingInvoicing = false;
  bool loadingExpenses = false;

  Future<void> loadPanelDashboard() async {
    loadingDashboard = true;
    update();
    try {
      ResponseModel r = await repo.dashboard();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          dashboard = PanelDashboardModel.fromJson(json['data']);
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingDashboard = false;
    update();
  }

  Future<void> loadTables() async {
    loadingTables = true;
    update();
    try {
      ResponseModel r = await repo.tables();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          areas = (json['data']['areas'] as List?)?.map((x) => PanelAreaModel.fromJson(x)).toList() ?? [];
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingTables = false;
    update();
  }

  Future<bool> saveTablePosition(int tableId, int x, int y) async {
    try {
      ResponseModel r = await repo.savePosition({'id': tableId, 'x': x, 'y': y});
      return r.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  Future<bool> createTable(Map<String, dynamic> data) async {
    try {
      ResponseModel r = await repo.createTable(data);
      return r.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  Future<bool> deleteTable(int id) async {
    try {
      ResponseModel r = await repo.deleteTable(id);
      return r.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  Future<bool> createArea(String name) async {
    try {
      ResponseModel r = await repo.createArea({'name': name});
      if (r.statusCode == 200) {
        await loadTables();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  Future<bool> deleteArea(int id) async {
    try {
      ResponseModel r = await repo.deleteArea(id);
      if (r.statusCode == 200) {
        await loadTables();
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  Future<void> loadKitchen() async {
    loadingKitchen = true;
    update();
    try {
      ResponseModel r = await repo.kitchen();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          kitchenOrders = (json['data']['orders'] as List?)?.map((x) => KitchenOrderModel.fromJson(x)).toList() ?? [];
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingKitchen = false;
    update();
  }

  /// Silent version: refreshes kitchen orders in the background WITHOUT
  /// showing the full-screen loading indicator. Called by the periodic timer.
  Future<void> loadKitchenSilent() async {
    if (loadingKitchenSilent) return; // skip if already polling
    loadingKitchenSilent = true;
    update();
    try {
      ResponseModel r = await repo.kitchen();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          kitchenOrders = (json['data']['orders'] as List?)?.map((x) => KitchenOrderModel.fromJson(x)).toList() ?? [];
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingKitchenSilent = false;
    update();
  }

  Future<bool> updateKitchenOrderStatus(int orderId, String status) async {
    try {
      ResponseModel r = await repo.updateKitchenStatus(orderId, {'status': status});
      return r.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  Future<bool> toggleKitchenItem(int orderId, int itemId, String itemStatus) async {
    try {
      ResponseModel r = await repo.updateKitchenStatus(orderId, {'status': 'preparing', 'item_id': itemId, 'item_status': itemStatus});
      return r.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  String cashError = '';
  bool cashLoadFailed = false;

  Future<void> loadCash() async {
    loadingCash = true;
    cashLoadFailed = false;
    update();
    try {
      ResponseModel r = await repo.cash();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          cashData = PanelCashModel.fromJson(json['data']);
        } else {
          cashLoadFailed = true;
          cashError = _extractError(r);
        }
      } else {
        cashLoadFailed = true;
        cashError = r.message;
      }
    } catch (e) {
      printX(e);
      cashLoadFailed = true;
      cashError = 'Error al cargar la caja';
    }
    loadingCash = false;
    update();
  }

  Future<bool> openCash(double amount) async {
    cashError = '';
    try {
      ResponseModel r = await repo.openCash(amount);
      if (r.statusCode == 200 && r.responseJson?['status'] == 'success') return true;
      cashError = _extractError(r);
      return false;
    } catch (e) {
      cashError = 'Error de conexión';
      return false;
    }
  }

  Future<bool> closeCash(double amount) async {
    cashError = '';
    try {
      ResponseModel r = await repo.closeCash(amount);
      if (r.statusCode == 200 && r.responseJson?['status'] == 'success') return true;
      cashError = _extractError(r);
      return false;
    } catch (e) {
      cashError = 'Error de conexión';
      return false;
    }
  }

  Future<bool> cashTransaction(String type, double amount, String? desc) async {
    cashError = '';
    try {
      ResponseModel r = await repo.cashTransaction(type, amount, description: desc);
      if (r.statusCode == 200 && r.responseJson?['status'] == 'success') return true;
      cashError = _extractError(r);
      return false;
    } catch (e) {
      cashError = 'Error de conexión';
      return false;
    }
  }

  String _extractError(ResponseModel r) {
    try {
      final m = r.responseJson?['message'];
      if (m is List && m.isNotEmpty) return m.first.toString();
      if (m != null) return m.toString();
    } catch (_) {}
    return r.message;
  }

  Future<void> loadBilling() async {
    loadingBilling = true;
    update();
    try {
      ResponseModel r = await repo.billing();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          billingData = PanelBillingModel.fromJson(json['data']);
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingBilling = false;
    update();
  }

  bool isLookupLoading = false;

  Future<Map<String, dynamic>?> lookupSunat(String numdoc, String tpdoc) async {
    isLookupLoading = true;
    update();
    try {
      ResponseModel r = await repo.sunatLookup(numdoc, tpdoc);
      isLookupLoading = false;
      update();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == true) {
          return Map<String, dynamic>.from(json);
        } else {
          Get.snackbar('Consulta', json['result']?.toString() ?? 'No se encontró el documento', backgroundColor: Colors.redAccent, colorText: Colors.white);
        }
      } else {
        Get.snackbar('Error', 'No se pudo conectar con el servicio de consulta', backgroundColor: Colors.redAccent, colorText: Colors.white);
      }
    } catch (e) {
      isLookupLoading = false;
      update();
      printX(e);
    }
    return null;
  }

  Future<bool> payBillingOrder(int id, String method, {String? docType, String? docNum, String? name, String? address, String detailMode = 'detailed', String? consumptionDescription}) async {
    try {
      ResponseModel r = await repo.payOrder(id, method, docType: docType, docNum: docNum, name: name, address: address, detailMode: detailMode, consumptionDescription: consumptionDescription);
      var json = r.responseJson;
      if (r.statusCode == 200 && (json['status'] == 'success' || json['status'] == true)) {
        return true;
      } else {
        String msg = json['message'] is List ? (json['message'] as List).first?.toString() ?? 'Error al procesar pago' : json['message']?.toString() ?? 'Error al procesar pago';
        Get.snackbar('Atención', msg, backgroundColor: Colors.redAccent, colorText: Colors.white, duration: const Duration(seconds: 4));
        return false;
      }
    } catch (e) {
      return false;
    }
  }

  Future<bool> generateInvoice(int id, int seriesId, String method, {String? docType, String? docNum, String? name, String? address, String detailMode = 'detailed', String? consumptionDescription}) async {
    try {
      ResponseModel r = await repo.generateInvoice(id, seriesId, method, docType: docType, docNum: docNum, name: name, address: address, detailMode: detailMode, consumptionDescription: consumptionDescription);
      var json = r.responseJson;
      if (r.statusCode == 200 && (json['status'] == 'success' || json['status'] == true)) {
        return true;
      } else {
        String msg = json['message'] is List ? (json['message'] as List).first?.toString() ?? 'Error al emitir comprobante' : json['message']?.toString() ?? 'Error al emitir comprobante';
        Get.snackbar('Atención', msg, backgroundColor: Colors.redAccent, colorText: Colors.white, duration: const Duration(seconds: 4));
        return false;
      }
    } catch (e) {
      return false;
    }
  }

  Future<void> loadReports({String from = '', String to = ''}) async {
    loadingReports = true;
    update();
    try {
      ResponseModel r = await repo.reports(from: from, to: to);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          report = PanelReportModel.fromJson(json['data']['report']);
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingReports = false;
    update();
  }

  Future<void> loadCustomers() async {
    try {
      ResponseModel r = await repo.customers();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          customers = (json['data']['customers'] as List?)?.map((x) => PanelCustomerModel.fromJson(x)).toList() ?? [];
          update();
        }
      }
    } catch (e) {
      printX(e);
    }
  }

  Future<void> loadInvoicing() async {
    loadingInvoicing = true;
    update();
    try {
      ResponseModel r = await repo.invoicing();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          invoiceTypes = (json['data']['types'] as List?)?.map((x) => InvoiceTypeModel.fromJson(x)).toList() ?? [];
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingInvoicing = false;
    update();
  }

  Future<bool> createInvoiceSeries(int typeId, String series, int currentNumber) async {
    try {
      ResponseModel r = await repo.createSeries({
        'invoice_type_id': typeId,
        'series': series,
        'current_number': currentNumber,
      });
      return r.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  Future<void> loadExpenses({int page = 1}) async {
    loadingExpenses = true;
    update();
    try {
      ResponseModel r = await repo.expenses(page: page);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          expensesList = (json['data']['expenses']['data'] as List?)?.map((x) => ExpenseModel.fromJson(x)).toList() ?? [];
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingExpenses = false;
    update();
  }

  Future<bool> registerExpense(Map<String, dynamic> data) async {
    try {
      ResponseModel r = await repo.createExpense(data);
      return r.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  Future<bool> removeExpense(int id) async {
    try {
      ResponseModel r = await repo.deleteExpense(id);
      return r.statusCode == 200;
    } catch (e) {
      return false;
    }
  }
}
