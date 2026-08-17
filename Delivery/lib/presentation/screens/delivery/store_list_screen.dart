import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';
import 'package:lizto_delivery/presentation/components/shimmer_loaders.dart';
import 'package:lizto_delivery/presentation/screens/delivery/store_screen.dart';

class StoreListScreen extends StatefulWidget {
  final int subCategoryId;
  final String subCategoryName;
  const StoreListScreen({super.key, required this.subCategoryId, required this.subCategoryName});

  @override
  State<StoreListScreen> createState() => _StoreListScreenState();
}

class _StoreListScreenState extends State<StoreListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<DeliveryController>().loadStores(widget.subCategoryId);
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
                  child: const Icon(Icons.arrow_back_ios_new_rounded, color: MyColor.primaryTextColor, size: 18),
                ),
              ),
            ),
            title: Text(
              widget.subCategoryName,
              style: boldExtraLarge.copyWith(
                color: MyColor.primaryTextColor,
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
            centerTitle: true,
          ),
          body: controller.isLoading
              ? const ShimmerListLoader(itemCount: 5, itemHeight: 90)
              : controller.stores.isEmpty
                  ? Center(child: Text('Sin tiendas disponibles', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
                  : ListView.builder(
                      padding: const EdgeInsets.all(Dimensions.space16),
                      itemCount: controller.stores.length,
                      itemBuilder: (context, index) => _buildStoreCard(controller, controller.stores[index]),
                    ),
        );
      },
    );
  }

  Widget _buildStoreCard(dynamic controller, dynamic store) {
    final isFavorite = controller.isStoreFavorite(store.id ?? 0);
    return GestureDetector(
      onTap: () => Get.to(() => StoreScreen(storeId: store.id ?? 0)),
      child: Container(
        margin: const EdgeInsets.only(bottom: Dimensions.space16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 12,
              offset: const Offset(0, 6),
            )
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Cover Banner ──
            Stack(
              children: [
                ColorFiltered(
                  colorFilter: store.isOpenNow ? const ColorFilter.mode(Colors.transparent, BlendMode.srcOver) : const ColorFilter.matrix(<double>[0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0, 0, 0, 1, 0]),
                  child: MyImageWidget(
                    imageUrl: store.coverImage != null ? '${controller.storeCoverPath}/${store.coverImage}' : '${controller.storeImagePath}/${store.image}',
                    height: 130,
                    width: double.infinity,
                    boxFit: BoxFit.cover,
                  ),
                ),
                if (!store.isOpenNow) const Positioned.fill(child: ColoredBox(color: Color(0x66000000))),
                // Heart favorite button floating top-right
                Positioned(
                  top: 8,
                  right: 8,
                  child: GestureDetector(
                    onTap: () {
                      controller.toggleFavoriteStore(store.id ?? 0);
                      controller.update();
                    },
                    child: Container(
                      width: 32,
                      height: 32,
                      decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                      child: Icon(
                        isFavorite ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                        color: isFavorite ? MyColor.redCancelTextColor : Colors.grey.shade400,
                        size: 18,
                      ),
                    ),
                  ),
                ),
                // Status badge
                Positioned(
                  bottom: 8,
                  right: 8,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: store.isOpenNow ? const Color(0xFF10B981) : MyColor.redCancelTextColor,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      store.isOpenNow ? 'Abierto' : 'Cerrado',
                      style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: 10),
                    ),
                  ),
                ),
                // Store logo at bottom of cover
                Positioned(
                  left: Dimensions.space12,
                  bottom: 8,
                  child: Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: MyColor.colorWhite, width: 2.5),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.1),
                          blurRadius: 4,
                          offset: const Offset(0, 2),
                        )
                      ],
                    ),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(13.5),
                      child: MyImageWidget(
                        imageUrl: '${controller.storeImagePath}/${store.image}',
                        height: 48,
                        width: 48,
                        boxFit: BoxFit.cover,
                      ),
                    ),
                  ),
                ),
              ],
            ),
            // ── Store Info ──
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          store.name ?? '',
                          style: boldDefault.copyWith(fontSize: 16, color: MyColor.primaryTextColor),
                        ),
                      ),
                      Row(
                        children: [
                          Icon(Icons.star_rounded, size: 14, color: Colors.amber.shade700),
                          const SizedBox(width: 2),
                          Text(store.rating?.toStringAsFixed(1) ?? '0', style: boldDefault.copyWith(fontSize: 12, color: MyColor.primaryTextColor)),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    store.description ?? '',
                    style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 12),
                  // Tags row
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Wrap(
                          spacing: Dimensions.space8,
                          runSpacing: Dimensions.space6,
                          children: [
                            _tag(Icons.delivery_dining, store.deliveryFee == 0 ? 'Envío gratis' : 'Envío S/ ${(store.deliveryFee ?? 0).toStringAsFixed(1)}'),
                            if (store.preparationTime != null) _tag(Icons.timer_outlined, '${store.preparationTime} min'),
                            if (store.distanceFormatted != null) _tag(Icons.near_me_rounded, store.distanceFormatted),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                        decoration: BoxDecoration(
                          color: store.isOpenNow ? const Color(0xFF0F172B) : Colors.grey.shade400,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          store.isOpenNow ? 'Pedir' : 'Cerrado',
                          style: boldDefault.copyWith(color: Colors.white, fontSize: 11),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _tag(IconData icon, String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: MyColor.primaryColor.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 12, color: MyColor.primaryColor),
          const SizedBox(width: 4),
          Text(text, style: regularSmall.copyWith(color: MyColor.primaryColor, fontSize: 11, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
