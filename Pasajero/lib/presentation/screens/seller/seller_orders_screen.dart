import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/seller/seller_controller.dart';
import 'package:liztogo/data/model/delivery/delivery_models.dart';

class SellerOrdersScreen extends StatefulWidget {
  const SellerOrdersScreen({super.key});

  @override
  State<SellerOrdersScreen> createState() => _SellerOrdersScreenState();
}

class _SellerOrdersScreenState extends State<SellerOrdersScreen> {
  String _statusFilter = '';
  List<DeliveryOrderModel> _orders = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadOrders();
  }

  Future<void> _loadOrders() async {
    setState(() => _loading = true);
    try {
      final c = Get.find<SellerController>();
      final response = await c.sellerRepo.getOrders(_statusFilter);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          var raw = json['data']['orders'];
          if (raw is Map && raw.containsKey('data')) {
            _orders = (raw['data'] as List).map((x) => DeliveryOrderModel.fromJson(x)).toList();
          } else if (raw is List) {
            _orders = raw.map((x) => DeliveryOrderModel.fromJson(x)).toList();
          }
        }
      }
    } catch (e) {
      // ignore
    }
    if (mounted) setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Pedidos', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
        actions: [
          PopupMenuButton<String>(
            icon: Icon(Icons.filter_list_rounded, color: MyColor.colorWhite),
            onSelected: (s) {
              _statusFilter = s;
              _loadOrders();
            },
            itemBuilder: (_) => [
              PopupMenuItem(value: '', child: Text('Todos', style: regularDefault)),
              PopupMenuItem(value: 'pending', child: Text('Pendientes', style: regularDefault)),
              PopupMenuItem(value: 'confirmed', child: Text('Confirmados', style: regularDefault)),
              PopupMenuItem(value: 'preparing', child: Text('Preparando', style: regularDefault)),
              PopupMenuItem(value: 'ready', child: Text('Listos', style: regularDefault)),
            ],
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _orders.isEmpty
              ? Center(child: Text('Sin pedidos', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
              : RefreshIndicator(
                  onRefresh: _loadOrders,
                  child: ListView.builder(
                    padding: EdgeInsets.all(Dimensions.space16),
                    itemCount: _orders.length,
                    itemBuilder: (_, i) => _buildOrderCard(_orders[i]),
                  ),
                ),
    );
  }

  Widget _buildOrderCard(DeliveryOrderModel order) {
    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space12),
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(order.orderNo ?? '', style: boldDefault),
              Container(
                padding: EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: Dimensions.space4),
                decoration: BoxDecoration(
                  color: order.statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                ),
                child: Text(order.statusLabel, style: regularSmall.copyWith(color: order.statusColor, fontWeight: FontWeight.w600)),
              ),
            ],
          ),
          SizedBox(height: Dimensions.space4),
          Text('${order.contactName ?? ''} - ${order.contactPhone ?? ''}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
          SizedBox(height: Dimensions.space4),
          Text(
            'S/ ${order.total?.toStringAsFixed(2) ?? "0.00"}',
            style: boldDefault.copyWith(color: MyColor.primaryColor),
          ),
          if (order.items != null && order.items!.isNotEmpty) ...[
            SizedBox(height: Dimensions.space8),
            ...order.items!.map((item) => Text(
                  '${item.productName ?? ""} x${item.quantity ?? 0}',
                  style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                )),
          ],
          SizedBox(height: Dimensions.space12),
          if (order.status == 'pending')
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => _updateStatus(order.id!, 'cancelled'),
                    style: OutlinedButton.styleFrom(
                      side: BorderSide(color: MyColor.redCancelTextColor),
                      padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                    child: Text('Rechazar', style: regularSmall.copyWith(color: MyColor.redCancelTextColor)),
                  ),
                ),
                SizedBox(width: Dimensions.space8),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => _updateStatus(order.id!, 'confirmed'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: MyColor.primaryColor,
                      padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                    child: Text('Aceptar', style: regularSmall.copyWith(color: MyColor.colorWhite)),
                  ),
                ),
              ],
            ),
          if (order.status == 'confirmed')
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => _updateStatus(order.id!, 'preparing'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF8B5CF6),
                      padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                    child: Text('Iniciar preparación', style: regularSmall.copyWith(color: MyColor.colorWhite)),
                  ),
                ),
              ],
            ),
          if (order.status == 'preparing')
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => _updateStatus(order.id!, 'ready'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF10B981),
                      padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                    child: Text('Marcar como listo', style: regularSmall.copyWith(color: MyColor.colorWhite)),
                  ),
                ),
              ],
            ),
        ],
      ),
    );
  }

  void _updateStatus(int orderId, String status) async {
    try {
      final c = Get.find<SellerController>();
      await c.sellerRepo.updateOrderStatus(orderId, status);
      _loadOrders();
    } catch (e) {
      Get.snackbar('Error', 'No se pudo actualizar el estado',
          backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
    }
  }
}
