import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';

class SellerReportsScreen extends StatefulWidget {
  const SellerReportsScreen({super.key});
  @override
  State<SellerReportsScreen> createState() => _SellerReportsScreenState();
}

class _SellerReportsScreenState extends State<SellerReportsScreen> {
  late SellerPanelController c;
  DateTime _from = DateTime.now().subtract(const Duration(days: 7));
  DateTime _to = DateTime.now();

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPanelController>()) {
      Get.put(SellerPanelRepo(apiClient: Get.find()));
      Get.put(SellerPanelController(repo: Get.find()));
    }
    c = Get.find<SellerPanelController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => c.loadReports(from: _dateStr(_from), to: _dateStr(_to)));
  }

  String _dateStr(DateTime d) => '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPanelController>(
      builder: (_) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(backgroundColor: MyColor.primaryColor, title: Text('Reportes', style: boldLarge.copyWith(color: Colors.white)), centerTitle: true),
        body: c.loadingReports
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : ListView(padding: EdgeInsets.all(Dimensions.space16), children: [
                _datePicker(),
                SizedBox(height: Dimensions.space16),
                if (c.report != null) ...[
                  _metricRow('Pedidos POS', '${c.report!.posCount ?? 0}', 'S/ ${c.report!.posSales?.toStringAsFixed(2) ?? "0"}', MyColor.primaryColor),
                  SizedBox(height: 10),
                  _metricRow('Pedidos Delivery', '${c.report!.delCount ?? 0}', 'S/ ${c.report!.delSales?.toStringAsFixed(2) ?? "0"}', const Color(0xFF10B981)),
                  SizedBox(height: 10),
                  _metricRow('Total', '${c.report!.totalOrders ?? 0}', 'S/ ${c.report!.totalSales?.toStringAsFixed(2) ?? "0"}', const Color(0xFFF59E0B)),
                ] else
                  Center(child: Text('Sin datos', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))),
              ]),
      ),
    );
  }

  Widget _datePicker() => Row(children: [
    Expanded(child: _dateBtn('Desde: ${_dateStr(_from)}', () async {
      final d = await showDatePicker(context: context, initialDate: _from, firstDate: DateTime(2024), lastDate: DateTime.now());
      if (d != null) { setState(() => _from = d); c.loadReports(from: _dateStr(_from), to: _dateStr(_to)); }
    })),
    SizedBox(width: 10),
    Expanded(child: _dateBtn('Hasta: ${_dateStr(_to)}', () async {
      final d = await showDatePicker(context: context, initialDate: _to, firstDate: DateTime(2024), lastDate: DateTime.now());
      if (d != null) { setState(() => _to = d); c.loadReports(from: _dateStr(_from), to: _dateStr(_to)); }
    })),
  ]);

  Widget _dateBtn(String label, VoidCallback onTap) => GestureDetector(onTap: onTap, child: Container(padding: EdgeInsets.all(14), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(14), border: Border.all(color: MyColor.neutral200)), child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.calendar_today_rounded, size: 16, color: MyColor.primaryColor), SizedBox(width: 8), Text(label, style: regularDefault)])));

  Widget _metricRow(String label, String count, String amount, Color color) => Container(
    padding: EdgeInsets.all(Dimensions.space16),
    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border(left: BorderSide(color: color, width: 4))),
    child: Row(children: [
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(label, style: boldDefault), Text('$count pedidos', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))])),
      Text(amount, style: boldLarge.copyWith(color: color, fontSize: 18)),
    ]),
  );
}
