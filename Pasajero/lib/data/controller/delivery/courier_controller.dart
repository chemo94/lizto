import 'dart:async';
import 'dart:io';
import 'package:geolocator/geolocator.dart';
import 'package:get/get_state_manager/src/simple/get_controllers.dart';

import '../../../core/helper/string_format_helper.dart';
import '../../../core/utils/my_strings.dart';
import '../../model/delivery/courier_models.dart';
import '../../model/global/response_model/response_model.dart';
import '../../repo/delivery/courier_repo.dart';

class CourierController extends GetxController {
  final CourierRepo courierRepo;
  CourierController({required this.courierRepo});

  List<CourierJobModel> pendingJobs = [];
  List<CourierJobModel> activeJobs = [];
  List<CourierJobModel> jobHistory = [];
  CourierJobModel? selectedJob;
  CourierEarningsModel? earnings;

  bool isLoading = false;
  bool loadingEarnings = false;
  bool updatingStatus = false;

  bool get isOnline => _isOnline;
  bool _isOnline = true;

  Timer? _locationTimer;

  void toggleOnline() {
    _isOnline = !_isOnline;
    update();
    if (_isOnline) {
      loadPendingJobs();
    } else {
      stopLocationBroadcast();
    }
  }

  void startLocationBroadcast(int jobId) {
    _locationTimer?.cancel();
    _locationTimer = Timer.periodic(const Duration(seconds: 10), (timer) async {
      try {
        bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
        if (!serviceEnabled) return;
        LocationPermission permission = await Geolocator.checkPermission();
        if (permission == LocationPermission.denied) {
          permission = await Geolocator.requestPermission();
          if (permission == LocationPermission.denied) return;
        }
        if (permission == LocationPermission.deniedForever) return;

        Position position = await Geolocator.getCurrentPosition(
          // ignore: deprecated_member_use
          desiredAccuracy: LocationAccuracy.high,
        );

        await courierRepo.updateJobStatus(
          jobId,
          selectedJob?.status ?? 'on_way',
          lat: position.latitude,
          lng: position.longitude,
        );
      } catch (_) {}
    });
  }

  void stopLocationBroadcast() {
    _locationTimer?.cancel();
    _locationTimer = null;
  }

  @override
  void onClose() {
    stopLocationBroadcast();
    super.onClose();
  }

  // ── Jobs ──

  Future<void> loadPendingJobs() async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await courierRepo.getPendingJobs();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['jobs'];
          pendingJobs = (raw is List ? raw : raw['data'] ?? []).map<CourierJobModel>((x) => CourierJobModel.fromJson(x)).toList();
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<void> loadActiveJobs() async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await courierRepo.getActiveJobs();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['jobs'];
          activeJobs = (raw is List ? raw : raw['data'] ?? []).map<CourierJobModel>((x) => CourierJobModel.fromJson(x)).toList();
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<void> loadJobHistory() async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await courierRepo.getJobHistory();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['jobs'];
          jobHistory = (raw is List ? raw : raw['data'] ?? []).map<CourierJobModel>((x) => CourierJobModel.fromJson(x)).toList();
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<void> loadJobDetail(int jobId) async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await courierRepo.getJobDetail(jobId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          selectedJob = CourierJobModel.fromJson(json['data']['job']);
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<bool> acceptJob(int jobId, String type) async {
    try {
      ResponseModel response = await courierRepo.acceptJob(jobId, type);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          pendingJobs.removeWhere((j) => j.id == jobId);
          await loadActiveJobs();
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  Future<bool> updateJobStatus(int jobId, String status, {double? lat, double? lng}) async {
    updatingStatus = true;
    update();
    try {
      ResponseModel response = await courierRepo.updateJobStatus(jobId, status, lat: lat, lng: lng);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          if (json['data'] != null && json['data']['job'] != null) {
            selectedJob = CourierJobModel.fromJson(json['data']['job']);
          }
          await loadActiveJobs();
          updatingStatus = false;
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    updatingStatus = false;
    update();
    return false;
  }

  Future<bool> uploadProofImage(int jobId, File imageFile) async {
    try {
      ResponseModel response = await courierRepo.uploadProofImage(jobId, imageFile);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        return json['status'] == MyStrings.success;
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  // ── Earnings ──

  Future<void> loadEarnings() async {
    loadingEarnings = true;
    update();
    try {
      ResponseModel response = await courierRepo.getEarnings();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          earnings = CourierEarningsModel.fromJson(json['data']);
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingEarnings = false;
    update();
  }
}
