import 'package:flutter/material.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/data/model/global/app/app_service_model.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';
import 'package:get/get.dart';

class ServiceCard extends StatelessWidget {
  final AppService service;
  final HomeController controller;
  const ServiceCard({
    super.key,
    required this.service,
    required this.controller,
  });

  @override
  Widget build(BuildContext context) {
    final bool isSelected = service.id == controller.selectedService.id;
    final String price = service.recommendAmount ?? service.cityRecommendFare ?? '';
    final String currency = controller.homeRepo.apiClient.getCurrency(isSymbol: true);
    final String vehicleTypeLabel = service.vehicleType == 'moto' ? 'Moto' : 'Auto';
    final String detail = service.subTitle?.tr.trim() ?? '';

    return GestureDetector(
      onTap: () async {
        await controller.selectService(service, shouldLoadFare: false);
      },
      child: Container(
        width: 205,
        padding: const EdgeInsets.all(10),
        decoration: BoxDecoration(
          color: isSelected ? MyColor.primaryColor.withValues(alpha: 0.10) : MyColor.neutral50,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? MyColor.primaryColor : MyColor.neutral200,
            width: isSelected ? 1.8 : 1.0,
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: MyColor.primaryColor.withValues(alpha: 0.12),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  )
                ]
              : null,
        ),
        child: Row(
          children: [
            Container(
              width: 54,
              height: 54,
              padding: const EdgeInsets.all(5),
              decoration: BoxDecoration(color: isSelected ? MyColor.primaryColor.withValues(alpha: .1) : MyColor.neutral100, borderRadius: BorderRadius.circular(14)),
              child: MyImageWidget(
                imageUrl: '${UrlContainer.domainUrl}/${controller.serviceImagePath}/${service.image}',
                height: 44,
                width: 44,
                radius: 6,
                boxFit: BoxFit.contain,
              ),
            ),
            const SizedBox(width: 9),
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(service.name?.tr ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: semiBoldSmall.copyWith(fontSize: 12, color: isSelected ? MyColor.primaryColor : MyColor.primaryTextColor)),
                  const SizedBox(height: 3),
                  Text('$vehicleTypeLabel • ${detail.isNotEmpty ? detail : (service.allowOffer ? 'Puedes ofrecer tu precio' : 'Tarifa fija')}', maxLines: 1, overflow: TextOverflow.ellipsis, style: regularDefault.copyWith(fontSize: 10, color: MyColor.bodyTextColor)),
                  if (price.isNotEmpty) ...[
                    const SizedBox(height: 3),
                    Text(() {
                      final base = isSelected && service.allowOffer ? controller.mainAmount : (double.tryParse(price) ?? 0.0);
                      final isMercadoPago = controller.selectedPaymentMethod.name?.toLowerCase() == 'mercadopago' || controller.selectedPaymentMethod.name?.toLowerCase() == 'mercado pago';
                      final priceToShow = isMercadoPago && !(isSelected && service.allowOffer) ? base * 1.05 : base;
                      return "$currency${priceToShow.toStringAsFixed(2)}";
                    }(), style: boldDefault.copyWith(fontSize: 12, color: isSelected ? MyColor.primaryColor : MyColor.primaryTextColor)),
                  ],
                ],
              ),
            ),
            if (isSelected && service.allowOffer) ...[
              Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    constraints: const BoxConstraints.tightFor(width: 27, height: 24),
                    padding: EdgeInsets.zero,
                    onPressed: () => controller.adjustSelectedServiceOffer(1),
                    icon: const Icon(Icons.add_circle_outline, size: 18, color: MyColor.primaryColor),
                  ),
                  IconButton(
                    visualDensity: VisualDensity.compact,
                    constraints: const BoxConstraints.tightFor(width: 27, height: 24),
                    padding: EdgeInsets.zero,
                    onPressed: () => controller.adjustSelectedServiceOffer(-1),
                    icon: const Icon(Icons.remove_circle_outline, size: 18, color: MyColor.primaryColor),
                  ),
                ],
              ),
            ] else if (isSelected) ...[
              const Icon(Icons.check_circle, size: 18, color: MyColor.primaryColor),
            ],
          ],
        ),
      ),
    );
  }
}
