import 'package:flutter/material.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';

class CourierBatchRadarCard extends StatelessWidget {
  final CourierBatchModel batch;
  final Function(CourierBatchModel) onAccept;
  final Function(CourierBatchModel)? onReject;

  const CourierBatchRadarCard({
    super.key,
    required this.batch,
    required this.onAccept,
    this.onReject,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final totalPayout = batch.totalPayout ?? batch.driverEarning ?? 0.0;
    final totalDistance = batch.totalDistanceKm ?? 0.0;
    final totalDuration = batch.totalDurationMinutes ?? 0.0;
    final batchBonus = batch.batchBonus ?? 0.0;
    final totalPoints = batch.totalPoints ?? 10;

    Color badgeColor;
    String badgeTitle;
    switch (batch.batchType) {
      case 'DOUBLE':
        badgeColor = const Color(0xFF3B82F6);
        badgeTitle = 'DOBLETE (2 PEDIDOS)';
        break;
      case 'TRIPLET':
        badgeColor = const Color(0xFF8B5CF6);
        badgeTitle = 'TRIPLETE (3 PEDIDOS)';
        break;
      case 'QUADRUPLE':
        badgeColor = const Color(0xFFEC4899);
        badgeTitle = 'CUÁDRUPLE (4 PEDIDOS)';
        break;
      default:
        badgeColor = MyColor.primaryColor;
        badgeTitle = 'LOTE MULTI-PEDIDO';
    }

    return Container(
      margin: const EdgeInsets.symmetric(
        horizontal: Dimensions.space15,
        vertical: Dimensions.space8,
      ),
      padding: const EdgeInsets.all(Dimensions.space15),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(Dimensions.space15),
        border: Border.all(
          color: badgeColor.withValues(alpha: 0.6),
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: badgeColor.withValues(alpha: 0.15),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header: Type Badge & Total Payout
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: Dimensions.space10, vertical: Dimensions.space4),
                decoration: BoxDecoration(
                  color: badgeColor.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(Dimensions.space8),
                  border: Border.all(color: badgeColor, width: 1),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.layers_rounded, size: 16, color: badgeColor),
                    const SizedBox(width: Dimensions.space4),
                    Text(
                      badgeTitle,
                      style: boldDefault.copyWith(color: badgeColor, fontSize: Dimensions.fontExtraSmall),
                    ),
                  ],
                ),
              ),
              Row(
                crossAxisAlignment: CrossAxisAlignment.baseline,
                textBaseline: TextBaseline.alphabetic,
                children: [
                  Text(
                    'S/ ${totalPayout.toStringAsFixed(2)}',
                    style: boldLarge.copyWith(color: const Color(0xFF16A34A), fontSize: 20),
                  ),
                ],
              ),
            ],
          ),

          const SizedBox(height: Dimensions.space10),

          // Metrics Pills Row
          Row(
            children: [
              _metricPill(
                icon: Icons.route_rounded,
                text: '${totalDistance.toStringAsFixed(1)} km',
                isDark: isDark,
              ),
              const SizedBox(width: Dimensions.space8),
              _metricPill(
                icon: Icons.timer_outlined,
                text: '${totalDuration.toStringAsFixed(0)} min',
                isDark: isDark,
              ),
              if (batchBonus > 0) ...[
                const SizedBox(width: Dimensions.space8),
                _metricPill(
                  icon: Icons.bolt_rounded,
                  text: '+ S/ ${batchBonus.toStringAsFixed(2)} bono',
                  isDark: isDark,
                  color: const Color(0xFF8B5CF6),
                ),
              ],
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: Dimensions.space4),
                decoration: BoxDecoration(
                  color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(Dimensions.space6),
                ),
                child: Text(
                  '+$totalPoints pts',
                  style: boldDefault.copyWith(color: const Color(0xFFD97706), fontSize: Dimensions.fontExtraSmall),
                ),
              ),
            ],
          ),

          const SizedBox(height: Dimensions.space12),

          // Multi-Stop Route Summary (up to 4 stops preview)
          if (batch.optimizedStops.isNotEmpty) ...[
            Container(
              padding: const EdgeInsets.all(Dimensions.space10),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF0F172A) : Colors.grey.shade50,
                borderRadius: BorderRadius.circular(Dimensions.space10),
              ),
              child: Column(
                children: batch.optimizedStops.take(4).map((stop) {
                  final isPickup = stop.isPickup;
                  return Padding(
                    padding: const EdgeInsets.symmetric(vertical: 3.0),
                    child: Row(
                      children: [
                        Container(
                          width: 20,
                          height: 20,
                          decoration: BoxDecoration(
                            color: isPickup ? const Color(0xFF3B82F6) : const Color(0xFF10B981),
                            shape: BoxShape.circle,
                          ),
                          alignment: Alignment.center,
                          child: Text(
                            '${stop.stopNumber ?? 1}',
                            style: boldDefault.copyWith(color: Colors.white, fontSize: 10),
                          ),
                        ),
                        const SizedBox(width: Dimensions.space8),
                        Expanded(
                          child: Text(
                            '${isPickup ? "Recojo" : "Entrega"}: ${stop.contactName ?? stop.address ?? ""}',
                            style: regularDefault.copyWith(fontSize: 11),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  );
                }).toList(),
              ),
            ),
          ],

          const SizedBox(height: Dimensions.space15),

          // Accept Batch Button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => onAccept(batch),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF16A34A),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: Dimensions.space12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.space10)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.flash_on_rounded, size: 18),
                  const SizedBox(width: Dimensions.space6),
                  Text(
                    'Aceptar Lote (${batch.totalOrders ?? 2} pedidos)',
                    style: boldDefault.copyWith(color: Colors.white, fontSize: Dimensions.fontSmall),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _metricPill({required IconData icon, required String text, required bool isDark, Color? color}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: Dimensions.space4),
      decoration: BoxDecoration(
        color: (color ?? (isDark ? Colors.white12 : Colors.grey.shade200)).withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(Dimensions.space6),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: color ?? (isDark ? Colors.white70 : Colors.black87)),
          const SizedBox(width: 4),
          Text(
            text,
            style: semiBoldDefault.copyWith(
              fontSize: Dimensions.fontExtraSmall,
              color: color ?? (isDark ? Colors.white70 : Colors.black87),
            ),
          ),
        ],
      ),
    );
  }
}
