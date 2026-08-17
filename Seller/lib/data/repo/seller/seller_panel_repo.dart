import 'package:lizto_store/core/utils/method.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/services/api_client.dart';

class SellerPanelRepo {
  final ApiClient apiClient;
  SellerPanelRepo({required this.apiClient});

  Future<ResponseModel> _get(String url) async {
    return await apiClient.request('${UrlContainer.baseUrl}$url', Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> _post(String url, [Map<String, dynamic>? data]) async {
    return await apiClient.request('${UrlContainer.baseUrl}$url', Method.postMethod, data, passHeader: true);
  }

  // Dashboard
  Future<ResponseModel> dashboard() => _get(UrlContainer.panelDashboard);

  // Tables
  Future<ResponseModel> tables() => _get(UrlContainer.panelTables);
  Future<ResponseModel> createTable(Map<String, dynamic> data) => _post(UrlContainer.panelTablesStore, data);
  Future<ResponseModel> updateTable(int id, Map<String, dynamic> data) => _post(UrlContainer.panelTableUpdate(id), data);
  Future<ResponseModel> deleteTable(int id) => _post(UrlContainer.panelTableDelete(id));
  Future<ResponseModel> savePosition(Map<String, dynamic> data) => _post(UrlContainer.panelTablesPosition, data);

  // Areas
  Future<ResponseModel> areas() => _get(UrlContainer.panelAreas);
  Future<ResponseModel> createArea(Map<String, dynamic> data) => _post(UrlContainer.panelAreasStore, data);
  Future<ResponseModel> deleteArea(int id) => _post(UrlContainer.panelAreaDelete(id));

  // Kitchen
  Future<ResponseModel> kitchen() => _get(UrlContainer.panelKitchen);
  Future<ResponseModel> updateKitchenStatus(int id, Map<String, dynamic> data) => _post(UrlContainer.panelKitchenStatus(id), data);

  // Orders
  Future<ResponseModel> panelOrders({int page = 1}) => _get('${UrlContainer.panelOrders}?page=$page');

  // Customers
  Future<ResponseModel> customers() => _get(UrlContainer.panelCustomers);

  // Cash
  Future<ResponseModel> cash() => _get(UrlContainer.panelCash);
  Future<ResponseModel> openCash(double amount) => _post(UrlContainer.panelCashOpen, {'opening_balance': amount});
  Future<ResponseModel> closeCash(double amount) => _post(UrlContainer.panelCashClose, {'closing_balance': amount});
  Future<ResponseModel> cashTransaction(String type, double amount, {String? description}) => _post(UrlContainer.panelCashTransaction, {'type': type, 'amount': amount, if (description != null) 'description': description});

  // Expenses
  Future<ResponseModel> expenses({int page = 1}) => _get('${UrlContainer.panelExpenses}?page=$page');
  Future<ResponseModel> createExpense(Map<String, dynamic> data) => _post(UrlContainer.panelExpensesStore, data);
  Future<ResponseModel> deleteExpense(int id) => _post(UrlContainer.panelExpenseDelete(id));

  // Billing
  Future<ResponseModel> billing() => _get(UrlContainer.panelBilling);
  Future<ResponseModel> payOrder(int id, String method, {String? docType, String? docNum, String? name, String detailMode = 'detailed', String? consumptionDescription}) => _post(UrlContainer.panelBillingPay(id), {
        'payment_method': method,
        if (docType != null) 'tipo_doc': docType,
        if (docNum != null) 'num_doc': docNum,
        if (name != null) 'nombre': name,
        'detail_mode': detailMode,
        if (consumptionDescription != null) 'consumption_description': consumptionDescription,
      });
  Future<ResponseModel> generateInvoice(int id, int seriesId, String method, {String? docType, String? docNum, String? name, String detailMode = 'detailed', String? consumptionDescription}) => _post(UrlContainer.panelBillingInvoice(id), {
        'series_id': seriesId,
        'payment_method': method,
        if (docType != null) 'tipo_doc': docType,
        if (docNum != null) 'num_doc': docNum,
        if (name != null) 'nombre': name,
        'detail_mode': detailMode,
        if (consumptionDescription != null) 'consumption_description': consumptionDescription,
      });

  // Invoicing
  Future<ResponseModel> invoicing() => _get(UrlContainer.panelInvoicing);
  Future<ResponseModel> createSeries(Map<String, dynamic> data) => _post(UrlContainer.panelInvoicingSeriesStore, data);

  // Notifications
  Future<ResponseModel> sendNotification(String title, String body) => _post(UrlContainer.panelNotificationsSend, {'title': title, 'body': body});

  // QR Menu
  Future<ResponseModel> qrMenu() => _get(UrlContainer.panelQrMenu);

  // Reports
  Future<ResponseModel> reports({String from = '', String to = ''}) => _get('${UrlContainer.panelReports}?from=$from&to=$to');

  // SUNAT
  Future<ResponseModel> sunatLookup(String numdoc, String tpdoc) => _post(UrlContainer.sunatLookup, {'numdoc': numdoc, 'tpdoc': tpdoc});

  // ── Mozo Ordering ──
  Future<ResponseModel> products() => _get(UrlContainer.panelProducts);
  Future<ResponseModel> createOrder(Map<String, dynamic> data) => _post(UrlContainer.panelOrderCreate, data);
  Future<ResponseModel> getActiveTableOrder(int tableId) => _get(UrlContainer.panelTableActiveOrder(tableId));
}
