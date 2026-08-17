import 'dart:async';
import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_foreground_task/flutter_foreground_task.dart';
import 'package:geolocator/geolocator.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/data/repo/dashboard/dashboard_repo.dart';
import 'package:liztogo_repartidor/data/repo/delivery/courier_repo.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:liztogo_repartidor/environment.dart';

final GlobalKey<ForGroundTaskWidgetState> foregroundTaskKey = GlobalKey();

class ForGroundTaskWidget extends StatefulWidget {
  final AsyncValueGetter<bool> onWillStart;
  final Widget child;
  final VoidCallback? callback;

  const ForGroundTaskWidget({
    super.key,
    required this.onWillStart,
    required this.child,
    this.callback,
  });

  @override
  State<ForGroundTaskWidget> createState() => ForGroundTaskWidgetState();
}

class ForGroundTaskWidgetState extends State<ForGroundTaskWidget> with WidgetsBindingObserver {
  late ApiClient apiClient;
  bool _isInitialized = false;
  StreamSubscription<Position>? _positionStream;
  DashBoardRepo? _dashBoardRepo;
  CourierRepo? _courierRepo;

  @override
  void initState() {
    super.initState();
    apiClient = Get.find<ApiClient>();
    WidgetsBinding.instance.addObserver(this);
    _initForegroundSystem();
  }

  /// Initialize only notification channel and system setup
  Future<void> _initForegroundSystem() async {
    if (_isInitialized) return;

    FlutterForegroundTask.init(
      androidNotificationOptions: AndroidNotificationOptions(
        channelId: 'foreground_service',
        channelName: 'Foreground Service Notification',
        channelDescription: 'This notification appears when the foreground service is running.',
        channelImportance: NotificationChannelImportance.LOW,
        priority: NotificationPriority.DEFAULT,
        visibility: NotificationVisibility.VISIBILITY_PUBLIC,
      ),
      iosNotificationOptions: const IOSNotificationOptions(
        showNotification: true,
        playSound: false,
      ),
      foregroundTaskOptions: ForegroundTaskOptions(
        eventAction: ForegroundTaskEventAction.repeat(300000),
        autoRunOnBoot: true,
        autoRunOnMyPackageReplaced: true,
        allowWakeLock: true,
        allowWifiLock: true,
      ),
    );

    _isInitialized = true;

    // Auto start only if logged in AND online
    if (apiClient.isLoggedIn() && await widget.onWillStart()) {
      await startForegroundTask();
    }
  }

