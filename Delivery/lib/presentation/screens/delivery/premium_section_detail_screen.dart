import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_models.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';
import 'package:lizto_delivery/presentation/screens/delivery/store_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/floating_cart_bar.dart';

class PremiumSectionDetailScreen extends StatefulWidget {
  final String keyName;
  final String title;
  final String subtitle;
  final String type;
  final List<dynamic> dataList;
  final Color accentColor;

  const PremiumSectionDetailScreen({
    super.key,
    required this.keyName,
    required this.title,
    required this.subtitle,
    required this.type,
    required this.dataList,
    required this.accentColor,
  });

  @override
  State<PremiumSectionDetailScreen> createState() => _PremiumSectionDetailScreenState();
}

class _PremiumSectionDetailScreenState extends State<PremiumSectionDetailScreen> {
  final _searchCtrl = TextEditingController();
  String _searchQuery = '';

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  List<dynamic> _getFilteredData() {
    final query = _searchQuery.trim().toLowerCase();
    if (query.isEmpty) return widget.dataList;

    return widget.dataList.where((item) {
      if (item is! Map) return false;
      final name = item['name']?.toString().toLowerCase() ?? '';
      final desc = item['description']?.toString().toLowerCase() ?? '';
      if (widget.type == 'product') {
        return name.contains(query) || desc.contains(query);
      } else {
        final address = item['address']?.toString().toLowerCase() ?? '';
        return name.contains(query) || desc.contains(query) || address.contains(query);
      }
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final filteredList = _getFilteredData();
    final isProduct = widget.type == 'product';

    return GetBuilder<DeliveryController>(
      builder: (controller) {
        return Scaffold(
          backgroundColor: const Color(0xFFF7F8FA),
          appBar: AppBar(
            backgroundColor: widget.accentColor,
            elevation: 0,
            leading: IconButton(
              icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20),
              onPressed: () => Get.back(),
            ),
            title: Text(widget.title, style: boldLarge.copyWith(color: Colors.white)),
            centerTitle: true,
          ),
          body: Column(
            children: [
              // Search Bar
              Padding(
                padding: const EdgeInsets.all(Dimensions.space16),
                child: Container(
                  height: 48,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: MyColor.neutral200),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.04),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  padding: const EdgeInsets.symmetric(horizontal: Dimensions.space14),
                  child: Row(
                    children: [
                      Icon(Icons.search_rounded, color: MyColor.bodyMutedTextColor, size: 22),
                      const SizedBox(width: Dimensions.space8),
                      Expanded(
                        child: TextField(
                          controller: _searchCtrl,
                          onChanged: (value) {
                            setState(() {
                              _searchQuery = value;
                            });
                          },
                          decoration: InputDecoration(
                            hintText: isProduct ? 'Buscar productos...' : 'Buscar tiendas...',
                            hintStyle: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                            border: InputBorder.none,
                            isDense: true,
                            contentPadding: EdgeInsets.zero,
                          ),
                          style: regularDefault.copyWith(color: MyColor.primaryTextColor),
                        ),
                      ),
                      if (_searchQuery.isNotEmpty)
                        GestureDetector(
                          onTap: () {
                            setState(() {
                              _searchCtrl.clear();
                              _searchQuery = '';
                            });
                          },
                          child: Icon(Icons.close_rounded, color: MyColor.bodyMutedTextColor, size: 20),
                        ),
                    ],
                  ),
                ),
              ),

              // Subtitle/Header Info
              if (widget.subtitle.isNotEmpty && _searchQuery.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: Dimensions.space16),
                  child: Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: Dimensions.space12),
                    padding: const EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space8),
                    decoration: BoxDecoration(
                      color: widget.accentColor.withValues(alpha: 0.06),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: widget.accentColor.withValues(alpha: 0.12)),
                    ),
                    child: Text(
                      widget.subtitle,
                      style: regularSmall.copyWith(color: widget.accentColor, fontWeight: FontWeight.w500),
                    ),
                  ),
                ),

              // Items View
              Expanded(
                child: filteredList.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.search_off_rounded, size: 48, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.4)),
                            const SizedBox(height: Dimensions.space8),
                            Text(
                              'No se encontraron resultados',
                              style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                            ),
                          ],
                        ),
                      )
                    : isProduct
                        ? GridView.builder(
                            padding: const EdgeInsets.fromLTRB(Dimensions.space16, 0, Dimensions.space16, Dimensions.space24),
                            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              crossAxisSpacing: Dimensions.space12,
                              mainAxisSpacing: Dimensions.space12,
                              childAspectRatio: 0.76,
                            ),
                            itemCount: filteredList.length,
                            itemBuilder: (context, index) {
                              final item = filteredList[index];
                              return _PremiumGridProductCard(
                                controller: controller,
                                item: item,
                                accentColor: widget.accentColor,
                              );
                            },
                          )
                        : ListView.separated(
                            padding: const EdgeInsets.fromLTRB(Dimensions.space16, 0, Dimensions.space16, Dimensions.space24),
                            itemCount: filteredList.length,
                            separatorBuilder: (_, __) => const SizedBox(height: Dimensions.space12),
                            itemBuilder: (context, index) {
                              final item = filteredList[index];
                              try {
                                final store = StoreModel.fromJson(item as Map<String, dynamic>);
                                return _PremiumListStoreCard(
                                  controller: controller,
                                  store: store,
                                  accentColor: widget.accentColor,
                                );
                              } catch (_) {
                                return const SizedBox.shrink();
                              }
                            },
                          ),
              ),
            ],
          ),
          bottomNavigationBar: controller.hasItemsInCart
              ? FloatingCartBar(controller: controller)
              : null,
        );
      },
    );
  }
}

// ── Product Grid Card Component ──

