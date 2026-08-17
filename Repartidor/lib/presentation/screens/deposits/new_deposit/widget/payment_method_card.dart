// ignore: must_be_immutable
import 'package:flutter/material.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/model/global/payment_method/app_payment_gateway.dart';
import 'package:liztogo_repartidor/presentation/components/card/inner_shadow_container.dart';
import 'package:liztogo_repartidor/presentation/components/divider/custom_spacer.dart';
import 'package:liztogo_repartidor/presentation/components/image/my_network_image_widget.dart';

class PaymentMethodCard extends StatelessWidget {
  final VoidCallback press;
  AppPaymentGateway paymentMethod;
  final String assetPath;
  bool selected = false;
  PaymentMethodCard({
    super.key,
    required this.press,
    required this.paymentMethod,
    required this.assetPath,
    this.selected = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsetsDirectional.only(top: 10),
      child: InnerShadowContainer(
        width: double.infinity,
        backgroundColor: MyColor.neutral50,
        borderRadius: Dimensions.largeRadius,
        blur: 6,
        offset: Offset(3, 3),
        shadowColor: MyColor.colorBlack.withValues(alpha: 0.04),
        isShadowTopLeft: true,
        isShadowBottomRight: true,
        padding: EdgeInsetsGeometry.symmetric(
          vertical: Dimensions.space2,
          horizontal: Dimensions.space2,
        ),
        child: CheckboxListTile(
          value: selected,
          checkboxShape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(Dimensions.space10),
          ),
          onChanged: (val) {
            press();
          },
          contentPadding: const EdgeInsetsDirectional.only(
            start: Dimensions.space20,
            end: Dimensions.space20,
            top: Dimensions.space1,
            bottom: Dimensions.space1,
          ),
          activeColor: MyColor.primaryColor,
          title: Row(
            mainAxisAlignment: MainAxisAlignment.start,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              MyImageWidget(
                imageUrl: '$assetPath/${paymentMethod.method?.image}',
                width: Dimensions.space40,
                height: Dimensions.space40,
                boxFit: BoxFit.fitWidth,
                radius: 4,
              ),
              spaceSide(Dimensions.space10),
              FittedBox(
                fit: BoxFit.scaleDown,
                child: Text(
                  paymentMethod.name ?? '',
                  style: semiBoldDefault.copyWith(),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  //   return GestureDetector(
  //     onTap: press,
  //     child: Container(
  //       margin: const EdgeInsetsDirectional.only(top: 10),
  //       child: Material(
  //         elevation: 0.0,
  //         shape: RoundedRectangleBorder(
  //           borderRadius: BorderRadius.circular(Dimensions.space10),
  //           side: BorderSide(
  //             color: selected ? MyColor.primaryColor : MyColor.rideBorderColor.withValues(alpha: .9),
  //           ),
  //         ),
  //         color: Colors.white,
  //         child: CheckboxListTile(
  //           value: selected,
  //           checkboxShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.space10)),
  //           onChanged: (val) {
  //             press();
  //           },
  //           contentPadding: const EdgeInsetsDirectional.only(start: Dimensions.space20, end: Dimensions.space20, top: Dimensions.space1, bottom: Dimensions.space1),
  //           activeColor: MyColor.primaryColor,
  //           title: Row(
  //             mainAxisAlignment: MainAxisAlignment.start,
  //             crossAxisAlignment: CrossAxisAlignment.center,
  //             children: [
  //               MyImageWidget(
  //                 imageUrl: '$assetPath/${paymentMethod.method?.image}',
  //                 width: Dimensions.space40,
  //                 height: Dimensions.space40,
  //                 boxFit: BoxFit.fitWidth,
  //                 radius: 4,
  //               ),
  //               const SizedBox(width: Dimensions.space10),
  //               Expanded(
  //                 child: Text(paymentMethod.name ?? '', style: semiBoldDefault.copyWith(color: MyColor.colorBlack), maxLines: 2, overflow: TextOverflow.ellipsis),
  //               ),
  //             ],
  //           ),
  //         ),
  //       ),
  //     ),
  //   );
  // }
}