  Future<void> _showAlwaysPermissionDialog() async {
    if (!mounted) return;
    final goToSettings = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        title: const Text('Ubicación en segundo plano'),
        content: const Text(
          'Para mostrar tu ubicación a los clientes mientras usas la app en segundo plano, '
          'necesitamos el permiso "Permitir siempre".\n\n'
          'Se abrirá la configuración de la app. Selecciona "Permitir siempre" en Ubicación.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Ahora no'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Siguiente'),
          ),
        ],
      ),
    );
    if (goToSettings == true) {
      await Geolocator.openAppSettings();
    }
  }

  /// Check and request necessary permissions
  Future<bool> _checkPermissions() async {
    LocationPermission permission = await Geolocator.checkPermission();

    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
        printE("Location permission denied");
        return false;
      }
    }

    if (permission == LocationPermission.whileInUse) {
      await _showAlwaysPermissionDialog();
      return false;
    }

    if (permission == LocationPermission.always) {
      return true;
    }

    if (permission == LocationPermission.deniedForever) {
      if (!mounted) return false;
      final goToSettings = await showDialog<bool>(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          title: const Text('Permiso de ubicación requerido'),
          content: const Text(
            'El permiso de ubicación está deshabilitado. Para seguir recibiendo pedidos, '
            'debes activarlo manualmente en la configuración de la app.\n\n'
            'Selecciona "Permitir siempre" en Ubicación.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancelar'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: const Text('Siguiente'),
            ),
          ],
        ),
      );
      if (goToSettings == true) {
        await Geolocator.openAppSettings();
      }
      return false;
    }

    return permission == LocationPermission.always;
  }

  void _startLocationUpdates() {
    _stopLocationUpdates();
    _dashBoardRepo ??= DashBoardRepo(apiClient: apiClient);
    _courierRepo ??= CourierRepo(apiClient: apiClient);
    _positionStream = Geolocator.getPositionStream(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.best,
        distanceFilter: Environment.driverLocationUpdateAfterNmetersOrMovements,
      ),
    ).listen((Position location) async {
      if (!apiClient.isLoggedIn() || !apiClient.getUserOnlineStatus()) return;
      try {
        FlutterForegroundTask.updateService(
          notificationText: "Current Location: ${location.latitude}, ${location.longitude}",
        );
        await _sendLocationUpdate(location);
      } catch (e) {
        printE(e);
      }
    });
    printX("Location updates started (main isolate)");
  }

  Future<void> _sendLocationUpdate(Position location) async {
    final lat = location.latitude;
    final lng = location.longitude;
    await _dashBoardRepo!.updateLiveLocation(
      lat: lat.toString(),
      long: lng.toString(),
    );
    try {
      final response = await _courierRepo!.getActiveJobs();
      if (response.statusCode == 200) {
        final json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          final jobs = (json['data']['jobs'] as List?) ?? [];
          for (final job in jobs) {
            final jobId = job['id'];
            if (jobId is int) {
              await _courierRepo!.sendLocationUpdate(jobId, lat, lng, null);
            }
          }
        }
      }
    } catch (_) {}
  }

  void _stopLocationUpdates() {
    _positionStream?.cancel();
    _positionStream = null;
  }

  Future<void> startForegroundTask() async {
    if (widget.callback == null) {
      printE("callback is null, cannot start service");
      return;
    }

    final isServiceRunning = await FlutterForegroundTask.isRunningService;

    if (isServiceRunning) {
      final permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.whileInUse) {
        await _showAlwaysPermissionDialog();
      }
      printX("Service already running, restarting...");
      FlutterForegroundTask.restartService();
      _startLocationUpdates();
      return;
    }

    if (!await _checkPermissions()) {
      return;
    }

    try {
      await FlutterForegroundTask.startService(
        serviceTypes: [ForegroundServiceTypes.dataSync],
        serviceId: 256,
        notificationTitle: "${Environment.appName} is running",
        notificationText: "Do not close the app",
        callback: widget.callback!,
        notificationIcon: const NotificationIcon(
          metaDataName: 'service.NOTIFICATION_ICON',
          backgroundColor: Colors.white,
        ),
      );
      printX("Foreground service started");
      _startLocationUpdates();
    } catch (e) {
      printE("Failed to start service: $e");
    }
  }

  /// Stop foreground service
  Future<void> stopForegroundTask() async {
    _stopLocationUpdates();
    if (await FlutterForegroundTask.isRunningService) {
      await FlutterForegroundTask.stopService();
      printX("Foreground service stopped");
    }
  }

  /// Handle lifecycle
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) async {
    printX("APP STATE -> $state");

    if (!await widget.onWillStart()) {
      await stopForegroundTask();
      return;
    }

    if (state == AppLifecycleState.paused || state == AppLifecycleState.inactive) {
      if (widget.callback == null) return;
      if (await FlutterForegroundTask.isRunningService) {
        FlutterForegroundTask.restartService();
        _startLocationUpdates();
        return;
      }
      try {
        await FlutterForegroundTask.startService(
          serviceTypes: [ForegroundServiceTypes.dataSync],
          serviceId: 256,
          notificationTitle: "${Environment.appName} is running",
          notificationText: "Do not close the app",
          callback: widget.callback!,
        );
        printX("Foreground service started from lifecycle");
        _startLocationUpdates();
      } catch (e) {
        printE("Failed to start service: $e");
      }
    }
    if (state == AppLifecycleState.resumed) {
      if (await FlutterForegroundTask.isRunningService) {
        _startLocationUpdates();
      }
    }
  }

  Future<bool> _canPop() async {
    if (!mounted) return true;

    final bool canPop = Navigator.canPop(context);

    if (!canPop && await widget.onWillStart()) {
      FlutterForegroundTask.minimizeApp();
      printE("MINIMIZE");
      return false;
    }
    return true;
  }

  @override
  void dispose() {
    _stopLocationUpdates();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (bool didPop, dynamic result) async {
        if (!didPop) {
          final bool canPop = await _canPop();
          if (canPop && context.mounted) {
            Navigator.of(context).pop();
          }
        }
      },
      child: widget.child,
    );
  }
}
