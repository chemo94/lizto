import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/util.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/presentation/components/bottom-sheet/my_bottom_sheet_bar.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';

import '../../../../core/utils/dimensions.dart';
import '../../../../core/utils/my_color.dart';
import '../../../../core/utils/my_strings.dart';
import '../../../../core/utils/style.dart';
import '../../../components/buttons/rounded_button.dart';
import '../../../components/divider/custom_spacer.dart';
import '../../../components/text/header_text.dart';

class HomeOfferRateWidget extends StatefulWidget {
  const HomeOfferRateWidget({super.key});

  @override
  State<HomeOfferRateWidget> createState() => _HomeOfferRateWidgetState();
}

class _HomeOfferRateWidgetState extends State<HomeOfferRateWidget> {
  TextEditingController textEditingController = TextEditingController();
  @override
  void initState() {
    super.initState();
    final controller = Get.find<HomeController>();
    // Synchronize the text controller with the current mainAmount when opened
    if (controller.mainAmount > 0) {
      controller.amountController.text = StringConverter.formatNumber(controller.mainAmount.toString());
    } else if (controller.selectedService.recommendAmount != null && controller.selectedService.recommendAmount != '0') {
      controller.amountController.text = StringConverter.formatNumber(controller.selectedService.recommendAmount.toString());
    }
    controller.amountController.addListener(() {
      if (mounted) {
        setState(() {});
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<HomeController>(
      builder: (controller) {
        return Container(
          color: MyColor.colorWhite,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.start,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const MyBottomSheetBar(),
              HeaderText(
                text: MyStrings.offerYourRate,
                style: boldLarge.copyWith(
                  color: MyColor.getRideTitleColor(),
                  fontWeight: FontWeight.bold,
                  fontSize: Dimensions.fontOverLarge,
                ),
              ),
              spaceDown(Dimensions.space15),
              Column(
                children: [
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(Dimensions.space10),
                    decoration: BoxDecoration(
                      color: MyColor.bodyTextBgColor,
                      borderRadius: BorderRadius.circular(Dimensions.space5),
                    ),
                    child: Center(
                      child: Text(
                        MyStrings.recommendedPrice.rKv({
                          "priceKey": "${controller.defaultCurrencySymbol}${(() {
                            final base = double.tryParse(controller.selectedService.recommendAmount.toString()) ?? 0.0;
                            final isMercadoPago = controller.selectedPaymentMethod.name?.toLowerCase() == 'mercadopago' || controller.selectedPaymentMethod.name?.toLowerCase() == 'mercado pago';
                            final finalPrice = isMercadoPago ? (base * 1.05) : base;
                            return finalPrice.toStringAsFixed(2);
                          }())}",
                          "distanceKey": "${controller.distance.toPrecision(2)} ${MyUtils.getDistanceLabel(distance: controller.distance.toString(), unit: controller.homeRepo.apiClient.getDistanceUnit())}",
                        }).tr,
                        style: regularDefault.copyWith(color: MyColor.bodyTextColor),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ),
                  spaceDown(Dimensions.space20),
                  Container(
                    decoration: BoxDecoration(
                      shape: BoxShape.rectangle,
                      color: MyColor.primaryColor.withValues(alpha: 0.05),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          controller.defaultCurrencySymbol,
                          style: mediumExtraLarge.copyWith(
                            fontSize: 50,
                            color: MyColor.primaryColor,
                          ),
                        ),
                        IntrinsicWidth(
                          child: TextFormField(
                            onChanged: (val) {},
                            expands: false,
                            controller: controller.amountController,
                            scrollPadding: EdgeInsets.zero,
                            inputFormatters: [
                              LengthLimitingTextInputFormatter(8),
                            ],
                            decoration: InputDecoration(
                              contentPadding: const EdgeInsets.symmetric(
                                vertical: Dimensions.space20,
                              ),
                              border: InputBorder.none,
                              hintText: '0.0',
                              hintStyle: mediumDefault.copyWith(
                                fontSize: 50,
                                color: MyColor.primaryColor.withValues(alpha: 0.5),
                              ),
                            ),
                            style: mediumDefault.copyWith(
                              fontSize: 50,
                              color: MyColor.primaryColor,
                            ),
                            clipBehavior: Clip.antiAliasWithSaveLayer,
                            selectionHeightStyle: BoxHeightStyle.includeLineSpacingTop,
                            keyboardType: TextInputType.number,
                            cursorColor: Colors.grey.shade400,
                          ),
                        ),
                      ],
                    ),
                  ),
                  spaceDown(Dimensions.space40),
                  RoundedButton(
                    text: MyStrings.done.tr.toUpperCase(),
                    textStyle: boldDefault.copyWith(
                      color: MyColor.colorWhite,
                      fontSize: Dimensions.fontLarge,
                    ),
                    press: () {
                      double enterValue = StringConverter.formatDouble(
                        controller.amountController.text,
                      );
                      double min = StringConverter.formatDouble(
                        controller.selectedService.minAmount ?? '0.0',
                      );
                      double max = StringConverter.formatDouble(
                        controller.selectedService.maxAmount ?? '0.0',
                      );
                      printD(min);
                      printD(max);

                      if (enterValue < max + 1 && enterValue >= min) {
                        controller.updateMainAmount(enterValue);
                        Get.back();
                      } else {
                        CustomSnackBar.error(
                          errorList: [
                            '${MyStrings.pleaseEnterMinimum.tr} ${controller.defaultCurrencySymbol}${StringConverter.formatNumber(controller.selectedService.minAmount ?? '0')} to ${controller.defaultCurrencySymbol}${StringConverter.formatNumber(controller.selectedService.maxAmount ?? '')}',
                          ],
                        );
                      }
                    },
                  ),
                  spaceDown(Dimensions.space20),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}
