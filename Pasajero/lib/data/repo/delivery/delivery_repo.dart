import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/services/api_client.dart';

class DeliveryRepo {
  final ApiClient apiClient;
  DeliveryRepo({required this.apiClient});

  Future<ResponseModel> getGeneralCategories({double? lat, double? lng}) async {
    String url = '${UrlContainer.baseUrl}delivery/categories';
    if (lat != null && lng != null) {
      url += '?lat=$lat&lng=$lng';
    }
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  /// Loads the full home page for a specific service/category:
  /// subcategories + premium sections (offers, top, best rated, most ordered)
  /// all scoped to that category.
  Future<ResponseModel> getCategoryHome(int categoryId, {double? lat, double? lng, bool top = false, bool fast = false, bool highRating = false, String sort = 'relevance'}) async {
    String url = '${UrlContainer.baseUrl}delivery/categories/$categoryId/home';
    final params = <String>[];
    if (lat != null && lng != null) {
      params.add('lat=$lat');
      params.add('lng=$lng');
    }
    if (top) params.add('top=1');
    if (fast) params.add('fast=1');
    if (highRating) params.add('high_rating=1');
    if (sort != 'relevance') params.add('sort=$sort');
    if (params.isNotEmpty) url += '?${params.join('&')}';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  /// Search stores within a specific service/category.
  Future<ResponseModel> searchCategoryStores(int categoryId, String query, {double? lat, double? lng, int perPage = 20, bool top = false, bool fast = false, bool highRating = false, String sort = 'relevance'}) async {
    String url = '${UrlContainer.baseUrl}delivery/categories/$categoryId/stores?q=${Uri.encodeComponent(query)}&per_page=$perPage';
    if (lat != null && lng != null) url += '&lat=$lat&lng=$lng';
    if (top) url += '&top=1';
    if (fast) url += '&fast=1';
    if (highRating) url += '&high_rating=1';
    if (sort != 'relevance') url += '&sort=$sort';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> getSubCategories(int categoryId) async {
    String url = '${UrlContainer.baseUrl}delivery/categories/$categoryId/subcategories';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> getStores(int subCategoryId, {double? lat, double? lng}) async {
    String url = '${UrlContainer.baseUrl}delivery/subcategories/$subCategoryId/stores';
    if (lat != null && lng != null) url += '?latitude=$lat&longitude=$lng';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> getNearbyStores({double? lat, double? lng, int perPage = 12, String? query}) async {
    String url = '${UrlContainer.baseUrl}delivery/nearby-stores?per_page=$perPage';
    if (lat != null && lng != null) url += '&lat=$lat&lng=$lng';
    if (query != null && query.trim().isNotEmpty) {
      url += '&q=${Uri.encodeComponent(query.trim())}';
    }
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> estimateDeliveryFee({
    required int storeId,
    required double deliveryLat,
    required double deliveryLng,
  }) async {
    String url = '${UrlContainer.baseUrl}delivery/fee-estimate';
    return await apiClient.request(
      url,
      Method.postMethod,
      {
        'store_id': storeId,
        'delivery_lat': deliveryLat,
        'delivery_lng': deliveryLng,
      },
      passHeader: true,
    );
  }

  Future<ResponseModel> getUserInfo() async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.getProfileEndPoint}';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> getStoreDetail(int storeId, {double? lat, double? lng}) async {
    String url = '${UrlContainer.baseUrl}delivery/store/$storeId';
    if (lat != null && lng != null) {
      url += '?lat=$lat&lng=$lng';
    }
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> toggleFavoriteStore(int storeId) async {
    String url = '${UrlContainer.baseUrl}delivery/favorites/$storeId';
    return await apiClient.request(url, Method.postMethod, null, passHeader: true);
  }

  Future<ResponseModel> getFavoriteStores() async {
    String url = '${UrlContainer.baseUrl}delivery/favorites';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> createOrder(Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/create';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> getOrders({int page = 1}) async {
    String url = '${UrlContainer.baseUrl}delivery/orders?page=$page';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> getOrderDetail(int orderId) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/$orderId';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> cancelOrder(int orderId, {String? reason}) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/cancel/$orderId';
    return await apiClient.request(url, Method.postMethod, reason != null ? {'reason': reason} : null, passHeader: true);
  }

  Future<ResponseModel> payOrder(int orderId, int gatewayCode) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/$orderId/pay';
    return await apiClient.request(url, Method.postMethod, {'payment_method_code': gatewayCode}, passHeader: true);
  }

  Future<ResponseModel> processMercadoPagoPayment({
    required int orderId,
    required String cardToken,
    required int installments,
    required String paymentMethodId,
    required String? issuerId,
    required String payerEmail,
    required String? docType,
    required String? docNumber,
  }) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/$orderId/mp-process';
    return await apiClient.request(
        url,
        Method.postMethod,
        {
          'card_token': cardToken,
          'installments': installments,
          'payment_method_id': paymentMethodId,
          'issuer_id': issuerId,
          'payer_email': payerEmail,
          'payer_doc_type': docType,
          'payer_doc_num': docNumber,
        },
        passHeader: true);
  }

  Future<ResponseModel> deletePendingOrder(int orderId) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/delete-pending/$orderId';
    return await apiClient.request(url, Method.postMethod, null, passHeader: true);
  }

  Future<ResponseModel> addTip(int orderId, double tip) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/tip/$orderId';
    return await apiClient.request(url, Method.postMethod, {'tip': tip}, passHeader: true);
  }

  Future<ResponseModel> getGateways() async {
    String url = '${UrlContainer.baseUrl}delivery/gateways';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> requestRefund(int orderId, String reason) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/refund/$orderId';
    return await apiClient.request(url, Method.postMethod, {'reason': reason}, passHeader: true);
  }

  Future<ResponseModel> getRefunds() async {
    String url = '${UrlContainer.baseUrl}delivery/refunds';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> getDeliveryPayments() async {
    String url = '${UrlContainer.baseUrl}delivery/payments';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> reportOrderProblem(int orderId, String subject, String description) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/$orderId/report';
    return await apiClient.request(
        url,
        Method.postMethod,
        {
          'order_id': orderId,
          'subject': subject,
          'description': description,
        },
        passHeader: true);
  }

  Future<ResponseModel> reviewDelivery(int orderId, {double rating = 5, String review = ''}) async {
    String url = '${UrlContainer.baseUrl}delivery/orders/$orderId/review';
    return await apiClient.request(url, Method.postMethod, {'rating': rating, 'review': review}, passHeader: true);
  }

  Future<ResponseModel> applyCoupon(String code, double orderAmount) async {
    String url = '${UrlContainer.baseUrl}delivery/coupon/apply';
    return await apiClient.request(url, Method.postMethod, {'code': code, 'amount': orderAmount}, passHeader: true);
  }

  Future<ResponseModel> getUserAddresses() async {
    String url = '${UrlContainer.baseUrl}user/addresses';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> saveUserAddress(Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}user/addresses/save';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> deleteUserAddress(String id) async {
    String url = '${UrlContainer.baseUrl}user/addresses/delete/$id';
    return await apiClient.request(url, Method.postMethod, null, passHeader: true);
  }

  Future<ResponseModel> getStories() async {
    String url = '${UrlContainer.baseUrl}delivery/stories';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }
}
