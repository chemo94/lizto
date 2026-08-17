import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/services/api_client.dart';

class WalletRepo {
  final ApiClient apiClient;
  WalletRepo({required this.apiClient});

  Future<ResponseModel> getBalance() async {
    return await apiClient.request('${UrlContainer.baseUrl}wallet/balance', Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> getTransactions({int page = 1}) async {
    return await apiClient.request('${UrlContainer.baseUrl}wallet/transactions?page=$page', Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> addFunds(double amount, {int? gatewayCode}) async {
    Map<String, dynamic> data = {'amount': amount};
    if (gatewayCode != null) data['gateway_code'] = gatewayCode;
    return await apiClient.request('${UrlContainer.baseUrl}wallet/add-funds', Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> withdrawFunds(double amount, int methodCode) async {
    return await apiClient.request('${UrlContainer.baseUrl}wallet/withdraw', Method.postMethod, {
      'amount': amount,
      'method_code': methodCode,
    }, passHeader: true);
  }
}
