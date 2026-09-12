import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_models.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';
import 'package:lizto_delivery/presentation/screens/delivery/cart_sheet.dart';
import 'package:lizto_delivery/presentation/screens/delivery/checkout_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/floating_cart_bar.dart';

class StoreScreen extends StatefulWidget {
  final int storeId;
  const StoreScreen({super.key, required this.storeId});

  @override
  State<StoreScreen> createState() => _StoreScreenState();
}

class _StoreScreenState extends State<StoreScreen> with TickerProviderStateMixin {
  final _scrollController = ScrollController();
  final _searchCtrl = TextEditingController();
  String _searchQuery = '';
  int _selectedCatIndex = 0;
  bool _isHeaderCollapsed = false;
  int _lastCartCount = 0;

  // Keys for each category section to auto-scroll
  final List<GlobalKey> _catKeys = [];

  late final AnimationController _cartAnimCtrl;
  late final Animation<double> _cartScaleAnim;

  @override
  void initState() {
    super.initState();
    _cartAnimCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 350));
    _cartScaleAnim = CurvedAnimation(parent: _cartAnimCtrl, curve: Curves.elasticOut);

    _scrollController.addListener(() {
      final collapsed = _scrollController.offset > 180;
      if (collapsed != _isHeaderCollapsed) {
        setState(() => _isHeaderCollapsed = collapsed);
      }
    });

    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<DeliveryController>().loadStoreDetail(widget.storeId);
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    _searchCtrl.dispose();
    _cartAnimCtrl.dispose();
    super.dispose();
  }

  List<StoreCategoryModel> _getFilteredCats(DeliveryController c) {
    if (_searchQuery.isEmpty) return c.storeCategories;
    return c.storeCategories.where((cat) {
      final filteredProducts = (cat.products ?? [])
          .where((p) =>
              (p.name ?? '').toLowerCase().contains(_searchQuery) ||
              (p.description ?? '').toLowerCase().contains(_searchQuery))
          .toList();
      return filteredProducts.isNotEmpty;
    }).toList();
  }

  void _scrollToCategory(int index) {
    setState(() => _selectedCatIndex = index);
    if (index < _catKeys.length) {
      final ctx = _catKeys[index].currentContext;
      if (ctx != null) {
        Scrollable.ensureVisible(ctx, duration: const Duration(milliseconds: 400), curve: Curves.easeOut, alignment: 0.1);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<DeliveryController>(
      builder: (controller) {
        final store = controller.selectedStore;
        final cats = _getFilteredCats(controller);

        // Rebuild category keys to match list length
        while (_catKeys.length < cats.length) {
          _catKeys.add(GlobalKey());
        }

        if (controller.isLoading || store == null) {
          return _LoadingStoreScreen(isLoading: controller.isLoading);
        }

        // Trigger cart animation when items change
        if (controller.hasItemsInCart) {
          if (controller.cartCount != _lastCartCount) {
            _lastCartCount = controller.cartCount;
            _cartAnimCtrl.forward(from: 0.0);
          } else {
            _cartAnimCtrl.forward();
          }
        } else {
          _lastCartCount = 0;
          _cartAnimCtrl.reverse();
        }

        return Scaffold(
          backgroundColor: const Color(0xFFF7F8FA),
          body: CustomScrollView(
            controller: _scrollController,
            physics: const BouncingScrollPhysics(),
            slivers: [
              // ── Parallax Hero Header ──
              _StoreParallaxHeader(controller: controller, store: store),

              // ── Glassmorphism Info Card ──
              SliverToBoxAdapter(
                child: _StoreInfoCard(controller: controller, store: store),
              ),

              // ── Search Bar ──
              SliverToBoxAdapter(
                child: _StoreSearchBar(
                  controller: _searchCtrl,
                  onChanged: (v) => setState(() => _searchQuery = v.toLowerCase().trim()),
                ),
              ),

              // ── Sticky Category Rail ──
              if (cats.isNotEmpty && _searchQuery.isEmpty)
                SliverPersistentHeader(
                  pinned: true,
                  delegate: _CategoryRailDelegate(
                    categories: cats,
                    selectedIndex: _selectedCatIndex,
                    onTap: _scrollToCategory,
                  ),
                ),

              // ── Product Categories ──
              SliverToBoxAdapter(
                child: Column(
                  children: List.generate(cats.length, (catIndex) {
                    final cat = cats[catIndex];
                    final products = _searchQuery.isEmpty
                        ? (cat.products ?? [])
                        : (cat.products ?? [])
                            .where((p) =>
                                (p.name ?? '').toLowerCase().contains(_searchQuery) ||
                                (p.description ?? '').toLowerCase().contains(_searchQuery))
                            .toList();
                    if (products.isEmpty) return const SizedBox.shrink();
                    return _CategorySection(
                      key: _catKeys[catIndex],
                      categoryName: cat.name ?? '',
                      products: products,
                      controller: controller,
                      onAddTap: (p) => _addToCart(controller, p),
                      onDecreasesTap: (p) => _decreaseProduct(controller, p),
                    );
                  }),
                ),
              ),

              SliverToBoxAdapter(
                child: SizedBox(height: controller.hasItemsInCart ? 120 : Dimensions.space32),
              ),
            ],
          ),
          // ── Premium Floating Cart Bar ──
          bottomNavigationBar: controller.hasItemsInCart
              ? FloatingCartBar(controller: controller)
              : null,
        );
      },
    );
  }

  void _addToCart(DeliveryController controller, ProductModel product) {
    if ((product.variations?.length ?? 0) > 0) {
      _showProductConfigDialog(controller, product);
    } else {
      controller.addToCart(product, store: controller.selectedStore);
    }
  }

  void _decreaseProduct(DeliveryController controller, ProductModel product) {
    final lines = controller.cartItemsForProduct(product.id ?? 0);
    if (lines.length == 1) {
      controller.decreaseCartItem(lines.first);
    } else if (lines.length > 1) {
      _showCartSheet(controller);
    }
  }

  void _showCartSheet(DeliveryController controller) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => CartSheet(controller: controller),
    );
  }

  void _showProductConfigDialog(DeliveryController controller, ProductModel product) {
    ProductVariationModel? selectedVar =
        (product.variations?.isNotEmpty ?? false) ? product.variations!.first : null;
    List<ProductAddonModel> selectedAddons = [];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setDialogState) {
          double calcPrice() {
            double base = (selectedVar != null && (selectedVar!.price ?? 0) > 0)
                ? selectedVar!.price!
                : product.finalPrice;
            for (var a in selectedAddons) {
              base += a.price ?? 0;
            }
            return base;
          }

          return _ProductConfigSheet(
            product: product,
            selectedVar: selectedVar,
            selectedAddons: selectedAddons,
            calcPrice: calcPrice,
            onVarChanged: (v) => setDialogState(() => selectedVar = v),
            onAddonToggle: (a, val) => setDialogState(() {
              if (val == true) {
                selectedAddons.add(a);
              } else {
                selectedAddons.remove(a);
              }
            }),
            onAddToCart: () {
              controller.addToCartWithOptions(product, variation: selectedVar, addons: selectedAddons, store: controller.selectedStore);
              Get.back();
            },
          );
        },
      ),
    );
  }
}

