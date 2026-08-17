import 'dart:io';
import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/services/api_client.dart';

class FavorRepo {
  final ApiClient apiClient;
  FavorRepo({required this.apiClient});

  Future<ResponseModel> createFavor(Map<String, dynamic> data) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/create';
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> getFavors() async {
    String url = '${UrlContainer.baseUrl}delivery/favors';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> getFavorDetail(int favorId) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> cancelFavor(int favorId) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/cancel/$favorId';
    return await apiClient.request(url, Method.postMethod, null, passHeader: true);
  }

  Future<ResponseModel> getFavorMessages(int favorId) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/messages';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> sendFavorMessage(int favorId, String message, {String? imagePath}) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/messages/send';
    var data = {'message': message};
    if (imagePath != null) data['image'] = imagePath;
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> reviewFavor(int favorId, {double rating = 5, String review = ''}) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/review';
    return await apiClient.request(url, Method.postMethod, {'rating': rating, 'review': review}, passHeader: true);
  }

  Future<ResponseModel> sendFavorImage(int favorId, File imageFile) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/messages/send-image';
    return await apiClient.multipartRequest(url, Method.postMethod, {}, files: {'image': imageFile}, passHeader: true);
  }

  Future<ResponseModel> getFavorBids(int favorId) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/bids';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> acceptBid(int favorId, int bidId) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/$favorId/bids/$bidId/accept';
    return await apiClient.request(url, Method.postMethod, null, passHeader: true);
  }

  Future<ResponseModel> requestFavorRefund(int favorId, String reason) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/refund/$favorId';
    return await apiClient.request(url, Method.postMethod, {'reason': reason}, passHeader: true);
  }

  Future<ResponseModel> getFavorGateways() async {
    String url = '${UrlContainer.baseUrl}delivery/favors/gateways';
    return await apiClient.request(url, Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> estimateFavorFee({
    double? pickupLat,
    double? pickupLng,
    double? deliveryLat,
    double? deliveryLng,
    double? estimatedAmount,
  }) async {
    String url = '${UrlContainer.baseUrl}delivery/favors/fee-estimate';
    var data = <String, dynamic>{};
    if (pickupLat != null) data['pickup_lat'] = pickupLat;
    if (pickupLng != null) data['pickup_lng'] = pickupLng;
    if (deliveryLat != null) data['delivery_lat'] = deliveryLat;
    if (deliveryLng != null) data['delivery_lng'] = deliveryLng;
    if (estimatedAmount != null) data['estimated_amount'] = estimatedAmount;
    return await apiClient.request(url, Method.postMethod, data, passHeader: true);
  }
}
