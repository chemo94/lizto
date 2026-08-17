import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/util.dart';
import 'package:lizto_delivery/data/controller/home/home_controller.dart';
import 'package:lizto_delivery/data/controller/menu/my_menu_controller.dart';
import 'package:lizto_delivery/data/repo/auth/general_setting_repo.dart';
import 'package:lizto_delivery/data/repo/menu_repo/menu_repo.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_notification_service.dart';
import 'package:lizto_delivery/data/repo/delivery/delivery_repo.dart';
import 'package:lizto_delivery/data/repo/home/home_repo.dart';
import 'package:lizto_delivery/data/controller/delivery/notification_inbox_controller.dart';
import 'package:lizto_delivery/data/controller/delivery/user_address_controller.dart';
import 'package:lizto_delivery/presentation/components/annotated_region/annotated_region_widget.dart';
import 'package:lizto_delivery/presentation/screens/delivery/delivery_home_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/favor_home_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/order_list_screen.dart';
import 'package:lizto_delivery/presentation/components/customer_design.dart';
import 'package:lizto_delivery/presentation/screens/profile_and_settings/profile_and_settings_screen.dart';
import 'package:flutter/material.dart';
import '../../../core/utils/my_color.dart';
import '../../../data/repo/account/profile_repo.dart';
import '../../components/will_pop_widget.dart';
import '../drawer/drawer_screen.dart';

class DashBoardScreen extends StatefulWidget {
  const DashBoardScreen({super.key});

  @override
  State<DashBoardScreen> createState() => _DashBoardScreenState();
}

class _DashBoardScreenState extends State<DashBoardScreen> {
  final GlobalKey<ScaffoldState> _dashBoardScaffoldKey = GlobalKey<ScaffoldState>();
  late List<Widget> _widgets;
  int selectedIndex = 0;

  @override
  void initState() {
    super.initState();
    final routeArgument = Get.arguments;
    final index = routeArgument is int ? routeArgument : 0;

    if (!Get.isRegistered<GeneralSettingRepo>()) {
      Get.put(GeneralSettingRepo(apiClient: Get.find()));
    }
    if (!Get.isRegistered<MenuRepo>()) {
      Get.put(MenuRepo(apiClient: Get.find()));
    }
    if (!Get.isRegistered<MyMenuController>()) {
      Get.put(MyMenuController(menuRepo: Get.find(), repo: Get.find()));
    }
    if (!Get.isRegistered<DeliveryRepo>()) {
      Get.put(DeliveryRepo(apiClient: Get.find()));
    }
    if (!Get.isRegistered<DeliveryController>()) {
      Get.put(DeliveryController(deliveryRepo: Get.find()), permanent: true);
    }
    if (!Get.isRegistered<ProfileRepo>()) {
      Get.put(ProfileRepo(apiClient: Get.find()));
    }
    if (!Get.isRegistered<NotificationInboxController>()) {
      Get.put(NotificationInboxController());
    }
    if (!Get.isRegistered<UserAddressController>()) {
      Get.put(UserAddressRepo(apiClient: Get.find()));
      Get.put(UserAddressController(repo: Get.find()));
    }
    if (!Get.isRegistered<HomeRepo>()) {
      Get.put(HomeRepo(apiClient: Get.find()));
    }
    if (!Get.isRegistered<HomeController>()) {
      Get.put(HomeController(homeRepo: Get.find()));
    }
    DeliveryNotificationService.init();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<DeliveryNotificationService>().subscribeAll();
    });

    _widgets = <Widget>[
      DeliveryHomeScreen(dashBoardScaffoldKey: _dashBoardScaffoldKey),
      const FavorHomeScreen(showBottomMenu: false),
      const OrderListScreen(embedded: true),
      ProfileAndSettingsScreen(
        onBackPress: () {
          changeScreen(0);
        },
      ),
    ];
    selectedIndex = index.clamp(0, _widgets.length - 1);
  }

  void closeDrawer() {
    _dashBoardScaffoldKey.currentState!.closeDrawer();
  }

  void changeScreen(int val) {
    if (val < 0 || val >= _widgets.length || val == selectedIndex) return;
    setState(() {
      selectedIndex = val;
    });
  }

  Widget _buildPrimaryTabs() {
    return CustomerBottomNavigation(
      currentIndex: selectedIndex,
      onChanged: changeScreen,
      items: const [
        CustomerNavItem(icon: Icons.shopping_bag_outlined, label: 'Comprar'),
        CustomerNavItem(icon: Icons.volunteer_activism_outlined, label: 'Favor'),
        CustomerNavItem(icon: Icons.receipt_long_outlined, label: 'Actividad'),
        CustomerNavItem(icon: Icons.person_outline_rounded, label: 'Perfil'),
      ],
    );
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
                },
              ),
              body: WillPopWidget(child: IndexedStack(index: selectedIndex, children: _widgets)),
              bottomNavigationBar: _buildPrimaryTabs(),
            );
          },
        ),
      ),
    );
  }
}
