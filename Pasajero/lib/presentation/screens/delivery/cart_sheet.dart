import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';

class CartSheet extends StatelessWidget {
  final DeliveryController controller;
  const CartSheet({super.key, required this.controller});

  @override
  Widget build(BuildContext context) {
    return GetBuilder<DeliveryController>(
      builder: (c) {
        return Container(
          padding: EdgeInsets.fromLTRB(
            Dimensions.space16,
            Dimensions.space16,
            Dimensions.space16,
            MediaQuery.of(context).padding.bottom + Dimensions.space16,
          ),
          constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.6),
          decoration: BoxDecoration(
            color: MyColor.colorWhite,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.1),
                blurRadius: 15,
                offset: const Offset(0, -4),
              ),
            ],
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(width: 40, height: 4, decoration: BoxDecoration(color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(2))),
              SizedBox(height: Dimensions.space12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('${MyStrings.cart.tr} (${c.cartCount})', style: boldLarge),
                  GestureDetector(onTap: () => c.clearCart(), child: Text('Vaciar', style: regularDefault.copyWith(color: MyColor.redCancelTextColor))),
                ],
              ),
              SizedBox(height: Dimensions.space16),
              if (c.cartItems.isEmpty)
                Expanded(child: Center(child: Text('Carrito vacío', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))))
              else
                Expanded(
                  child: ListView.separated(
                    itemCount: c.cartItems.length,
                    separatorBuilder: (_, __) => Divider(height: 1, color: MyColor.borderColor),
                    itemBuilder: (_, i) {
                      final item = c.cartItems[i];
                      return Padding(
                        padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            ClipRRect(
                              borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                              child: MyImageWidget(imageUrl: '${c.productImagePath}/${item.product.image}', height: 44, width: 44, boxFit: BoxFit.cover),
                            ),
                            SizedBox(width: Dimensions.space12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(item.product.name ?? '', style: boldDefault),
                                  if (item.selectedVariation != null) Text(item.selectedVariation!.name ?? '', style: regularSmall.copyWith(color: MyColor.primaryColor)),
                                  if (item.selectedAddons.isNotEmpty) ...item.selectedAddons.map((a) => Text('+ ${a.name}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))),
                                  Text('S/ ${item.unitPrice.toStringAsFixed(2)}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                                ],
                              ),
                            ),
                            Row(
                              children: [
                                _miniBtn(Icons.remove_rounded, () => c.decreaseCartItem(item)),
                                Padding(padding: EdgeInsets.symmetric(horizontal: Dimensions.space8), child: Text('${item.quantity}', style: boldDefault)),
                                _miniBtn(Icons.add_rounded, () => c.increaseCartItem(item)),
                              ],
                            ),
                            SizedBox(width: Dimensions.space8),
                            Text('S/ ${item.totalPrice.toStringAsFixed(2)}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
                          ],
                        ),
                      );
                    },
                  ),
                ),
              SizedBox(height: Dimensions.space12),
              Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                Text('Total:', style: boldLarge),
                Text('S/ ${c.cartSubtotal.toStringAsFixed(2)}', style: boldLarge.copyWith(color: MyColor.primaryColor)),
              ]),
            ],
          ),
        );
      },
    );
  }

  Widget _miniBtn(IconData icon, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.all(2),
        decoration: BoxDecoration(color: MyColor.primaryColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(4)),
        child: Icon(icon, size: 16, color: MyColor.primaryColor),
      ),
    );
  }
}
