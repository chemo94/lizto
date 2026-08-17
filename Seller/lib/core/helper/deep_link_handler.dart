import 'package:get/get.dart';
import 'package:lizto_store/core/route/route.dart';

class DeepLinkHandler {
  static void handleDeepLink(String? route, dynamic arguments) {
    if (route == null) return;
    switch (route) {
      case 'seller':
        Get.toNamed(RouteHelper.loginScreen);
        break;
      default:
        Get.toNamed(RouteHelper.loginScreen);
    }
  }
}
