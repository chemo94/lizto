import 'package:flutter/services.dart';
import 'package:lizto_delivery/core/helper/shared_preference_helper.dart';
import 'package:lizto_delivery/core/helper/string_format_helper.dart';
import 'package:lizto_delivery/core/route/route.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/menu/my_menu_controller.dart';
import 'package:lizto_delivery/data/services/api_client.dart';
import 'package:lizto_delivery/presentation/components/divider/custom_spacer.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:get/get.dart';

import '../../../core/utils/dimensions.dart';
import '../../../core/utils/my_color.dart';
import '../../../core/utils/my_icons.dart';
import '../../../core/utils/my_strings.dart';
import '../../components/divider/custom_divider.dart';
import '../../components/image/my_network_image_widget.dart';
import 'widget/drawer_item_list_card.dart';
import 'widget/drawer_user_info_card.dart';
import '../delivery/order_list_screen.dart';
import '../delivery/favor_list_screen.dart';
import '../seller/seller_screen.dart';
import '../delivery/payment_history_screen.dart';
import '../delivery/notification_inbox_screen.dart';
import '../delivery/wallet_screen.dart';

class AppDrawerScreen extends StatefulWidget {
  Function(int) callback;
  VoidCallback closeFunction;
  AppDrawerScreen({
    super.key,
    required this.callback,
    required this.closeFunction,
  });

  @override
  State<AppDrawerScreen> createState() => _AppDrawerScreenState();
}

class _AppDrawerScreenState extends State<AppDrawerScreen> {
  TextStyle get _drawerItemStyle => boldDefault.copyWith(
        fontWeight: FontWeight.w500,
        fontSize: Dimensions.fontLarge,
      );

  Widget _sectionTitle(String title) {
    return Align(
      alignment: AlignmentDirectional.centerStart,
      child: Text(
        title,
        style: regularDefault.copyWith(
          color: MyColor.bodyMutedTextColor,
          fontSize: Dimensions.fontSmall,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.6,
        ),
      ),
    );
  }

  Widget _itemGap() => spaceDown(Dimensions.space17);

  Widget _sectionGap() {
    return const CustomDivider(
      onlyTop: true,
      space: Dimensions.space20,
      onlyBottom: true,
    );
  }