// ── Parallax Header ──

class _StoreParallaxHeader extends StatelessWidget {
  final DeliveryController controller;
  final StoreModel store;

  const _StoreParallaxHeader({required this.controller, required this.store});

  @override
  Widget build(BuildContext context) {
    return SliverAppBar(
      expandedHeight: 260,
      pinned: true,
      backgroundColor: MyColor.primaryColor,
      leading: Padding(
        padding: const EdgeInsets.all(8),
        child: GestureDetector(
          onTap: Get.back,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
              child: Container(
                color: Colors.black.withValues(alpha: 0.28),
                child: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 18),
              ),
            ),
          ),
        ),
      ),
      actions: [
        GestureDetector(
          onTap: () {
            if (store.id != null) {
              Get.find<DeliveryController>().toggleFavoriteStore(store.id!);
            }
          },
          child: Padding(
            padding: const EdgeInsets.all(8),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
                child: Container(
                  color: Colors.black.withValues(alpha: 0.28),
                  padding: const EdgeInsets.all(8),
                  child: GetBuilder<DeliveryController>(
                    builder: (c) => Icon(
                      c.isStoreFavorite(store.id ?? 0) ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                      color: c.isStoreFavorite(store.id ?? 0) ? MyColor.redCancelTextColor : Colors.white,
                      size: 20,
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
      flexibleSpace: FlexibleSpaceBar(
        background: Stack(
          fit: StackFit.expand,
          children: [
            MyImageWidget(
              imageUrl: store.coverImage != null
                  ? '${controller.storeCoverPath}/${store.coverImage}'
                  : '${controller.storeImagePath}/${store.image}',
              height: 260,
              width: double.infinity,
              boxFit: BoxFit.cover,
            ),
            // Gradient overlay
            Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    Colors.black.withValues(alpha: 0.15),
                    Colors.black.withValues(alpha: 0.6),
                  ],
                ),
              ),
            ),
            // Store name at bottom
            Positioned(
              bottom: Dimensions.space20,
              left: Dimensions.space16,
              right: Dimensions.space16,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: 3),
                        decoration: BoxDecoration(
                          color: store.isOpenNow ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          store.isOpenNow ? '● Abierto' : '● Cerrado',
                          style: regularSmall.copyWith(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600),
                        ),
                      ),
                      const SizedBox(width: Dimensions.space8),
                      if (store.distanceFormatted != null)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: 3),
                          decoration: BoxDecoration(
                            color: Colors.black.withValues(alpha: 0.5),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            store.distanceFormatted!,
                            style: regularSmall.copyWith(color: Colors.white, fontSize: 11),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: Dimensions.space6),
                  Text(
                    store.name ?? '',
                    style: boldOverLarge.copyWith(color: Colors.white, fontSize: 26, shadows: [
                      Shadow(color: Colors.black.withValues(alpha: 0.4), blurRadius: 8),
                    ]),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Glassmorphism Info Card ──

class _StoreInfoCard extends StatelessWidget {
  final DeliveryController controller;
  final StoreModel store;

  const _StoreInfoCard({required this.controller, required this.store});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space16, Dimensions.space16, 0),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.07), blurRadius: 20, offset: const Offset(0, 6)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Store logo + description
          Padding(
            padding: const EdgeInsets.all(Dimensions.space16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(14),
                    boxShadow: [
                      BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 8, offset: const Offset(0, 3)),
                    ],
                  ),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(14),
                    child: MyImageWidget(
                      imageUrl: '${controller.storeImagePath}/${store.image}',
                      height: 60,
                      width: 60,
                      boxFit: BoxFit.cover,
                    ),
                  ),
                ),
                const SizedBox(width: Dimensions.space12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(Icons.star_rounded, color: const Color(0xFFF79009), size: 15),
                          const SizedBox(width: 3),
                          Text('${store.rating?.toStringAsFixed(1) ?? "—"}', style: semiBoldSmall.copyWith(color: MyColor.primaryTextColor)),
                          Text('  ·  ', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                          Text('${store.totalOrders ?? 0}+ pedidos', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        store.description?.isNotEmpty == true ? store.description! : 'Tienda en LiztoGo',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor, height: 1.3),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          // Divider
          Divider(height: 1, color: MyColor.neutral200),
          // Metrics row
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: Dimensions.space12),
            child: Row(
              children: [
                _MetricTile(
                  icon: Icons.delivery_dining_rounded,
                  color: MyColor.primaryColor,
                  label: 'Delivery',
                  value: 'S/ ${store.deliveryFee?.toStringAsFixed(2) ?? "0.00"}',
                ),
                _MetricDivider(),
                _MetricTile(
                  icon: Icons.timer_rounded,
                  color: const Color(0xFF8B5CF6),
                  label: 'Tiempo',
                  value: '${store.preparationTime ?? 20} min',
                ),
                _MetricDivider(),
                _MetricTile(
                  icon: Icons.attach_money_rounded,
                  color: const Color(0xFF10B981),
                  label: 'Pedido mín.',
                  value: 'S/ ${store.minOrderAmount?.toStringAsFixed(2) ?? "0.00"}',
                ),
                if (store.openingTime != null && store.closingTime != null) ...[
                  _MetricDivider(),
                  _MetricTile(
                    icon: Icons.access_time_rounded,
                    color: const Color(0xFFF59E0B),
                    label: 'Horario',
                    value: '${store.openingTime!.substring(0, 5)}-${store.closingTime!.substring(0, 5)}',
                  ),
                ],
              ],
            ),
          ),
          if (store.address != null) ...[
            Divider(height: 1, color: MyColor.neutral200),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: Dimensions.space10),
              child: Row(
                children: [
                  Icon(Icons.location_on_rounded, size: 15, color: MyColor.bodyMutedTextColor),
                  const SizedBox(width: Dimensions.space6),
                  Expanded(
                    child: Text(
                      store.address!,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _MetricTile extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String label;
  final String value;

  const _MetricTile({required this.icon, required this.color, required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(height: 4),
          Text(value, style: semiBoldSmall.copyWith(color: MyColor.primaryTextColor, fontSize: 12)),
          Text(label, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10)),
        ],
      ),
    );
  }
}

class _MetricDivider extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(width: 1, height: 40, color: MyColor.neutral200);
  }
}

