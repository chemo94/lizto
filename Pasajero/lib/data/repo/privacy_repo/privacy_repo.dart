import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/services/api_client.dart';

class PrivacyRepo {
  ApiClient apiClient;
  PrivacyRepo({required this.apiClient});

  Future<dynamic> loadAboutData() async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.privacyPolicyEndPoint}';

    final response = await apiClient.request(url, Method.getMethod, null);
    return response;
  }
}
