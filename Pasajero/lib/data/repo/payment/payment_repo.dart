import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/services/api_client.dart';

class PaymentRepo {
  ApiClient apiClient;
  PaymentRepo({required this.apiClient});

  Future<ResponseModel> getRideDetails(String id) async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.rideDetails}/$id";
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.getMethod,
      null,
      passHeader: true,
    );
    return responseModel;
  }

  Future<ResponseModel> getRidePaymentDetails(String id) async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.ridePayment}/$id";
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.getMethod,
      null,
      passHeader: true,
    );
    return responseModel;
  }

  Future<ResponseModel> getPaymentList() async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.paymentGateways}";
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.getMethod,
      null,
      passHeader: true,
    );
    return responseModel;
  }

  Future<ResponseModel> submitPayment({
    required String rideId,
    required String currency,
    required String methodCode,
    required String type,
    String tips = "0",
  }) async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.ridePayment}/$rideId";
    Map<String, String> params = {
      'ride_id': rideId,
      'currency': currency,
      'method_code': methodCode,
      'payment_type': type,
      'tips_amount': tips,
    };
    printX(url);
    printX(params);
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.postMethod,
      params,
      passHeader: true,
    );
    return responseModel;
  }

  Future<ResponseModel> processMercadoPagoPayment({
    required String rideId,
    required int depositId,
    required String cardToken,
    required int installments,
    required String paymentMethodId,
    required String? issuerId,
    required String payerEmail,
    required String? docType,
    required String? docNumber,
  }) async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.ridePayment}/$rideId/mp-process";
    return await apiClient.request(
      url,
      Method.postMethod,
      {
        'deposit_id': depositId,
        'card_token': cardToken,
        'installments': installments,
        'payment_method_id': paymentMethodId,
        'issuer_id': issuerId,
        'payer_email': payerEmail,
        'payer_doc_type': docType,
        'payer_doc_num': docNumber,
      },
      passHeader: true,
    );
  }
}
