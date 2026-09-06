import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/notification_inbox_controller.dart';

class NotificationInboxScreen extends StatefulWidget {
  const NotificationInboxScreen({super.key});

  @override
  State<NotificationInboxScreen> createState() => _NotificationInboxScreenState();
}

class _NotificationInboxScreenState extends State<NotificationInboxScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (Get.isRegistered<NotificationInboxController>()) {
        Get.find<NotificationInboxController>().loadNotificationsFromBackend();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<NotificationInboxController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Notificaciones', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            actions: [
              if (c.notifications.isNotEmpty)
                TextButton(
                  onPressed: () => c.markAllRead(),
                  child: Text('Todo leído', style: regularSmall.copyWith(color: MyColor.colorWhite)),
                ),
            ],
          ),
          body: c.notifications.isEmpty
              ? Center(
                  child: Column(mainAxisSize: MainAxisSize.min, children: [
                    Icon(Icons.notifications_none_rounded, size: 64, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3)),
                    SizedBox(height: Dimensions.space12),
                    Text('Sin notificaciones', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                  ]),
                )
              : RefreshIndicator(
                  onRefresh: () => c.loadNotificationsFromBackend(),
                  child: ListView.builder(
                    padding: EdgeInsets.all(Dimensions.space12),
                    itemCount: c.notifications.length,
                    itemBuilder: (_, i) {
                      final n = c.notifications[i];
                      return GestureDetector(
                        onTap: () => c.markReadOnBackend(n.id),
                        child: Container(
                          margin: EdgeInsets.only(bottom: Dimensions.space6),
                          padding: EdgeInsets.all(Dimensions.space12),
                          decoration: BoxDecoration(
                            color: n.read ? MyColor.colorWhite : MyColor.primaryColor.withValues(alpha: 0.04),
                            borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                            border: n.read ? null : Border(left: BorderSide(color: MyColor.primaryColor, width: 3)),
                          ),
                          child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Container(
                              padding: EdgeInsets.all(Dimensions.space6),
                              margin: EdgeInsets.only(top: 2),
                              decoration: BoxDecoration(
                                color: _iconColor(n.type).withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                              ),
                              child: Icon(_icon(n.type), color: _iconColor(n.type), size: 18),
                            ),
                            SizedBox(width: Dimensions.space12),
                            Expanded(
                              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                Row(children: [
                                  Expanded(child: Text(n.title, style: boldDefault.copyWith(fontSize: Dimensions.fontDefault))),
                                  Text(_timeAgo(n.createdAt), style: regularSmall.copyWith(fontSize: 10, color: MyColor.bodyMutedTextColor)),
                                ]),
                                SizedBox(height: 2),
                                Text(n.body, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 2, overflow: TextOverflow.ellipsis),
                              ]),
                            ),
                          ]),
                        ),
                      );
                    },
                  ),
                ),
        );
      },
    );
  }

  Color _iconColor(String type) {
    switch (type) {
      case 'delivery':
        return MyColor.primaryColor;
      case 'favor':
        return const Color(0xFFF59E0B);
      case 'courier':
        return const Color(0xFF8B5CF6);
      default:
        return MyColor.bodyMutedTextColor;
    }
  }

  IconData _icon(String type) {
    switch (type) {
      case 'delivery':
        return Icons.delivery_dining_rounded;
      case 'favor':
        return Icons.volunteer_activism_rounded;
      case 'courier':
        return Icons.motorcycle_rounded;
      default:
        return Icons.notifications_rounded;
    }
  }

  String _timeAgo(DateTime dt) {
    final diff = DateTime.now().difference(dt);
    if (diff.inMinutes < 1) return 'Ahora';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m';
    if (diff.inHours < 24) return '${diff.inHours}h';
    return '${diff.inDays}d';
  }
}
