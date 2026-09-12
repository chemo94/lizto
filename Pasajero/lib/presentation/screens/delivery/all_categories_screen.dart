import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/presentation/components/animated_screen_entrance.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';
import 'package:liztogo/presentation/screens/delivery/sub_category_screen.dart';

class AllCategoriesScreen extends StatelessWidget {
  const AllCategoriesScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return GetBuilder<DeliveryController>(
      builder: (controller) {
        final categories = controller.generalCategories;
        return Scaffold(
          backgroundColor: const Color(0xFFFFFBF7),
          appBar: AppBar(
            backgroundColor: Colors.transparent,
            elevation: 0,
            scrolledUnderElevation: 0,
            leadingWidth: 60,
            leading: Center(
              child: GestureDetector(
                onTap: () => Get.back(),
                child: Container(
                  height: 40,
                  width: 40,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.04),
                        blurRadius: 8,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: const Icon(Icons.arrow_back_ios_new_rounded, color: MyColor.primaryTextColor, size: 18),
                ),
              ),
            ),
            title: Text(
              'Categorías',
              style: boldExtraLarge.copyWith(
                color: MyColor.primaryTextColor,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
            centerTitle: true,
          ),
          body: categories.isEmpty
              ? Center(
                  child: Text(
                    'No hay categorías disponibles',
                    style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                  ),
                )
              : GridView.builder(
                  padding: const EdgeInsets.all(Dimensions.space16),
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 3,
                    crossAxisSpacing: Dimensions.space12,
                    mainAxisSpacing: Dimensions.space12,
                    childAspectRatio: 0.85,
                  ),
                  itemCount: categories.length,
                  itemBuilder: (context, index) {
                    final cat = categories[index];
                    return GestureDetector(
                      onTap: () {
                        if (cat.isFavorCategory) {
                          Get.offAllNamed('/dashboard_screen', arguments: 1);
                        } else {
                          Get.to(() => SubCategoryScreen(categoryId: cat.id ?? 0, categoryName: cat.name ?? ''));
                        }
                      },
                      child: Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.03),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Stack(
                              clipBehavior: Clip.none,
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: const BoxDecoration(
                                    color: Color(0xFFFFFBF7),
                                    shape: BoxShape.circle,
                                  ),
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(20),
                                    child: MyImageWidget(
                                      imageUrl: '${controller.categoryImagePath}/${cat.image}',
                                      height: 44,
                                      width: 44,
                                      boxFit: BoxFit.cover,
                                    ),
                                  ),
                                ),
                                Positioned(
                                  right: -2,
                                  bottom: -2,
                                  child: Container(
                                    height: 18,
                                    width: 18,
                                    decoration: BoxDecoration(
                                      color: MyColor.primaryColor,
                                      shape: BoxShape.circle,
                                      border: Border.all(color: MyColor.colorWhite, width: 1.5),
                                    ),
                                    child: Icon(
                                      cat.isFavorCategory ? Icons.assignment_turned_in_rounded : Icons.storefront_rounded,
                                      color: MyColor.colorWhite,
                                      size: 10,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: Dimensions.space10),
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 6),
                              child: Text(
                                cat.name?.tr ?? '',
                                style: boldDefault.copyWith(fontSize: 12, color: MyColor.primaryTextColor),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                textAlign: TextAlign.center,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ).animatedStagger(index: index, staggerDelay: const Duration(milliseconds: 40));
                  },
                ),
        );
      },
    );
  }
}
