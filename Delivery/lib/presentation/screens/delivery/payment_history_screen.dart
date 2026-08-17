import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';

class DeliveryPaymentHistoryScreen extends StatefulWidget {
  const DeliveryPaymentHistoryScreen({super.key});

  @override
  State<DeliveryPaymentHistoryScreen> createState() => _DeliveryPaymentHistoryScreenState();
}

class _DeliveryPaymentHistoryScreenState extends State<DeliveryPaymentHistoryScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<DeliveryController>();
      c.loadDeliveryPayments();
      c.loadRefunds();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<DeliveryController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Pagos y Reembolsos', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            bottom: TabBar(
              controller: _tabController,
              indicatorColor: MyColor.colorWhite,
              labelColor: MyColor.colorWhite,
              unselectedLabelColor: MyColor.colorWhite.withValues(alpha: 0.6),
              tabs: const [
                Tab(text: 'Pagos'),
                Tab(text: 'Reembolsos'),
              ],
            ),
          ),
          body: TabBarView(
            controller: _tabController,
            children: [
              _buildPaymentsTab(c),
              _buildRefundsTab(c),
            ],
          ),
        );
      },
    );
  }

  Widget _buildPaymentsTab(DeliveryController c) {
    if (c.loadingPayments) return const Center(child: CircularProgressIndicator());
    if (c.deliveryPayments.isEmpty) {
      return Center(child: Text('Sin pagos registrados', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)));
    }
    return ListView.builder(
      padding: EdgeInsets.all(Dimensions.space16),
      itemCount: c.deliveryPayments.length,
      itemBuilder: (_, i) {
        final p = c.deliveryPayments[i];
        return Container(
          margin: EdgeInsets.only(bottom: Dimensions.space10),
          padding: EdgeInsets.all(Dimensions.space12),
          decoration: BoxDecoration(
            color: MyColor.colorWhite,
            borderRadius: BorderRadius.circular(Dimensions.largeRadius),
            boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
          ),
          child: Row(
            children: [
              Container(
                padding: EdgeInsets.all(Dimensions.space8),
                decoration: BoxDecoration(
                  color: p.statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                ),
                child: Icon(Icons.payment_rounded, color: p.statusColor, size: 24),
              ),
              SizedBox(width: Dimensions.space12),
              Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(p.orderNo ?? '', style: boldDefault),
                  Text(p.gatewayName ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                  if (p.transactionId != null)
                    Text('ID: ${p.transactionId}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: Dimensions.fontSmall)),
                ]),
              ),
              Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                Text('S/ ${p.amount?.toStringAsFixed(2) ?? "0.00"}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
                SizedBox(height: 4),
                Container(
                  padding: EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: 2),
                  decoration: BoxDecoration(
                    color: p.statusColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(p.statusLabel, style: regularSmall.copyWith(color: p.statusColor, fontSize: 10, fontWeight: FontWeight.w600)),
                ),
              ]),
            ],
          ),
        );
      },
    );
  }

  Widget _buildRefundsTab(DeliveryController c) {
    if (c.loadingRefunds) return const Center(child: CircularProgressIndicator());
    if (c.refunds.isEmpty) {
      return Center(child: Text('Sin reembolsos solicitados', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)));
    }
    return ListView.builder(
      padding: EdgeInsets.all(Dimensions.space16),
      itemCount: c.refunds.length,
      itemBuilder: (_, i) {
        final r = c.refunds[i];
        return Container(
          margin: EdgeInsets.only(bottom: Dimensions.space10),
          padding: EdgeInsets.all(Dimensions.space12),
          decoration: BoxDecoration(
            color: MyColor.colorWhite,
            borderRadius: BorderRadius.circular(Dimensions.largeRadius),
            boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
          ),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [
              Icon(Icons.replay_rounded, color: r.statusColor, size: 20),
              SizedBox(width: Dimensions.space8),
              Expanded(
                child: Text(r.orderNo ?? '', style: boldDefault),
              ),
              Container(
                padding: EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: 2),
                decoration: BoxDecoration(
                  color: r.statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(r.statusLabel, style: regularSmall.copyWith(color: r.statusColor, fontSize: 10, fontWeight: FontWeight.w600)),
              ),
            ]),
            SizedBox(height: Dimensions.space6),
            Text('Motivo: ${r.reason ?? ""}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
            SizedBox(height: Dimensions.space4),
            Text('Monto: S/ ${r.amount?.toStringAsFixed(2) ?? "0.00"}',
                style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: Dimensions.fontDefault)),
            if (r.adminRemark != null && r.adminRemark!.isNotEmpty) ...[
              SizedBox(height: Dimensions.space6),
              Container(
                padding: EdgeInsets.all(Dimensions.space8),
                decoration: BoxDecoration(
                  color: r.statusColor.withValues(alpha: 0.05),
                  borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                ),
                child: Row(children: [
                  Icon(Icons.info_outline, size: 14, color: r.statusColor),
                  SizedBox(width: 6),
                  Expanded(child: Text(r.adminRemark!, style: regularSmall.copyWith(color: r.statusColor))),
                ]),
              ),
            ],
          ]),
        );
      },
    );
  }
}