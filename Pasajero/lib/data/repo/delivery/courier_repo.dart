import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'dart:io';

class CourierRepo {
  final ApiClient apiClient;
  CourierRepo({required this.apiClient});

  Future<ResponseModel> _get(String path) async {
    return await apiClient.request('${UrlContainer.baseUrl}driver/courier/$path', Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> _post(String path, [Map<String, dynamic>? data]) async {
    return await apiClient.request('${UrlContainer.baseUrl}driver/courier/$path', Method.postMethod, data, passHeader: true);
  }

  Future<ResponseModel> getPendingJobs() => _get('jobs/pending');
  Future<ResponseModel> getActiveJobs() => _get('jobs/active');
  Future<ResponseModel> getJobHistory() => _get('jobs/history');
  Future<ResponseModel> getEarnings() => _get('earnings');
  Future<ResponseModel> getJobDetail(int jobId) => _get('jobs/$jobId');

  Future<ResponseModel> acceptJob(int jobId, String type) async {
    return await _post('jobs/$jobId/accept', <String, dynamic>{'type': type});
  }

  Future<ResponseModel> updateJobStatus(int jobId, String status, {double? lat, double? lng}) async {
    Map<String, dynamic> data = {'status': status};
    if (lat != null) data['latitude'] = lat;
    if (lng != null) data['longitude'] = lng;
    return await _post('jobs/$jobId/status', data);
  }

  Future<ResponseModel> sendLocationUpdate(int jobId, double lat, double lng, double? bearing) async {
    Map<String, dynamic> data = {'latitude': lat, 'longitude': lng};
    if (bearing != null) data['bearing'] = bearing;
    return await _post('jobs/$jobId/location', data);
  }

  Future<ResponseModel> uploadProofImage(int jobId, File imageFile) async {
    String url = '${UrlContainer.baseUrl}driver/courier/jobs/$jobId/proof';
    return await apiClient.multipartRequest(url, Method.postMethod, {},
        files: {'image': imageFile}, passHeader: true);
  }
}
