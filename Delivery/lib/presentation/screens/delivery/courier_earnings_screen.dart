import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/courier_controller.dart';
import 'package:lizto_delivery/data/repo/delivery/courier_repo.dart';
import 'package:lizto_delivery/data/services/api_client.dart';

class CourierEarningsScreen extends StatefulWidget {
  const CourierEarningsScreen({super.key});

  @override
  State<CourierEarningsScreen> createState() => _CourierEarningsScreenState();
}

class _CourierEarningsScreenState extends State<CourierEarningsScreen> {
  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<CourierController>()) {
      Get.put(CourierController(courierRepo: CourierRepo(apiClient: Get.find<ApiClient>())));
    }
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
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Ganancias', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
          ),
          body: c.loadingEarnings
              ? const Center(child: CircularProgressIndicator())
              : e == null
                  ? const Center(child: Text('Sin datos de ganancias'))
                  : ListView(
                      padding: EdgeInsets.all(Dimensions.space16),
                      children: [
                        _buildSummaryCard(),
                        SizedBox(height: Dimensions.space16),
                        _buildPeriodCard('Hoy', e.todayEarnings, e.todayJobs, const Color(0xFF3B82F6)),
                        SizedBox(height: Dimensions.space12),
                        _buildPeriodCard('Esta semana', e.weekEarnings, e.weekJobs, const Color(0xFF8B5CF6)),
                        SizedBox(height: Dimensions.space12),
                        _buildPeriodCard('Este mes', e.monthEarnings, e.monthJobs, const Color(0xFF10B981)),
                        SizedBox(height: Dimensions.space12),
                        _buildPeriodCard('Total histórico', e.totalEarnings, e.totalJobs, MyColor.primaryColor),
                        if (e.avgRating != null) ...[
                          SizedBox(height: Dimensions.space16),
                          _buildRatingCard(e.avgRating!),
                        ],
                      ],
                    ),
        );
      },
    );
  }

  Widget _buildSummaryCard() {
    final e = Get.find<CourierController>().earnings!;
    return Container(
      padding: EdgeInsets.all(Dimensions.space20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [MyColor.primaryColor, MyColor.primaryColor.withValues(alpha: 0.8)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
      ),
      child: Column(children: [
        Text('Balance total', style: regularDefault.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.8))),
        SizedBox(height: Dimensions.space8),
        Text('S/ ${e.totalEarnings?.toStringAsFixed(2) ?? "0.00"}',
            style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: MyColor.colorWhite)),
        SizedBox(height: Dimensions.space16),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: [
            _statCol('Hoy', 'S/ ${e.todayEarnings?.toStringAsFixed(2) ?? "0.00"}'),
            _statCol('Total pedidos', '${e.totalJobs ?? 0}'),
            if (e.avgRating != null) _statCol('Rating', e.avgRating!.toStringAsFixed(1)),
          ],
        ),
      ]),
    );
  }

  Widget _statCol(String label, String value) {
    return Column(children: [
      Text(value, style: boldLarge.copyWith(color: MyColor.colorWhite)),
      SizedBox(height: 4),
      Text(label, style: regularSmall.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.7))),
    ]);
  }

  Widget _buildPeriodCard(String title, double? amount, int? jobs, Color color) {
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
          width: 40, height: 40,
          decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
          child: Icon(Icons.payments_rounded, color: color, size: 22),
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
}
