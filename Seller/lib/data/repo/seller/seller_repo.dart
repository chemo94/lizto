import 'dart:io';
import 'package:dio/dio.dart' as dioX;
import 'package:lizto_store/core/helper/shared_preference_helper.dart';
import 'package:lizto_store/core/helper/string_format_helper.dart';
import 'package:lizto_store/core/utils/method.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/environment.dart';
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
    // Dio must return every HTTP response so the API error body is not lost.
    // Transport failures still arrive through DioException.
    _dio.options.validateStatus = (status) => status != null && status < 600;
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
        case Method.deleteMethod:
          response = await _dio.delete(uri, data: params);
          break;
        default:
          response = await _dio.get(uri);
      }

      printX('seller url: $uri');
      printX('seller body: ${response.data.toString()}');

      if (response.statusCode != null && response.statusCode! >= 200 && response.statusCode! < 300) {
        if (response.data is Map && response.data['status'] == 'success') {
          return ResponseModel(true, response.data['message']?.toString() ?? '', response.statusCode!, response.data);
        }
        return ResponseModel(false, response.data['message']?.toString() ?? '', response.statusCode!, response.data);
      }
      final apiMessage = _responseMessage(response.data);
      return ResponseModel(false, apiMessage ?? 'Error del servidor', response.statusCode ?? 500, response.data);
    } on dioX.DioException catch (e) {
      printX('seller error: $e');
      final response = e.response;
      if (response != null) {
        printX('seller error body: ${response.data}');
        return ResponseModel(
          false,
          _responseMessage(response.data) ?? 'Error del servidor',
          response.statusCode ?? 500,
          response.data,
        );
      }
      return ResponseModel(false, 'No se pudo conectar con el servidor', 500, null);
    } catch (e) {
      printX('seller error: $e');
      return ResponseModel(false, 'Ocurrió un error inesperado', 500, null);
    }
  }

  String? _responseMessage(dynamic body) {
    if (body is! Map) return null;
    final message = body['message'];
    if (message is List && message.isNotEmpty) {
      return message.map((item) => item.toString()).join('\n');
    }
    final text = message?.toString().trim();
    return text == null || text.isEmpty ? null : text;
  }

  Future<ResponseModel> login(String email, String password) async {
    String url = '${UrlContainer.baseUrl}seller/login';
    return await _request(url, Method.postMethod, {'email': email, 'password': password});
  }

  Future<void> saveDeviceToken(String token) async {
    String url = '${UrlContainer.baseUrl}seller/save-device-token';
    await _request(url, Method.postMethod, {'token': token}, auth: true);
  }

  Future<ResponseModel> register(String name, String email, String phone, String password) async {
    String url = '${UrlContainer.baseUrl}seller/register';
    return await _request(url, Method.postMethod, {
      'name': name,
      'email': email,
      'phone': phone,
      'password': password,
    });
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

  Future<ResponseModel> getSubCategories() async {
    String url = '${UrlContainer.baseUrl}seller/subcategories';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> getSchedules(int storeId) async {
    String url = '${UrlContainer.baseUrl}seller/schedules?store_id=$storeId';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> createSchedule(Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}seller/schedules/store';
    return await _request(url, Method.postMethod, data, auth: true);
  }

  Future<ResponseModel> deleteSchedule(int id) async {
    String url = '${UrlContainer.baseUrl}seller/schedules/$id';
    return await _request(url, Method.deleteMethod, null, auth: true);
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

  Future<ResponseModel> createStoreFavor(Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}seller/favors/create';
    return await _request(url, Method.postMethod, data, auth: true);
  }

  Future<ResponseModel> estimateStoreFavorFee(Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}seller/favors/fee-estimate';
    return await _request(url, Method.postMethod, data, auth: true);
  }

  Future<ResponseModel> storeFavorSearchStatus(int favorId) async {
    return await _request('${UrlContainer.baseUrl}seller/favors/$favorId/search-status', Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> storeFavorDetail(int favorId) async {
    return await _request('${UrlContainer.baseUrl}seller/favors/$favorId', Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> storeFavors({int page = 1}) async {
    return await _request('${UrlContainer.baseUrl}seller/favors?page=$page', Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> retryStoreFavorSearch(int favorId) async {
    return await _request('${UrlContainer.baseUrl}seller/favors/$favorId/retry-search', Method.postMethod, null, auth: true);
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

  // ─── Stories ──────────────────────────────────────────────────────────────

  Future<ResponseModel> createStory(int storeId, Map<String, dynamic> data, {File? media}) async {
    String url = '${UrlContainer.baseUrl}seller/stories/store';
    if (media != null) {
      return await _requestMultipartStory(url, data, media: media);
    }
    return await _request(url, Method.postMethod, data, auth: true);
  }

  Future<ResponseModel> getStories() async {
    String url = '${UrlContainer.baseUrl}seller/stories';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> getStoryDetail(int id) async {
    String url = '${UrlContainer.baseUrl}seller/stories/$id';
    return await _request(url, Method.getMethod, null, auth: true);
  }

  Future<ResponseModel> pauseStory(int id) async {
    String url = '${UrlContainer.baseUrl}seller/stories/$id/pause';
    return await _request(url, Method.postMethod, null, auth: true);
  }

  Future<ResponseModel> resumeStory(int id) async {
    String url = '${UrlContainer.baseUrl}seller/stories/$id/resume';
    return await _request(url, Method.postMethod, null, auth: true);
  }

  Future<ResponseModel> deleteStory(int id) async {
    String url = '${UrlContainer.baseUrl}seller/stories/$id';
    return await _request(url, Method.deleteMethod, null, auth: true);
  }

  Future<ResponseModel> _requestMultipartStory(String url, Map<String, dynamic> fields, {File? media}) async {
    try {
      _dio.options.headers['Authorization'] = '$tokenType $token';
      final formData = dioX.FormData.fromMap({
        ...fields,
        if (media != null) 'media': await dioX.MultipartFile.fromFile(media.path, filename: media.path.split(RegExp(r'[/\\]')).last),
      });
      final response = await _dio.post(url, data: formData);
      if (response.statusCode == 200) {
        if (response.data is Map && response.data['status'] == 'success') {
          return ResponseModel(true, response.data['message']?.toString() ?? '', response.statusCode!, response.data);
        }
        return ResponseModel(false, response.data['message']?.toString() ?? 'Error', response.statusCode!, response.data);
      }
      return ResponseModel(false, 'Error del servidor: ${response.statusCode}', response.statusCode!, response.data);
    } catch (e) {
      print('Multipart error: $e');
      return ResponseModel(false, 'Error de conexión: $e', 500, null);
    }
  }
}
