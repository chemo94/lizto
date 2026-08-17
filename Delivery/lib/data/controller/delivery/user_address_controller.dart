import 'package:get/get.dart';
import 'package:lizto_delivery/core/helper/string_format_helper.dart';
import 'package:lizto_delivery/core/utils/method.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';
import 'package:lizto_delivery/core/utils/url_container.dart';
import 'package:lizto_delivery/data/model/delivery/user_address_model.dart';
import 'package:lizto_delivery/data/model/global/response_model/response_model.dart';
import 'package:lizto_delivery/data/services/api_client.dart';

class UserAddressRepo {
  final ApiClient apiClient;
  UserAddressRepo({required this.apiClient});

  Future<ResponseModel> getAddresses() async {
    String url = '${UrlContainer.baseUrl}user/addresses';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> saveAddress(Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}user/addresses/save';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> deleteAddress(String id) async {
    String url = '${UrlContainer.baseUrl}user/addresses/delete/$id';
    return await apiClient.request(url, Method.postMethod, null, passHeader: true);
  }
}

class UserAddressController extends GetxController {
  final UserAddressRepo repo;
  UserAddressController({required this.repo});

  List<UserAddressModel> addresses = [];
  bool isLoading = false;

  Future<void> loadAddresses() async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await repo.getAddresses();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          addresses = (json['data']['addresses'] as List).map((x) => UserAddressModel.fromJson(x)).toList();
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<bool> saveAddress(UserAddressModel addr) async {
    try {
      ResponseModel response = await repo.saveAddress(addr.toJson());
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          await loadAddresses();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  Future<bool> deleteAddress(String id) async {
    try {
      ResponseModel response = await repo.deleteAddress(id);
      if (response.statusCode == 200) {
        await loadAddresses();
        return true;
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }
}