// ── Search Bar ──

class _StoreSearchBar extends StatelessWidget {
  final TextEditingController controller;
  final ValueChanged<String> onChanged;

  const _StoreSearchBar({required this.controller, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space16, Dimensions.space16, 0),
      child: Container(
        height: 46,
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: MyColor.neutral200),
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
        ),
        padding: const EdgeInsets.symmetric(horizontal: Dimensions.space14),
        child: Row(
          children: [
            Icon(Icons.search_rounded, color: MyColor.bodyMutedTextColor, size: 20),
            const SizedBox(width: Dimensions.space8),
            Expanded(
              child: TextField(
                controller: controller,
                onChanged: onChanged,
                decoration: InputDecoration(
                  hintText: 'Buscar en el menú...',
                  hintStyle: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                  border: InputBorder.none,
                  isDense: true,
                  contentPadding: EdgeInsets.zero,
                ),
                style: regularDefault.copyWith(color: MyColor.primaryTextColor),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ── Sticky Category Rail ──

class _CategoryRailDelegate extends SliverPersistentHeaderDelegate {
  final List<StoreCategoryModel> categories;
  final int selectedIndex;
  final ValueChanged<int> onTap;

  const _CategoryRailDelegate({
    required this.categories,
    required this.selectedIndex,
    required this.onTap,
  });

  @override
  double get minExtent => 52;
  @override
  double get maxExtent => 52;

  @override
  Widget build(BuildContext context, double shrinkOffset, bool overlapsContent) {
    return Container(
      color: const Color(0xFFF7F8FA),
      padding: const EdgeInsets.symmetric(vertical: Dimensions.space8),
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: Dimensions.space16),
        physics: const BouncingScrollPhysics(),
        itemCount: categories.length,
        separatorBuilder: (_, __) => const SizedBox(width: Dimensions.space8),
        itemBuilder: (context, i) {
          final isSelected = selectedIndex == i;
          return GestureDetector(
            onTap: () => onTap(i),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 220),
              curve: Curves.easeOut,
              padding: const EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space6),
              decoration: BoxDecoration(
                color: isSelected ? MyColor.primaryColor : MyColor.colorWhite,
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: isSelected ? MyColor.primaryColor : MyColor.neutral200),
                boxShadow: isSelected
                    ? [BoxShadow(color: MyColor.primaryColor.withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 3))]
                    : [],
              ),
              child: Text(
                categories[i].name ?? '',
                style: isSelected
                    ? semiBoldSmall.copyWith(color: Colors.white, fontSize: 13)
                    : regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 13),
              ),
            ),
          );
        },
      ),
    );
  }

  @override
  bool shouldRebuild(_CategoryRailDelegate old) =>
      old.selectedIndex != selectedIndex || old.categories != categories;
}

