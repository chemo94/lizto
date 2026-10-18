import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_icons.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/presentation/components/bottom-sheet/custom_bottom_sheet.dart';
import 'package:liztogo/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo/presentation/components/image/custom_svg_picture.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';
import 'package:liztogo/presentation/components/shimmer/create_ride_shimmer.dart';
import 'package:liztogo/presentation/screens/home/widgets/bottomsheet/ride_meassage_bottom_sheet_body.dart';
import 'package:liztogo/presentation/screens/home/widgets/home_select_payment_method.dart';
import 'package:liztogo/presentation/screens/home/widgets/passenger_bottom_sheet.dart';

class RideCreateForm extends StatelessWidget {
  const RideCreateForm({super.key});

  @override
  Widget build(BuildContext context) {
    return GetBuilder<HomeController>(
      builder: (controller) {
        if (controller.isLoading) return const CreateRideShimmer();

        final paymentName = controller.selectedPaymentMethod.id == '-1' ? MyStrings.paymentMethod.tr : (controller.selectedPaymentMethod.method?.name ?? MyStrings.paymentMethod).tr;
        final paymentLabel = paymentName.toLowerCase().contains('efectivo') ? 'Efectivo' : paymentName;
        final paymentImage = controller.selectedPaymentMethod.method?.image;
        final serviceName = controller.selectedService.name?.tr;
        final confirmLabel = controller.selectedService.id == '-99' || serviceName == null || serviceName.isEmpty ? 'Selecciona un servicio' : 'Confirmar $serviceName';

        return Row(
          children: [
            SizedBox(
              width: 48,
              child: Tooltip(
                message: 'Método de pago: $paymentLabel',
                child: InkWell(
                  borderRadius: BorderRadius.circular(14),
                  onTap: controller.isPriceLocked
                      ? null
                      : () => CustomBottomSheet(
                            child: const HomeSelectPaymentMethod(),
                          ).customBottomSheet(context),
                  child: Container(
                    height: 56,
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                    decoration: BoxDecoration(
                      color: MyColor.cardBgColor,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: MyColor.neutral200),
                    ),
                    alignment: Alignment.center,
                    child: controller.selectedPaymentMethod.id == '-1' || controller.selectedPaymentMethod.id == '-9'
                        ? const CustomSvgPicture(
                            image: MyIcons.money,
                            color: MyColor.primaryColor,
                            width: 24,
                            height: 20,
                          )
                        : MyImageWidget(
                            imageUrl: '${UrlContainer.domainUrl}/${controller.gatewayImagePath}/$paymentImage',
                            width: 30,
                            height: 22,
                            boxFit: BoxFit.contain,
                            radius: 2,
                          ),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 6),
            _CompactFooterAction(
              icon: Icons.person_outline_rounded,
              value: controller.passenger.toString(),
              tooltip: '${controller.passenger} ${MyStrings.person.tr}',
              onTap: controller.selectedService.id == '-99'
                  ? null
                  : () => CustomBottomSheet(
                        child: const PassengerBottomSheet(),
                      ).customBottomSheet(context),
            ),
            const SizedBox(width: 4),
            _CompactFooterAction(
              icon: Icons.edit_note_rounded,
              tooltip: 'Agregar nota',
              onTap: controller.selectedService.id == '-99'
                  ? null
                  : () => CustomBottomSheet(
                        child: const RideMassageBottomSheet(),
                      ).customBottomSheet(context),
            ),
            const SizedBox(width: 8),
            Expanded(
              flex: 2,
              child: RoundedButton(
                text: confirmLabel,
                child: FittedBox(
                  fit: BoxFit.scaleDown,
                  child: Text(
                    confirmLabel,
                    maxLines: 1,
                    style: boldDefault.copyWith(
                      fontSize: 16,
                      color: MyColor.colorWhite,
                      letterSpacing: 0,
                    ),
                  ),
                ),
                isLoading: controller.isSubmitLoading,
                cornerRadius: 16,
                height: 56,
                press: () {
                  if (controller.isValidForNewRide()) controller.createRide();
                },
                isDisabled: controller.selectedService.id == '-99',
              ),
            ),
          ],
        );
      },
    );
  }
}

class _CompactFooterAction extends StatelessWidget {
  final IconData icon;
  final String? value;
  final String tooltip;
  final VoidCallback? onTap;

  const _CompactFooterAction({
    required this.icon,
    this.value,
    required this.tooltip,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: tooltip,
      child: InkWell(
        borderRadius: BorderRadius.circular(13),
        onTap: onTap,
        child: Container(
          width: 38,
          height: 48,
          decoration: BoxDecoration(
            color: MyColor.cardBgColor,
            borderRadius: BorderRadius.circular(13),
            border: Border.all(color: MyColor.neutral100),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 18, color: MyColor.primaryColor),
              if (value != null)
                Text(
                  value!,
                  style: semiBoldSmall.copyWith(fontSize: 10, color: MyColor.primaryTextColor),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
