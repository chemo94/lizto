import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_notification_service.dart';
import 'package:liztogo_repartidor/data/controller/dashboard/dashboard_controller.dart';
import 'package:liztogo_repartidor/data/repo/delivery/courier_repo.dart';
import 'package:liztogo_repartidor/data/repo/dashboard/dashboard_repo.dart';
import 'package:liztogo_repartidor/presentation/components/annotated_region/annotated_region_widget.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:liztogo_repartidor/data/services/push_notification_service.dart';
import 'package:liztogo_repartidor/presentation/components/will_pop_widget.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/courier_earnings_screen.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/courier_home_screen.dart';
import 'package:liztogo_repartidor/presentation/screens/notifications/courier_notifications_screen.dart';
import 'package:liztogo_repartidor/presentation/screens/profile_and_settings/profile_and_settings_screen.dart';
import '../../packages/flutter_floating_bottom_navigation_bar/floating_bottom_navigation_bar.dart';

class DashBoardScreen extends StatefulWidget {
  const DashBoardScreen({super.key});

  @override
  State<DashBoardScreen> createState() => _DashBoardScreenState();
}

class _DashBoardScreenState extends State<DashBoardScreen> {
  int selectedIndex = 0;
  late List<Widget> _widgets;

  @override
  void initState() {
    Get.find<ApiClient>().setSuppressAuthRedirect(true);
    Get.put(DashBoardRepo(apiClient: Get.find()));
    Get.put(DashBoardController(repo: Get.find()));
    Get.put(CourierRepo(apiClient: Get.find()));
    Get.put(CourierController(courierRepo: Get.find()));
    CourierNotificationService.init();
    _widgets = <Widget>[
      const CourierHomeScreen(),
      const CourierEarningsScreen(),
      const CourierNotificationsScreen(),
      const ProfileAndSettingsScreen(),
    ];
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      Get.find<CourierNotificationService>().subscribeAll();
      PushNotificationService(apiClient: Get.find()).sendUserToken();
    });
  }

  void changeScreen(int val) {
    setState(() {
      selectedIndex = val;
    });
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return WillPopWidget(
      child: AnnotatedRegionWidget(
        systemNavigationBarColor: isDark ? const Color(0xFF1E293B) : MyColor.colorWhite,
        statusBarColor: MyColor.transparentColor,
        child: GetBuilder<DashBoardController>(
          builder: (controller) => Scaffold(
            extendBody: true,
            body: IndexedStack(index: controller.selectedTab, children: _widgets),
            bottomNavigationBar: FloatingNavbar(
              inLine: false,
              fontSize: Dimensions.fontExtraSmall,
              backgroundColor: isDark ? const Color(0xFF1E293B) : MyColor.colorWhite,
              unselectedItemColor: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor,
              selectedItemColor: MyColor.primaryColor,
              borderRadius: Dimensions.space30,
              itemBorderRadius: Dimensions.space20,
              selectedBackgroundColor: MyColor.primaryColor.withValues(
                alpha: 0.12,
              ),
              onTap: (int val) {
                controller.changeTab(val);
              },
              margin: const EdgeInsetsDirectional.only(
                start: Dimensions.space15,
                end: Dimensions.space15,
                bottom: Dimensions.space12,
              ),
              currentIndex: controller.selectedTab,
              items: [
                FloatingNavbarItem(
                  icon: Icons.delivery_dining_rounded,
                  title: 'Repartos',
                  customWidget: Icon(
                    Icons.delivery_dining_rounded,
                    color: selectedIndex == 0 ? MyColor.primaryColor : (isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor),
                  ),
                ),
                FloatingNavbarItem(
                  icon: Icons.payments_rounded,
                  title: 'Ganancias',
                  customWidget: Icon(
                    Icons.payments_rounded,
                    color: selectedIndex == 1 ? MyColor.primaryColor : (isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor),
                  ),
                ),
                FloatingNavbarItem(
                  icon: Icons.notifications_active_rounded,
                  title: 'Alertas',
                  customWidget: Icon(
                    Icons.notifications_active_rounded,
                    color: selectedIndex == 2 ? MyColor.primaryColor : (isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor),
                  ),
                ),
                FloatingNavbarItem(
                  icon: Icons.person_rounded,
                  title: 'Perfil',
                  customWidget: Icon(
                    Icons.person_rounded,
                    color: selectedIndex == 3 ? MyColor.primaryColor : (isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
