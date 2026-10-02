import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/utils/my_strings.dart';
import 'package:liztogo_repartidor/core/utils/url_container.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';
import 'package:liztogo_repartidor/data/model/delivery/favor_models.dart';
import 'package:liztogo_repartidor/data/model/global/response_model/response_model.dart';
import 'package:liztogo_repartidor/data/repo/delivery/courier_repo.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:liztogo_repartidor/data/services/pusher_service.dart';
import 'package:geolocator/geolocator.dart';
import 'package:liztogo_repartidor/core/utils/method.dart';
import 'package:liztogo_repartidor/presentation/components/foreground_task_widget.dart';
import 'package:liztogo_repartidor/data/services/realtime_service.dart';

class CourierController extends GetxController {
  final CourierRepo courierRepo;
  CourierController({required this.courierRepo});

  List<CourierJobModel> pendingJobs = [];
  List<CourierJobModel> activeJobs = [];
  List<CourierJobModel> jobHistory = [];
  CourierJobModel? selectedJob;
  CourierEarningsModel? earnings;

  // Phase 2, 3 & 4 State
  List<TargetedCourierOfferModel> pendingOffers = [];
  CourierBatchModel? activeBatch;
  AutoAcceptSettingsModel autoAcceptSettings = AutoAcceptSettingsModel();
  Map<String, dynamic>? economicStatus;
  bool loadingOffers = false;
  bool loadingBatch = false;
  bool savingAutoAccept = false;

  bool isLoading = false;
  bool loadingEarnings = false;
  bool updatingStatus = false;
  String? statusError;

  List<Map<String, dynamic>> heatmapPoints = [];
  List<Map<String, dynamic>> heatmapZones = [];
  bool loadingHeatmap = false;

