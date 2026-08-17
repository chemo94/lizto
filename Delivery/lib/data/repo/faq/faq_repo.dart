import 'package:lizto_delivery/core/utils/method.dart';
import 'package:lizto_delivery/core/utils/url_container.dart';
import 'package:lizto_delivery/data/model/global/response_model/response_model.dart';
import 'package:lizto_delivery/data/services/api_client.dart';

class FaqRepo {
  ApiClient apiClient;
  FaqRepo({required this.apiClient});

  Future<ResponseModel> getFaqData() async {
    String url = UrlContainer.baseUrl + UrlContainer.faq;
    final response = await apiClient.request(url, Method.getMethod, null);
    return response;
  }
}
