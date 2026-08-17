import 'dart:io';
import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/services/api_client.dart';

class ShoppingRepo {
  final ApiClient apiClient;
  ShoppingRepo({required this.apiClient});

  // ── Shopping List Items ──

  Future<ResponseModel> addItem(int favorId, Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/items';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> updateItem(int favorId, int itemId, Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/items/$itemId';
    return await apiClient.request(url, Method.updateMethod, data, passHeader: true);
  }

  Future<ResponseModel> removeItem(int favorId, int itemId) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/items/$itemId';
    return await apiClient.request(url, Method.deleteMethod, null, passHeader: true);
  }

  Future<ResponseModel> uploadItemImage(int favorId, int itemId, File imageFile) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/items/$itemId/image';
    return await apiClient.multipartRequest(url, Method.postMethod, {},
        files: {'image': imageFile}, passHeader: true);
  }

  Future<ResponseModel> getShoppingList(int favorId) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/items';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  // ── Budget ──

  Future<ResponseModel> setBudget(int favorId, Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/budget';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  // ── Submit Shopping ──

  Future<ResponseModel> submitShopping(int favorId) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/submit';
    return await apiClient.request(url, Method.postMethod, null, passHeader: true);
  }

  // ── Substitution Approval ──

  Future<ResponseModel> approveSubstitution(int favorId, int itemId, Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/items/$itemId/approve-substitution';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  // ── Confirm Purchase ──

  Future<ResponseModel> confirmPurchase(int favorId, Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/confirm-purchase';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  // ── Shopping Status ──

  Future<ResponseModel> getShoppingStatus(int favorId) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/shopping/status';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }
}
