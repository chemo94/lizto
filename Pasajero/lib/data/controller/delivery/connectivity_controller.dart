import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/core/utils/dimensions.dart';

class ConnectivityController extends GetxController {
  final Connectivity _connectivity = Connectivity();
  StreamSubscription<List<ConnectivityResult>>? _subscription;
  RxBool isOnline = true.obs;
  bool _wasOffline = false;

  @override
  void onInit() {
    super.onInit();
    _checkConnectivity();
    _subscription = _connectivity.onConnectivityChanged.listen(_updateState);
  }

  Future<void> _checkConnectivity() async {
    final result = await _connectivity.checkConnectivity();
    _updateState(result);
  }

  void _updateState(List<ConnectivityResult> results) {
    final online = results.any((r) => r != ConnectivityResult.none);
    if (!online && isOnline.value) _wasOffline = true;
    if (online && _wasOffline) {
      _wasOffline = false;
      Get.snackbar('Conectado', 'Conexión a internet restaurada',
          backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite, duration: const Duration(seconds: 2));
    }
    isOnline.value = online;
  }

  @override
  void onClose() {
    _subscription?.cancel();
    super.onClose();
  }
}

class ConnectivityBanner extends StatelessWidget {
  const ConnectivityBanner({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final c = Get.find<ConnectivityController>();
      if (c.isOnline.value) return const SizedBox.shrink();
      return Container(
        width: double.infinity,
        padding: EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space8),
        color: MyColor.redCancelTextColor,
        child: SafeArea(
          bottom: false,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.wifi_off_rounded, color: MyColor.colorWhite, size: 16),
              SizedBox(width: Dimensions.space6),
              Text('Sin conexión a internet', style: regularSmall.copyWith(color: MyColor.colorWhite)),
            ],
          ),
        ),
      );
    });
  }
}
