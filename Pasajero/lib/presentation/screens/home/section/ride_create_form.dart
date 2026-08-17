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
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';
import 'package:liztogo/presentation/screens/home/widgets/bottomsheet/ride_meassage_bottom_sheet_body.dart';
import 'package:liztogo/presentation/screens/home/widgets/home_offer_rate_widget.dart';
import 'package:liztogo/presentation/screens/home/widgets/home_select_payment_method.dart';
import 'package:liztogo/presentation/screens/home/widgets/passenger_bottom_sheet.dart';

class RideCreateForm extends StatelessWidget {
  const RideCreateForm({super.key});

  @override
  Widget build(BuildContext context) {
    return GetBuilder<HomeController>(
      builder: (controller) {
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            if (controller.isLoading) ...[
              const CreateRideShimmer(),
            ] else ...[
              Row(
                children: [
                  Expanded(
                    child: InkWell(
                      onTap: controller.isPriceLocked ? null : () {
                        CustomBottomSheet(
                          child: const HomeSelectPaymentMethod(),
                        ).customBottomSheet(context);
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                        decoration: BoxDecoration(
                          color: MyColor.neutral50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: MyColor.neutral200.withValues(alpha: 0.8)),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            if (controller.selectedPaymentMethod.id == '-1' || controller.selectedPaymentMethod.id == '-9') ...[
                              CustomSvgPicture(
                                image: MyIcons.money,
                                color: MyColor.primaryColor,
                                width: 16,
                                height: 16,
                              ),
                            ] else ...[
                              MyImageWidget(
                                imageUrl: '${UrlContainer.domainUrl}/${controller.gatewayImagePath}/${controller.selectedPaymentMethod.method?.image}',
                                width: 16,
                                height: 16,
                                boxFit: BoxFit.fitWidth,
                                radius: 2,
                              ),
                            ],
                            const SizedBox(width: 6),
                            Flexible(
                              child: Text(
                                () {
                                  final rawName = (controller.selectedPaymentMethod.id == '-1' ? MyStrings.paymentMethod.tr : controller.selectedPaymentMethod.method?.name ?? MyStrings.paymentMethod).tr;
                                  if (rawName.toLowerCase().contains('efectivo')) {
                                    return 'Efectivo';
                                  }
                                  if (rawName.toLowerCase() == 'mercadopago' || rawName.toLowerCase() == 'mercado pago') {
                                    return 'Tarjeta de débito / crédito';
                                  }
                                  return rawName;
                                }(),
                                style: regularDefault.copyWith(fontSize: 12, color: MyColor.getHeadingTextColor(), fontWeight: FontWeight.w500),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: InkWell(
                      onTap: controller.selectedService.id == '-99' ? null : () {
                        if (controller.isPriceLocked == false) {
                          controller.updateMainAmount(controller.mainAmount);
                          CustomBottomSheet(child: const HomeOfferRateWidget()).customBottomSheet(context);
                        } else {
                          CustomSnackBar.error(errorList: [MyStrings.pleaseSelectAService]);
                        }
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                        decoration: BoxDecoration(
                          color: MyColor.neutral50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: MyColor.neutral200.withValues(alpha: 0.8)),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.monetization_on_outlined, size: 16, color: MyColor.primaryColor),
                            const SizedBox(width: 6),
                            Flexible(
                              child: Text(
                                controller.mainAmount == 0
                                    ? (controller.selectedService.id != '-99'
                                        ? (() {
                                            final base = double.tryParse(controller.selectedService.recommendAmount ?? controller.selectedService.cityRecommendFare ?? '0') ?? 0.0;
                                            final isMercadoPago = controller.selectedPaymentMethod.name?.toLowerCase() == 'mercadopago' || controller.selectedPaymentMethod.name?.toLowerCase() == 'mercado pago';
                                            final finalPrice = isMercadoPago ? (base * 1.05) : base;
                                            return '${controller.homeRepo.apiClient.getCurrency(isSymbol: true)}${finalPrice.toStringAsFixed(2)}';
                                          }())
                                        : MyStrings.offerYourRate.tr)
                                    : '${controller.mainAmount.toStringAsFixed(2)} ${controller.defaultCurrency}',
                                style: regularDefault.copyWith(fontSize: 12, color: MyColor.getHeadingTextColor(), fontWeight: FontWeight.w500),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: InkWell(
                      onTap: controller.selectedService.id == '-99' ? null : () {
                        CustomBottomSheet(
                          child: const PassengerBottomSheet(),
                        ).customBottomSheet(context);
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
                        decoration: BoxDecoration(
                          color: MyColor.neutral50,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: MyColor.neutral200.withValues(alpha: 0.8)),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            CustomSvgPicture(
                              image: MyIcons.user,
                              color: MyColor.primaryColor,
                              height: 14,
                              width: 14,
                            ),
                            const SizedBox(width: 6),
                            Flexible(
                              child: Text(
                                "${controller.passenger.toString()} ${MyStrings.person.tr}",
                                style: regularDefault.copyWith(fontSize: 12, color: MyColor.getHeadingTextColor(), fontWeight: FontWeight.w500),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(
                    child: RoundedButton(
                      text: MyStrings.findDriver.tr,
                      isLoading: controller.isSubmitLoading,
                      cornerRadius: 16,
                      height: 52,
                      press: () {
                        if (controller.isValidForNewRide()) {
                          controller.createRide();
                        }
                      },
                      isOutlined: false,
                    ),
                  ),
                  const SizedBox(width: 10),
                  InkWell(
                    onTap: () {
                      if (controller.selectedService.id != '-99') {
                        CustomBottomSheet(
                          child: const RideMassageBottomSheet(),
                        ).customBottomSheet(context);
                      }
                    },
                    borderRadius: BorderRadius.circular(16),
                    child: Container(
                      height: 52,
                      width: 52,
                      alignment: Alignment.center,
                      decoration: BoxDecoration(
                        color: MyColor.primaryColor.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: MyColor.primaryColor.withValues(alpha: 0.2)),
                      ),
                      child: CustomSvgPicture(
                        image: MyIcons.note,
                        color: MyColor.primaryColor,
                        height: 22,
                        width: 22,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
            ],
          ],
        );
      },
    );
  }
}
