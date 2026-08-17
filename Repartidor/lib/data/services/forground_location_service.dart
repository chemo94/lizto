import 'dart:async';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/data/repo/dashboard/dashboard_repo.dart';
import 'package:liztogo_repartidor/data/repo/delivery/courier_repo.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ForgroundLocationService extends TaskHandler {
  ForgroundLocationService();

  late DashBoardRepo dashBoardRepo;
  late CourierRepo courierRepo;
  List<int> _activeJobIds = [];

  @override
  Future<void> onStart(DateTime timestamp, TaskStarter starter) async {
    try {
      SharedPreferences sharedPreferences = await SharedPreferences.getInstance();
      ApiClient apiClient = ApiClient(sharedPreferences: sharedPreferences);
      dashBoardRepo = DashBoardRepo(apiClient: apiClient);
      courierRepo = CourierRepo(apiClient: apiClient);
      printX("ForgroundLocationService started - waiting for location data from main isolate");
    } catch (e) {
      printE(e);
    }
  }

  Future<void> sendLocationToServer({
    required double lat,
    required double long,
  }) async {
    try {
      var response = await dashBoardRepo.updateLiveLocation(
        lat: "$lat",
        long: "$long",
      );
      if (response.statusCode == 200) {
        FlutterForegroundTask.updateService(
          notificationText: "Ubicación actualizada",
        );
      }
    } catch (e) {
      printE(e);
    }
  }

  Future<void> sendToActiveJobs({required double lat, required double lng}) async {
    try {
      var response = await courierRepo.getActiveJobs();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          final jobs = (json['data']['jobs'] as List?) ?? [];
          for (final job in jobs) {
            final jobId = job['id'];
            if (jobId is int) {
              await courierRepo.sendLocationUpdate(jobId, lat, lng, null);
            }
          }
        }
      }
    } catch (e) {
      printE(e);
    }
  }

  @override
  void onRepeatEvent(DateTime timestamp) async {}

  @override
  Future<void> onDestroy(DateTime timestamp, bool isTimeout) async {
    printX('onDestroy:::::::::::: Forground Task');
  }

  @override
  void onReceiveData(Object data) {
    printX('onReceiveData999: $data');
    FlutterForegroundTask.sendDataToMain(data);
  }

  @override
  void onNotificationButtonPressed(String id) {
    printX('onNotificationButtonPressed: $id');
  }

  @override
  void onNotificationPressed() {
    FlutterForegroundTask.launchApp('/');
    printX('onNotificationPressed');
  }

  @override
  void onNotificationDismissed() {
    printX('onNotificationDismissed');
  }
}
