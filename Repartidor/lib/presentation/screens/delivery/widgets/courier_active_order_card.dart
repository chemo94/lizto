import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/courier_chat_screen.dart';

class CourierActiveOrderCard extends StatelessWidget {
  final CourierJobModel job;
  final VoidCallback onTapDetail;

  const CourierActiveOrderCard({
    super.key,
    required this.job,
    required this.onTapDetail,
  });

  int _getStepIndex(String? status) {
    switch (status) {
      case 'accepted':
      case 'assigned':
        return 0;
      case 'on_way_to_pickup':
      case 'at_pickup':
        return 1;
      case 'on_way_to_delivery':
        return 2;
      case 'delivered':
        return 3;
      default:
        return 0;
    }
  }

  Future<void> _openMap(double? lat, double? lng, String address) async {
    if (lat != null && lng != null) {
      final googleUrl = Uri.parse('https://www.google.com/maps/search/?api=1&query=$lat,$lng');
      if (await canLaunchUrl(googleUrl)) {
        await launchUrl(googleUrl, mode: LaunchMode.externalApplication);
        return;
      }
    }
    final searchUrl = Uri.parse('https://www.google.com/maps/search/?api=1&query=${Uri.encodeComponent(address)}');
    if (await canLaunchUrl(searchUrl)) {
      await launchUrl(searchUrl, mode: LaunchMode.externalApplication);
    }
  }

  Future<void> _makeCall(String? phone) async {
    if (phone != null && phone.isNotEmpty) {
      final phoneUri = Uri.parse('tel:$phone');
      if (await canLaunchUrl(phoneUri)) {
        await launchUrl(phoneUri);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final currentStep = _getStepIndex(job.status);
    final targetLat = currentStep < 2 ? job.pickupLat : job.deliveryLat;
    final targetLng = currentStep < 2 ? job.pickupLng : job.deliveryLng;
    final targetAddress = (currentStep < 2 ? job.pickupAddress : job.deliveryAddress) ?? '';

    return Container(
      margin: const EdgeInsets.symmetric(
        horizontal: Dimensions.space15,
        vertical: Dimensions.space8,
      ),
      padding: const EdgeInsets.all(Dimensions.space15),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.space15),
        border: Border.all(
          color: MyColor.primaryColor,
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: MyColor.primaryColor.withValues(alpha: 0.15),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Badge Active Order
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: Dimensions.space10,
                  vertical: Dimensions.space4,
                ),
                decoration: BoxDecoration(
                  color: MyColor.primaryColor,
                  borderRadius: BorderRadius.circular(Dimensions.space8),
                ),
                child: Text(
                  'PEDIDO EN CURSO #${job.orderNo ?? job.id ?? ''}',
                  style: boldDefault.copyWith(
                    color: MyColor.colorWhite,
                    fontSize: Dimensions.fontExtraSmall,
                  ),
                ),
              ),
              Text(
                'S/ ${(job.totalEarning ?? job.deliveryFee ?? 0.0).toStringAsFixed(2)}',
                style: boldOverLarge.copyWith(
                  color: isDark ? const Color(0xFF34D399) : MyColor.primaryColor,
                  fontSize: 18,
                ),
              ),
            ],
          ),
          const SizedBox(height: Dimensions.space12),

          // Stepper of 4 steps
          Row(
            children: List.generate(4, (index) {
              final isCompleted = index <= currentStep;
              return Expanded(
                child: Container(
                  height: 4,
                  margin: const EdgeInsets.symmetric(horizontal: 2),
                  decoration: BoxDecoration(
                    color: isCompleted ? MyColor.primaryColor : (isDark ? Colors.grey[700] : Colors.grey[300]),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              );
            }),
          ),
          const SizedBox(height: Dimensions.space6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                currentStep == 0
                    ? 'Aceptado'
                    : currentStep == 1
                        ? 'Ir a Recogida'
                        : currentStep == 2
                            ? 'Llevando al Cliente'
                            : 'Entregado',
                style: boldDefault.copyWith(
                  color: MyColor.primaryColor,
                  fontSize: Dimensions.fontSmall,
                ),
              ),
              Text(
                'Paso ${currentStep + 1} de 4',
                style: regularDefault.copyWith(
                  color: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor,
                  fontSize: Dimensions.fontExtraSmall,
                ),
              ),
            ],
          ),
          const SizedBox(height: Dimensions.space12),

          // Customer / Pickup info
          Row(
            children: [
              CircleAvatar(
                radius: 18,
                backgroundColor: MyColor.primaryColor.withValues(alpha: 0.15),
                child: Icon(
                  currentStep < 2 ? Icons.storefront_rounded : Icons.person_rounded,
                  color: MyColor.primaryColor,
                  size: 20,
                ),
              ),
              const SizedBox(width: Dimensions.space10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      currentStep < 2
                          ? (job.storeName ?? 'Local Comercial')
                          : (job.customerName ?? 'Cliente'),
                      style: boldDefault.copyWith(
                        color: isDark ? Colors.white : MyColor.primaryTextColor,
                        fontSize: Dimensions.fontSmall + 1,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    Text(
                      targetAddress,
                      style: regularDefault.copyWith(
                        color: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor,
                        fontSize: Dimensions.fontExtraSmall,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: Dimensions.space15),

          // Action Row: GPS Navigation, Call, Chat, Details
          Row(
            children: [
              // Open GPS Map
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _openMap(targetLat, targetLng, targetAddress),
                  icon: const Icon(Icons.navigation_rounded, size: 16, color: MyColor.primaryColor),
                  label: Text('GPS', style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: Dimensions.fontSmall)),
                  style: OutlinedButton.styleFrom(
                    side: const BorderSide(color: MyColor.primaryColor),
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
              const SizedBox(width: Dimensions.space8),
              // Call
              IconButton(
                onPressed: () => _makeCall(job.customerPhone),
                icon: const Icon(Icons.phone_rounded, color: Color(0xFF10B981)),
                style: IconButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981).withValues(alpha: 0.12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
              const SizedBox(width: Dimensions.space6),
              // Chat
              IconButton(
                onPressed: () {
                  if (job.id != null) {
                    Get.to(() => CourierChatScreen(
                          jobId: job.id!,
                          customerName: job.customerName ?? 'Cliente',
                        ));
                  }
                },
                icon: const Icon(Icons.chat_bubble_rounded, color: Color(0xFF3B82F6)),
                style: IconButton.styleFrom(
                  backgroundColor: const Color(0xFF3B82F6).withValues(alpha: 0.12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
              const SizedBox(width: Dimensions.space8),
              // Details Button
              ElevatedButton(
                onPressed: onTapDetail,
                style: ElevatedButton.styleFrom(
                  backgroundColor: MyColor.primaryColor,
                  elevation: 0,
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                child: Text('Gestionar', style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: Dimensions.fontSmall)),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
