import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/my_strings.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/core/utils/url_container.dart';
import 'package:liztogo_repartidor/data/controller/deposit/add_new_deposit_controller.dart';
import 'package:liztogo_repartidor/presentation/components/annotated_region/annotated_region_widget.dart';
import 'package:liztogo_repartidor/presentation/components/bottom-sheet/bottom_sheet_header_row.dart';
import 'package:liztogo_repartidor/presentation/components/divider/custom_spacer.dart';
import 'package:liztogo_repartidor/presentation/components/text/header_text.dart';
import 'package:liztogo_repartidor/presentation/screens/deposits/new_deposit/widget/payment_method_card.dart';

class PaymentMethodListBottomSheet extends StatelessWidget {
  const PaymentMethodListBottomSheet({super.key});

  @override
  Widget build(BuildContext context) {
    return GetBuilder<AddNewDepositController>(
      builder: (controller) {
        return AnnotatedRegionWidget(
          child: Container(
            height: context.height / 1.4,
            color: MyColor.colorWhite,
            child: Column(
              children: [
                const BottomSheetHeaderRow(),
                HeaderText(
                  text: MyStrings.selectPaymentMethod,
                  style: mediumOverLarge.copyWith(
                    fontSize: Dimensions.fontOverLarge,
                    fontWeight: FontWeight.normal,
                    color: MyColor.colorBlack,
                  ),
                ),
                spaceDown(Dimensions.space15),
                Flexible(
                  child: ListView.builder(
                    itemCount: controller.methodList.length,
                    shrinkWrap: true,
                    itemBuilder: (context, index) {
                      return PaymentMethodCard(
                        paymentMethod: controller.methodList[index],
                        assetPath: "${UrlContainer.domainUrl}/${controller.imagePath}",
                        selected: controller.methodList[index].id.toString() == controller.paymentMethod?.id.toString(),
                        press: () {
                          controller.setPaymentMethod(
                            controller.methodList[index],
                          );
                          Get.back();
                        },
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