  DrawerItem _drawerItem({
    required String icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return DrawerItem(
      svgIcon: icon,
      name: label,
      titleStyle: _drawerItemStyle,
      iconColor: MyColor.getPrimaryColor(),
      onTap: onTap,
    );
  }

  @override
  Widget build(BuildContext context) {
    String name = Get.find<ApiClient>().sharedPreferences.getString(
              SharedPreferenceHelper.userFullNameKey,
            ) ??
        '';
    String userName = Get.find<ApiClient>().getUserName();
    String phone = Get.find<ApiClient>().sharedPreferences.getString(
              SharedPreferenceHelper.userPhoneNumberKey,
            ) ??
        '';
    String avatar = Get.find<ApiClient>().sharedPreferences.getString(
              SharedPreferenceHelper.userProfileKey,
            ) ??
        '';
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: const SystemUiOverlayStyle(
        statusBarColor: MyColor.colorWhite,
        statusBarBrightness: Brightness.light,
        statusBarIconBrightness: Brightness.dark,
      ),
      child: SafeArea(
        child: ClipRRect(
          borderRadius: const BorderRadiusDirectional.only(
            topStart: Radius.circular(Dimensions.space15),
            bottomStart: Radius.circular(Dimensions.space15),
          ),
          child: GetBuilder<MyMenuController>(
            builder: (controller) {
              return Drawer(
                width: MediaQuery.of(context).size.width / 1.5,
                backgroundColor: MyColor.colorWhite,
                child: Column(
                  children: [
                    Expanded(
                      child: SingleChildScrollView(
                        physics: const BouncingScrollPhysics(),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              margin: const EdgeInsetsDirectional.only(
                                end: Dimensions.space15,
                              ),
                              height: Dimensions.space50,
                              width: double.infinity,
                              child: GestureDetector(
                                behavior: HitTestBehavior.translucent,
                                onTap: () => widget.closeFunction(),
                                child: Align(
                                  alignment: Alignment.centerRight,
                                  child: Icon(
                                    Icons.close_rounded,
                                    size: 30,
                                    color: MyColor.getTextColor(),
                                  ),
                                ),
                              ),
                            ),
                            Padding(
                              padding: const EdgeInsetsDirectional.only(
                                start: Dimensions.space25,
                                end: Dimensions.space15,
                              ),
                              child: GestureDetector(
                                onTap: () {
                                  Get.toNamed(RouteHelper.profileScreen);
                                  widget.closeFunction();
                                },
                                child: DrawerUserCard(
                                  fullName: name.removeNull(),
                                  username: userName.removeNull(),
                                  subtitle: "+${phone.removeNull()}",
                                  imgWidget: Container(
                                    decoration: BoxDecoration(
                                      border: Border.all(
                                        color: MyColor.borderColor,
                                        width: 0.5,
                                      ),
                                      shape: BoxShape.circle,
                                    ),
                                    height: 50,
                                    width: 50,
                                    child: ClipOval(
                                      child: MyImageWidget(
                                        imageUrl: avatar,
                                        boxFit: BoxFit.cover,
                                        isProfile: true,
                                      ),
                                    ),
                                  ),
                                  imgHeight: 40,
                                  imgWidth: 40,
                                ),
                              ),
                            ),
                            const CustomDivider(
                              onlyTop: true,
                              space: Dimensions.space20,
                              onlyBottom: true,
                            ),
                            Padding(
                              padding: const EdgeInsetsDirectional.only(
                                start: Dimensions.space30,
                                top: Dimensions.space10,
                              ),
                              child: GetBuilder<MyMenuController>(
                                builder: (controller) {
                                  return Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      _sectionTitle('Principal'),
                                      _itemGap(),
                                      _drawerItem(
                                        icon: MyIcons.home,
                                        label: 'Inicio',
                                        onTap: () => widget.callback(0),
                                      ),
                                      _itemGap(),
                                      _drawerItem(
                                        icon: MyIcons.menu,
                                        label: 'Mi Perfil',
                                        onTap: () => widget.callback(2),
                                      ),
                                      _sectionGap(),
                                      _sectionTitle('Pedidos'),
                                      _itemGap(),
                                      _drawerItem(
                                        icon: MyIcons.city,
                                        label: 'Delivery',
                                        onTap: () => widget.callback(1),
                                      ),
                                      _itemGap(),
                                      _drawerItem(
                                        icon: MyIcons.activity,
                                        label: 'Mis Pedidos',
                                        onTap: () {
                                          Get.to(() => const OrderListScreen());
                                          widget.closeFunction();
                                        },
                                      ),
                                      _itemGap(),
                                      _drawerItem(
                                        icon: MyIcons.activity,
                                        label: 'Mis Favores',
                                        onTap: () {
                                          Get.to(() => const FavorListScreen());
                                          widget.closeFunction();
                                        },
                                      ),
                                      _sectionGap(),
                                      _sectionTitle('Finanzas'),
                                      _itemGap(),
                                      _drawerItem(
                                        icon: MyIcons.payment,
                                        label: 'Mi Billetera',
                                        onTap: () {
                                          Get.to(() => const WalletScreen());
                                          widget.closeFunction();
                                        },
                                      ),
                                      _itemGap(),
                                      _drawerItem(
                                        icon: MyIcons.payment,
                                        label: 'Pagos',
                                        onTap: () {
                                          Get.to(() => const DeliveryPaymentHistoryScreen());
                                          widget.closeFunction();
                                        },
                                      ),
                                      _itemGap(),
                                      _drawerItem(
                                        icon: MyIcons.activity,
                                        label: 'Notificaciones',
                                        onTap: () {
                                          Get.to(() => const NotificationInboxScreen());
                                          widget.closeFunction();
                                        },
                                      ),

                                    ],
                                  );
                                },
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    spaceDown(Dimensions.space10),
                    GestureDetector(
                      onTap: () {
                        if (controller.logoutLoading == false) {
                          controller.logout();
                        }
                      },
                      child: Container(
                        width: double.infinity,
                        margin: const EdgeInsetsDirectional.only(
                          end: Dimensions.space20,
                          start: Dimensions.space30,
                        ),
                        padding: const EdgeInsets.all(Dimensions.space15),
                        decoration: BoxDecoration(
                          color: MyColor.colorRed2.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(
                            Dimensions.space5,
                          ),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          mainAxisAlignment: MainAxisAlignment.start,
                          children: [
                            if (controller.logoutLoading) ...[
                              SizedBox(
                                width: Dimensions.space20,
                                height: Dimensions.space20,
                                child: FittedBox(
                                  child: CircularProgressIndicator(
                                    color: MyColor.colorRed2,
                                  ),
                                ),
                              )
                            ] else ...[
                              SvgPicture.asset(
                                MyIcons.logout,
                                fit: BoxFit.contain,
                                colorFilter: const ColorFilter.mode(
                                  MyColor.colorRed2,
                                  BlendMode.srcIn,
                                ),
                                height: 22,
                                width: 22,
                              ),
                            ],
                            spaceSide(Dimensions.space20),
                            Expanded(
                              child: Text(
                                MyStrings.logout.tr,
                                style: regularLarge.copyWith(
                                  color: MyColor.colorRed2,
                                  fontSize: Dimensions.fontExtraLarge,
                                  fontWeight: FontWeight.normal,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    spaceDown(Dimensions.space20),
                  ],
                ),
              );
            },
          ),
        ),
      ),
    );
  }
}
