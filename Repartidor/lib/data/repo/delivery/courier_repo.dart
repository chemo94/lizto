import 'package:liztogo_repartidor/core/utils/method.dart';
import 'package:liztogo_repartidor/core/utils/url_container.dart';
import 'package:liztogo_repartidor/data/model/global/response_model/response_model.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'dart:io';

class CourierRepo {
  final ApiClient apiClient;
  CourierRepo({required this.apiClient});

  Future<ResponseModel> _get(String path) async {
    return await apiClient.request('${UrlContainer.baseUrl}driver/courier/$path', Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> _getWithParams(String path, Map<String, String> params) async {
    final uri = Uri.parse('${UrlContainer.baseUrl}driver/courier/$path').replace(queryParameters: params);
    return await apiClient.request(uri.toString(), Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> _post(String path, [Map<String, dynamic>? data]) async {
    return await apiClient.request('${UrlContainer.baseUrl}driver/courier/$path', Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> getPendingJobs() => _get('jobs/pending');
  Future<ResponseModel> getActiveJobs() => _get('jobs/active');
  Future<ResponseModel> getJobHistory() => _get('jobs/history');
  Future<ResponseModel> getEarnings() => _get('earnings');
  Future<ResponseModel> getHeatmapData() => _get('heatmap');
  Future<ResponseModel> getJobDetail(int jobId, {String? type}) {
    if (type != null) {
      return _getWithParams('jobs/$jobId', {'type': type});
    }
    return _get('jobs/$jobId');
  }

  Future<ResponseModel> acceptJob(int jobId, String type) async {
    return await _post('jobs/$jobId/accept', <String, dynamic>{'type': type});
  }

  Future<ResponseModel> cancelJob(int jobId, String type, String reasonCode, {String? reasonDetail}) async {
    final data = <String, dynamic>{'type': type, 'reason_code': reasonCode};
    if (reasonDetail != null && reasonDetail.trim().isNotEmpty) data['reason_detail'] = reasonDetail.trim();
    return await _post('jobs/$jobId/cancel', data);
  }

  Future<ResponseModel> updateJobStatus(int jobId, String status, {double? lat, double? lng, String? type, bool paymentConfirmed = false}) async {
    Map<String, dynamic> data = {'status': status};
    if (lat != null) data['latitude'] = lat;
    if (lng != null) data['longitude'] = lng;
    if (type != null) data['type'] = type;
    if (paymentConfirmed) data['payment_confirmed'] = true;
    return await _post('jobs/$jobId/status', data);
  }

  Future<ResponseModel> sendLocationUpdate(int jobId, double lat, double lng, double? bearing) async {
    Map<String, dynamic> data = {'latitude': lat, 'longitude': lng};
    if (bearing != null) data['bearing'] = bearing;
    return await _post('jobs/$jobId/location', data);
  }

  Future<ResponseModel> uploadProofImage(int jobId, File imageFile) async {
    String url = '${UrlContainer.baseUrl}driver/courier/jobs/$jobId/proof';
    return await apiClient.multipartRequest(url, Method.postMethod, {}, files: {'image': imageFile}, passHeader: true);
  }

  // ── Sprint 1: Delivery Confirmation ──

  Future<ResponseModel> verifyPin(int jobId, String pinCode, {String type = 'favor'}) async {
    return await _post('jobs/$jobId/verify-pin', {'pin_code': pinCode, 'type': type});
  }

  Future<ResponseModel> getConfirmationRequirements(int jobId, {String type = 'favor'}) {
    return _getWithParams('jobs/$jobId/confirmation-requirements', {'type': type});
  }

  // ── Sprint 1: Update status with PIN ──

  Future<ResponseModel> updateJobStatusWithPin(int jobId, String status, {double? lat, double? lng, String? type, bool paymentConfirmed = false, String? pinCode}) async {
    Map<String, dynamic> data = {'status': status};
    if (lat != null) data['latitude'] = lat;
    if (lng != null) data['longitude'] = lng;
    if (type != null) data['type'] = type;
    if (paymentConfirmed) data['payment_confirmed'] = true;
    if (pinCode != null) data['pin_code'] = pinCode;
    return await _post('jobs/$jobId/status', data);
  }

  // ── Sprint 3.2: Return Handling ──

  Future<ResponseModel> acceptReturn(int jobId, {String type = 'favor'}) async {
    return await _post('jobs/$jobId/accept-return', {'type': type});
  }

  Future<ResponseModel> pickupReturn(int jobId, {String type = 'favor'}) async {
    return await _post('jobs/$jobId/pickup-return', {'type': type});
  }

  Future<ResponseModel> completeReturn(int jobId, {String type = 'favor'}) async {
    return await _post('jobs/$jobId/complete-return', {'type': type});
  }

  // ── Chat ──
  Future<ResponseModel> getMessages(int jobId) => _get('jobs/$jobId/messages');

  Future<ResponseModel> sendMessage(int jobId, String text) async {
    return await _post('jobs/$jobId/messages/send', {'message': text});
  }

  Future<ResponseModel> sendImage(int jobId, File imageFile) async {
    String url = '${UrlContainer.baseUrl}driver/courier/jobs/$jobId/messages/send-image';
    return await apiClient.multipartRequest(url, Method.postMethod, {}, files: {'image': imageFile}, passHeader: true);
  }

  // ── Phase 2 & 3: Batches, Offers & Auto-Acceptance ──

  Future<ResponseModel> getPendingOffers() => _get('offers/pending');

  Future<ResponseModel> acceptOffer(int offerId) => _post('offers/$offerId/accept');

  Future<ResponseModel> rejectOffer(int offerId) => _post('offers/$offerId/reject');

  Future<ResponseModel> getActiveBatch() => _get('batches/active');

  Future<ResponseModel> getBatchDetail(int batchId) => _get('batches/$batchId');

  Future<ResponseModel> acceptBatch(int batchId) => _post('batches/$batchId/accept');

  Future<ResponseModel> completeBatchStop(int batchId, int stopNumber) =>
      _post('batches/$batchId/stops/$stopNumber/complete');

  Future<ResponseModel> getAutoAcceptSettings() => _get('auto-accept/settings');

  Future<ResponseModel> updateAutoAcceptSettings(bool enabled, double minEarning, double maxDistance) =>
      _post('auto-accept/settings', {
        'auto_accept_enabled': enabled,
        'auto_accept_min_earning': minEarning,
        'auto_accept_max_distance': maxDistance,
      });

  Future<ResponseModel> getEconomicStatus() => _get('wallet/economic-status');
}
