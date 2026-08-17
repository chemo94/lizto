import 'package:get/get.dart';
import 'package:liztogo/core/utils/util.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/data/controller/delivery/notification_inbox_controller.dart';
import 'package:liztogo/data/controller/menu/my_menu_controller.dart';
import 'package:liztogo/data/controller/pusher/global_pusher_controller.dart';
import 'package:liztogo/data/controller/ride/all_ride_controller.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/data/controller/location/app_location_controller.dart';
import 'package:liztogo/data/controller/map/home_map_controller.dart';
import 'package:liztogo/data/repo/home/home_repo.dart';
import 'package:liztogo/data/repo/auth/general_setting_repo.dart';
import 'package:liztogo/data/repo/delivery/delivery_repo.dart';
import 'package:liztogo/data/repo/menu_repo/menu_repo.dart';
import 'package:liztogo/presentation/components/annotated_region/annotated_region_widget.dart';
import 'package:liztogo/presentation/screens/delivery/delivery_home_screen.dart';
import 'package:liztogo/presentation/screens/delivery/favor_home_screen.dart';
import 'package:liztogo/presentation/screens/home/home_screen.dart';
import 'package:liztogo/presentation/screens/profile_and_settings/profile_and_settings_screen.dart';
import 'package:flutter/material.dart';
import 'package:liztogo/presentation/screens/ride/ride_activity_screen.dart';
import 'package:liztogo/presentation/components/customer_design.dart';
import '../../../core/utils/my_color.dart';
import '../../components/will_pop_widget.dart';
import '../drawer/drawer_screen.dart';

class DashBoardScreen extends StatefulWidget {
  const DashBoardScreen({super.key});

  @override
  State<DashBoardScreen> createState() => _DashBoardScreenState();
}

class _DashBoardScreenState extends State<DashBoardScreen> {
  late final GlobalKey<ScaffoldState> _dashBoardScaffoldKey;
  late List<Widget> _widgets;
  int selectedIndex = 0;

  @override
  void initState() {
    int index = Get.arguments ?? 0;
    selectedIndex = index;
    super.initState();

    // Register Home Dependencies first to prevent null-errors in dashboard layout builders
    Get.put(HomeRepo(apiClient: Get.find()));
    Get.put(AppLocationController());
    Get.put(HomeController(homeRepo: Get.find(), appLocationController: Get.find()));
    Get.put(HomeMapController(homeRepo: Get.find(), homeController: Get.find()));

    Get.put(GeneralSettingRepo(apiClient: Get.find()));
    Get.put(MenuRepo(apiClient: Get.find()));
    Get.put(MyMenuController(menuRepo: Get.find(), repo: Get.find()));
    Get.put(DeliveryRepo(apiClient: Get.find()));
    Get.put(DeliveryController(deliveryRepo: Get.find()));
    Get.put(NotificationInboxController());
    final pusherController = Get.put(GlobalPusherController(apiClient: Get.find()));
    _dashBoardScaffoldKey = GlobalKey<ScaffoldState>();

    _widgets = <Widget>[
      HomeScreen(dashBoardScaffoldKey: _dashBoardScaffoldKey), // 0 - Viaje
      DeliveryHomeScreen(dashBoardScaffoldKey: _dashBoardScaffoldKey), // 1 - Comida
      FavorHomeScreen(), // 2 - Entrega (Favores)
      RideActivityScreen(
        onBackPress: () {
          changeScreen(0);
        },
      ), // 3 - Actividad
      ProfileAndSettingsScreen(
        onBackPress: () {
          changeScreen(0);
        },
      ), // 4 - Perfil
    ];
    WidgetsBinding.instance.addPostFrameCallback((t) {
      pusherController.ensureConnection();
    });
  }

  void closeDrawer() {
    _dashBoardScaffoldKey.currentState!.closeDrawer();
  }

  void changeScreen(int val) {
    setState(() {
      selectedIndex = val;
    });
    if (val == 1) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (Get.isRegistered<DeliveryController>()) {
          final c = Get.find<DeliveryController>();
          if (c.generalCategories.isEmpty && !c.isLoading) {
            c.loadGeneralCategories();
            c.loadNearbyStores();
            c.loadFavoriteStores();
          }
        }
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        MyUtils.closeKeyboard();
      },
      child: AnnotatedRegionWidget(
        systemNavigationBarColor: MyColor.colorWhite,
        statusBarColor: MyColor.transparentColor,
        child: GetBuilder<MyMenuController>(
          builder: (controller) {
            return Scaffold(
              key: _dashBoardScaffoldKey,
              extendBody: true,
              drawer: AppDrawerScreen(
                closeFunction: closeDrawer,
                callback: (val) {
                  controller.repo.apiClient.storeCurrentTab(val.toString());
                  selectedIndex = val;
                  setState(() {});
                  closeDrawer();
                  if (val == 0 && Get.isRegistered<AllRideController>()) {
                    Get.find<AllRideController>().changeTab(0);
                  }
                },
              ),
              body: WillPopWidget(
                child: Stack(
                  children: [
                    IndexedStack(index: selectedIndex, children: _widgets),
                    // Floating hamburger menu button (visible on main tabs except Comida/Delivery)
                    if (selectedIndex <= 2 && selectedIndex != 1)
                      Positioned(
                        top: MediaQuery.of(context).padding.top + 12,
                        left: 16,
                        child: GestureDetector(
                          onTap: () => _dashBoardScaffoldKey.currentState?.openDrawer(),
                          child: Container(
                            height: 44,
                            width: 44,
                            decoration: BoxDecoration(
                              color: MyColor.cardBgColor,
                              shape: BoxShape.circle,
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.18),
                                  blurRadius: 10,
                                  offset: const Offset(0, 3),
                                ),
                              ],
                            ),
                            child: const Icon(
                              Icons.menu_rounded,
                              color: MyColor.primaryColor,
                              size: 22,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
              bottomNavigationBar: CustomerBottomNavigation(
                currentIndex: selectedIndex == 4 ? 4 : selectedIndex,
                onChanged: changeScreen,
                items: const [
                  CustomerNavItem(icon: Icons.local_taxi_outlined, label: 'Taxi'),
                  CustomerNavItem(icon: Icons.shopping_bag_outlined, label: 'Delivery'),
                  CustomerNavItem(icon: Icons.volunteer_activism_outlined, label: 'Favor'),
                  CustomerNavItem(icon: Icons.receipt_long_outlined, label: 'Actividad'),
                  CustomerNavItem(icon: Icons.person_outline_rounded, label: 'Perfil'),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}
