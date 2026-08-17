import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/model/seller/panel_models.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';

class SellerKitchenScreen extends StatefulWidget {
  const SellerKitchenScreen({super.key});
  @override
  State<SellerKitchenScreen> createState() => _SellerKitchenScreenState();
}

class _SellerKitchenScreenState extends State<SellerKitchenScreen> {
  late SellerPanelController c;
  Timer? _timer;

  // Per-card busy set so individual buttons disable without blocking the list.
  final Set<int> _busyOrders = {};

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPanelController>()) {
      Get.put(SellerPanelRepo(apiClient: Get.find()));
      Get.put(SellerPanelController(repo: Get.find()));
    }
    c = Get.find<SellerPanelController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => c.loadKitchen());

    // Silent background refresh every 8 s — NO loading indicator, NO widget rebuild freeze.
    _timer = Timer.periodic(const Duration(seconds: 8), (_) {
      if (mounted) c.loadKitchenSilent();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPanelController>(
      builder: (_) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          title: Text('Cocina', style: boldLarge.copyWith(color: Colors.white)),
          centerTitle: true,
          actions: [
            if (c.loadingKitchenSilent)
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 14),
                child: Center(
                  child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white54, strokeWidth: 2)),
                ),
              )
            else
              IconButton(
                icon: const Icon(Icons.refresh_rounded, color: Colors.white),
                onPressed: () => c.loadKitchen(),
              ),
            IconButton(
              icon: const Icon(Icons.logout_rounded, color: Colors.white),
              onPressed: _confirmLogout,
            ),
          ],
        ),
        body: c.loadingKitchen
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : c.kitchenOrders.isEmpty
                ? Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.soup_kitchen_rounded, size: 56, color: MyColor.neutral300),
                        const SizedBox(height: 12),
                        Text('Sin pedidos en cocina', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                        const SizedBox(height: 4),
                        Text('Se actualiza automáticamente', style: regularSmall.copyWith(color: MyColor.neutral300)),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: EdgeInsets.all(Dimensions.space16),
                    itemCount: c.kitchenOrders.length,
                    itemBuilder: (_, i) => _buildOrderCard(c.kitchenOrders[i]),
                  ),
      ),
    );
  }

  Widget _buildOrderCard(KitchenOrderModel o) {
    final isBusy = _busyOrders.contains(o.id);

    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space10),
      padding: EdgeInsets.all(Dimensions.space14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6)],
        border: Border.all(color: _statusColor(o.status).withValues(alpha: 0.25), width: 1.5),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Expanded(child: Text(o.orderNo ?? '', style: boldDefault)),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: _statusColor(o.status).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(_statusLabel(o.status), style: boldSmall.copyWith(color: _statusColor(o.status))),
          ),
        ]),
        if (o.table != null)
          Text(o.table!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
        if (o.kitchenNotes != null) ...[
          const SizedBox(height: 4),
          Text('⚠ ${o.kitchenNotes}', style: regularSmall.copyWith(color: MyColor.redCancelTextColor)),
        ],
        const SizedBox(height: 8),
        ...o.items.map((item) => _buildItemRow(item, o)),
        const SizedBox(height: 8),
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text('Total: S/ ${o.total?.toStringAsFixed(2) ?? "0"}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
          if (isBusy)
            const SizedBox(
              width: 28, height: 28,
              child: CircularProgressIndicator(strokeWidth: 2, color: MyColor.primaryColor),
            )
          else
            Row(children: [
              if (o.status == 'confirmed')
                ElevatedButton(
                  onPressed: () => _setOrderStatus(o, 'preparing'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: MyColor.primaryColor,
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  child: Text('Preparar', style: boldSmall.copyWith(color: Colors.white)),
                ),
              if (o.status == 'preparing')
                ElevatedButton(
                  onPressed: () => _setOrderStatus(o, 'ready'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF10B981),
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  ),
                  child: Text('Listo ✓', style: boldSmall.copyWith(color: Colors.white)),
                ),
            ]),
        ]),
      ]),
    );
  }

  Widget _buildItemRow(KitchenItemModel item, KitchenOrderModel o) {
    final done = item.status == 'done';
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: () => _toggleItem(o, item),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 2),
          child: Row(children: [
            AnimatedSwitcher(
              duration: const Duration(milliseconds: 200),
              child: Icon(
                done ? Icons.check_circle_rounded : Icons.radio_button_unchecked,
                key: ValueKey(done),
                size: 22,
                color: done ? const Color(0xFF10B981) : MyColor.neutral300,
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                '${item.qty}x ${item.name ?? ''}',
                style: regularDefault.copyWith(
                  decoration: done ? TextDecoration.lineThrough : null,
                  color: done ? MyColor.bodyMutedTextColor : MyColor.primaryTextColor,
                ),
              ),
            ),
          ]),
        ),
      ),
    );
  }

  Future<void> _setOrderStatus(KitchenOrderModel o, String newStatus) async {
    if (_busyOrders.contains(o.id)) return;
    setState(() => _busyOrders.add(o.id!));
    try {
      final ok = await c.updateKitchenOrderStatus(o.id!, newStatus);
      if (ok) await c.loadKitchenSilent();
    } finally {
      if (mounted) setState(() => _busyOrders.remove(o.id));
    }
  }

  Future<void> _toggleItem(KitchenOrderModel o, KitchenItemModel item) async {
    if (o.status != 'confirmed' && o.status != 'preparing') return;
    // Optimistic local update for instant feedback
    final nextStatus = item.status == 'done' ? 'pending' : 'done';
    setState(() => item.status = nextStatus);
    final ok = await c.toggleKitchenItem(o.id!, item.id!, nextStatus);
    if (!ok) {
      // Rollback if API fails
      if (mounted) setState(() => item.status = nextStatus == 'done' ? 'pending' : 'done');
    }
  }

  Color _statusColor(String? s) {
    switch (s) {
      case 'confirmed': return const Color(0xFFF59E0B);
      case 'preparing': return MyColor.primaryColor;
      case 'ready': return const Color(0xFF10B981);
      default: return MyColor.bodyMutedTextColor;
    }
  }

  String _statusLabel(String? s) {
    switch (s) {
      case 'confirmed': return 'Pendiente';
      case 'preparing': return 'Preparando';
      case 'ready': return 'Listo';
      default: return s ?? '';
    }
  }

  void _confirmLogout() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cerrar sesión'),
        content: const Text('¿Estás seguro que deseas salir?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('Cancelar', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              Get.find<SellerController>().logout();
            },
            child: Text('Salir', style: boldDefault.copyWith(color: MyColor.redCancelTextColor)),
          ),
        ],
      ),
    );
  }
}