// ── Category Section ──

class _CategorySection extends StatelessWidget {
  final String categoryName;
  final List<ProductModel> products;
  final DeliveryController controller;
  final void Function(ProductModel) onAddTap;
  final void Function(ProductModel) onDecreasesTap;

  const _CategorySection({
    super.key,
    required this.categoryName,
    required this.products,
    required this.controller,
    required this.onAddTap,
    required this.onDecreasesTap,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space24, Dimensions.space16, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Category header with accent line
          Row(
            children: [
              Container(
                width: 4,
                height: 20,
                margin: const EdgeInsets.only(right: Dimensions.space8),
                decoration: BoxDecoration(
                  color: MyColor.primaryColor,
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              Text(categoryName, style: boldLarge.copyWith(fontSize: 18, color: MyColor.primaryTextColor)),
              const Spacer(),
              Text('${products.length} items', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
            ],
          ),
          const SizedBox(height: Dimensions.space12),
          ...products.map((p) => _PremiumProductCard(
                product: p,
                controller: controller,
                onAdd: () => onAddTap(p),
                onDecrease: () => onDecreasesTap(p),
              )),
        ],
      ),
    );
  }
}

// ── Premium Product Card ──

class _PremiumProductCard extends StatelessWidget {
  final ProductModel product;
  final DeliveryController controller;
  final VoidCallback onAdd;
  final VoidCallback onDecrease;

