import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';
import 'package:lizto_store/presentation/screens/seller/subscription_guard.dart';

class SellerCashScreen extends StatefulWidget {
  const SellerCashScreen({super.key});
  @override
  State<SellerCashScreen> createState() => _SellerCashScreenState();
}

class _SellerCashScreenState extends State<SellerCashScreen> {
  late SellerPanelController c;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPanelController>()) {
      Get.put(SellerPanelRepo(apiClient: Get.find()));
      Get.put(SellerPanelController(repo: Get.find()));
    }
    c = Get.find<SellerPanelController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => c.loadCash());
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPanelController>(
      builder: (_) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(backgroundColor: MyColor.primaryColor, title: Text('Caja', style: boldLarge.copyWith(color: Colors.white)), centerTitle: true),
        body: c.loadingCash
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : ListView(padding: EdgeInsets.all(Dimensions.space16), children: [
                if (c.cashLoadFailed)
                  Container(
                    padding: EdgeInsets.all(Dimensions.space14),
                    margin: EdgeInsets.only(bottom: Dimensions.space16),
                    decoration: BoxDecoration(color: const Color(0xFFFEF2F2), borderRadius: BorderRadius.circular(Dimensions.mediumRadius), border: Border.all(color: MyColor.redCancelTextColor.withValues(alpha: 0.4))),
                    child: Row(children: [
                      Icon(Icons.cloud_off_rounded, color: MyColor.redCancelTextColor, size: 22),
                      SizedBox(width: Dimensions.space10),
                      Expanded(child: Text(c.cashError.isEmpty ? 'No se pudo cargar la caja' : c.cashError, style: regularSmall.copyWith(color: const Color(0xFF7F1D1D)))),
                      TextButton(onPressed: () => c.loadCash(), child: const Text('Reintentar')),
                    ]),
                  ),
                if (c.cashData?.openSession != null) _openSessionCard(),
                _actionButtons(),
                _transactionsList(),
              ]),
      ),
    );
  }

  Widget _openSessionCard() {
    final s = c.cashData!.openSession!;
    return Container(
      padding: EdgeInsets.all(Dimensions.space20),
      margin: EdgeInsets.only(bottom: Dimensions.space16),
      decoration: BoxDecoration(gradient: LinearGradient(colors: [MyColor.primaryColor, const Color(0xFF8B0000)]), borderRadius: BorderRadius.circular(20)),
      child: Column(children: [
        Text('Caja abierta', style: regularDefault.copyWith(color: Colors.white.withValues(alpha: 0.8))),
        SizedBox(height: Dimensions.space8),
        Text('S/ ${((s.openingBalance ?? 0) + (s.totalSales ?? 0) - (s.totalExpenses ?? 0)).toStringAsFixed(2)}', style: TextStyle(fontSize: 36, fontWeight: FontWeight.bold, color: Colors.white)),
        SizedBox(height: Dimensions.space12),
        Row(mainAxisAlignment: MainAxisAlignment.spaceAround, children: [
          _stat('Apertura', 'S/ ${s.openingBalance?.toStringAsFixed(2) ?? "0"}'),
          _stat('Ventas', 'S/ ${s.totalSales?.toStringAsFixed(2) ?? "0"}'),
          _stat('Gastos', '- S/ ${s.totalExpenses?.toStringAsFixed(2) ?? "0"}'),
        ]),
      ]),
    );
  }

  Widget _stat(String label, String value) => Column(children: [Text(value, style: boldDefault.copyWith(color: Colors.white)), Text(label, style: regularSmall.copyWith(color: Colors.white.withValues(alpha: 0.7)))]);

  Widget _actionButtons() {
    final hasOpen = c.cashData?.openSession != null;
    final canAct = !c.cashLoadFailed;
    return Column(children: [
      Row(children: [
        Expanded(child: _actionBtn('Abrir caja', Icons.play_arrow_rounded, const Color(0xFF10B981), canAct && !hasOpen ? () => _openCash() : null)),
        SizedBox(width: 10),
        Expanded(child: _actionBtn('Cerrar caja', Icons.stop_rounded, MyColor.redCancelTextColor, hasOpen ? () => _closeCash() : null)),
      ]),
      SizedBox(height: 10),
      Row(children: [
        Expanded(child: _actionBtn('Ingreso', Icons.add_circle_rounded, const Color(0xFF3B82F6), canAct && hasOpen ? () => _txn('cash_in') : null)),
        SizedBox(width: 10),
        Expanded(child: _actionBtn('Egreso', Icons.remove_circle_rounded, const Color(0xFFF59E0B), canAct && hasOpen ? () => _txn('cash_out') : null)),
      ]),
      SizedBox(height: Dimensions.space16),
    ]);
  }

  Widget _actionBtn(String label, IconData icon, Color color, VoidCallback? onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.symmetric(vertical: 14), decoration: BoxDecoration(color: color.withValues(alpha: onTap != null ? 1 : 0.3), borderRadius: BorderRadius.circular(14)),
        child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(icon, color: Colors.white, size: 18), SizedBox(width: 6), Text(label, style: boldDefault.copyWith(color: Colors.white))]),
      ),
    );
  }

  Widget _transactionsList() {
    final txns = c.cashData?.transactions ?? [];
    if (txns.isEmpty) return const SizedBox.shrink();
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text('Movimientos', style: boldLarge),
      SizedBox(height: 8),
      ...txns.map((t) => Container(
        margin: EdgeInsets.only(bottom: 6),
        padding: EdgeInsets.all(12), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
        child: Row(children: [
          Icon(t.type == 'sale' ? Icons.shopping_cart : t.type == 'cash_in' ? Icons.add_circle : Icons.remove_circle, color: t.type == 'cash_out' ? MyColor.redCancelTextColor : const Color(0xFF10B981), size: 20),
          SizedBox(width: 10),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(t.description ?? t.type ?? '', style: boldDefault), Text(t.createdAt ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))])),
          Text('S/ ${t.amount?.toStringAsFixed(2) ?? "0"}', style: boldDefault.copyWith(color: t.type == 'cash_out' ? MyColor.redCancelTextColor : const Color(0xFF10B981))),
        ]),
      )),
    ]);
  }

  int _currentStoreId() {
    try {
      if (Get.isRegistered<SellerController>() && Get.find<SellerController>().stores.isNotEmpty) {
        final raw = Get.find<SellerController>().stores.first['id'];
        return raw is int ? raw : int.tryParse(raw?.toString() ?? '') ?? 0;
      }
    } catch (_) {}
    return 0;
  }

  void _openCash() async {
    final canGo = await SubscriptionGuard.canProceed(context, storeId: _currentStoreId());
    if (!canGo) return;
    final ctrl = TextEditingController();
    final amt = await showDialog<double>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Abrir caja'),
        content: TextField(
          controller: ctrl,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: const InputDecoration(labelText: 'Saldo inicial'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancelar')),
          TextButton(onPressed: () => Navigator.pop(ctx, _parseAmount(ctrl.text)), child: const Text('Abrir')),
        ],
      ),
    );
    if (amt == null) return;
    if (amt <= 0) {
      Get.snackbar('Monto inválido', 'Ingresa un saldo inicial mayor a 0', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
      return;
    }
    bool ok = await c.openCash(amt);
    if (ok) {
      c.loadCash();
      Get.snackbar('Caja abierta', 'S/ ${amt.toStringAsFixed(2)}', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
    } else {
      Get.snackbar('No se pudo abrir la caja', c.cashError, backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
    }
  }

  void _closeCash() async {
    final canGo = await SubscriptionGuard.canProceed(context, storeId: _currentStoreId());
    if (!canGo) return;
    final s = c.cashData!.openSession!;
    final bal = (s.openingBalance ?? 0) + (s.totalSales ?? 0) - (s.totalExpenses ?? 0);
    bool ok = await c.closeCash(bal);
    if (ok) {
      c.loadCash();
      Get.snackbar('Caja cerrada', 'Saldo final: S/ ${bal.toStringAsFixed(2)}', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
    } else {
      Get.snackbar('No se pudo cerrar la caja', c.cashError, backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
    }
  }

  void _txn(String type) async {
    final canGo = await SubscriptionGuard.canProceed(context, storeId: _currentStoreId());
    if (!canGo) return;
    final amtCtrl = TextEditingController();
    final descCtrl = TextEditingController();
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (ctx) => AlertDialog(title: Text(type == 'cash_in' ? 'Ingreso' : 'Egreso'), content: Column(mainAxisSize: MainAxisSize.min, children: [
        TextField(controller: amtCtrl, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: InputDecoration(labelText: 'Monto', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)))),
        SizedBox(height: 12), TextField(controller: descCtrl, decoration: InputDecoration(labelText: 'Descripción', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)))),
      ]), actions: [
        TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancelar')),
        TextButton(onPressed: () => Navigator.pop(ctx, {'amount': _parseAmount(amtCtrl.text), 'desc': descCtrl.text}), child: const Text('Guardar')),
      ]),
    );
    if (result == null) return;
    final amount = result['amount'];
    if (amount == null || amount <= 0) {
      Get.snackbar('Monto inválido', 'Ingresa un monto mayor a 0', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
      return;
    }
    bool ok = await c.cashTransaction(type, amount, result['desc']?.toString());
    if (ok) {
      c.loadCash();
      Get.snackbar('Registrado', 'Movimiento guardado', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
    } else {
      Get.snackbar('No se pudo registrar', c.cashError, backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
    }
  }

  double _parseAmount(String text) {
    final normalized = text.trim().replaceAll(',', '.');
    return double.tryParse(normalized) ?? 0;
  }
}
