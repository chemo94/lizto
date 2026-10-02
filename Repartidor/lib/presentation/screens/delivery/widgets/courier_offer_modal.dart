import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';

class CourierOfferModal extends StatefulWidget {
  final TargetedCourierOfferModel offer;
  final Function(int) onAccept;
  final Function(int) onReject;

  const CourierOfferModal({
    super.key,
    required this.offer,
    required this.onAccept,
    required this.onReject,
  });

  static Future<void> show({
    required BuildContext context,
    required TargetedCourierOfferModel offer,
    required Function(int) onAccept,
    required Function(int) onReject,
  }) async {
    return showModalBottomSheet(
      context: context,
      isDismissible: false,
      enableDrag: false,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => CourierOfferModal(
        offer: offer,
        onAccept: onAccept,
        onReject: onReject,
      ),
    );
  }

  @override
  State<CourierOfferModal> createState() => _CourierOfferModalState();
}

class _CourierOfferModalState extends State<CourierOfferModal> with SingleTickerProviderStateMixin {
  late int _remainingSeconds;
  Timer? _timer;
  late AnimationController _animController;
  bool _isProcessing = false;

  @override
  void initState() {
    super.initState();
    _remainingSeconds = widget.offer.remainingSeconds > 0 ? widget.offer.remainingSeconds : 15;

    _animController = AnimationController(
      vsync: this,
      duration: Duration(seconds: _remainingSeconds),
    )..reverse(from: 1.0);

    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      setState(() {
        if (_remainingSeconds > 1) {
          _remainingSeconds--;
        } else {
          _remainingSeconds = 0;
          _timer?.cancel();
          _handleExpired();
        }
      });
    });
  }

  void _handleExpired() {
    if (!mounted) return;
    Get.back();
    Get.snackbar(
      'Oferta Expirada',
      'La ventana de 15 segundos ha finalizado. El pedido ha sido reasignado.',
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: Colors.black87,
      colorText: Colors.white,
      duration: const Duration(seconds: 3),
    );
  }

  @override
  void dispose() {
    _timer?.cancel();
    _animController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final isBatch = widget.offer.isBatch;
    final batch = widget.offer.batchDetail;
    final job = widget.offer.jobDetail;

    final batchType = isBatch ? (batch?.batchType ?? 'DOBLETE') : 'INDIVIDUAL';
    final totalPayout = isBatch
        ? (batch?.totalPayout ?? batch?.driverEarning ?? 0.0)
        : (job?.totalPayout ?? job?.totalEarning ?? 0.0);
    final driverEarning = isBatch
        ? (batch?.driverEarning ?? totalPayout)
        : (job?.driverEarning ?? totalPayout);
    final tip = isBatch ? (batch?.totalTips ?? 0.0) : (job?.fareBreakdown?.tip ?? 0.0);
    final distanceKm = isBatch
        ? (batch?.totalDistanceKm ?? 0.0)
        : (job?.distanceKm ?? 0.0);
    final points = isBatch
        ? (batch?.totalPoints ?? 10)
        : (job?.points ?? job?.fareBreakdown?.points ?? 10);
    final fareBreakdown = isBatch ? batch?.fareBreakdown : job?.fareBreakdown;

    // Batch label and bonus
    Color badgeColor;
    String badgeText;
    switch (batchType) {
      case 'DOUBLE':
        badgeColor = const Color(0xFF3B82F6);
        badgeText = 'DOBLETE (2 PEDIDOS)';
        break;
      case 'TRIPLET':
        badgeColor = const Color(0xFF8B5CF6);
        badgeText = 'TRIPLETE (3 PEDIDOS)';
        break;
      case 'QUADRUPLE':
        badgeColor = const Color(0xFFEC4899);
        badgeText = 'CUÁDRUPLE (4 PEDIDOS)';
        break;
      default:
        badgeColor = MyColor.primaryColor;
        badgeText = 'PEDIDO INDIVIDUAL';
    }

    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(Dimensions.space25)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.3),
            blurRadius: 20,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      padding: const EdgeInsets.symmetric(horizontal: Dimensions.space20, vertical: Dimensions.space15),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Top Bar: Timer & Batch Type
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space6),
                  decoration: BoxDecoration(
                    color: badgeColor.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(Dimensions.space10),
                    border: Border.all(color: badgeColor, width: 1.2),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.flash_on_rounded, size: 16, color: badgeColor),
                      const SizedBox(width: Dimensions.space4),
                      Text(
                        badgeText,
                        style: boldDefault.copyWith(color: badgeColor, fontSize: Dimensions.fontSmall),
                      ),
                    ],
                  ),
                ),
                // Circular Countdown Timer
                Stack(
                  alignment: Alignment.center,
                  children: [
                    SizedBox(
                      width: 44,
                      height: 44,
                      child: AnimatedBuilder(
                        animation: _animController,
                        builder: (context, child) {
                          return CircularProgressIndicator(
                            value: _animController.value,
                            strokeWidth: 4,
                            backgroundColor: isDark ? Colors.white12 : Colors.grey.shade200,
                            valueColor: AlwaysStoppedAnimation<Color>(
                              _remainingSeconds <= 5 ? Colors.redAccent : MyColor.primaryColor,
                            ),
                          );
                        },
                      ),
                    ),
                    Text(
                      '$_remainingSeconds',
                      style: boldDefault.copyWith(
                        fontSize: Dimensions.fontMedium,
                        color: _remainingSeconds <= 5 ? Colors.redAccent : (isDark ? Colors.white : Colors.black87),
                      ),
                    ),
                  ],
                ),
              ],
            ),

            const SizedBox(height: Dimensions.space15),

            // Payout Section
            Container(
              padding: const EdgeInsets.all(Dimensions.space15),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: isDark
                      ? [const Color(0xFF0F172A), const Color(0xFF1E293B)]
                      : [const Color(0xFFF0FDF4), const Color(0xFFDCFCE7)],
                ),
                borderRadius: BorderRadius.circular(Dimensions.space15),
                border: Border.all(color: const Color(0xFF22C55E).withValues(alpha: 0.3)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        tip > 0
                            ? 'Ganancia Garantizada (Tarifa S/ ${driverEarning.toStringAsFixed(2)})'
                            : 'Ganancia Garantizada',
                        style: regularDefault.copyWith(
                          fontSize: Dimensions.fontSmall,
                          color: isDark ? Colors.white70 : Colors.black54,
                        ),
                      ),
                      const SizedBox(height: Dimensions.space4),
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.baseline,
                        textBaseline: TextBaseline.alphabetic,
                        children: [
                          Text(
                            'S/ ${totalPayout.toStringAsFixed(2)}',
                            style: boldExtraLarge.copyWith(
                              fontSize: 28,
                              color: const Color(0xFF16A34A),
                            ),
                          ),
                          if (tip > 0) ...[
                            const SizedBox(width: Dimensions.space8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: Dimensions.space6, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFFEAB308).withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(Dimensions.space6),
                              ),
                              child: Text(
                                '+ S/ ${tip.toStringAsFixed(2)} propina',
                                style: semiBoldDefault.copyWith(
                                  fontSize: Dimensions.fontExtraSmall,
                                  color: const Color(0xFFCA8A04),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ],
                  ),
                  // Points Badge
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: Dimensions.space10, vertical: Dimensions.space6),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(Dimensions.space10),
                    ),
                    child: Column(
                      children: [
                        const Icon(Icons.stars_rounded, color: Color(0xFFF59E0B), size: 22),
                        const SizedBox(height: 2),
                        Text(
                          '+$points pts',
                          style: boldDefault.copyWith(
                            fontSize: Dimensions.fontExtraSmall,
                            color: const Color(0xFFD97706),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: Dimensions.space12),

            // Route Summary & Distance
            Row(
              children: [
                Expanded(
                  child: _infoPill(
                    icon: Icons.route_rounded,
                    label: '${distanceKm.toStringAsFixed(1)} km',
                    sub: 'Recorrido total',
                    isDark: isDark,
                  ),
                ),
                const SizedBox(width: Dimensions.space10),
                Expanded(
                  child: _infoPill(
                    icon: Icons.timer_outlined,
                    label: isBatch
                        ? '${batch?.totalDurationMinutes?.toStringAsFixed(0) ?? "20"} min'
                        : '${job?.durationMinutes?.toStringAsFixed(0) ?? "15"} min',
                    sub: 'Tiempo aprox.',
                    isDark: isDark,
                  ),
                ),
                if (fareBreakdown?.batchBonus != null && (fareBreakdown?.batchBonus ?? 0) > 0) ...[
                  const SizedBox(width: Dimensions.space10),
                  Expanded(
                    child: _infoPill(
                      icon: Icons.add_circle_outline_rounded,
                      label: '+ S/ ${fareBreakdown!.batchBonus!.toStringAsFixed(2)}',
                      sub: 'Bono lote',
                      isDark: isDark,
                      accentColor: Colors.deepPurpleAccent,
                    ),
                  ),
                ],
              ],
            ),

            const SizedBox(height: Dimensions.space15),

            // Stops Preview
            if (isBatch && batch?.optimizedStops.isNotEmpty == true) ...[
              Text(
                'Itinerario Multi-Parada (${batch!.optimizedStops.length} paradas)',
                style: semiBoldDefault.copyWith(fontSize: Dimensions.fontSmall),
              ),
              const SizedBox(height: Dimensions.space8),
              Container(
                constraints: const BoxConstraints(maxHeight: 140),
                child: ListView.separated(
                  shrinkWrap: true,
                  physics: const BouncingScrollPhysics(),
                  itemCount: batch.optimizedStops.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 6),
                  itemBuilder: (ctx, i) {
                    final stop = batch.optimizedStops[i];
                    return _stopRow(stop, isDark);
                  },
                ),
              ),
            ] else ...[
              // Single Order preview
              _singleOrderRouteRow(job, isDark),
            ],

            const SizedBox(height: Dimensions.space20),

            // Action Buttons (Accept / Reject)
            Row(
              children: [
                Expanded(
                  flex: 1,
                  child: OutlinedButton(
                    onPressed: _isProcessing
                        ? null
                        : () {
                            _timer?.cancel();
                            Get.back();
                            widget.onReject(widget.offer.id ?? 0);
                          },
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: Dimensions.space14),
                      side: BorderSide(color: Colors.red.shade400),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.space12)),
                    ),
                    child: Text(
                      'Rechazar',
                      style: boldDefault.copyWith(color: Colors.red.shade400),
                    ),
                  ),
                ),
                const SizedBox(width: Dimensions.space12),
                Expanded(
                  flex: 2,
                  child: ElevatedButton(
                    onPressed: _isProcessing
                        ? null
                        : () async {
                            setState(() => _isProcessing = true);
                            _timer?.cancel();
                            Get.back();
                            await widget.onAccept(widget.offer.id ?? 0);
                          },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF16A34A),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: Dimensions.space14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.space12)),
                      elevation: 3,
                    ),
                    child: _isProcessing
                        ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                        : Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Icons.check_circle_outline, size: 20),
                              const SizedBox(width: Dimensions.space8),
                              Text(
                                'Aceptar Oferta',
                                style: boldDefault.copyWith(fontSize: Dimensions.fontDefault, color: Colors.white),
                              ),
                            ],
                          ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _infoPill({required IconData icon, required String label, required String sub, required bool isDark, Color? accentColor}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: Dimensions.space8),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF0F172A) : Colors.grey.shade100,
        borderRadius: BorderRadius.circular(Dimensions.space10),
      ),
      child: Column(
        children: [
          Icon(icon, size: 18, color: accentColor ?? (isDark ? Colors.white70 : Colors.black87)),
          const SizedBox(height: 2),
          Text(label, style: boldDefault.copyWith(fontSize: Dimensions.fontExtraSmall, color: accentColor)),
          Text(sub, style: regularDefault.copyWith(fontSize: 9, color: Colors.grey.shade600)),
        ],
      ),
    );
  }

  Widget _stopRow(OptimizedStopModel stop, bool isDark) {
    final isPickup = stop.isPickup;
    return Row(
      children: [
        Container(
          width: 24,
          height: 24,
          decoration: BoxDecoration(
            color: isPickup ? const Color(0xFF3B82F6) : const Color(0xFF10B981),
            shape: BoxShape.circle,
          ),
          alignment: Alignment.center,
          child: Text(
            '${stop.stopNumber ?? 1}',
            style: boldDefault.copyWith(color: Colors.white, fontSize: 11),
          ),
        ),
        const SizedBox(width: Dimensions.space10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${isPickup ? "Recojo" : "Entrega"}: ${stop.contactName ?? ""}',
                style: semiBoldDefault.copyWith(fontSize: Dimensions.fontExtraSmall),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              Text(
                stop.address ?? '',
                style: regularDefault.copyWith(fontSize: 10, color: Colors.grey),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
        Text(
          '${stop.legDistanceKm?.toStringAsFixed(1) ?? "0"} km',
          style: regularDefault.copyWith(fontSize: 10, color: Colors.grey.shade600),
        ),
      ],
    );
  }

  Widget _singleOrderRouteRow(CourierJobModel? job, bool isDark) {
    return Column(
      children: [
        Row(
          children: [
            const Icon(Icons.storefront_rounded, size: 18, color: Color(0xFF3B82F6)),
            const SizedBox(width: Dimensions.space8),
            Expanded(
              child: Text(
                'Recojo: ${job?.storeName ?? job?.pickupAddress ?? "Restaurante"}',
                style: semiBoldDefault.copyWith(fontSize: Dimensions.fontSmall),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
        const SizedBox(height: Dimensions.space6),
        Row(
          children: [
            const Icon(Icons.location_on_rounded, size: 18, color: Color(0xFF10B981)),
            const SizedBox(width: Dimensions.space8),
            Expanded(
              child: Text(
                'Entrega: ${job?.customerName ?? job?.deliveryAddress ?? "Cliente"}',
                style: semiBoldDefault.copyWith(fontSize: Dimensions.fontSmall),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ),
      ],
    );
  }
}