  const _PremiumProductCard({
    required this.product,
    required this.controller,
    required this.onAdd,
    required this.onDecrease,
  });

  @override
  Widget build(BuildContext context) {
    final qty = controller.cartQuantity(product.id ?? 0);
    final hasDiscount = product.discountPrice != null && product.discountPrice! < (product.price ?? 0);

    return Container(
      margin: const EdgeInsets.only(bottom: Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
        ],
      ),
      child: InkWell(
        onTap: onAdd,
        borderRadius: BorderRadius.circular(18),
        child: Padding(
          padding: const EdgeInsets.all(Dimensions.space12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Info section
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (hasDiscount)
                      Container(
                        margin: const EdgeInsets.only(bottom: 4),
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEF4444).withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          'OFERTA',
                          style: regularSmall.copyWith(color: const Color(0xFFEF4444), fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 0.5),
                        ),
                      ),
                    Text(
                      product.name ?? '',
                      style: boldDefault.copyWith(color: MyColor.primaryTextColor, height: 1.2),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (product.description?.isNotEmpty == true) ...[
                      const SizedBox(height: 3),
                      Text(
                        product.description!,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, height: 1.3),
                      ),
                    ],
                    const SizedBox(height: Dimensions.space8),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        Text(
                          'S/ ${product.finalPrice.toStringAsFixed(2)}',
                          style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 16),
                        ),
                        if (hasDiscount) ...[
                          const SizedBox(width: Dimensions.space6),
                          Text(
                            'S/ ${product.price!.toStringAsFixed(2)}',
                            style: regularSmall.copyWith(
                              color: MyColor.bodyMutedTextColor,
                              decoration: TextDecoration.lineThrough,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: Dimensions.space12),
              // Image + qty controls
              Column(
                children: [
                  Stack(
                    clipBehavior: Clip.none,
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(14),
                        child: MyImageWidget(
                          imageUrl: '${controller.productImagePath}/${product.image}',
                          height: 86,
                          width: 86,
                          boxFit: BoxFit.cover,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: Dimensions.space8),
                  // Qty or Add button
                  qty > 0
                      ? _QtySelector(qty: qty, onAdd: onAdd, onRemove: onDecrease)
                      : GestureDetector(
                          onTap: onAdd,
                          child: Container(
                            height: 32,
                            width: 86,
                            decoration: BoxDecoration(
                              color: MyColor.primaryColor,
                              borderRadius: BorderRadius.circular(10),
                              boxShadow: [
                                BoxShadow(color: MyColor.primaryColor.withValues(alpha: 0.35), blurRadius: 8, offset: const Offset(0, 3)),
                              ],
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.add_rounded, color: Colors.white, size: 16),
                                const SizedBox(width: 4),
                                Text('Agregar', style: regularSmall.copyWith(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
                              ],
                            ),
                          ),
                        ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _QtySelector extends StatelessWidget {
  final int qty;
  final VoidCallback onAdd;
  final VoidCallback onRemove;

  const _QtySelector({required this.qty, required this.onAdd, required this.onRemove});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 32,
      width: 86,
      decoration: BoxDecoration(
        color: const Color(0xFFF7F8FA),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: MyColor.primaryColor.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Expanded(
            child: GestureDetector(
              onTap: onRemove,
              child: Container(
                alignment: Alignment.center,
                child: Icon(Icons.remove_rounded, size: 16, color: MyColor.primaryColor),
              ),
            ),
          ),
          Container(width: 1, height: 20, color: MyColor.primaryColor.withValues(alpha: 0.2)),
          Expanded(
            child: Text('$qty', textAlign: TextAlign.center, style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 14)),
          ),
          Container(width: 1, height: 20, color: MyColor.primaryColor.withValues(alpha: 0.2)),
          Expanded(
            child: GestureDetector(
              onTap: onAdd,
              child: Container(
                alignment: Alignment.center,
                child: Icon(Icons.add_rounded, size: 16, color: MyColor.primaryColor),
              ),
            ),
          ),
        ],
      ),
    );
  }
}



// ── Product Config Bottom Sheet ──

class _ProductConfigSheet extends StatelessWidget {
  final ProductModel product;
  final ProductVariationModel? selectedVar;
  final List<ProductAddonModel> selectedAddons;
  final double Function() calcPrice;
  final ValueChanged<ProductVariationModel?> onVarChanged;
  final void Function(ProductAddonModel, bool?) onAddonToggle;
  final VoidCallback onAddToCart;

  const _ProductConfigSheet({
    required this.product,
    required this.selectedVar,
    required this.selectedAddons,
    required this.calcPrice,
    required this.onVarChanged,
    required this.onAddonToggle,
    required this.onAddToCart,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(top: MediaQuery.of(context).padding.top + 60),
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Handle
          Container(
            margin: const EdgeInsets.only(top: Dimensions.space12),
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: MyColor.bodyMutedTextColor.withValues(alpha: 0.25),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          Flexible(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(Dimensions.space20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(product.name ?? '', style: boldLarge.copyWith(fontSize: 20)),
                  const SizedBox(height: Dimensions.space4),
                  if (product.description?.isNotEmpty == true)
                    Text(product.description!, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                  const SizedBox(height: Dimensions.space4),
                  Text('S/ ${product.finalPrice.toStringAsFixed(2)}', style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 18)),

                  if ((product.variations?.length ?? 0) > 0) ...[
                    const SizedBox(height: Dimensions.space20),
                    Row(
                      children: [
                        Text('Elige una opción', style: boldDefault.copyWith(fontSize: 16)),
                        const Spacer(),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: MyColor.primaryColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text('Obligatorio', style: regularSmall.copyWith(color: MyColor.primaryColor, fontSize: 11)),
                        ),
                      ],
                    ),
                    const SizedBox(height: Dimensions.space8),
                    ...product.variations!.map((v) {
                      final isSelected = selectedVar?.id == v.id;
                      return GestureDetector(
                        onTap: () => onVarChanged(v),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 180),
                          margin: const EdgeInsets.only(bottom: Dimensions.space8),
                          padding: const EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space12),
                          decoration: BoxDecoration(
                            color: isSelected ? MyColor.primaryColor.withValues(alpha: 0.06) : const Color(0xFFF7F8FA),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: isSelected ? MyColor.primaryColor : Colors.transparent,
                              width: 1.5,
                            ),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                isSelected ? Icons.radio_button_checked_rounded : Icons.radio_button_off_rounded,
                                color: isSelected ? MyColor.primaryColor : MyColor.bodyMutedTextColor,
                                size: 20,
                              ),
                              const SizedBox(width: Dimensions.space12),
                              Expanded(child: Text(v.name ?? '', style: regularDefault.copyWith(color: MyColor.primaryTextColor))),
                              Builder(builder: (_) {
                                final varPrice = v.price ?? 0;
                                final diff = varPrice - product.finalPrice;
                                String priceText = 'S/ ${varPrice.toStringAsFixed(2)}';
                                if (diff.abs() > 0.01) {
                                  priceText += ' (${diff > 0 ? "+S/ " : "-S/ "}${diff.abs().toStringAsFixed(2)})';
                                }
                                return Text(
                                  priceText,
                                  style: semiBoldSmall.copyWith(color: MyColor.primaryColor),
                                );
                              }),
                            ],
                          ),
                        ),
                      );
                    }),
                  ],

                  if ((product.addons?.length ?? 0) > 0) ...[
                    const SizedBox(height: Dimensions.space16),
                    Text('Adicionales', style: boldDefault.copyWith(fontSize: 16)),
                    Text('Opcional', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                    const SizedBox(height: Dimensions.space8),
                    ...product.addons!.map((a) {
                      final isSelected = selectedAddons.contains(a);
                      return GestureDetector(
                        onTap: () => onAddonToggle(a, !isSelected),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 180),
                          margin: const EdgeInsets.only(bottom: Dimensions.space8),
                          padding: const EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space12),
                          decoration: BoxDecoration(
                            color: isSelected ? MyColor.primaryColor.withValues(alpha: 0.06) : const Color(0xFFF7F8FA),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: isSelected ? MyColor.primaryColor : Colors.transparent,
                              width: 1.5,
                            ),
                          ),
                          child: Row(
                            children: [
                              AnimatedContainer(
                                duration: const Duration(milliseconds: 180),
                                height: 20,
                                width: 20,
                                decoration: BoxDecoration(
                                  color: isSelected ? MyColor.primaryColor : Colors.transparent,
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(color: isSelected ? MyColor.primaryColor : MyColor.bodyMutedTextColor),
                                ),
                                child: isSelected ? const Icon(Icons.check_rounded, size: 14, color: Colors.white) : null,
                              ),
                              const SizedBox(width: Dimensions.space12),
                              Expanded(child: Text(a.name ?? '', style: regularDefault.copyWith(color: MyColor.primaryTextColor))),
                              Text(
                                '+ S/ ${a.price?.toStringAsFixed(2) ?? "0.00"}',
                                style: semiBoldSmall.copyWith(color: MyColor.primaryColor),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                  ],
                ],
              ),
            ),
          ),
          // Add to cart footer
          Container(
            padding: EdgeInsets.fromLTRB(Dimensions.space20, Dimensions.space12, Dimensions.space20, MediaQuery.of(context).padding.bottom + Dimensions.space16),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 12, offset: const Offset(0, -4))],
            ),
            child: Row(
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text('Total', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                    Text('S/ ${calcPrice().toStringAsFixed(2)}', style: boldLarge.copyWith(color: MyColor.primaryColor, fontSize: 20)),
                  ],
                ),
                const SizedBox(width: Dimensions.space16),
                Expanded(
                  child: GestureDetector(
                    onTap: onAddToCart,
                    child: Container(
                      height: 50,
                      decoration: BoxDecoration(
                        color: MyColor.primaryColor,
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(color: MyColor.primaryColor.withValues(alpha: 0.4), blurRadius: 12, offset: const Offset(0, 4)),
                        ],
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.add_shopping_cart_rounded, color: Colors.white, size: 20),
                          const SizedBox(width: Dimensions.space8),
                          Text('Agregar al carrito', style: boldDefault.copyWith(color: Colors.white)),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ── Loading State ──

class _LoadingStoreScreen extends StatelessWidget {
  final bool isLoading;

  const _LoadingStoreScreen({required this.isLoading});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF7F8FA),
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 18),
          onPressed: Get.back,
        ),
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.storefront_rounded, size: 64, color: Color(0xFFD1D5DB)),
                  SizedBox(height: 16),
                  Text('Tienda no encontrada', style: TextStyle(color: Color(0xFF6B7280))),
                ],
              ),
            ),
    );
  }
}
