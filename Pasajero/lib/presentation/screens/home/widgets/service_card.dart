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

    return GestureDetector(
      onTap: () async {
        await controller.selectService(service, shouldLoadFare: true);
      },
      child: Container(
        width: 104,
        padding: const EdgeInsets.symmetric(vertical: 8),
        alignment: Alignment.center,
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
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            MyImageWidget(
              imageUrl: '${UrlContainer.domainUrl}/${controller.serviceImagePath}/${service.image}',
              height: 44,
              width: 44,
              radius: 6,
              boxFit: BoxFit.contain,
            ),
            const SizedBox(height: Dimensions.space6),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: Dimensions.space6),
              child: Text(
                service.name?.tr ?? '',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: semiBoldSmall.copyWith(
                  fontSize: 11,
                  color: isSelected ? MyColor.primaryColor : MyColor.primaryTextColor,
                ),
              ),
            ),
            if (price.isNotEmpty) ...[
              const SizedBox(height: 2),
              Text(
                () {
                  final base = double.tryParse(price) ?? 0.0;
                  final isMercadoPago = controller.selectedPaymentMethod.name?.toLowerCase() == 'mercadopago' || controller.selectedPaymentMethod.name?.toLowerCase() == 'mercado pago';
                  final finalPrice = isMercadoPago ? (base * 1.05) : base;
                  return "$currency${finalPrice.toStringAsFixed(2)}";
                }(),
                style: boldDefault.copyWith(
                  fontSize: 12,
                  color: isSelected ? MyColor.primaryColor : MyColor.primaryTextColor,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
