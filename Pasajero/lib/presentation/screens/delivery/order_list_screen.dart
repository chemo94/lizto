import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/presentation/components/animated_screen_entrance.dart';
import 'package:liztogo/presentation/screens/delivery/order_detail_screen.dart';
import 'package:liztogo/presentation/components/customer_design.dart';

class OrderListScreen extends StatefulWidget {
  const OrderListScreen({super.key});

  @override
  State<OrderListScreen> createState() => _OrderListScreenState();
}

class _OrderListScreenState extends State<OrderListScreen> {
  final _searchCtrl = TextEditingController();
  String _statusFilter = 'all';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<DeliveryController>().loadOrders();
    });
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  List<dynamic> _filterOrders(List<dynamic> orders) {
    return orders.where((o) {
      bool matchesStatus = _statusFilter == 'all' || o.status == _statusFilter;
      bool matchesSearch = _searchCtrl.text.isEmpty || (o.orderNo ?? '').toLowerCase().contains(_searchCtrl.text.toLowerCase()) || (o.store?.name ?? '').toLowerCase().contains(_searchCtrl.text.toLowerCase());
      return matchesStatus && matchesSearch;
    }).toList();
  }

  Widget _filterChip(String label, String value) {
    bool selected = _statusFilter == value;
    return GestureDetector(
      onTap: () => setState(() => _statusFilter = value),
      child: Container(
        margin: EdgeInsets.only(right: Dimensions.space8),
        padding: EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space6),
        decoration: BoxDecoration(
          color: selected ? CustomerDesign.primary.withValues(alpha: .14) : MyColor.colorWhite,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: selected ? CustomerDesign.primary : CustomerDesign.border),
        ),
        child: Text(label,
            style: regularSmall.copyWith(
              color: selected ? CustomerDesign.primary : CustomerDesign.muted,
              fontWeight: selected ? FontWeight.w600 : FontWeight.normal,
            )),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<DeliveryController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: CustomerDesign.surface,
          appBar: AppBar(
            backgroundColor: CustomerDesign.surface,
            title: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Actividad', style: TextStyle(color: CustomerDesign.ink, fontSize: 26, fontWeight: FontWeight.w800)),
              Text('Tus pedidos de Delivery', style: TextStyle(color: CustomerDesign.muted, fontSize: 13, fontWeight: FontWeight.w400)),
            ]),
            toolbarHeight: 78,
          ),
          body: c.isLoading
              ? const Center(child: CircularProgressIndicator())
              : c.orders.isEmpty
                  ? Center(
                      child: Text('No tienes pedidos aún', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                    )
                  : Column(
                      children: [
                        Padding(
                          padding: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space12, Dimensions.space16, 0),
                          child: TextField(
                            controller: _searchCtrl,
                            onChanged: (_) => setState(() {}),
                            decoration: InputDecoration(
                              hintText: 'Buscar por número o tienda...',
                              prefixIcon: Icon(Icons.search_rounded, color: MyColor.bodyMutedTextColor, size: 20),
                              filled: true,
                              fillColor: MyColor.colorWhite,
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: const BorderSide(color: CustomerDesign.border)),
                              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: const BorderSide(color: CustomerDesign.border)),
                              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(18), borderSide: const BorderSide(color: CustomerDesign.primary, width: 1.5)),
                              contentPadding: EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space8),
                              isDense: true,
                            ),
                          ),
                        ),
                        SizedBox(height: Dimensions.space8),
                        SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          padding: EdgeInsets.symmetric(horizontal: Dimensions.space16),
                          child: Row(children: [
                            _filterChip('Todas', 'all'),
                            _filterChip('Pendiente', 'pending'),
                            _filterChip('Confirmado', 'confirmed'),
                            _filterChip('Preparando', 'preparing'),
                            _filterChip('En camino', 'on_way'),
                            _filterChip('Entregado', 'delivered'),
                            _filterChip('Cancelado', 'cancelled'),
                          ]),
                        ),
                        Expanded(
                          child: RefreshIndicator(
                            onRefresh: () => c.loadOrders(),
                            child: ListView.builder(
                              padding: EdgeInsets.all(Dimensions.space16),
                              itemCount: _filterOrders(c.orders).length,
                              itemBuilder: (_, i) {
                                final order = _filterOrders(c.orders)[i];
                                String dateStr = '';
                                if (order.createdAt != null && order.createdAt!.length >= 10) {
                                  dateStr = order.createdAt!.substring(0, 10);
                                }
                                return GestureDetector(
                                  onTap: () => Get.to(() => OrderDetailScreen(orderId: order.id ?? 0)),
                                  child: Container(
                                    margin: EdgeInsets.only(bottom: Dimensions.space12),
                                    padding: EdgeInsets.all(Dimensions.space12),
                                    decoration: BoxDecoration(
                                      color: MyColor.colorWhite,
                                      borderRadius: BorderRadius.circular(22),
                                      border: Border.all(color: CustomerDesign.border),
                                    ),
                                    child: Row(
                                      children: [
                                        Container(
                                          padding: EdgeInsets.all(Dimensions.space10),
                                          decoration: BoxDecoration(
                                            color: order.statusColor.withValues(alpha: 0.1),
                                            borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                                          ),
                                          child: Icon(order.statusIcon, color: order.statusColor, size: 28),
                                        ),
                                        SizedBox(width: Dimensions.space12),
                                        Expanded(
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Text(order.orderNo ?? '', style: boldDefault),
                                              SizedBox(height: 2),
                                              Text(order.store?.name ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                                              SizedBox(height: 4),
                                              Text('S/ ${order.total?.toStringAsFixed(2) ?? "0.00"}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
                                            ],
                                          ),
                                        ),
                                        Column(
                                          crossAxisAlignment: CrossAxisAlignment.end,
                                          children: [
                                            Container(
                                              padding: EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: Dimensions.space4),
                                              decoration: BoxDecoration(
                                                color: order.statusColor.withValues(alpha: 0.1),
                                                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                                              ),
                                              child: Text(order.statusLabel, style: regularSmall.copyWith(color: order.statusColor, fontWeight: FontWeight.w600)),
                                            ),
                                            SizedBox(height: 4),
                                            Text(dateStr, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                                          ],
                                        ),
                                      ],
                                    ),
                                  ),
                                ).animatedStagger(index: i);
                              },
                            ),
                          ),
                        ),
                      ],
                    ),
        );
      },
    );
  }
}
