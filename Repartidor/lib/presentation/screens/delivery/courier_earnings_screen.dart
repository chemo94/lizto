import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';

import '../../../data/controller/dashboard/dashboard_controller.dart';

class CourierEarningsScreen extends StatefulWidget {
  const CourierEarningsScreen({super.key});

  @override
  State<CourierEarningsScreen> createState() => _CourierEarningsScreenState();
}

class _CourierEarningsScreenState extends State<CourierEarningsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<CourierController>().loadEarnings();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<CourierController>(
      builder: (c) {
        final e = c.earnings;
        return Scaffold(
          backgroundColor: MyColor.screenBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Ganancias', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            leading: IconButton(
              icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
              onPressed: () {
                if (Navigator.canPop(context)) {
                  Get.back();
                } else {
                  if (Get.isRegistered<DashBoardController>()) {
                    Get.find<DashBoardController>().changeTab(0);
                  } else {
                    Get.back();
                  }
                }
              },
            ),
          ),
          body: c.loadingEarnings
              ? const Center(child: CircularProgressIndicator())
              : e == null
                  ? const Center(child: Text('Sin datos de ganancias'))
                  : RefreshIndicator(
                      onRefresh: () async => c.loadEarnings(),
                      child: ListView(
                        padding: EdgeInsets.all(Dimensions.space16),
                        children: [
                          // ── REAL BALANCE HERO ──
                          _buildRealBalanceCard(e),
                          SizedBox(height: Dimensions.space16),

                          // ── DRIVER FREQUENCY TIER CARD ──
                          _buildTierCard(e),
                          SizedBox(height: Dimensions.space16),

                          // ── Earnings breakdown header ──
                          Padding(
                            padding: EdgeInsets.only(left: Dimensions.space4, bottom: Dimensions.space8),
                            child: Text('Historial de ganancias', style: boldDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                          ),

                          _buildPeriodCard('Hoy', e.todayEarnings, e.todayJobs, const Color(0xFF3B82F6), Icons.today_rounded),
                          SizedBox(height: Dimensions.space12),
                          _buildPeriodCard('Esta semana', e.weekEarnings, e.weekJobs, const Color(0xFF8B5CF6), Icons.date_range_rounded),
                          SizedBox(height: Dimensions.space12),
                          _buildPeriodCard('Este mes', e.monthEarnings, e.monthJobs, const Color(0xFF10B981), Icons.calendar_month_rounded),
                          SizedBox(height: Dimensions.space12),
                          _buildPeriodCard('Total histórico', e.totalEarnings, e.totalJobs, MyColor.primaryColor, Icons.all_inclusive_rounded),

                          if (e.recentSettlements.isNotEmpty) ...[
                            SizedBox(height: Dimensions.space20),
                            Padding(
                              padding: EdgeInsets.only(left: Dimensions.space4, bottom: Dimensions.space8),
                              child: Text('Pagos del administrador', style: boldDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                            ),
                            ...e.recentSettlements.map((s) => _buildSettlementRow(s)),
                          ],

                          if (e.avgRating != null) ...[
                            SizedBox(height: Dimensions.space16),
                            _buildRatingCard(e.avgRating!),
                          ],

                          SizedBox(height: 100),
                        ],
                      ),
                    ),
        );
      },
    );
  }

  /// The main hero card clearly distinguishing pending balance to collect vs gross historical earnings
  Widget _buildRealBalanceCard(CourierEarningsModel e) {
    final available = e.earningBalance ?? 0.0;
    final gross = e.totalEarnings ?? 0.0;
    final paid = e.totalPaid ?? 0.0;
    final isZeroPending = available <= 0.001;

    return Container(
      padding: EdgeInsets.all(Dimensions.space20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.3), blurRadius: 16, offset: const Offset(0, 6)),
        ],
      ),
      child: Column(
        children: [
          // Section Title: Por Cobrar a Lizto
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                isZeroPending ? Icons.check_circle_rounded : Icons.pending_actions_rounded,
                color: isZeroPending ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                size: 20,
              ),
              const SizedBox(width: 8),
              Text(
                'Por cobrar a Lizto',
                style: boldDefault.copyWith(color: Colors.white.withValues(alpha: 0.9), fontSize: 15),
              ),
            ],
          ),
          SizedBox(height: Dimensions.space8),
          Text(
            'S/ ${available.toStringAsFixed(2)}',
            style: TextStyle(
              fontSize: 42,
              fontWeight: FontWeight.w900,
              color: isZeroPending ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
            ),
          ),
          const SizedBox(height: 4),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            decoration: BoxDecoration(
              color: (isZeroPending ? const Color(0xFF10B981) : const Color(0xFFF59E0B)).withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: isZeroPending ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                width: 1,
              ),
            ),
            child: Text(
              isZeroPending ? '✅ AL DÍA (S/ 0.00 pendientes)' : '⏳ ACUMULANDO PENDIENTE POR COBRAR',
              style: boldSmall.copyWith(
                color: isZeroPending ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                fontSize: 11,
              ),
            ),
          ),
          SizedBox(height: Dimensions.space20),

          // Detailed Breakdown Strip
          Container(
            padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 12),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.07),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Row(
              children: [
                Expanded(
                  child: _summaryCol(
                    'Ganancias totales',
                    'S/ ${gross.toStringAsFixed(2)}',
                    const Color(0xFF60A5FA),
                    Icons.trending_up_rounded,
                  ),
                ),
                Container(width: 1, height: 40, color: Colors.white.withValues(alpha: 0.15)),
                Expanded(
                  child: _summaryCol(
                    'Ya cobrado',
                    'S/ ${paid.toStringAsFixed(2)}',
                    const Color(0xFF34D399),
                    Icons.task_alt_rounded,
                  ),
                ),
                Container(width: 1, height: 40, color: Colors.white.withValues(alpha: 0.15)),
                Expanded(
                  child: _summaryCol(
                    'Pedidos',
                    '${e.totalJobs ?? 0}',
                    const Color(0xFFFBBF24),
                    Icons.moped_rounded,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _summaryCol(String label, String value, Color color, IconData icon) {
    return Column(
      children: [
        Icon(icon, color: color, size: 18),
        const SizedBox(height: 4),
        Text(value, style: boldDefault.copyWith(color: color, fontSize: 13)),
        const SizedBox(height: 2),
        Text(label, style: regularSmall.copyWith(color: Colors.white.withValues(alpha: 0.5), fontSize: 10), textAlign: TextAlign.center),
      ],
    );
  }

  Widget _buildPeriodCard(String title, double? amount, int? jobs, Color color, IconData icon) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
        border: Border(left: BorderSide(color: color, width: 4)),
      ),
      child: Row(children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
          child: Icon(icon, color: color, size: 22),
        ),
        SizedBox(width: Dimensions.space12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(title, style: boldDefault),
            Text('${jobs ?? 0} pedidos', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
          ]),
        ),
        Text('S/ ${amount?.toStringAsFixed(2) ?? "0.00"}', style: boldLarge.copyWith(color: color)),
      ]),
    );
  }

  Widget _buildSettlementRow(EarningSettlement s) {
    final isPaid = s.trxType == '-';
    final color = isPaid ? const Color(0xFFEF4444) : const Color(0xFF10B981);
    final icon = isPaid ? Icons.arrow_circle_down_rounded : Icons.arrow_circle_up_rounded;
    final methodLabel = _methodLabel(s.settlementMethod);

    String dateStr = '';
    if (s.date != null) {
      try {
        final dt = DateTime.parse(s.date!);
        dateStr = '${dt.day.toString().padLeft(2, '0')}/${dt.month.toString().padLeft(2, '0')}/${dt.year}';
      } catch (_) {
        dateStr = s.date!;
      }
    }

    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space10),
      padding: EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          SizedBox(width: Dimensions.space12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  isPaid ? 'Pago del administrador' : 'Ajuste',
                  style: boldDefault.copyWith(fontSize: 13),
                ),
                if (methodLabel.isNotEmpty) Text('Vía $methodLabel', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                if (s.notes != null && s.notes!.isNotEmpty && s.notes != 'null') Text(s.notes!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 1, overflow: TextOverflow.ellipsis),
                Text(dateStr, style: regularSmall.copyWith(color: MyColor.neutral500, fontSize: 11)),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${isPaid ? '-' : '+'}S/ ${(s.amount ?? 0).toStringAsFixed(2)}',
                style: boldDefault.copyWith(color: color),
              ),
              Text(
                'Saldo: S/ ${(s.postBalance ?? 0).toStringAsFixed(2)}',
                style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10),
              ),
            ],
          ),
        ],
      ),
    );
  }

  String _methodLabel(String? method) {
    return switch (method) {
      'yape' => 'Yape',
      'plin' => 'Plin',
      'bank' => 'Banco',
      'balance' => 'Saldo',
      'cash' => 'Efectivo',
      _ => method ?? '',
    };
  }

  Widget _buildRatingCard(double rating) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Row(children: [
        Icon(Icons.star_rounded, color: const Color(0xFFF59E0B), size: 40),
        SizedBox(width: Dimensions.space12),
        Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('$rating', style: boldLarge.copyWith(fontSize: Dimensions.fontExtraLarge)),
          Text('Calificación promedio', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
        ]),
      ]),
    );
  }

  Widget _buildTierCard(CourierEarningsModel e) {
    final tierName = e.tierName ?? 'Inicial';
    final tierBadge = e.tierBadge ?? '🛵';
    final percent = (e.effectivePercent ?? e.baseCommissionPercent ?? 10.0).toStringAsFixed(0);
    final completedJobs = e.totalCompletedJobs ?? e.totalWeeklyJobs ?? 0;
    final commissionLabel = '$percent% por pedido';
    final nextNeeded = e.nextTierNeeded ?? 0;
    final nextName = e.nextTierName ?? 'Plata';

    Color cardColor;
    if (tierName == 'Preferente') {
      cardColor = const Color(0xFF10B981);
    } else if (tierName == 'Plata') {
      cardColor = const Color(0xFF64748B);
    } else if (tierName == 'Bronce') {
      cardColor = const Color(0xFFD97706);
    } else {
      cardColor = const Color(0xFF3B82F6);
    }

    return GestureDetector(
      onTap: () => _showTierDetailsModal(context, e),
      child: Container(
        padding: const EdgeInsets.all(Dimensions.space16),
        decoration: BoxDecoration(
          color: cardColor.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
          border: Border.all(color: cardColor.withValues(alpha: 0.4), width: 1.5),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Row(
                    children: [
                      Text(tierBadge, style: const TextStyle(fontSize: 28)),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Text(
                                  'Nivel $tierName',
                                  style: boldLarge.copyWith(color: cardColor, fontSize: 18),
                                  overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(width: 6),
                                Icon(Icons.info_outline_rounded, color: cardColor, size: 16),
                              ],
                            ),
                            Text(
                              'Comisión actual: $commissionLabel',
                              style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontWeight: FontWeight.w600),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: cardColor,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    'Comisión: $percent%',
                    style: boldDefault.copyWith(color: Colors.white, fontSize: 12),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Divider(color: cardColor.withValues(alpha: 0.2), height: 1),
            const SizedBox(height: 10),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    'Entregas acumuladas: $completedJobs',
                    style: regularDefault.copyWith(color: MyColor.primaryTextColor, fontWeight: FontWeight.w600),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                const SizedBox(width: 8),
                if (nextNeeded > 0)
                  Text(
                    'Faltan $nextNeeded para $nextName',
                    style: boldSmall.copyWith(color: cardColor),
                  )
                else
                  Text(
                    '¡Nivel Máximo!',
                    style: boldSmall.copyWith(color: const Color(0xFF10B981)),
                  ),
              ],
            ),
            if ((e.offersReceived ?? 0) > 0) ...[
              const SizedBox(height: 10),
              Text(
                'Respuesta últimos 30 días: ${(e.responseRate ?? 0).toStringAsFixed(0)}% '
                '(${e.offersResponded ?? 0}/${e.offersReceived ?? 0})'
                '${e.eligibleForReview == true ? '' : ' · Muestra insuficiente, sin evaluación'}',
                style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _showTierDetailsModal(BuildContext context, CourierEarningsModel e) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final currentTier = e.tierName ?? 'Inicial';
    final base = e.baseCommissionPercent ?? 10;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return Container(
          decoration: BoxDecoration(
            color: isDark ? const Color(0xFF0F172A) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
          ),
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: isDark ? Colors.grey[700] : Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  const Icon(Icons.stars_rounded, color: Color(0xFFF59E0B), size: 28),
                  const SizedBox(width: 10),
                  Text(
                    'Programa de Niveles y Beneficios',
                    style: boldLarge.copyWith(
                      color: isDark ? Colors.white : MyColor.primaryTextColor,
                      fontSize: 18,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                'Tus entregas se acumulan y no vuelven a cero. Al llegar a 30 desbloqueas la comisión mínima estable.',
                style: regularDefault.copyWith(
                  color: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor,
                  fontSize: 12,
                ),
              ),
              const SizedBox(height: 20),
              _buildTierModalItem(
                tier: 'Inicial',
                badge: '🛵',
                range: '0 a 9 entregas acumuladas',
                commission: '${base.toStringAsFixed(0)}% (Base)',
                color: const Color(0xFF3B82F6),
                isCurrent: currentTier == 'Inicial',
                isDark: isDark,
              ),
              const SizedBox(height: 10),
              _buildTierModalItem(
                tier: 'Bronce',
                badge: '🥉',
                range: '10 a 19 entregas acumuladas',
                commission: '${(base - 3).clamp(5, 100).toStringAsFixed(0)}%',
                color: const Color(0xFFD97706),
                isCurrent: currentTier == 'Bronce',
                isDark: isDark,
              ),
              const SizedBox(height: 10),
              _buildTierModalItem(
                tier: 'Plata',
                badge: '🥈',
                range: '20 a 29 entregas acumuladas',
                commission: '${(base - 5).clamp(5, 100).toStringAsFixed(0)}%',
                color: const Color(0xFF64748B),
                isCurrent: currentTier == 'Plata',
                isDark: isDark,
              ),
              const SizedBox(height: 10),
              _buildTierModalItem(
                tier: 'Preferente',
                badge: '⭐',
                range: '30 o más entregas acumuladas',
                commission: '10% por pedido (tarifa preferencial)',
                color: const Color(0xFF10B981),
                isCurrent: currentTier == 'Preferente',
                isDark: isDark,
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () => Navigator.pop(context),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: MyColor.primaryColor,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                  child: Text('¡Entendido!', style: boldDefault.copyWith(color: Colors.white)),
                ),
              ),
              const SizedBox(height: 10),
            ],
          ),
        );
      },
    );
  }

  Widget _buildTierModalItem({
    required String tier,
    required String badge,
    required String range,
    required String commission,
    required Color color,
    required bool isCurrent,
    required bool isDark,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isCurrent ? color.withValues(alpha: 0.18) : (isDark ? const Color(0xFF1E293B) : Colors.grey[100]),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isCurrent ? color : (isDark ? Colors.grey[800]! : Colors.grey[300]!),
          width: isCurrent ? 2 : 1,
        ),
      ),
      child: Row(
        children: [
          Text(badge, style: const TextStyle(fontSize: 24)),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text('Nivel $tier', style: boldDefault.copyWith(color: color, fontSize: 14)),
                    if (isCurrent) ...[
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: color,
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          'TU NIVEL',
                          style: boldSmall.copyWith(color: Colors.white, fontSize: 9),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(range, style: regularSmall.copyWith(color: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor, fontSize: 11)),
              ],
            ),
          ),
          Text(commission, style: boldDefault.copyWith(color: color, fontSize: 12)),
        ],
      ),
    );
  }
}
