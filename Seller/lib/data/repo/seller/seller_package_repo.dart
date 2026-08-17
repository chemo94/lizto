import 'package:lizto_store/core/utils/method.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/services/api_client.dart';

class SellerPackageRepo {
  final ApiClient apiClient;
  SellerPackageRepo({required this.apiClient});

  String get _base => '${UrlContainer.baseUrl}${UrlContainer.sellerPackagesEndpoint}';

  Future<ResponseModel> getPackages() async {
    return await apiClient.request(_base, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> purchasePackage({
    required int packageId,
    required int storeId,
    String? paymentMethod,
    String? paymentRef,
  }) async {
    return await apiClient.request(
      '${UrlContainer.baseUrl}${UrlContainer.sellerPurchasePackageEndpoint}',
      Method.postMethod,
      {
        'package_id': packageId,
        'store_id': storeId,
        if (paymentMethod != null) 'payment_method': paymentMethod,
        if (paymentRef != null) 'payment_ref': paymentRef,
      },
      passHeader: true,
    );
  }

  Future<ResponseModel> getMySubscriptions(int storeId) async {
    final url = '${UrlContainer.baseUrl}${UrlContainer.sellerMyPackagesEndpoint}?store_id=$storeId';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> getStoreAnalytics(int storeId) async {
    final url = '${UrlContainer.baseUrl}${UrlContainer.sellerStoreAnalytics(storeId)}';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> notifyCustomers(int storeId, String title, String body) async {
    final url = '${UrlContainer.baseUrl}${UrlContainer.sellerNotifyCustomers(storeId)}';
    return await apiClient.request(url, Method.postMethod, {
      'title': title,
      'body': body,
    }, passHeader: true);
  }

  Future<ResponseModel> getStoreQr(int storeId) async {
    final url = '${UrlContainer.baseUrl}${UrlContainer.sellerStoreQr(storeId)}';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }
}
