import 'dart:io';
import 'package:dio/dio.dart' as dioX;
import 'package:liztogo/core/helper/shared_preference_helper.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/environment.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SellerRepo {
  final dioX.Dio _dio = dioX.Dio();
  final SharedPreferences _prefs;

  SellerRepo({required SharedPreferences prefs}) : _prefs = prefs {
    _dio.options.headers = {
      "Accept": "application/json",
      "dev-token": Environment.devToken,
    };
    _dio.options.followRedirects = false;
    _dio.options.validateStatus = (status) => status! < 500;
  }

  String? get token => _prefs.getString(SharedPreferenceHelper.sellerTokenKey);
  String get tokenType => 'Bearer';

  void saveToken(String token) => _prefs.setString(SharedPreferenceHelper.sellerTokenKey, token);
  void removeToken() => _prefs.remove(SharedPreferenceHelper.sellerTokenKey);
  bool get isLoggedIn => token != null && token!.isNotEmpty;

  Future<ResponseModel> _request(String uri, String method, Map<String, dynamic>? params, {bool auth = false}) async {
    try {
      if (auth && token != null) {
        _dio.options.headers["Authorization"] = "$tokenType $token";
      }

      dioX.Response response;
      switch (method) {
        case Method.postMethod:
          response = await _dio.post(uri, data: params);
          break;
        default:
          response = await _dio.get(uri);
      }

      printX('seller url: $uri');
      printX('seller body: ${response.data.toString()}');

      if (response.statusCode == 200) {
        if (response.data is Map && response.data['status'] == 'success') {
          return ResponseModel(true, response.data['message']?.toString() ?? '', response.statusCode!, response.data);
        }
        return ResponseModel(false, response.data['message']?.toString() ?? '', response.statusCode!, response.data);
      }
      return ResponseModel(false, 'Error del servidor', response.statusCode!, response.data);
    } catch (e) {
      printX('seller error: $e');
      return ResponseModel(false, 'Error de conexión', 500, null);
    }
  }

  Future<ResponseModel> login(String email, String password) async {
    String url = '${UrlContainer.baseUrl}seller/login';
    return await _request(url, Method.postMethod, {'email': email, 'password': password});
  }

  Future<ResponseModel> dashboard() async {
    String url = '${UrlContainer.baseUrl}seller/dashboard';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> getProducts(int storeId) async {
    String url = '${UrlContainer.baseUrl}seller/products/$storeId';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> createProduct(int storeId, Map<String, dynamic> data, {File? image}) async {
    String url = '${UrlContainer.baseUrl}seller/products/$storeId/store';
    return await _requestMultipart(url, data, image: image);
  }

  Future<ResponseModel> updateProduct(int productId, Map<String, dynamic> data, {File? image}) async {
    String url = '${UrlContainer.baseUrl}seller/products/update/$productId';
    return await _requestMultipart(url, data, image: image);
  }

  Future<ResponseModel> deleteProduct(int productId) async {
    String url = '${UrlContainer.baseUrl}seller/products/delete/$productId';
    return await _request(url, Method.postMethod, null, auth: true);
  }

  Future<ResponseModel> toggleProductStatus(int productId) async {
    String url = '${UrlContainer.baseUrl}seller/products/toggle-status/$productId';
    return await _request(url, Method.postMethod, null, auth: true);
  }

  Future<ResponseModel> getWalletBalance() async {
    String url = '${UrlContainer.baseUrl}seller/wallet/balance';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> getWalletTransactions() async {
    String url = '${UrlContainer.baseUrl}seller/wallet/transactions';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> requestWithdraw(Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}seller/wallet/withdraw';
    return await _request(url, Method.postMethod, data, auth: true);
  }

  Future<ResponseModel> getOrders(String status) async {
    String url = '${UrlContainer.baseUrl}seller/orders${status.isNotEmpty ? '?status=$status' : ''}';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> updateOrderStatus(int orderId, String status) async {
    String url = '${UrlContainer.baseUrl}seller/orders/status/$orderId';
    return await _request(url, Method.postMethod, {'status': status}, auth: true);
  }

  // ─── Menu Category CRUD ───────────────────────────────────────────────────

  Future<ResponseModel> getMenuCategories(int storeId) async {
    String url = '${UrlContainer.baseUrl}seller/menu-categories/$storeId';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> createMenuCategory(int storeId, String name, {File? image}) async {
    String url = '${UrlContainer.baseUrl}seller/menu-categories/$storeId/store';
    return await _requestMultipart(url, {'name': name}, image: image);
  }

  Future<ResponseModel> updateMenuCategory(int categoryId, String name, {File? image}) async {
    String url = '${UrlContainer.baseUrl}seller/menu-categories/update/$categoryId';
    return await _requestMultipart(url, {'name': name}, image: image);
  }

  Future<ResponseModel> deleteMenuCategory(int categoryId) async {
    String url = '${UrlContainer.baseUrl}seller/menu-categories/delete/$categoryId';
    return await _request(url, Method.postMethod, null, auth: true);
  }

  Future<ResponseModel> getStores() async {
    String url = '${UrlContainer.baseUrl}seller/stores';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> createStore(Map<String, dynamic> data, {File? image, File? coverImage}) async {
    String url = '${UrlContainer.baseUrl}seller/stores/store';
    try {
      _dio.options.headers['Authorization'] = '$tokenType $token';
      final formData = dioX.FormData.fromMap({
        ...data,
        if (image != null) 'image': await dioX.MultipartFile.fromFile(image.path, filename: image.path.split('/').last),
        if (coverImage != null) 'cover_image': await dioX.MultipartFile.fromFile(coverImage.path, filename: coverImage.path.split('/').last),
      });
      final response = await _dio.post(url, data: formData);
      if (response.statusCode == 200) {
        if (response.data is Map && response.data['status'] == 'success') {
          return ResponseModel(true, response.data['message']?.toString() ?? '', response.statusCode!, response.data);
        }
        return ResponseModel(false, response.data['message']?.toString() ?? 'Error', response.statusCode!, response.data);
      }
      return ResponseModel(false, 'Error del servidor', response.statusCode!, response.data);
    } catch (e) {
      return ResponseModel(false, 'Error de conexión', 500, null);
    }
  }

  Future<ResponseModel> updateStore(int storeId, Map<String, dynamic> data, {File? image, File? coverImage}) async {
    String url = '${UrlContainer.baseUrl}seller/stores/update/$storeId';
    try {
      _dio.options.headers['Authorization'] = '$tokenType $token';
      final formData = dioX.FormData.fromMap({
        ...data,
        if (image != null) 'image': await dioX.MultipartFile.fromFile(image.path, filename: image.path.split('/').last),
        if (coverImage != null) 'cover_image': await dioX.MultipartFile.fromFile(coverImage.path, filename: coverImage.path.split('/').last),
      });
      final response = await _dio.post(url, data: formData);
      if (response.statusCode == 200) {
        if (response.data is Map && response.data['status'] == 'success') {
          return ResponseModel(true, response.data['message']?.toString() ?? '', response.statusCode!, response.data);
        }
        return ResponseModel(false, response.data['message']?.toString() ?? 'Error', response.statusCode!, response.data);
      }
      return ResponseModel(false, 'Error del servidor', response.statusCode!, response.data);
    } catch (e) {
      return ResponseModel(false, 'Error de conexión', 500, null);
    }
  }

  Future<ResponseModel> deleteStore(int storeId) async {
    String url = '${UrlContainer.baseUrl}seller/stores/delete/$storeId';
    return await _request(url, Method.postMethod, null, auth: true);
  }

  Future<ResponseModel> getSubCategories() async {
    String url = '${UrlContainer.baseUrl}seller/subcategories';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> createStoreFavor(Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}seller/favors/create';
    return await _request(url, Method.postMethod, data, auth: true);
  }

  Future<ResponseModel> _requestMultipart(String url, Map<String, dynamic> fields, {File? image}) async {
    try {
      _dio.options.headers['Authorization'] = '$tokenType $token';
      final formData = dioX.FormData.fromMap({
        ...fields,
        if (image != null) 'image': await dioX.MultipartFile.fromFile(image.path, filename: image.path.split('/').last),
      });
      final response = await _dio.post(url, data: formData);
      printX('multipart url: $url');
      printX('multipart response: ${response.data}');
      if (response.statusCode == 200) {
        if (response.data is Map && response.data['status'] == 'success') {
          return ResponseModel(true, response.data['message']?.toString() ?? '', response.statusCode!, response.data);
        }
        return ResponseModel(false, response.data['message']?.toString() ?? 'Error', response.statusCode!, response.data);
      }
      return ResponseModel(false, 'Error del servidor', response.statusCode!, response.data);
    } catch (e) {
      printX('multipart error: $e');
      return ResponseModel(false, 'Error de conexión', 500, null);
    }
  }
}
