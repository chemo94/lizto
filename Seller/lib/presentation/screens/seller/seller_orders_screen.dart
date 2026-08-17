import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/model/delivery/delivery_models.dart';
import 'package:lizto_store/presentation/screens/seller/seller_order_detail_screen.dart';

class SellerOrdersScreen extends StatefulWidget {
  const SellerOrdersScreen({super.key});

  @override
  State<SellerOrdersScreen> createState() => _SellerOrdersScreenState();
}

class _SellerOrdersScreenState extends State<SellerOrdersScreen> {
  String _statusFilter = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    await Get.find<SellerController>().loadOrders(_statusFilter);
  }

  @override
  Widget build(BuildContext context) => GetBuilder<SellerController>(
    builder: (c) {
      final orders = c.orders;
      return Scaffold(
        backgroundColor: MyColor.cardBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          title: Text('Pedidos', style: boldLarge.copyWith(color: MyColor.colorWhite)),
          centerTitle: true,
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(56),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(children: [
                  _chip('Todos', ''),
                  _chip('Pendientes', 'pending'),
                  _chip('Confirmados', 'confirmed'),
                  _chip('Preparando', 'preparing'),
                  _chip('En camino', 'on_way'),
                  _chip('Entregados', 'delivered'),
                  _chip('Cancelados', 'cancelled'),
                ]),
              ),
            ),
          ),
        ),
        body: c.isLoadingOrders
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : orders.isEmpty
                ? Center(child: Text('No hay pedidos', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
                : RefreshIndicator(
                    color: MyColor.primaryColor,
                    onRefresh: () => Get.find<SellerController>().loadOrders(_statusFilter),
                    child: ListView.separated(
                      padding: EdgeInsets.all(16),
                      itemCount: orders.length,
                      separatorBuilder: (_, __) => SizedBox(height: 12),
                      itemBuilder: (_, i) {
                        final o = orders[i];
                        return GestureDetector(
                          onTap: () => Get.to(() => SellerOrderDetailScreen(orderId: o.id ?? 0)),
                          child: Container(
                            padding: EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: MyColor.colorWhite,
                              borderRadius: BorderRadius.circular(14),
                              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8, offset: Offset(0, 2))],
                            ),
                            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                              Row(children: [
                                Expanded(child: Text(o.orderNo ?? '', style: boldDefault.copyWith(fontSize: 15))),
                                Container(
                                  padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(color: o.statusColor.withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
                                  child: Text(o.statusLabel, style: boldDefault.copyWith(color: o.statusColor, fontSize: 11)),
                                ),
                              ]),
                              SizedBox(height: 8),
                              Row(children: [
                                Icon(Icons.person_outline, size: 15, color: MyColor.bodyMutedTextColor),
                                SizedBox(width: 4),
                                Text(o.contactName ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                                Spacer(),
                                Text('S/ ${(o.total ?? 0).toStringAsFixed(2)}', style: boldDefault.copyWith(fontSize: 15, color: MyColor.primaryColor)),
                              ]),
                              SizedBox(height: 4),
                              Row(children: [
                                Icon(Icons.location_on_outlined, size: 15, color: MyColor.bodyMutedTextColor),
                                SizedBox(width: 4),
                                Expanded(child: Text(o.deliveryAddress ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))),
                              ]),
                            ]),
                          ),
                        );
                      },
                    ),
                  ),
      );
    },
  );

  Widget _chip(String label, String value) => Padding(
    padding: EdgeInsets.only(right: 8),
    child: ChoiceChip(
      label: Text(label, style: regularSmall.copyWith(fontSize: 12, color: _statusFilter == value ? Colors.white : MyColor.primaryTextColor)),
      selected: _statusFilter == value,
      selectedColor: MyColor.primaryColor,
      backgroundColor: MyColor.colorWhite,
      onSelected: (sel) {
        setState(() => _statusFilter = value);
        _load();
      },
    ),
  );
}
