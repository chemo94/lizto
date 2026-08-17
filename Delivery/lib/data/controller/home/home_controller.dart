import 'package:get/get.dart';
import 'package:lizto_delivery/data/model/dashboard/dashboard_response_model.dart';
import 'package:lizto_delivery/data/model/home/banner_model.dart';
import 'package:lizto_delivery/data/repo/home/home_repo.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';

class HomeController extends GetxController {
  HomeRepo homeRepo;
  HomeController({required this.homeRepo});

  List<BannerModel> deliveryBannersList = [];
  String bannerImagePath = '';
  bool isLoading = false;

  Future<void> loadData() async {
    isLoading = true;
    update();
    try {
      var response = await homeRepo.getData();
      if (response.statusCode == 200 && response.responseJson != null) {
        DashBoardResponseModel model = DashBoardResponseModel.fromJson(response.responseJson);
        if (model.status == MyStrings.success && model.data != null) {
          deliveryBannersList = model.data?.deliveryBanners ?? [];
          bannerImagePath = model.data?.bannerImagePath ?? '';
        }
      }
    } catch (e) {
      // silently fail - static banners will be shown as fallback
    } finally {
      isLoading = false;
      update();
    }
  }

  @override
  void onInit() {
    super.onInit();
    loadData();
  }
}