class _PremiumGridProductCard extends StatelessWidget {
  final DeliveryController controller;
  final dynamic item;
  final Color accentColor;

  const _PremiumGridProductCard({
    required this.controller,
    required this.item,
    required this.accentColor,
  });

  @override
  Widget build(BuildContext context) {
    ProductModel? product;
    StoreModel? store;
    try {
      final map = item as Map<String, dynamic>;
      product = ProductModel.fromJson(map);
      if (map['store'] != null) {
        store = StoreModel.fromJson(map['store'] as Map<String, dynamic>);
      }
    } catch (_) {
      return const SizedBox.shrink();
    }

    final hasDiscount = product.discountPrice != null && product.discountPrice! < (product.price ?? 0);
    final discountPct = hasDiscount
        ? (((product.price! - product.discountPrice!) / product.price!) * 100).round()
        : 0;

    return InkWell(
      onTap: () {
        if (store != null) {
          Get.to(() => StoreScreen(storeId: store!.id ?? 0));
        }
      },
      borderRadius: BorderRadius.circular(18),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: MyColor.neutral200),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Stack(
              children: [
                MyImageWidget(
                  imageUrl: '${controller.productImagePath}/${product.image}',
                  height: 110,
                  width: double.infinity,
                  boxFit: BoxFit.cover,
                ),
                if (hasDiscount)
                  Positioned(
                    top: Dimensions.space8,
                    left: Dimensions.space8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: Dimensions.space6, vertical: 3),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEF4444),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        '-$discountPct%',
                        style: semiBoldSmall.copyWith(color: Colors.white, fontSize: 10),
                      ),
                    ),
                  ),
                Positioned(
                  bottom: Dimensions.space8,
                  right: Dimensions.space8,
                  child: GestureDetector(
                    onTap: () => controller.addToCart(product!, store: store),
                    child: Container(
                      height: 32,
                      width: 32,
                      decoration: BoxDecoration(
                        color: accentColor,
                        borderRadius: BorderRadius.circular(10),
                        boxShadow: [
                          BoxShadow(color: accentColor.withValues(alpha: 0.35), blurRadius: 6, offset: const Offset(0, 3)),
                        ],
                      ),
                      child: const Icon(Icons.add_rounded, color: Colors.white, size: 20),
                    ),
                  ),
                ),
              ],
            ),
            Padding(
              padding: const EdgeInsets.all(Dimensions.space10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.name ?? '',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: semiBoldSmall.copyWith(color: MyColor.primaryTextColor, height: 1.2, fontSize: 13),
                  ),
                  const SizedBox(height: Dimensions.space6),
                  Row(
                    children: [
                      Text(
                        'S/ ${product.finalPrice.toStringAsFixed(2)}',
                        style: boldDefault.copyWith(color: accentColor, fontSize: 13.5),
                      ),
                      if (hasDiscount) ...[
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            'S/ ${product.price!.toStringAsFixed(2)}',
                            style: regularSmall.copyWith(
                              color: MyColor.bodyMutedTextColor,
                              decoration: TextDecoration.lineThrough,
                              fontSize: 10,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ],
                  ),
                  if (store != null) ...[
                    const SizedBox(height: Dimensions.space6),
                    Row(
                      children: [
                        Icon(Icons.storefront_rounded, size: 12, color: MyColor.bodyMutedTextColor),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            store.name ?? '',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Store List Card Component ──

class _PremiumListStoreCard extends StatelessWidget {
  final DeliveryController controller;
  final StoreModel store;
  final Color accentColor;

  const _PremiumListStoreCard({
    required this.controller,
    required this.store,
    required this.accentColor,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => Get.to(() => StoreScreen(storeId: store.id ?? 0)),
      borderRadius: BorderRadius.circular(18),
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: MyColor.neutral200),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Row(
          children: [
            Stack(
              children: [
                MyImageWidget(
                  imageUrl: store.coverImage != null
                      ? '${controller.storeCoverPath}/${store.coverImage}'
                      : '${controller.storeImagePath}/${store.image}',
                  height: 96,
                  width: 96,
                  boxFit: BoxFit.cover,
                ),
                Positioned(
                  top: 6,
                  left: 6,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: (store.isOpenNow ? const Color(0xFF039855) : MyColor.redCancelTextColor).withValues(alpha: 0.9),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      store.isOpenNow ? 'Abierto' : 'Cerrado',
                      style: semiBoldSmall.copyWith(color: Colors.white, fontSize: 9),
                    ),
                  ),
                ),
              ],
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      store.name ?? '',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: boldDefault.copyWith(fontSize: 14.5),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      store.description?.isNotEmpty == true ? store.description! : store.address ?? 'Tienda cercana',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, height: 1.15, fontSize: 11),
                    ),
                    const SizedBox(height: Dimensions.space6),
                    Row(
                      children: [
                        Icon(Icons.delivery_dining_rounded, color: accentColor, size: 14),
                        const SizedBox(width: Dimensions.space4),
                        Text(
                          'S/ ${(store.deliveryFee ?? 0).toStringAsFixed(2)}',
                          style: semiBoldSmall.copyWith(color: accentColor, fontSize: 11.5),
                        ),
                        const SizedBox(width: Dimensions.space8),
                        Icon(Icons.timer_rounded, color: MyColor.bodyMutedTextColor, size: 13),
                        const SizedBox(width: 3),
                        Text(
                          '${store.preparationTime ?? 20} min',
                          style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                        ),
                        if (store.distanceFormatted != null) ...[
                          const SizedBox(width: Dimensions.space8),
                          Icon(Icons.place_rounded, color: MyColor.bodyMutedTextColor, size: 13),
                          const SizedBox(width: 3),
                          Text(
                            store.distanceFormatted!,
                            style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
