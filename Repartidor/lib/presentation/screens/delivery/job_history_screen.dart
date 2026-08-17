import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';

class JobHistoryScreen extends StatefulWidget {
  const JobHistoryScreen({super.key});

  @override
  State<JobHistoryScreen> createState() => _JobHistoryScreenState();
}

class _JobHistoryScreenState extends State<JobHistoryScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<CourierController>().loadJobHistory();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Historial de Repartos', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white),
          onPressed: () => Get.back(),
        ),
      ),
      body: GetBuilder<CourierController>(
        builder: (c) {
          if (c.isLoading) {
            return const Center(child: CircularProgressIndicator());
          }

          if (c.jobHistory.isEmpty) {
            return RefreshIndicator(
              onRefresh: () => c.loadJobHistory(),
              child: ListView(
                children: [
                  SizedBox(height: MediaQuery.of(context).size.height * 0.28),
                  Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 100,
                          height: 100,
                          decoration: BoxDecoration(
                            color: MyColor.primaryColor.withValues(alpha: 0.08),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            Icons.history_rounded,
                            size: 48,
                            color: MyColor.primaryColor.withValues(alpha: 0.4),
                          ),
                        ),
                        SizedBox(height: Dimensions.space16),
                        Text('Sin repartos anteriores', style: boldDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                        SizedBox(height: Dimensions.space4),
                        Text(
                          'Aquí verás tus entregas completadas\ny canceladas',
                          style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                          textAlign: TextAlign.center,
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => c.loadJobHistory(),
            child: ListView.builder(
              padding: const EdgeInsets.all(Dimensions.space12),
              itemCount: c.jobHistory.length,
              itemBuilder: (_, i) => _buildHistoryCard(c.jobHistory[i]),
            ),
          );
        },
      ),
    );
  }

  Widget _buildHistoryCard(CourierJobModel job) {
    final bool isDelivered = job.status == 'delivered';
    final Color statusColor = job.statusColor;

    return Container(
      margin: const EdgeInsets.only(bottom: Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header strip
          Container(
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space10),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.08),
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(Dimensions.largeRadius),
                topRight: Radius.circular(Dimensions.largeRadius),
              ),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(color: statusColor.withValues(alpha: 0.15), shape: BoxShape.circle),
                  child: Icon(job.statusIcon, size: 14, color: statusColor),
                ),
                const SizedBox(width: Dimensions.space8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(job.orderNo ?? '-', style: boldDefault.copyWith(fontSize: Dimensions.fontSmall)),
                      if (job.createdAt != null)
                        Text(
                          _formatDate(job.createdAt ?? ''),
                          style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10),
                        ),
                    ],
                  ),
                ),
                _typeBadge(job),
                const SizedBox(width: Dimensions.space6),
                _statusBadge(job),
              ],
            ),
          ),

          // Body
          Padding(
            padding: const EdgeInsets.all(Dimensions.space12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (job.storeName != null && job.storeName!.isNotEmpty) ...[
                  Row(children: [
                    const Icon(Icons.storefront_rounded, size: 14, color: MyColor.primaryColor),
                    const SizedBox(width: 4),
                    Expanded(child: Text(job.storeName ?? '', style: regularDefault.copyWith(fontWeight: FontWeight.w600))),
                  ]),
                  const SizedBox(height: Dimensions.space8),
                ],
                if (job.description != null && job.description!.isNotEmpty) ...[
                  Text(job.description ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 2, overflow: TextOverflow.ellipsis),
                  const SizedBox(height: Dimensions.space8),
                ],
                _locationRow(Icons.radio_button_checked_rounded, job.pickupAddress ?? 'Direccion de recogida', MyColor.primaryColor),
                Container(
                  margin: const EdgeInsets.only(left: 7),
                  width: 2,
                  height: 14,
                  color: MyColor.bodyMutedTextColor.withValues(alpha: 0.2),
                ),
                _locationRow(Icons.location_on_rounded, job.deliveryAddress ?? 'Direccion de entrega', MyColor.redCancelTextColor),
                const SizedBox(height: Dimensions.space12),

                // Footer stats
                Row(
                  children: [
                    _statChip(
                      icon: Icons.attach_money_rounded,
                      label: 'Ganancia',
                      value: 'S/ ${job.totalEarning?.toStringAsFixed(2) ?? "0.00"}',
                      color: const Color(0xFF10B981),
                    ),
                    const SizedBox(width: Dimensions.space8),
                    if (job.deliveryFee != null)
                      _statChip(
                        icon: Icons.motorcycle_rounded,
                        label: 'Delivery',
                        value: 'S/ ${job.deliveryFee!.toStringAsFixed(2)}',
                        color: MyColor.primaryColor,
                      ),
                    const Spacer(),
                    if (job.customerRating != null && isDelivered)
                      Row(
                        children: [
                          const Icon(Icons.star_rounded, size: 16, color: Color(0xFFF59E0B)),
                          const SizedBox(width: 2),
                          Text(
                            (job.customerRating ?? 0).toStringAsFixed(1),
                            style: boldDefault.copyWith(fontSize: Dimensions.fontSmall, color: const Color(0xFFF59E0B)),
                          ),
                        ],
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _locationRow(IconData icon, String address, Color color) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: color),
        const SizedBox(width: 6),
        Expanded(
          child: Text(address, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 2, overflow: TextOverflow.ellipsis),
        ),
      ],
    );
  }

  Widget _typeBadge(CourierJobModel job) {
    final isFavor = job.isFavor;
    final color = isFavor ? const Color(0xFFF59E0B) : MyColor.primaryColor;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
      child: Text(
        isFavor ? 'Favor' : 'Delivery',
        style: regularSmall.copyWith(color: color, fontWeight: FontWeight.w700, fontSize: 10),
      ),
    );
  }

  Widget _statusBadge(CourierJobModel job) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(color: job.statusColor.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
      child: Text(
        job.statusLabel,
        style: regularSmall.copyWith(color: job.statusColor, fontWeight: FontWeight.w700, fontSize: 10),
      ),
    );
  }

  Widget _statChip({required IconData icon, required String label, required String value, required Color color}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.08), borderRadius: BorderRadius.circular(10)),
      child: Row(
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 3),
          Text(value, style: boldDefault.copyWith(fontSize: 11, color: color)),
        ],
      ),
    );
  }

  String _formatDate(String raw) {
    try {
      final dt = DateTime.tryParse(raw);
      if (dt == null) return raw;
      final local = dt.toLocal();
      final months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
      return '${local.day} ${months[local.month - 1]} ${local.year}  ${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return raw;
    }
  }
}
