import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';

class AutoAcceptSettingsModal extends StatefulWidget {
  final AutoAcceptSettingsModel initialSettings;
  final Function(bool enabled, double minEarning, double maxDistance) onSave;

  const AutoAcceptSettingsModal({
    super.key,
    required this.initialSettings,
    required this.onSave,
  });

  static Future<void> show({
    required BuildContext context,
    required AutoAcceptSettingsModel initialSettings,
    required Function(bool enabled, double minEarning, double maxDistance) onSave,
  }) async {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => AutoAcceptSettingsModal(
        initialSettings: initialSettings,
        onSave: onSave,
      ),
    );
  }

  @override
  State<AutoAcceptSettingsModal> createState() => _AutoAcceptSettingsModalState();
}

class _AutoAcceptSettingsModalState extends State<AutoAcceptSettingsModal> {
  late bool _enabled;
  late double _minEarning;
  late double _maxDistance;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _enabled = widget.initialSettings.autoAcceptEnabled;
    _minEarning = widget.initialSettings.minEarning > 0 ? widget.initialSettings.minEarning : 8.0;
    _maxDistance = widget.initialSettings.maxDistance > 0 ? widget.initialSettings.maxDistance : 6.0;
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(Dimensions.space25)),
      ),
      padding: EdgeInsets.only(
        left: Dimensions.space20,
        right: Dimensions.space20,
        top: Dimensions.space20,
        bottom: MediaQuery.of(context).viewInsets.bottom + Dimensions.space20,
      ),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Handle bar
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.withValues(alpha: 0.3),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: Dimensions.space15),

            // Header Title
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(Dimensions.space8),
                  decoration: BoxDecoration(
                    color: MyColor.primaryColor.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(Dimensions.space10),
                  ),
                  child: const Icon(Icons.bolt_rounded, color: MyColor.primaryColor, size: 24),
                ),
                const SizedBox(width: Dimensions.space12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Autoaceptación Inteligente',
                        style: boldLarge.copyWith(fontSize: Dimensions.fontLarge),
                      ),
                      Text(
                        'Acepta pedidos automáticamente sin esperas',
                        style: regularDefault.copyWith(color: Colors.grey, fontSize: Dimensions.fontExtraSmall),
                      ),
                    ],
                  ),
                ),
              ],
            ),

            const SizedBox(height: Dimensions.space20),

            // Switch Card
            Container(
              padding: const EdgeInsets.all(Dimensions.space15),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF0F172A) : Colors.grey.shade50,
                borderRadius: BorderRadius.circular(Dimensions.space15),
                border: Border.all(
                  color: _enabled ? MyColor.primaryColor.withValues(alpha: 0.5) : (isDark ? Colors.white12 : Colors.grey.shade200),
                ),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Estado de Autoaceptación',
                        style: semiBoldDefault.copyWith(fontSize: Dimensions.fontDefault),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        _enabled ? 'Activado: asignación inmediata' : 'Desactivado: esperarás los 15s',
                        style: regularDefault.copyWith(
                          fontSize: Dimensions.fontSmall,
                          color: _enabled ? const Color(0xFF16A34A) : Colors.grey,
                        ),
                      ),
                    ],
                  ),
                  Switch.adaptive(
                    value: _enabled,
                    activeTrackColor: MyColor.primaryColor,
                    activeThumbColor: Colors.white,
                    onChanged: (val) {
                      setState(() => _enabled = val);
                    },
                  ),
                ],
              ),
            ),

            if (_enabled) ...[
              const SizedBox(height: Dimensions.space20),

              // Min Earning Slider
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Ganancia mínima por carrera',
                    style: semiBoldDefault.copyWith(fontSize: Dimensions.fontSmall),
                  ),
                  Text(
                    'S/ ${_minEarning.toStringAsFixed(1)}',
                    style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: Dimensions.fontMedium),
                  ),
                ],
              ),
              Slider(
                value: _minEarning,
                min: 4.0,
                max: 30.0,
                divisions: 26,
                activeColor: MyColor.primaryColor,
                onChanged: (val) {
                  setState(() => _minEarning = val);
                },
              ),

              const SizedBox(height: Dimensions.space10),

              // Max Distance Slider
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Distancia máxima de recorrido',
                    style: semiBoldDefault.copyWith(fontSize: Dimensions.fontSmall),
                  ),
                  Text(
                    '${_maxDistance.toStringAsFixed(1)} km',
                    style: boldDefault.copyWith(color: const Color(0xFF3B82F6), fontSize: Dimensions.fontMedium),
                  ),
                ],
              ),
              Slider(
                value: _maxDistance,
                min: 1.0,
                max: 20.0,
                divisions: 19,
                activeColor: const Color(0xFF3B82F6),
                onChanged: (val) {
                  setState(() => _maxDistance = val);
                },
              ),

              // Notice Box
              Container(
                padding: const EdgeInsets.all(Dimensions.space10),
                decoration: BoxDecoration(
                  color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(Dimensions.space8),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.info_outline, size: 18, color: Color(0xFF3B82F6)),
                    const SizedBox(width: Dimensions.space8),
                    Expanded(
                      child: Text(
                        'Los pedidos y lotes que generen al menos S/ ${_minEarning.toStringAsFixed(1)} '
                        'y no excedan ${_maxDistance.toStringAsFixed(1)} km se confirmarán al instante.',
                        style: regularDefault.copyWith(fontSize: 11, color: isDark ? Colors.white70 : Colors.black87),
                      ),
                    ),
                  ],
                ),
              ),
            ],

            const SizedBox(height: Dimensions.space25),

            // Save Button
            ElevatedButton(
              onPressed: _isSaving
                  ? null
                  : () async {
                      setState(() => _isSaving = true);
                      await widget.onSave(_enabled, _minEarning, _maxDistance);
                      if (mounted) {
                        setState(() => _isSaving = false);
                        Get.back();
                        Get.snackbar(
                          'Preferencias Guardadas',
                          'Tu configuración de autoaceptación ha sido actualizada.',
                          snackPosition: SnackPosition.BOTTOM,
                          backgroundColor: const Color(0xFF16A34A),
                          colorText: Colors.white,
                        );
                      }
                    },
              style: ElevatedButton.styleFrom(
                backgroundColor: MyColor.primaryColor,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: Dimensions.space14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.space12)),
              ),
              child: _isSaving
                  ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : Text('Guardar Preferencias', style: boldDefault.copyWith(fontSize: Dimensions.fontDefault, color: Colors.white)),
            ),
          ],
        ),
      ),
    );
  }
}
