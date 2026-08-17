import 'package:liztogo_pro/core/utils/method.dart';
import 'package:liztogo_pro/core/utils/url_container.dart';
import 'package:liztogo_pro/data/services/api_client.dart';

class PrivacyRepo {
  ApiClient apiClient;
  PrivacyRepo({required this.apiClient});

  Future<dynamic> loadAboutData() async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.privacyPolicyEndPoint}';

    final response = await apiClient.request(url, Method.getMethod, null);
    return response;
  }
}
