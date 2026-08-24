import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/presentation/screens/seller/seller_favor_create_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_favor_search_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_favor_tracking_screen.dart';

class SellerFavorsScreen extends StatefulWidget {
  const SellerFavorsScreen({super.key});

  @override
  State<SellerFavorsScreen> createState() => _SellerFavorsScreenState();
}

class _SellerFavorsScreenState extends State<SellerFavorsScreen> {
  bool _loading = true;
  List<Map<String, dynamic>> _favors = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    if (mounted) setState(() => _loading = true);
    final result = await Get.find<SellerController>().getStoreFavors();
    if (!mounted) return;
    setState(() {
      if (result != null) _favors = result;
      _loading = false;
    });
  }

  void _open(Map<String, dynamic> favor) {
    final id = favor['id'];
    if (id is! num) return;
    final orderNo = favor['order_no']?.toString() ?? '';
    final status = favor['status']?.toString() ?? '';
    if (status == 'searching_courier') {
      Get.to(() => SellerFavorSearchScreen(favorId: id.toInt(), orderNo: orderNo));
    } else {
      Get.to(() => SellerFavorTrackingScreen(favorId: id.toInt(), orderNo: orderNo));
    }
  }

  String _statusLabel(String status) => switch (status) {
        'searching_courier' => 'Buscando repartidor',
        'accepted' => 'Repartidor asignado',
        'on_way_to_pickup' => 'En camino al recojo',
        'at_pickup' => 'En el punto de recojo',
        'on_way_to_delivery' => 'En camino a la entrega',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
        _ => status.replaceAll('_', ' '),
      };

  Color _statusColor(String status) {
    if (status == 'delivered') return const Color(0xFF10B981);
    if (status == 'cancelled') return const Color(0xFFEF4444);
    if (status == 'searching_courier') return const Color(0xFFF59E0B);
    return const Color(0xFF0EA5E9);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF7F8FA),
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        foregroundColor: Colors.white,
        title: Text('Mis solicitudes de envío', style: boldLarge.copyWith(color: Colors.white)),
        centerTitle: true,
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: MyColor.primaryColor,
        foregroundColor: Colors.white,
        onPressed: () async {
          await Get.to(() => const SellerFavorCreateScreen());
          _load();
        },
        icon: const Icon(Icons.add),
        label: const Text('Nuevo envío'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: _favors.isEmpty
                  ? ListView(children: const [
                      SizedBox(height: 150),
                      Icon(Icons.local_shipping_outlined, size: 72, color: Colors.black26),
                      SizedBox(height: 16),
                      Center(child: Text('Aún no tienes solicitudes de envío')),
                    ])
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(16, 16, 16, 96),
                      itemCount: _favors.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 12),
                      itemBuilder: (_, index) {
                        final favor = _favors[index];
                        final status = favor['status']?.toString() ?? '';
                        final color = _statusColor(status);
                        final courier = favor['courier'] as Map?;
                        return InkWell(
                          onTap: () => _open(favor),
                          borderRadius: BorderRadius.circular(16),
                          child: Container(
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: const [BoxShadow(color: Color(0x10000000), blurRadius: 8, offset: Offset(0, 2))],
                            ),
                            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Row(children: [
                                Expanded(child: Text('Solicitud #${favor['order_no'] ?? favor['id']}', style: boldLarge.copyWith(fontSize: 16))),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                                  decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(20)),
                                  child: Text(_statusLabel(status), style: boldSmall.copyWith(color: color)),
                                ),
                              ]),
                              const SizedBox(height: 12),
                              _AddressRow(icon: Icons.trip_origin, text: favor['pickup_address']?.toString() ?? 'Punto de recojo'),
                              const SizedBox(height: 8),
                              _AddressRow(icon: Icons.location_on_outlined, text: favor['delivery_address']?.toString() ?? 'Destino'),
                              if (courier != null) ...[
                                const Divider(height: 24),
                                Text('Repartidor: ${courier['fullname'] ?? courier['name'] ?? 'Asignado'}', style: regularDefault),
                              ],
                            ]),
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}

class _AddressRow extends StatelessWidget {
  final IconData icon;
  final String text;
  const _AddressRow({required this.icon, required this.text});

  @override
  Widget build(BuildContext context) => Row(children: [
        Icon(icon, size: 18, color: MyColor.primaryColor),
        const SizedBox(width: 8),
        Expanded(child: Text(text, maxLines: 2, overflow: TextOverflow.ellipsis, style: regularSmall.copyWith(color: MyColor.bodyTextColor))),
      ]);
}
