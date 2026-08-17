import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_notification_service.dart';

import '../../../data/controller/dashboard/dashboard_controller.dart';

class CourierNotificationsScreen extends StatelessWidget {
  const CourierNotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF0F172A) : MyColor.screenBgColor,
      appBar: AppBar(
        backgroundColor: isDark ? const Color(0xFF1E293B) : MyColor.primaryColor,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
          onPressed: () {
            if (Navigator.canPop(context)) {
              Get.back();
            } else {
              if (Get.isRegistered<DashBoardController>()) {
                Get.find<DashBoardController>().changeTab(0);
              } else {
                Get.back();
              }
            }
          },
        ),
        title: Text(
          'Historial de Notificaciones',
          style: boldDefault.copyWith(
            color: MyColor.colorWhite,
            fontSize: Dimensions.fontLarge,
          ),
        ),
        centerTitle: true,
      ),
      body: GetBuilder<CourierNotificationService>(
        init: Get.find<CourierNotificationService>(),
        builder: (service) {
          final notifications = service.liveNotifications;

          if (notifications.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(
                    Icons.notifications_off_outlined,
                    size: 64,
                    color: isDark ? Colors.grey[600] : MyColor.bodyMutedTextColor,
                  ),
                  const SizedBox(height: Dimensions.space15),
                  Text(
                    'No tienes notificaciones registradas',
                    style: boldDefault.copyWith(
                      color: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor,
                      fontSize: Dimensions.fontMedium,
                    ),
                  ),
                  const SizedBox(height: Dimensions.space6),
                  Text(
                    'Las alertas en tiempo real aparecerán aquí.',
                    style: regularDefault.copyWith(
                      color: isDark ? Colors.grey[500] : MyColor.bodyMutedTextColor,
                      fontSize: Dimensions.fontSmall,
                    ),
                  ),
                ],
              ),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.only(
              left: Dimensions.space15,
              right: Dimensions.space15,
              top: Dimensions.space15,
              bottom: 95,
            ),
            itemCount: notifications.length,
            itemBuilder: (context, index) {
              final item = notifications[index];
              return Container(
                margin: const EdgeInsets.only(bottom: Dimensions.space12),
                padding: const EdgeInsets.all(Dimensions.space15),
                decoration: BoxDecoration(
                  color: isDark ? const Color(0xFF1E293B) : MyColor.colorWhite,
                  borderRadius: BorderRadius.circular(Dimensions.space15),
                  boxShadow: [
                    BoxShadow(
                      color: isDark
                          ? Colors.black.withValues(alpha: 0.2)
                          : MyColor.shadowColor.withValues(alpha: 0.5),
                      blurRadius: 8,
                      offset: const Offset(0, 3),
                    ),
                  ],
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(Dimensions.space10),
                      decoration: BoxDecoration(
                        color: item.iconColor.withValues(alpha: 0.15),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(item.icon, color: item.iconColor, size: 22),
                    ),
                    const SizedBox(width: Dimensions.space12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  item.title,
                                  style: boldDefault.copyWith(
                                    color: isDark ? Colors.white : MyColor.primaryTextColor,
                                    fontSize: Dimensions.fontSmall + 1,
                                  ),
                                ),
                              ),
                              Text(
                                item.timestamp,
                                style: regularDefault.copyWith(
                                  color: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor,
                                  fontSize: Dimensions.fontExtraSmall,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: Dimensions.space6),
                          Text(
                            item.message,
                            style: regularDefault.copyWith(
                              color: isDark ? Colors.grey[300] : MyColor.bodyTextColor,
                              fontSize: Dimensions.fontSmall,
                            ),
                          ),
                          if (item.type == 'balance_alert') ...[
                            const SizedBox(height: Dimensions.space10),
                            Align(
                              alignment: Alignment.centerRight,
                              child: TextButton.icon(
                                onPressed: () => Get.toNamed(RouteHelper.newDepositScreenScreen),
                                icon: const Icon(Icons.add_card_rounded, size: 16),
                                label: const Text('Recargar Saldo'),
                                style: TextButton.styleFrom(
                                  foregroundColor: MyColor.primaryColor,
                                  padding: EdgeInsets.zero,
                                  minimumSize: Size.zero,
                                  tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              );
            },
          );
        },
      ),
    );
  }
}
