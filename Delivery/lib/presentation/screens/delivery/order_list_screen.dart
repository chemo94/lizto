import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';
import 'package:lizto_delivery/presentation/components/customer_design.dart';
import 'package:lizto_delivery/presentation/screens/delivery/order_detail_screen.dart';

class OrderListScreen extends StatefulWidget {
  final bool embedded;
  const OrderListScreen({super.key, this.embedded = false});

  @override
  State<OrderListScreen> createState() => _OrderListScreenState();
}

class _OrderListScreenState extends State<OrderListScreen> {
  final _searchCtrl = TextEditingController();
  String _statusFilter = 'all';

  static const _filters = <(String, String)>[
    ('Todas', 'all'),
    ('Pendientes', 'pending'),
    ('En curso', 'confirmed'),
    ('Entregadas', 'delivered'),
    ('Canceladas', 'cancelled'),
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => Get.find<DeliveryController>().loadOrders());
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  List<dynamic> _visibleOrders(List<dynamic> orders) {
    final query = _searchCtrl.text.trim().toLowerCase();
    return orders.where((order) {
      final status = order.status?.toString() ?? '';
      final statusMatches = _statusFilter == 'all' || status == _statusFilter || (_statusFilter == 'confirmed' && ['confirmed', 'preparing', 'on_way'].contains(status));
      final text = '${order.orderNo ?? ''} ${order.store?.name ?? ''}'.toLowerCase();
      return statusMatches && (query.isEmpty || text.contains(query));
    }).toList();
  }

  @override
  Widget build(BuildContext context) => GetBuilder<DeliveryController>(builder: (controller) {
        final orders = _visibleOrders(controller.orders);
        final content = RefreshIndicator(
          color: CustomerDesign.primary,
          onRefresh: controller.loadOrders,
          child: CustomScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              SliverToBoxAdapter(child: _header()),
              SliverToBoxAdapter(child: _searchAndFilters()),
              if (controller.isLoading && controller.orders.isEmpty)
                const SliverFillRemaining(child: Center(child: CircularProgressIndicator()))
              else if (orders.isEmpty)
                const SliverFillRemaining(hasScrollBody: false, child: _EmptyOrders())
              else
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 18, 20, 120),
                  sliver: SliverList(delegate: SliverChildBuilderDelegate((_, index) => _OrderCard(order: orders[index]), childCount: orders.length)),
                ),
            ],
          ),
        );
        return widget.embedded ? ColoredBox(color: CustomerDesign.surface, child: SafeArea(top: true, child: content)) : Scaffold(backgroundColor: CustomerDesign.surface, body: SafeArea(child: content));
      });

  Widget _header() => const Padding(
        padding: EdgeInsets.fromLTRB(20, 18, 20, 18),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Actividad', style: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: -1, color: CustomerDesign.ink)),
          SizedBox(height: 4),
          Text('Consulta y sigue todos tus pedidos.', style: TextStyle(color: CustomerDesign.muted, fontSize: 15)),
        ]),
      );

  Widget _searchAndFilters() => Column(children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 20),
          child: TextField(
            controller: _searchCtrl,
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(
              hintText: 'Buscar pedido o tienda',
              prefixIcon: const Icon(Icons.search_rounded),
              suffixIcon: _searchCtrl.text.isEmpty
                  ? null
                  : IconButton(
                      icon: const Icon(Icons.close_rounded),
                      onPressed: () {
                        _searchCtrl.clear();
                        setState(() {});
                      }),
              filled: true,
              fillColor: Colors.white,
              contentPadding: const EdgeInsets.symmetric(vertical: 16),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: const BorderSide(color: CustomerDesign.border)),
              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: const BorderSide(color: CustomerDesign.border)),
              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: const BorderSide(color: CustomerDesign.primary, width: 1.5)),
            ),
          ),
        ),
        const SizedBox(height: 14),
        SizedBox(
            height: 38,
            child: ListView.separated(
                scrollDirection: Axis.horizontal,
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: _filters.length,
                separatorBuilder: (_, __) => const SizedBox(width: 8),
                itemBuilder: (_, index) {
                  final item = _filters[index];
                  final selected = item.$2 == _statusFilter;
                  return ChoiceChip(label: Text(item.$1), selected: selected, onSelected: (_) => setState(() => _statusFilter = item.$2), selectedColor: CustomerDesign.primary.withValues(alpha: .14), side: BorderSide(color: selected ? CustomerDesign.primary : CustomerDesign.border), labelStyle: TextStyle(color: selected ? CustomerDesign.primary : CustomerDesign.muted, fontWeight: FontWeight.w700));
                })),
      ]);
}

class _OrderCard extends StatelessWidget {
  final dynamic order;
  const _OrderCard({required this.order});
  @override
  Widget build(BuildContext context) {
    final statusColor = order.statusColor as Color? ?? CustomerDesign.primary;
    final createdAt = order.createdAt?.toString() ?? '';
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Semantics(
        button: true,
        label: 'Ver pedido ${order.orderNo ?? ''}',
        child: InkWell(
          borderRadius: BorderRadius.circular(22),
          onTap: () => Get.to(() => OrderDetailScreen(orderId: order.id ?? 0)),
          child: Ink(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(22), border: Border.all(color: CustomerDesign.border)),
            child: Row(children: [
              Container(width: 48, height: 48, decoration: BoxDecoration(color: statusColor.withValues(alpha: .12), borderRadius: BorderRadius.circular(16)), child: Icon(order.statusIcon as IconData? ?? Icons.receipt_long_rounded, color: statusColor)),
              const SizedBox(width: 13),
              Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(order.store?.name?.toString().trim().isNotEmpty == true ? order.store.name : 'Pedido ${order.orderNo ?? ''}', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: CustomerDesign.ink, fontSize: 16, fontWeight: FontWeight.w800)),
                const SizedBox(height: 4),
                Text(order.orderNo ?? '', style: const TextStyle(color: CustomerDesign.muted, fontSize: 13)),
                if (createdAt.isNotEmpty) ...[const SizedBox(height: 4), Text(createdAt, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: CustomerDesign.muted, fontSize: 12))],
              ])),
              const SizedBox(width: 8),
              Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                Container(padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5), decoration: BoxDecoration(color: statusColor.withValues(alpha: .12), borderRadius: BorderRadius.circular(20)), child: Text(order.statusLabel ?? '', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: statusColor))),
                const SizedBox(height: 8),
                Text('S/ ${(order.total ?? 0).toStringAsFixed(2)}', style: const TextStyle(color: CustomerDesign.ink, fontWeight: FontWeight.w800)),
              ]),
            ]),
          ),
        ),
      ),
    );
  }
}

class _EmptyOrders extends StatelessWidget {
  const _EmptyOrders();
  @override
  Widget build(BuildContext context) => const Center(
      child: Padding(
          padding: EdgeInsets.all(36),
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            CircleAvatar(radius: 34, backgroundColor: Color(0x18159B12), child: Icon(Icons.receipt_long_outlined, size: 34, color: CustomerDesign.primary)),
            SizedBox(height: 16),
            Text('Aún no hay pedidos aquí', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18, color: CustomerDesign.ink)),
            SizedBox(height: 6),
            Text('Cuando realices un pedido podrás seguirlo desde esta pantalla.', textAlign: TextAlign.center, style: TextStyle(color: CustomerDesign.muted, height: 1.35)),
          ])));
}
