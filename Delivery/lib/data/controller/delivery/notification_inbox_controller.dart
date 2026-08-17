import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/method.dart';
import 'package:lizto_delivery/core/utils/url_container.dart';
import 'package:lizto_delivery/data/services/api_client.dart';

class AppNotification {
  final String id;
  final String title;
  final String body;
  final String type; // delivery, favor, courier, system
  final int? referenceId;
  final DateTime createdAt;
  bool read;

  AppNotification({
    required this.id,
    required this.title,
    required this.body,
    this.type = 'system',
    this.referenceId,
    DateTime? createdAt,
    this.read = false,
  }) : createdAt = createdAt ?? DateTime.now();
}

class NotificationInboxController extends GetxController {
  List<AppNotification> notifications = [];

  int get unreadCount => notifications.where((n) => !n.read).length;
  List<AppNotification> get unread => notifications.where((n) => !n.read).toList();

  void add(AppNotification notification) {
    notifications.insert(0, notification);
    if (notifications.length > 100) {
      notifications = notifications.sublist(0, 100);
    }
    update();
  }

  void markRead(String id) {
    final idx = notifications.indexWhere((n) => n.id == id);
    if (idx >= 0) {
      notifications[idx].read = true;
      update();
    }
  }

  void markAllRead() {
    for (var n in notifications) {
      n.read = true;
    }
    update();
  }

  void clear() {
    notifications.clear();
    update();
  }

  Future<void> loadNotificationsFromBackend() async {
    try {
      final apiClient = Get.find<ApiClient>();
      final url = '${UrlContainer.baseUrl}push-notifications';
      final response = await apiClient.request(url, Method.getMethod, null, passHeader: true);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        final List rawList = response.responseJson['data']['notifications'] ?? [];
        notifications = rawList.map((x) {
          return AppNotification(
            id: x['id']?.toString() ?? '',
            title: x['title']?.toString() ?? '',
            body: x['body']?.toString() ?? '',
            type: x['type']?.toString() ?? 'system',
            createdAt: x['created_at'] != null ? DateTime.tryParse(x['created_at'].toString()) : null,
            read: x['is_read']?.toString() == '1' || x['is_read'] == true,
          );
        }).toList();
        update();
      }
    } catch (_) {}
  }

  Future<void> markReadOnBackend(String id) async {
    markRead(id);
    try {
      final apiClient = Get.find<ApiClient>();
      final url = '${UrlContainer.baseUrl}push-notifications/read/$id';
      await apiClient.request(url, Method.postMethod, null, passHeader: true);
    } catch (_) {}
  }
}
