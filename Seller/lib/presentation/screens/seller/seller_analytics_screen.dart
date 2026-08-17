import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_package_controller.dart';
import 'package:lizto_store/data/model/seller/package_models.dart';
import 'package:lizto_store/data/repo/seller/seller_package_repo.dart';
import 'package:lizto_store/presentation/screens/seller/subscription_guard.dart';

class SellerAnalyticsScreen extends StatefulWidget {
  final int storeId;
  const SellerAnalyticsScreen({super.key, required this.storeId});

  @override
  State<SellerAnalyticsScreen> createState() => _SellerAnalyticsScreenState();
}

class _SellerAnalyticsScreenState extends State<SellerAnalyticsScreen> {
  late SellerPackageController _c;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPackageController>()) {
      Get.put(SellerPackageRepo(apiClient: Get.find()));
      Get.put(SellerPackageController(repo: Get.find()));
    }
    _c = Get.find<SellerPackageController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => _init());
  }

  Future<void> _init() async {
    final ok = await SubscriptionGuard.canProceed(context, storeId: widget.storeId);
    if (ok) {
      _c.loadAnalytics(widget.storeId);
    }
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPackageController>(
      builder: (c) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          title: Text('Analytics', style: boldLarge.copyWith(color: MyColor.colorWhite)),
          centerTitle: true,
        ),
        body: c.loadingAnalytics
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : c.analytics == null
                ? Center(child: Text('Sin datos', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
                : ListView(padding: EdgeInsets.all(Dimensions.space16), children: [
                    _summaryCards(c.analytics!),
                    SizedBox(height: Dimensions.space20),
                    _dailyChart(c.analytics!),
                    SizedBox(height: Dimensions.space20),
                    _topProducts(c.analytics!),
                  ]),
      ),
    );
  }

  Widget _summaryCards(StoreAnalyticsModel a) => Column(children: [
    Row(children: [
      Expanded(child: _statCard('Total pedidos', '${a.totalOrders ?? 0}', Icons.receipt_long_rounded, MyColor.primaryColor)),
      SizedBox(width: Dimensions.space10),
      Expanded(child: _statCard('Completados', '${a.completedOrders ?? 0}', Icons.check_circle_rounded, const Color(0xFF10B981))),
    ]),
    SizedBox(height: Dimensions.space10),
    Row(children: [
      Expanded(child: _statCard('Revenue', 'S/ ${a.totalRevenue?.toStringAsFixed(2) ?? "0"}', Icons.trending_up_rounded, const Color(0xFFF59E0B))),
      SizedBox(width: Dimensions.space10),
      Expanded(child: _statCard('Ticket promedio', 'S/ ${a.avgOrderValue?.toStringAsFixed(2) ?? "0"}', Icons.analytics_rounded, const Color(0xFF8B5CF6))),
    ]),
  ]);

  Widget _statCard(String label, String value, IconData icon, Color color) => Container(
    padding: EdgeInsets.all(Dimensions.space14),
    decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(16), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6)]),
    child: Column(children: [
      Container(width: 40, height: 40, decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)), child: Icon(icon, color: color, size: 22)),
      SizedBox(height: Dimensions.space8),
      Text(value, style: boldExtraLarge.copyWith(color: color, fontSize: 20)),
      SizedBox(height: 4),
      Text(label, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
    ]),
  );

  Widget _dailyChart(StoreAnalyticsModel a) {
    if (a.daily.isEmpty) return const SizedBox.shrink();
    final maxRev = a.daily.map((d) => d.revenue ?? 0).reduce((a, b) => a > b ? a : b);
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text('Ventas diarias', style: boldLarge),
      SizedBox(height: Dimensions.space12),
      Container(
        padding: EdgeInsets.all(Dimensions.space16),
        decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(16)),
        child: Column(children: a.daily.map((d) {
          final pct = maxRev > 0 ? (d.revenue ?? 0) / maxRev : 0.0;
          return Padding(
            padding: EdgeInsets.only(bottom: 8),
            child: Row(children: [
              SizedBox(width: 50, child: Text(_shortDate(d.date), style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Container(height: 20, decoration: BoxDecoration(color: MyColor.primaryColor.withValues(alpha: 0.2), borderRadius: BorderRadius.circular(4))),
                SizedBox(height: 2),
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(value: pct, backgroundColor: Colors.transparent, valueColor: AlwaysStoppedAnimation<Color>(MyColor.primaryColor), minHeight: 6),
                ),
              ])),
              SizedBox(width: 8),
              SizedBox(width: 70, child: Text('S/ ${d.revenue?.toStringAsFixed(2) ?? "0"}', style: regularSmall.copyWith(fontWeight: FontWeight.w600), textAlign: TextAlign.right)),
            ]),
          );
        }).toList()),
      ),
    ]);
  }

  Widget _topProducts(StoreAnalyticsModel a) {
    if (a.topProducts.isEmpty) return const SizedBox.shrink();
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text('Productos top', style: boldLarge),
      SizedBox(height: Dimensions.space12),
      ...a.topProducts.map((p) => Container(
        margin: EdgeInsets.only(bottom: Dimensions.space8),
        padding: EdgeInsets.all(Dimensions.space12),
        decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(12)),
        child: Row(children: [
          Expanded(child: Text(p.productName ?? '', style: boldDefault)),
          Text('x${p.totalQty ?? 0}', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          SizedBox(width: 12),
          Text('S/ ${p.totalSales?.toStringAsFixed(2) ?? "0"}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
        ]),
      )),
    ]);
  }

  String _shortDate(String? raw) {
    if (raw == null) return '';
    try {
      final d = DateTime.parse(raw);
      return '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')}';
    } catch (_) {
      return raw;
    }
  }
}
