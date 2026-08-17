import 'package:get/get.dart';
import 'package:liztogo/core/route/route.dart';
import 'package:liztogo/presentation/screens/delivery/delivery_home_screen.dart';
import 'package:liztogo/presentation/screens/delivery/order_detail_screen.dart';
import 'package:liztogo/presentation/screens/delivery/favor_tracking_screen.dart';
import 'package:liztogo/presentation/screens/delivery/courier_home_screen.dart';

class DeepLinkHandler {
  static void handleDeepLink(String? route, dynamic arguments) {
    if (route == null) return;
    switch (route) {
      case 'delivery_home':
        Get.to(() => const DeliveryHomeScreen());
        break;
      case 'delivery_order':
        if (arguments is int) Get.to(() => OrderDetailScreen(orderId: arguments));
        break;
      case 'favor_tracking':
        if (arguments is int) Get.to(() => FavorTrackingScreen(favorId: arguments));
        break;
      case 'courier_home':
        Get.to(() => const CourierHomeScreen());
        break;
      case 'payment_history':
        Get.toNamed(RouteHelper.paymentHistoryScreen);
        break;
      default:
        Get.toNamed(RouteHelper.homeScreen);
    }
  }
}
