import 'package:liztogo/core/utils/method.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/model/general_setting/general_setting_response_model.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/model/home/nearby_drivers_response_model.dart';
import 'package:liztogo/data/model/ride/create_ride_request_model.dart';
import 'package:liztogo/data/services/api_client.dart';

class HomeRepo {
  ApiClient apiClient;
  HomeRepo({required this.apiClient});

  Future<ResponseModel> getData() async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.dashBoardUrl}";
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.getMethod,
      null,
      passHeader: true,
    );
    return responseModel;
  }

  Future<ResponseModel> createRide({
    required CreateRideRequestModel data,
  }) async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.createRide}";
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.postMethod,
      data.toMap(),
      passHeader: true,
    );
    return responseModel;
  }

  Future<ResponseModel> getRideFare({
    required CreateRideRequestModel data,
  }) async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.rideFareAndDistance}";
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.postMethod,
      data.toMap(),
      passHeader: true,
    );
    return responseModel;
  }

  Future<NearbyDriversResponseModel> getNearbyDrivers({
    required double lat,
    required double lng,
    double radius = 10,
  }) async {
    String url = "${UrlContainer.baseUrl}${UrlContainer.nearbyDrivers}?lat=$lat&lng=$lng&radius=$radius";
    ResponseModel responseModel = await apiClient.request(
      url,
      Method.getMethod,
      null,
      passHeader: true,
    );
    return NearbyDriversResponseModel.fromJson(responseModel.responseJson);
  }

  Future<dynamic> refreshGeneralSetting() async {
    String url = '${UrlContainer.baseUrl}${UrlContainer.generalSettingEndPoint}';
    ResponseModel response = await apiClient.request(
      url,
      Method.getMethod,
      null,
      passHeader: false,
    );

    if (response.statusCode == 200) {
      GeneralSettingResponseModel model = GeneralSettingResponseModel.fromJson((response.responseJson));
      if (model.status?.toLowerCase() == MyStrings.success.toLowerCase()) {
        apiClient.storeGeneralSetting(model);
      }
    }
  }
}
