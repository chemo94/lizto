import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/data/model/delivery/delivery_models.dart';
import 'package:liztogo/presentation/screens/delivery/cart_sheet.dart';
import 'package:liztogo/presentation/screens/delivery/checkout_screen.dart';

class FloatingCartBar extends StatefulWidget {
  final DeliveryController controller;

  const FloatingCartBar({super.key, required this.controller});

  @override
  State<FloatingCartBar> createState() => _FloatingCartBarState();
}

class _FloatingCartBarState extends State<FloatingCartBar> with SingleTickerProviderStateMixin {
  late final AnimationController _cartAnimCtrl;
  late final Animation<double> _cartScaleAnim;
  int _lastCartCount = 0;

  @override
  void initState() {
    super.initState();
    _cartAnimCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 350));
    _cartScaleAnim = CurvedAnimation(parent: _cartAnimCtrl, curve: Curves.elasticOut);
    
    if (widget.controller.hasItemsInCart) {
      _lastCartCount = widget.controller.cartCount;
      _cartAnimCtrl.forward();
    }
  }

  @override
  void dispose() {
    _cartAnimCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<DeliveryController>(
      builder: (controller) {
        if (!controller.hasItemsInCart) {
          _lastCartCount = 0;
          _cartAnimCtrl.reverse();
          return const SizedBox.shrink();
        }

        if (controller.cartCount != _lastCartCount) {
          _lastCartCount = controller.cartCount;
          _cartAnimCtrl.forward(from: 0.0);
        } else {
          _cartAnimCtrl.forward();
        }

        return Padding(
          padding: EdgeInsets.fromLTRB(
            Dimensions.space16,
            0,
            Dimensions.space16,
            MediaQuery.of(context).padding.bottom + Dimensions.space16,
          ),
          child: ScaleTransition(
            scale: _cartScaleAnim,
            child: Container(
              decoration: BoxDecoration(
                color: MyColor.primaryColor,
                borderRadius: BorderRadius.circular(18),
                boxShadow: [
                  BoxShadow(
                    color: MyColor.primaryColor.withValues(alpha: 0.45),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: () async {
                    StoreModel? store = controller.selectedStore;
                    final firstItem = controller.cartItems.firstOrNull;
                    
                    if (firstItem != null) {
                      if (store == null || store.id != firstItem.product.storeId) {
                        if (firstItem.store != null) {
                          store = firstItem.store;
                          controller.selectedStore = store;
                        } else {
                          // Show a loading dialog and fetch store details
                          showDialog(
                            context: context,
                            barrierDismissible: false,
                            builder: (ctx) => const Center(
                              child: CircularProgressIndicator(color: Colors.white),
                            ),
                          );
                          await controller.loadStoreDetail(firstItem.product.storeId ?? 0);
                          Get.back(); // Dismiss loading dialog
                          store = controller.selectedStore;
                        }
                      }
                    }

                    if (store != null) {
                      Get.to(() => CheckoutScreen(store: store!));
                    }
                  },
                  borderRadius: BorderRadius.circular(18),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: Dimensions.space20,
                      vertical: Dimensions.space14,
                    ),
                    child: Row(
                      children: [
                        // Cart badge
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.2),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Stack(
                            clipBehavior: Clip.none,
                            children: [
                              const Icon(Icons.shopping_bag_rounded, color: Colors.white, size: 20),
                              Positioned(
                                top: -6,
                                right: -6,
                                child: Container(
                                  height: 16,
                                  width: 16,
                                  decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                                  child: Center(
                                    child: Text(
                                      '${controller.cartCount}',
                                      style: regularSmall.copyWith(
                                        color: MyColor.primaryColor,
                                        fontSize: 10,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: Dimensions.space12),
                        Expanded(
                          child: Text(
                            MyStrings.checkout.tr,
                            style: boldDefault.copyWith(color: Colors.white, fontSize: 16),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: Dimensions.space10,
                            vertical: Dimensions.space6,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.18),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Text(
                            'S/ ${controller.cartSubtotal.toStringAsFixed(2)}',
                            style: boldDefault.copyWith(color: Colors.white, fontSize: 15),
                          ),
                        ),
                        GestureDetector(
                          onTap: () {
                            showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              backgroundColor: Colors.transparent,
                              builder: (_) => CartSheet(controller: controller),
                            );
                          },
                          child: Container(
                            margin: const EdgeInsets.only(left: Dimensions.space8),
                            padding: const EdgeInsets.all(Dimensions.space8),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.visibility_rounded, color: Colors.white, size: 18),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }
}
