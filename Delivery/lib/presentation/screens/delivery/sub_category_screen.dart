import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';
import 'package:lizto_delivery/presentation/screens/delivery/store_list_screen.dart';

class SubCategoryScreen extends StatefulWidget {
  final int categoryId;
  final String categoryName;
  const SubCategoryScreen({super.key, required this.categoryId, required this.categoryName});

  @override
  State<SubCategoryScreen> createState() => _SubCategoryScreenState();
}

class _SubCategoryScreenState extends State<SubCategoryScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<DeliveryController>().loadSubCategories(widget.categoryId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<DeliveryController>(
      builder: (controller) {
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
                  child: const Icon(Icons.arrow_back_ios_new_rounded,
                      color: MyColor.primaryTextColor, size: 18),
                ),
              ),
            ),
            title: Text(
              widget.categoryName,
              style: boldExtraLarge.copyWith(
                color: MyColor.primaryTextColor,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
            centerTitle: true,
          ),
          body: controller.isLoading
              ? const Center(child: CircularProgressIndicator())
              : controller.subCategories.isEmpty
                  ? Center(
                      child: Text('Sin subcategorías', style: mediumSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                    )
                  : GridView.builder(
                      padding: const EdgeInsets.all(Dimensions.space16),
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 3,
                        crossAxisSpacing: Dimensions.space12,
                        mainAxisSpacing: Dimensions.space12,
                        childAspectRatio: 0.85,
                      ),
                      itemCount: controller.subCategories.length,
                      itemBuilder: (context, index) {
                        final sub = controller.subCategories[index];
                        return GestureDetector(
                          onTap: () => Get.to(() => StoreListScreen(
                                subCategoryId: sub.id ?? 0,
                                subCategoryName: sub.name ?? '',
                              )),
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
                                Container(
                                  padding: const EdgeInsets.all(8),
                                  decoration: const BoxDecoration(
                                    color: Color(0xFFFFFBF7),
                                    shape: BoxShape.circle,
                                  ),
                                  child: ClipRRect(
                                    borderRadius: BorderRadius.circular(20),
                                    child: MyImageWidget(
                                      imageUrl: '${controller.subCategoryImagePath}/${sub.image}',
                                      height: 44,
                                      width: 44,
                                      boxFit: BoxFit.cover,
                                    ),
                                  ),
                                ),
                                const SizedBox(height: Dimensions.space8),
                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 6),
                                  child: Text(
                                    sub.name ?? '',
                                    style: boldDefault.copyWith(fontSize: 12, color: MyColor.primaryTextColor),
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    textAlign: TextAlign.center,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
        );
      },
    );
  }
}
