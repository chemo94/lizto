import 'dart:io';
import 'package:lizto_delivery/core/utils/method.dart';
import 'package:lizto_delivery/core/utils/url_container.dart';
import 'package:lizto_delivery/data/model/global/response_model/response_model.dart';
import 'package:lizto_delivery/data/services/api_client.dart';

class ShoppingRepo {
  final ApiClient apiClient;
  ShoppingRepo({required this.apiClient});

  // ── Driver Shopping Endpoints ──

  Future<ResponseModel> storeConfirm(int favorId) async {
    String url = '${UrlContainer.baseUrl}driver/shopping/favor/$favorId/store-confirm';
    return await apiClient.request(url, Method.postMethod, null, passHeader: true);
  }

  Future<ResponseModel> confirmItem(int favorId, int itemId, {Map<String, dynamic>? data}) async {
    String url = '${UrlContainer.baseUrl}driver/shopping/favor/$favorId/items/$itemId/confirm';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> notFoundItem(int favorId, int itemId) async {
    String url = '${UrlContainer.baseUrl}driver/shopping/favor/$favorId/items/$itemId/not-found';
    return await apiClient.request(url, Method.postMethod, null, passHeader: true);
  }

  Future<ResponseModel> proposeSubstitute(int favorId, int itemId, Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}driver/shopping/favor/$favorId/items/$itemId/substitute';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> uploadReceipt(int favorId, Map<String, dynamic> data, {File? image, File? receiptImage}) async {
    String url = '${UrlContainer.baseUrl}driver/shopping/favor/$favorId/receipt';
    Map<String, File> files = {};
    if (image != null) files['image'] = image;
    if (receiptImage != null) files['receipt_image'] = receiptImage;
    return await apiClient.multipartRequest(url, Method.postMethod, data, files: files, passHeader: true);
  }

  Future<ResponseModel> getChecklist(int favorId) async {
    String url = '${UrlContainer.baseUrl}driver/shopping/favor/$favorId/checklist';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }
}