  Future<void> fetchHeatmapData() async {
    loadingHeatmap = true;
    update();
    try {
      ResponseModel response = await courierRepo.getHeatmapData();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final list = json['data']['hotspots'] as List?;
          if (list != null) {
            heatmapPoints = list.map((e) => Map<String, dynamic>.from(e as Map)).toList();
          }
          final zonesList = json['data']['zones'] as List?;
          if (zonesList != null) {
            heatmapZones = zonesList.map((e) => Map<String, dynamic>.from(e as Map)).toList();
          }
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingHeatmap = false;
    update();
  }

  // ── Chat ──
  List<FavorMessageModel> messages = [];
  bool loadingMessages = false;
  bool sendingMessage = false;
  int? _subscribedChatJobId;
  StreamSubscription? _jobRealtimeSub;

  void subscribeToChatChannel(int jobId) {
    if (_subscribedChatJobId == jobId) return;
    _subscribedChatJobId = jobId;
    final channel = 'private-job.$jobId';
    PusherManager().checkAndInitIfNeeded(channel);
    PusherManager().addListener(_onChatEvent);

    // WebSocket nativo
    RealtimeManager().subscribe('order.$jobId');
    RealtimeManager().subscribe('favor.$jobId');
    _jobRealtimeSub?.cancel();
    _jobRealtimeSub = RealtimeManager().onBroadcast.listen((msg) {
      final topic = msg['topic']?.toString() ?? '';
      if (topic == 'order.$jobId' || topic == 'favor.$jobId') {
        final payload = msg['payload'];
        if (payload is Map<String, dynamic> && payload.containsKey('message') && payload['message'] is Map) {
          try {
            final msgModel = FavorMessageModel.fromJson(Map<String, dynamic>.from(payload['message']));
            if (!messages.any((m) => m.id == msgModel.id && m.id != null)) {
              messages.add(msgModel);
              update();
            }
          } catch (_) {}
        }
      }
    });
  }

  void unsubscribeFromChatChannel() {
    if (_subscribedChatJobId != null) {
      RealtimeManager().unsubscribe('order.$_subscribedChatJobId');
      RealtimeManager().unsubscribe('favor.$_subscribedChatJobId');
    }
    _jobRealtimeSub?.cancel();
    _subscribedChatJobId = null;
    PusherManager().removeListener(_onChatEvent);
  }

  void _onChatEvent(PusherEvent event) {
    if (event.channelName != 'private-job.$_subscribedChatJobId') return;
    if (event.eventName == 'message_received' || event.eventName == 'job_message_received' || event.eventName == 'favor_message_received') {
      try {
        final decoded = (event.data is Map) ? Map<String, dynamic>.from(event.data as Map) : _parseJsonString(event.data.toString());
        final msg = FavorMessageModel.fromJson(decoded);
        if (!messages.any((m) => m.id == msg.id && m.id != null)) {
          messages.add(msg);
          update();
        }
      } catch (e) {
        printX('Chat event parse error: $e');
      }
    }
  }

  Map<String, dynamic> _parseJsonString(String raw) {
    try {
      final decoded = (raw.startsWith('{') || raw.startsWith('[')) ? raw : '{$raw}';
      return Map<String, dynamic>.from(Map<String, dynamic>.from(
        const JsonDecoder().convert(decoded) as Map,
      ));
    } catch (_) {
      return {};
    }
  }

  Future<void> loadMessages(int jobId) async {
    loadingMessages = true;
    update();
    try {
      ResponseModel response = await courierRepo.getMessages(jobId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final raw = json['data']['messages'] ?? [];
          messages = (raw as List).map((x) => FavorMessageModel.fromJson(x)).toList();
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingMessages = false;
    update();
  }

  Future<bool> sendMessage(int jobId, String text) async {
    sendingMessage = true;
    update();
    try {
      ResponseModel response = await courierRepo.sendMessage(jobId, text);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final msg = FavorMessageModel.fromJson(json['data']['message']);
          messages.add(msg);
          sendingMessage = false;
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    sendingMessage = false;
    update();
    return false;
  }

  Future<bool> sendImage(int jobId, File imageFile) async {
    sendingMessage = true;
    update();
    try {
      ResponseModel response = await courierRepo.sendImage(jobId, imageFile);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final msg = FavorMessageModel.fromJson(json['data']['message']);
          messages.add(msg);
          sendingMessage = false;
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    sendingMessage = false;
    update();
    return false;
  }

  bool get isOnline => _isOnline;
  bool _isOnline = true;

  void loadOnlineStatus() {
    _isOnline = Get.find<ApiClient>().getUserOnlineStatus();
    update();
  }

  Future<void> toggleOnline() async {
    _isOnline = !_isOnline;
    update();
    if (_isOnline) {
      final permission = await Geolocator.checkPermission();
      if (permission != LocationPermission.always) {
        if (permission == LocationPermission.denied) {
          await Geolocator.requestPermission();
        }
        final updatedPermission = await Geolocator.checkPermission();
        if (updatedPermission != LocationPermission.always) {
          Get.dialog(
            AlertDialog(
              title: const Text('Ubicación en segundo plano'),
              content: const Text(
                'Para activar el modo online y mostrar tu ubicación a los clientes, '
                'necesitamos el permiso "Permitir siempre".\n\n'
                'Se abrirá la configuración de la app. Selecciona "Permitir siempre" en Ubicación.',
              ),
              actions: [
                TextButton(
                  onPressed: () => Get.back(),
                  child: const Text('Ahora no'),
                ),
                ElevatedButton(
                  onPressed: () {
                    Get.back();
                    Geolocator.openAppSettings();
                  },
                  child: const Text('Siguiente'),
                ),
              ],
            ),
          );
          _isOnline = false;
          update();
          Get.find<ApiClient>().setOnlineStatus(false);
          return;
        }
      }
    }
    Get.find<ApiClient>().setOnlineStatus(_isOnline);
    await _syncOnlineStatus();
    if (_isOnline) {
      loadPendingJobs();
      _startLocationService();
    } else {
      _stopLocationService();
    }
  }

  Future<void> _syncOnlineStatus() async {
    try {
      final position = await GeolocatorPlatform.instance.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.best),
      );
      String url = '${UrlContainer.baseUrl}${UrlContainer.onlineStatus}';
      Map<String, String> params = {
        'lat': position.latitude.toString(),
        'long': position.longitude.toString(),
      };
      await Get.find<ApiClient>().request(url, Method.postMethod, params, passHeader: true);
    } catch (_) {}
  }

  void _startLocationService() async {
    foregroundTaskKey.currentState?.startForegroundTask();
  }

  void _stopLocationService() async {
    foregroundTaskKey.currentState?.stopForegroundTask();
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
          printX('loadActiveJobs: count=${activeJobs.length}, ids=${activeJobs.map((j) => '${j.id}:${j.orderNo}').toList()}');
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

  int? _loadingJobId;

  Future<void> loadJobDetail(int jobId, {String? type}) async {
    _loadingJobId = jobId;
    if (selectedJob?.id != jobId && selectedJob != null) {
      selectedJob = null;
      update();
    }
    isLoading = true;
    update();
    try {
      ResponseModel response = await courierRepo.getJobDetail(jobId, type: type);
      if (_loadingJobId != jobId) return;
      if (response.statusCode == 200) {
        var json = response.responseJson;
        printX('loadJobDetail response keys: ${json.keys}, data keys: ${json['data']?.keys}');
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final job = CourierJobModel.fromJson(json['data']['job']);
          printX('loadJobDetail: requested=$jobId, type=$type, got=${job.id}, orderNo=${job.orderNo}');
          if (job.id == jobId) {
            selectedJob = job;
          }
        }
      }
    } catch (e) {
      printX(e);
    }
    if (_loadingJobId == jobId) {
      isLoading = false;
      update();
    }
  }

  Future<bool> acceptJob(int jobId, String type) async {
    try {
      ResponseModel response = await courierRepo.acceptJob(jobId, type);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          final idx = pendingJobs.indexWhere((j) => j.id == jobId);
          CourierJobModel? acceptedJob;
          if (idx >= 0) {
            acceptedJob = pendingJobs.removeAt(idx);
            acceptedJob.status = 'accepted';
          }
          await loadActiveJobs();
          if (acceptedJob != null && !activeJobs.any((j) => j.id == jobId)) {
            activeJobs.insert(0, acceptedJob);
          }
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  Future<bool> updateJobStatus(int jobId, String status, {double? lat, double? lng, String? type, bool paymentConfirmed = false}) async {
    statusError = null;
    updatingStatus = true;
    update();
    try {
      ResponseModel response = await courierRepo.updateJobStatus(jobId, status, lat: lat, lng: lng, type: type, paymentConfirmed: paymentConfirmed);
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
        } else {
          final msg = (json['message']?.toString() ?? json['error']?.toString() ?? '');
          if (msg.toLowerCase().contains('saldo') || msg.toLowerCase().contains('insuficiente') || msg.toLowerCase().contains('balance') || msg.toLowerCase().contains('wallet')) {
            statusError = 'Saldo insuficiente. Recarga tu wallet para continuar.';
          } else {
            statusError = msg.isNotEmpty ? msg : null;
          }
        }
      }
    } catch (e) {
      printX(e);
    }
    updatingStatus = false;
    update();
    return false;
  }

  Future<bool> cancelJob(int jobId, String type, String reasonCode, {String? reasonDetail}) async {
    statusError = null;
    updatingStatus = true;
    update();
    try {
      final response = await courierRepo.cancelJob(jobId, type, reasonCode, reasonDetail: reasonDetail);
      final json = response.responseJson;
      if (response.statusCode == 200 && json['status'] == MyStrings.success) {
        selectedJob = null;
        await Future.wait([loadActiveJobs(), loadPendingJobs()]);
        updatingStatus = false;
        update();
        return true;
      }
      statusError = json['message']?.toString() ?? json['error']?.toString() ?? 'No se pudo cancelar el pedido.';
    } catch (e) {
      printX(e);
      statusError = 'Error de conexión. Inténtalo nuevamente.';
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

  // ── Sprint 1: PIN Verification ──

  bool verifyingPin = false;
  String? pinError;

  Future<bool> verifyPin(int jobId, String pinCode, {String type = 'favor'}) async {
    verifyingPin = true;
    pinError = null;
    update();
    try {
      ResponseModel response = await courierRepo.verifyPin(jobId, pinCode, type: type);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          verifyingPin = false;
          update();
          return true;
        } else {
          pinError = json['message']?.toString() ?? 'PIN incorrecto';
        }
      } else {
        pinError = 'Error al verificar PIN';
      }
    } catch (e) {
      printX(e);
      pinError = 'Error de conexión';
    }
    verifyingPin = false;
    update();
    return false;
  }

  Future<Map<String, dynamic>?> getConfirmationRequirements(int jobId, {String type = 'favor'}) async {
    try {
      ResponseModel response = await courierRepo.getConfirmationRequirements(jobId, type: type);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          return json['data']['requirements'];
        }
      }
    } catch (e) {
      printX(e);
    }
    return null;
  }

  Future<bool> updateJobStatusWithPin(int jobId, String status, {double? lat, double? lng, String? type, bool paymentConfirmed = false, String? pinCode}) async {
    statusError = null;
    updatingStatus = true;
    update();
    try {
      ResponseModel response = await courierRepo.updateJobStatusWithPin(jobId, status, lat: lat, lng: lng, type: type, paymentConfirmed: paymentConfirmed, pinCode: pinCode);
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
        } else {
          final msg = (json['message']?.toString() ?? json['error']?.toString() ?? '');
          if (msg.toLowerCase().contains('pin')) {
            pinError = msg;
          } else if (msg.toLowerCase().contains('foto') || msg.toLowerCase().contains('photo') || msg.toLowerCase().contains('proof')) {
            statusError = 'Se requiere subir una foto como comprobante de entrega.';
          } else {
            statusError = msg.isNotEmpty ? msg : null;
          }
        }
      }
    } catch (e) {
      printX(e);
    }
    updatingStatus = false;
    update();
    return false;
  }

  // ── Sprint 3.2: Return Handling ──

  bool processingReturn = false;

  Future<bool> acceptReturn(int jobId, {String type = 'favor'}) async {
    processingReturn = true;
    update();
    try {
      ResponseModel response = await courierRepo.acceptReturn(jobId, type: type);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          if (json['data'] != null && json['data']['job'] != null) {
            selectedJob = CourierJobModel.fromJson(json['data']['job']);
          }
          await loadActiveJobs();
          processingReturn = false;
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    processingReturn = false;
    update();
    return false;
  }

  Future<bool> pickupReturn(int jobId, {String type = 'favor'}) async {
    processingReturn = true;
    update();
    try {
      ResponseModel response = await courierRepo.pickupReturn(jobId, type: type);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          if (json['data'] != null && json['data']['job'] != null) {
            selectedJob = CourierJobModel.fromJson(json['data']['job']);
          }
          processingReturn = false;
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    processingReturn = false;
    update();
    return false;
  }

  Future<bool> completeReturn(int jobId, {String type = 'favor'}) async {
    processingReturn = true;
    update();
    try {
      ResponseModel response = await courierRepo.completeReturn(jobId, type: type);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          if (json['data'] != null && json['data']['job'] != null) {
            selectedJob = CourierJobModel.fromJson(json['data']['job']);
          }
          await loadActiveJobs();
          processingReturn = false;
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    processingReturn = false;
    update();
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

  // ── Phase 2, 3 & 4: Offers, Batches & Economic Status ──

  Future<void> loadPendingOffers() async {
    loadingOffers = true;
    update();
    try {
      ResponseModel response = await courierRepo.getPendingOffers();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final list = json['data']['offers'] as List?;
          if (list != null) {
            pendingOffers = list
                .map((x) => TargetedCourierOfferModel.fromJson(Map<String, dynamic>.from(x)))
                .where((o) => !o.isExpired)
                .toList();
          }
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingOffers = false;
    update();
  }

  Future<bool> acceptOffer(int offerId) async {
    try {
      ResponseModel response = await courierRepo.acceptOffer(offerId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          pendingOffers.removeWhere((o) => o.id == offerId);
          await loadActiveJobs();
          await loadActiveBatch();
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  Future<bool> rejectOffer(int offerId) async {
    try {
      pendingOffers.removeWhere((o) => o.id == offerId);
      update();
      ResponseModel response = await courierRepo.rejectOffer(offerId);
      return response.statusCode == 200;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  Future<void> loadActiveBatch() async {
    loadingBatch = true;
    update();
    try {
      ResponseModel response = await courierRepo.getActiveBatch();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final bData = json['data']['batch'];
          activeBatch = bData != null
              ? CourierBatchModel.fromJson(Map<String, dynamic>.from(bData))
              : null;
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingBatch = false;
    update();
  }

  Future<bool> completeBatchStop(int batchId, int stopNumber) async {
    try {
      ResponseModel response = await courierRepo.completeBatchStop(batchId, stopNumber);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final bData = json['data']['batch'];
          if (bData != null) {
            activeBatch = CourierBatchModel.fromJson(Map<String, dynamic>.from(bData));
          }
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

  Future<void> loadAutoAcceptSettings() async {
    try {
      ResponseModel response = await courierRepo.getAutoAcceptSettings();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          autoAcceptSettings = AutoAcceptSettingsModel.fromJson(Map<String, dynamic>.from(json['data']));
          update();
        }
      }
    } catch (e) {
      printX(e);
    }
  }

  Future<bool> saveAutoAcceptSettings(bool enabled, double minEarning, double maxDistance) async {
    savingAutoAccept = true;
    update();
    try {
      ResponseModel response = await courierRepo.updateAutoAcceptSettings(enabled, minEarning, maxDistance);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          autoAcceptSettings = AutoAcceptSettingsModel.fromJson(Map<String, dynamic>.from(json['data']));
          savingAutoAccept = false;
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    savingAutoAccept = false;
    update();
    return false;
  }

  Future<void> loadEconomicStatus() async {
    try {
      ResponseModel response = await courierRepo.getEconomicStatus();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          economicStatus = Map<String, dynamic>.from(json['data']);
          update();
        }
      }
    } catch (e) {
      printX(e);
    }
  }
}
