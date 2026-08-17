import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/model/delivery/delivery_models.dart';
import 'package:lizto_store/presentation/components/image/my_network_image_widget.dart';

class SellerOrderDetailScreen extends StatefulWidget {
  final int orderId;
  const SellerOrderDetailScreen({super.key, required this.orderId});

  @override
  State<SellerOrderDetailScreen> createState() => _SellerOrderDetailScreenState();
}

class _SellerOrderDetailScreenState extends State<SellerOrderDetailScreen> {
  late SellerController _c;

  @override
  void initState() {
    super.initState();
    _c = Get.find<SellerController>();
  }

  DeliveryOrderModel? get _order => _c.orders.where((o) => o.id == widget.orderId).firstOrNull;

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerController>(
      builder: (c) {
        final order = _order;
        if (order == null) {
          return Scaffold(
            backgroundColor: MyColor.cardBgColor,
            appBar: AppBar(backgroundColor: MyColor.primaryColor, title: Text('Pedido', style: boldLarge.copyWith(color: MyColor.colorWhite)), centerTitle: true),
            body: Center(child: Text('Pedido no encontrado', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))),
          );
        }

        return Scaffold(
          backgroundColor: MyColor.screenBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text(order.orderNo ?? 'Pedido', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            leading: IconButton(
              icon: Icon(Icons.arrow_back_rounded, color: MyColor.colorWhite),
              onPressed: () => Get.back(),
            ),
          ),
          body: ListView(
            padding: EdgeInsets.all(Dimensions.space16),
            children: [
              _buildStatusHeader(order),
              SizedBox(height: Dimensions.space16),
              _buildTrackingTimeline(order),
              SizedBox(height: Dimensions.space16),
              _buildCustomerInfo(order),
              SizedBox(height: Dimensions.space16),
              _buildPaymentInfo(order),
              SizedBox(height: Dimensions.space16),
              _buildOrderItems(order),
              if (order.driver != null) ...[
                SizedBox(height: Dimensions.space16),
                _buildDriverInfo(order),
              ],
              if (_shouldShowActions(order)) ...[
                SizedBox(height: Dimensions.space20),
                _buildActionButtons(c, order),
              ],
              SizedBox(height: Dimensions.space32),
            ],
          ),
        );
      },
    );
  }

  Widget _buildStatusHeader(DeliveryOrderModel order) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.all(Dimensions.space20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [order.statusColor, order.statusColor.withValues(alpha: 0.7)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
      ),
      child: Column(
        children: [
          Container(
            width: 60,
            height: 60,
            decoration: BoxDecoration(
              color: MyColor.colorWhite.withValues(alpha: 0.2),
              shape: BoxShape.circle,
            ),
            child: Icon(order.statusIcon, color: MyColor.colorWhite, size: 32),
          ),
          SizedBox(height: Dimensions.space12),
          Text(order.statusLabel, style: boldMediumLarge.copyWith(color: MyColor.colorWhite, fontSize: 20)),
          SizedBox(height: Dimensions.space4),
          Text('Pedido ${order.orderNo ?? ''}', style: regularDefault.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.85))),
          if (order.createdAt != null) ...[
            SizedBox(height: Dimensions.space4),
            Text(_formatDate(order.createdAt!), style: regularSmall.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.7))),
          ],
          SizedBox(height: Dimensions.space16),
          Text(
            'S/ ${order.total?.toStringAsFixed(2) ?? "0.00"}',
            style: TextStyle(fontSize: 32, fontWeight: FontWeight.bold, color: MyColor.colorWhite),
          ),
        ],
      ),
    );
  }

  Widget _buildTrackingTimeline(DeliveryOrderModel order) {
    final steps = [
      _TimelineStep(title: 'Pedido realizado', status: 'created', isActive: _isStepActive(order, 'created')),
      _TimelineStep(title: 'Confirmado', status: 'confirmed', isActive: _isStepActive(order, 'confirmed')),
      _TimelineStep(title: 'En preparación', status: 'preparing', isActive: _isStepActive(order, 'preparing')),
      _TimelineStep(title: 'Listo para recoger', status: 'ready', isActive: _isStepActive(order, 'ready')),
      _TimelineStep(title: 'En camino', status: 'on_way', isActive: _isStepActive(order, 'on_way')),
      _TimelineStep(title: 'Entregado', status: 'delivered', isActive: _isStepActive(order, 'delivered')),
    ];

    final currentIdx = steps.indexWhere((s) => s.isActive && s.status == order.status);

    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Estado del pedido', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
          SizedBox(height: Dimensions.space16),
          ...List.generate(steps.length, (i) {
            final step = steps[i];
            final isCompleted = currentIdx >= i;
            final isCurrent = currentIdx == i;
            final isCancelled = order.status == 'cancelled';

            return IntrinsicHeight(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    width: 32,
                    child: Column(
                      children: [
                        Container(
                          width: 24,
                          height: 24,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: isCancelled
                                ? MyColor.redCancelTextColor.withValues(alpha: 0.15)
                                : isCompleted
                                    ? MyColor.primaryColor
                                    : MyColor.neutral200,
                          ),
                          child: Center(
                            child: isCancelled && i == steps.length - 1
                                ? Icon(Icons.cancel_rounded, size: 14, color: MyColor.redCancelTextColor)
                                : isCompleted
                                    ? Icon(Icons.check_rounded, size: 14, color: MyColor.colorWhite)
                                    : Container(
                                        width: 8,
                                        height: 8,
                                        decoration: BoxDecoration(
                                          shape: BoxShape.circle,
                                          color: MyColor.neutral300,
                                        ),
                                      ),
                          ),
                        ),
                        if (i < steps.length - 1)
                          Expanded(
                            child: Container(
                              width: 2,
                              color: isCancelled
                                  ? MyColor.redCancelTextColor.withValues(alpha: 0.2)
                                  : isCompleted
                                      ? MyColor.primaryColor.withValues(alpha: 0.3)
                                      : MyColor.neutral200,
                            ),
                          ),
                      ],
                    ),
                  ),
                  SizedBox(width: Dimensions.space12),
                  Padding(
                    padding: EdgeInsets.only(top: i == steps.length - 1 ? 0 : 2),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          step.title,
                          style: isCurrent
                              ? boldDefault.copyWith(color: MyColor.primaryColor)
                              : isCompleted
                                  ? boldDefault
                                  : regularDefault.copyWith(color: MyColor.neutral300),
                        ),
                        if (isCurrent) ...[
                          SizedBox(height: 4),
                          Text(
                            isCancelled ? 'Pedido cancelado' : 'Estado actual',
                            style: regularSmall.copyWith(
                              color: isCancelled ? MyColor.redCancelTextColor : MyColor.primaryColor,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                ],
              ),
            );
          }),
          if (order.status == 'cancelled' && order.cancelReason != null) ...[
            SizedBox(height: Dimensions.space12),
            Container(
              padding: EdgeInsets.all(Dimensions.space12),
              decoration: BoxDecoration(
                color: MyColor.redCancelTextColor.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                border: Border.all(color: MyColor.redCancelTextColor.withValues(alpha: 0.15)),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.info_outline_rounded, size: 18, color: MyColor.redCancelTextColor),
                  SizedBox(width: Dimensions.space8),
                  Expanded(
                    child: Text(
                      'Motivo: ${order.cancelReason}',
                      style: regularSmall.copyWith(color: MyColor.redCancelTextColor),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  bool _isStepActive(DeliveryOrderModel order, String stepStatus) {
    const orderMap = ['created', 'pending', 'confirmed', 'preparing', 'ready', 'on_way', 'delivered'];
    final currentIdx = orderMap.indexOf(order.status ?? '');
    final stepIdx = orderMap.indexOf(stepStatus);
    return stepIdx <= currentIdx && order.status != 'cancelled';
  }

  Widget _buildCustomerInfo(DeliveryOrderModel order) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.person_rounded, size: 20, color: MyColor.primaryColor),
              SizedBox(width: Dimensions.space8),
              Text('Cliente', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
            ],
          ),
          SizedBox(height: Dimensions.space12),
          _InfoRow(icon: Icons.person_outline_rounded, label: 'Nombre', value: order.contactName ?? '—'),
          SizedBox(height: Dimensions.space8),
          _InfoRow(icon: Icons.phone_outlined, label: 'Teléfono', value: order.contactPhone ?? '—'),
          if (order.deliveryAddress != null) ...[
            SizedBox(height: Dimensions.space8),
            _InfoRow(icon: Icons.location_on_outlined, label: 'Dirección', value: order.deliveryAddress!),
          ],
          if (order.notes != null && order.notes!.isNotEmpty) ...[
            SizedBox(height: Dimensions.space8),
            _InfoRow(icon: Icons.notes_rounded, label: 'Notas', value: order.notes!),
          ],
        ],
      ),
    );
  }

  Widget _buildPaymentInfo(DeliveryOrderModel order) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.payment_rounded, size: 20, color: MyColor.primaryColor),
              SizedBox(width: Dimensions.space8),
              Text('Pago', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
            ],
          ),
          SizedBox(height: Dimensions.space12),
          _InfoRow(icon: Icons.receipt_rounded, label: 'Subtotal', value: 'S/ ${order.subtotal?.toStringAsFixed(2) ?? "0.00"}'),
          if (order.deliveryFee != null && order.deliveryFee! > 0)
            _InfoRow(icon: Icons.local_shipping_outlined, label: 'Delivery', value: 'S/ ${order.deliveryFee!.toStringAsFixed(2)}'),
          if (order.discount != null && order.discount! > 0)
            _InfoRow(icon: Icons.discount_outlined, label: 'Descuento', value: '- S/ ${order.discount!.toStringAsFixed(2)}'),
          if (order.tip != null && order.tip! > 0)
            _InfoRow(icon: Icons.volunteer_activism_outlined, label: 'Propina', value: 'S/ ${order.tip!.toStringAsFixed(2)}'),
          Divider(height: Dimensions.space20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Total', style: boldMediumLarge),
              Text('S/ ${order.total?.toStringAsFixed(2) ?? "0.00"}', style: boldMediumLarge.copyWith(color: MyColor.primaryColor)),
            ],
          ),
          if (order.paymentMethodName != null) ...[
            SizedBox(height: Dimensions.space8),
            Row(
              children: [
                Icon(Icons.credit_card_rounded, size: 16, color: MyColor.bodyMutedTextColor),
                SizedBox(width: Dimensions.space6),
                Text(order.paymentMethodName!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                Spacer(),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: Dimensions.space3),
                  decoration: BoxDecoration(
                    color: (order.paymentStatus == 1 ? const Color(0xFF10B981) : Colors.orange).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                  ),
                  child: Text(
                    order.paymentStatus == 1 ? 'Pagado' : 'Pendiente',
                    style: TextStyle(
                      fontSize: Dimensions.fontExtraSmall,
                      fontWeight: FontWeight.w600,
                      color: order.paymentStatus == 1 ? const Color(0xFF10B981) : Colors.orange,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildOrderItems(DeliveryOrderModel order) {
    if (order.items == null || order.items!.isEmpty) return const SizedBox();

    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.shopping_bag_rounded, size: 20, color: MyColor.primaryColor),
              SizedBox(width: Dimensions.space8),
              Text('Productos (${order.items!.length})', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
            ],
          ),
          SizedBox(height: Dimensions.space12),
          ...order.items!.map((item) => _buildOrderItemRow(item)),
        ],
      ),
    );
  }

  Widget _buildOrderItemRow(DeliveryOrderItemModel item) {
    return Container(
      padding: EdgeInsets.symmetric(vertical: Dimensions.space10),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: MyColor.neutral100)),
      ),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
            child: MyImageWidget(
              imageUrl: item.productImage ?? '',
              height: 48,
              width: 48,
              boxFit: BoxFit.cover,
            ),
          ),
          SizedBox(width: Dimensions.space12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item.productName ?? '', style: boldDefault),
                if (item.variation != null)
                  Text(item.variation!.variationName ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                if (item.addons != null && item.addons!.isNotEmpty)
                  Text(
                    item.addons!.map((a) => a.addonName).join(', '),
                    style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                    overflow: TextOverflow.ellipsis,
                  ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text('x${item.quantity ?? 1}', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
              SizedBox(height: 2),
              Text('S/ ${item.totalPrice?.toStringAsFixed(2) ?? "0.00"}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDriverInfo(DeliveryOrderModel order) {
    final driver = order.driver;
    if (driver == null) return const SizedBox();

    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.delivery_dining_rounded, size: 20, color: MyColor.primaryColor),
              SizedBox(width: Dimensions.space8),
              Text('Repartidor', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
            ],
          ),
          SizedBox(height: Dimensions.space12),
          Row(
            children: [
              CircleAvatar(
                radius: 22,
                backgroundColor: MyColor.primaryColor.withValues(alpha: 0.1),
                child: Icon(Icons.person, color: MyColor.primaryColor),
              ),
              SizedBox(width: Dimensions.space12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(driver['name']?.toString() ?? 'Repartidor', style: boldDefault),
                    Text(driver['phone']?.toString() ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                  ],
                ),
              ),
              if (driver['phone'] != null)
                IconButton(
                  icon: Icon(Icons.phone_in_talk_rounded, color: MyColor.primaryColor),
                  onPressed: () {},
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildActionButtons(SellerController c, DeliveryOrderModel order) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Acciones', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
          SizedBox(height: Dimensions.space16),
          if (order.status == 'pending')
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _rejectOrder(c, order),
                    icon: Icon(Icons.close_rounded, color: MyColor.redCancelTextColor),
                    label: Text('Rechazar', style: regularDefault.copyWith(color: MyColor.redCancelTextColor)),
                    style: OutlinedButton.styleFrom(
                      side: BorderSide(color: MyColor.redCancelTextColor.withValues(alpha: 0.5)),
                      padding: EdgeInsets.symmetric(vertical: Dimensions.space14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                  ),
                ),
                SizedBox(width: Dimensions.space12),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () => _updateStatus(c, order, 'confirmed'),
                    icon: Icon(Icons.check_rounded, color: MyColor.colorWhite),
                    label: Text('Aceptar pedido', style: regularDefault.copyWith(color: MyColor.colorWhite)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: MyColor.primaryColor,
                      padding: EdgeInsets.symmetric(vertical: Dimensions.space14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                  ),
                ),
              ],
            ),
          if (order.status == 'confirmed')
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _updateStatus(c, order, 'preparing'),
                icon: Icon(Icons.restaurant_rounded, color: MyColor.colorWhite),
                label: Text('Iniciar preparación', style: regularDefault.copyWith(color: MyColor.colorWhite)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF8B5CF6),
                  padding: EdgeInsets.symmetric(vertical: Dimensions.space14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                ),
              ),
            ),
          if (order.status == 'preparing')
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _updateStatus(c, order, 'ready'),
                icon: Icon(Icons.inventory_2_rounded, color: MyColor.colorWhite),
                label: Text('Marcar como listo', style: regularDefault.copyWith(color: MyColor.colorWhite)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981),
                  padding: EdgeInsets.symmetric(vertical: Dimensions.space14),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                ),
              ),
            ),
        ],
      ),
    );
  }

  bool _shouldShowActions(DeliveryOrderModel order) {
    return order.status == 'pending' || order.status == 'confirmed' || order.status == 'preparing';
  }

  Future<void> _updateStatus(SellerController c, DeliveryOrderModel order, String newStatus) async {
    final labels = {
      'confirmed': 'aceptado',
      'preparing': 'en preparación',
      'ready': 'listo para recoger',
    };
    final success = await c.updateOrderStatus(order.id!, newStatus);
    if (success) {
      Get.snackbar('Estado actualizado', 'Pedido ${labels[newStatus] ?? newStatus}',
          backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite, snackPosition: SnackPosition.BOTTOM);
    } else {
      Get.snackbar('Error', 'No se pudo actualizar el estado',
          backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
    }
  }

  Future<void> _rejectOrder(SellerController c, DeliveryOrderModel order) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Rechazar pedido'),
        content: Text('¿Estás seguro de rechazar ${order.orderNo}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancelar')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('Rechazar', style: TextStyle(color: MyColor.redCancelTextColor)),
          ),
        ],
      ),
    );
    if (confirm != true) return;
    final success = await c.updateOrderStatus(order.id!, 'cancelled');
    if (success) {
      Get.snackbar('Pedido rechazado', 'El pedido ${order.orderNo} ha sido cancelado',
          backgroundColor: Colors.orange, colorText: MyColor.colorWhite, snackPosition: SnackPosition.BOTTOM);
    }
  }

  String _formatDate(String date) {
    try {
      final dt = DateTime.parse(date);
      final months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
      return '${dt.day} ${months[dt.month - 1]} ${dt.year} - ${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return date;
    }
  }
}

class _TimelineStep {
  final String title;
  final String status;
  final bool isActive;
  const _TimelineStep({required this.title, required this.status, required this.isActive});
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  const _InfoRow({required this.icon, required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(width: 20, child: Icon(icon, size: 16, color: MyColor.bodyMutedTextColor)),
        SizedBox(width: Dimensions.space8),
        SizedBox(
          width: 80,
          child: Text('$label:', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
        ),
        Expanded(
          child: Text(value, style: regularDefault),
        ),
      ],
    );
  }
}
