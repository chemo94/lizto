import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:liztogo/presentation/components/animated_screen_entrance.dart';
import 'package:get/get.dart';
import 'package:speech_to_text/speech_to_text.dart' as stt;
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/data/model/delivery/delivery_models.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';
import 'package:liztogo/presentation/components/shimmer_loaders.dart';
import 'package:liztogo/presentation/screens/delivery/premium_section_detail_screen.dart';
import 'package:liztogo/presentation/screens/delivery/store_list_screen.dart';
import 'package:liztogo/presentation/screens/delivery/store_screen.dart';
import 'package:liztogo/presentation/screens/delivery/floating_cart_bar.dart';

// ── Accent color palette (cycles per section key) ──
const List<Color> _accentPalette = [
  Color(0xFF6C63FF),
  Color(0xFFFF6B6B),
  Color(0xFF00C9A7),
  Color(0xFFFFB347),
  Color(0xFF4ECDC4),
  Color(0xFFFF6B9D),
];

Color _accentFor(String key) {
  final idx = key.codeUnits.fold(0, (a, b) => a + b) % _accentPalette.length;
  return _accentPalette[idx];
}

// ══════════════════════════════════════════════════════════
// ServiceHomeScreen – rich landing page for a delivery service
// ══════════════════════════════════════════════════════════

class ServiceHomeScreen extends StatefulWidget {
  final int categoryId;
  final String categoryName;
  final String? categoryImageUrl;

  const ServiceHomeScreen({
    super.key,
    required this.categoryId,
    required this.categoryName,
    this.categoryImageUrl,
  });

  @override
  State<ServiceHomeScreen> createState() => _ServiceHomeScreenState();
}

class _ServiceHomeScreenState extends State<ServiceHomeScreen> {
  final _searchCtrl = TextEditingController();
  Timer? _searchDebounce;
  int? _selectedSubCategoryId; // null = show all sections

  final stt.SpeechToText _speech = stt.SpeechToText();
  bool _isListening = false;

  void _startVoiceSearch(TextEditingController controller, Function(String) onSearch) async {
    bool available = await _speech.initialize(
      onStatus: (status) {
        if (status == 'done' || status == 'notListening') {
          if (mounted) setState(() => _isListening = false);
        }
      },
      onError: (val) {
        if (mounted) setState(() => _isListening = false);
      },
    );
    if (available) {
      if (mounted) setState(() => _isListening = true);
      Get.bottomSheet(
        StatefulBuilder(
          builder: (context, setModalState) {
            _speech.listen(
              onResult: (val) {
                if (val.recognizedWords.isNotEmpty) {
                  controller.text = val.recognizedWords;
                  onSearch(val.recognizedWords);
                  setModalState(() {});
                }
              },
              localeId: 'es_ES',
            );
            return Container(
              padding: const EdgeInsets.all(24),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 48,
                    height: 5,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                  const SizedBox(height: 24),
                  Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      color: MyColor.primaryColor.withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.mic_rounded, color: MyColor.primaryColor, size: 36),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Escuchando...',
                    style: boldLarge.copyWith(color: MyColor.primaryTextColor),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    controller.text.isEmpty ? 'Habla ahora' : controller.text,
                    textAlign: TextAlign.center,
                    style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                  ),
                  const SizedBox(height: 24),
                  ElevatedButton(
                    onPressed: () {
                      _speech.stop();
                      Get.back();
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0F172B),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                    ),
                    child: const Text('Listo', style: TextStyle(color: Colors.white)),
                  ),
                ],
              ),
            );
          },
        ),
        isDismissible: true,
        enableDrag: true,
      ).then((_) {
        _speech.stop();
        if (mounted) setState(() => _isListening = false);
      });
    } else {
      Get.snackbar(
        'Voz no disponible',
        'No se pudo iniciar el reconocimiento de voz. Verifica que tengas permiso de micrófono y el servicio de Google Speech activo.',
        duration: const Duration(seconds: 4),
        snackPosition: SnackPosition.BOTTOM,
      );
    }
  }

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<DeliveryController>();
      c.loadCategoryHome(widget.categoryId);
    });
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    _searchDebounce?.cancel();
    _speech.stop();
    super.dispose();
  }

  void _onSearchChanged(String value) {
    _searchDebounce?.cancel();
    final c = Get.find<DeliveryController>();
    if (value.trim().isEmpty) {
      c.categoryHomeSearchQuery = '';
      c.update();
      return;
    }
    _searchDebounce = Timer(const Duration(milliseconds: 400), () {
      c.searchWithinCategory(widget.categoryId, value.trim());
    });
  }

  void _clearSearch() {
    _searchCtrl.clear();
    _searchDebounce?.cancel();
    final c = Get.find<DeliveryController>();
    c.categoryHomeSearchQuery = '';
    c.update();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<DeliveryController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: const Color(0xFFFFFBF7),
          body: RefreshIndicator(
            color: MyColor.primaryColor,
            onRefresh: () => c.loadCategoryHome(widget.categoryId),
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                // ── Sticky collapsing header ──
                _ServiceSliverHeader(
                  categoryName: widget.categoryName,
                  categoryImageUrl: widget.categoryImageUrl,
                  searchCtrl: _searchCtrl,
                  searchQuery: c.categoryHomeSearchQuery,
                  onSearchChanged: _onSearchChanged,
                  onClearSearch: _clearSearch,
                  onMicPressed: () => _startVoiceSearch(_searchCtrl, (v) {
                    _onSearchChanged(v);
                  }),
                ),

                // ── Subcategory filter pills ──
                if (c.categoryHomeSubCategories.isNotEmpty)
                  SliverToBoxAdapter(
                    child: _SubCategoryPills(
                      controller: c,
                      selected: _selectedSubCategoryId,
                      onSelect: (id) {
                        setState(() => _selectedSubCategoryId = id);
                        if (id != null) {
                          Get.to(() => StoreListScreen(
                                subCategoryId: id,
                                subCategoryName: c.categoryHomeSubCategories
                                    .firstWhere((s) => s.id == id,
                                        orElse: () => SubCategoryModel())
                                    .name ??
                                    '',
                              ));
                        }
                      },
                    ),
                  ),

                // ── Loading shimmer ──
                if (c.categoryHomeLoading) ...[
                  const SliverToBoxAdapter(child: ShimmerGridLoader(itemCount: 4)),
                ]

                // ── Search results ──
                else if (c.categoryHomeSearchQuery.trim().isNotEmpty) ...[
                  _buildSearchResults(c),
                ]

                // ── Dynamic sections ──
                else if (c.categoryHomeSections.isNotEmpty) ...[
                  ..._buildSections(c),
                  const SliverToBoxAdapter(child: SizedBox(height: Dimensions.space32)),
                ]

                // ── Empty state ──
                else ...[
                  SliverFillRemaining(
                    child: _EmptyState(categoryName: widget.categoryName),
                  ),
                ],
              ],
            ),
          ),
          bottomNavigationBar: c.hasItemsInCart
              ? FloatingCartBar(controller: c)
              : null,
        );
      },
    );
  }

  // ── Builds individual premium section slivers ──
  List<Widget> _buildSections(DeliveryController c) {
    return c.categoryHomeSections.map<Widget>((sec) {
      final keyName = sec['key']?.toString() ?? '';
      final title = sec['title']?.toString() ?? '';
      final subtitle = sec['subtitle']?.toString() ?? '';
      final type = sec['type']?.toString() ?? 'store';
      final dataList = sec['data'] as List? ?? [];

      if (dataList.isEmpty) return const SliverToBoxAdapter(child: SizedBox.shrink());

      return SliverToBoxAdapter(
        child: _ServiceSection(
          controller: c,
          keyName: keyName,
          title: title,
          subtitle: subtitle,
          type: type,
          dataList: dataList,
        ),
      );
    }).toList();
  }

  // ── Builds the search result view ──
  Widget _buildSearchResults(DeliveryController c) {
    final query = c.categoryHomeSearchQuery.toLowerCase();
    final stores = c.categoryHomeStores.isEmpty
        ? <StoreModel>[]
        : c.categoryHomeStores
            .where((s) =>
                (s.name?.toLowerCase().contains(query) ?? false) ||
                (s.description?.toLowerCase().contains(query) ?? false) ||
                (s.address?.toLowerCase().contains(query) ?? false))
            .toList();

    // Also filter sections data client-side
    final sectionItems = <dynamic>[];
    for (final sec in c.categoryHomeSections) {
      final dataList = sec['data'] as List? ?? [];
      for (final item in dataList) {
        if (item is Map) {
          final name = item['name']?.toString().toLowerCase() ?? '';
          final desc = item['description']?.toString().toLowerCase() ?? '';
          if (name.contains(query) || desc.contains(query)) sectionItems.add(item);
        }
      }
    }

    final allItems = [
      ...stores.map((s) => <String, dynamic>{
            'id': s.id, 'name': s.name, 'image': s.image,
            'cover_image': s.coverImage, 'description': s.description,
            'address': s.address, 'delivery_fee': s.deliveryFee,
            'is_open': s.isOpenNow, 'preparation_time': s.preparationTime,
            'distance': s.distance,
          }),
      ...sectionItems,
    ];

    // Deduplicate by id
    final seen = <int>{};
    final unique = allItems.where((item) {
      final id = item['id'];
      if (id == null || seen.contains(id)) return false;
      seen.add(id as int);
      return true;
    }).toList();

    if (unique.isEmpty) {
      return SliverFillRemaining(
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.search_off_rounded, size: 54, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.4)),
              const SizedBox(height: Dimensions.space12),
              Text('Sin resultados para "$query"',
                  textAlign: TextAlign.center,
                  style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
              const SizedBox(height: 4),
              Text('Prueba con otros términos',
                  style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor.withValues(alpha: 0.7))),
            ],
          ),
        ),
      );
    }

    return SliverPadding(
      padding: const EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space12, Dimensions.space16, Dimensions.space24),
      sliver: SliverList(
        delegate: SliverChildBuilderDelegate(
          (ctx, i) {
            final item = unique[i];
            try {
              final store = StoreModel.fromJson(item as Map<String, dynamic>);
              return Padding(
                padding: const EdgeInsets.only(bottom: Dimensions.space12),
                child: _ServiceStoreListCard(controller: c, store: store),
              );
            } catch (_) {
              return const SizedBox.shrink();
            }
          },
          childCount: unique.length,
        ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════
// Collapsing Sliver App Bar Header
// ══════════════════════════════════════════════════════════

class _ServiceSliverHeader extends StatelessWidget {
  final String categoryName;
  final String? categoryImageUrl;
  final TextEditingController searchCtrl;
  final String searchQuery;
  final ValueChanged<String> onSearchChanged;
  final VoidCallback onClearSearch;
  final VoidCallback onMicPressed;

  const _ServiceSliverHeader({
    required this.categoryName,
    this.categoryImageUrl,
    required this.searchCtrl,
    required this.searchQuery,
    required this.onSearchChanged,
    required this.onClearSearch,
    required this.onMicPressed,
  });

  @override
  Widget build(BuildContext context) {
    final top = MediaQuery.of(context).padding.top;
    return SliverToBoxAdapter(
      child: Container(
        color: Colors.transparent,
        padding: EdgeInsets.fromLTRB(16, top + 12, 16, 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Back button + title
            Row(
              children: [
                GestureDetector(
                  onTap: Get.back,
                  child: Container(
                    height: 44,
                    width: 44,
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
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        categoryName,
                        style: boldExtraLarge.copyWith(
                          color: MyColor.primaryTextColor,
                          fontSize: 22,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Tiendas, productos y ofertas',
                        style: regularSmall.copyWith(
                          color: MyColor.bodyMutedTextColor,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            // Search bar
            Container(
              height: 56,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(28),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 16,
                    offset: const Offset(0, 6),
                  ),
                ],
              ),
              child: Row(
                children: [
                  const SizedBox(width: 16),
                  Icon(
                    Icons.search_rounded,
                    color: Colors.grey.shade400,
                    size: 24,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: TextField(
                      controller: searchCtrl,
                      onChanged: onSearchChanged,
                      textInputAction: TextInputAction.search,
                      decoration: InputDecoration(
                        hintText: 'Buscar en $categoryName...',
                        hintStyle: TextStyle(
                          color: Colors.grey.shade400,
                          fontSize: 15,
                        ),
                        border: InputBorder.none,
                        isDense: true,
                        contentPadding: EdgeInsets.zero,
                      ),
                      style: const TextStyle(color: MyColor.primaryTextColor, fontSize: 16),
                    ),
                  ),
                  if (searchQuery.isNotEmpty)
                    GestureDetector(
                      onTap: onClearSearch,
                      child: const Padding(
                        padding: EdgeInsets.only(right: 12),
                        child: Icon(Icons.close_rounded,
                            color: MyColor.bodyMutedTextColor, size: 20),
                      ),
                    )
                  else
                    GestureDetector(
                      onTap: onMicPressed,
                      child: Container(
                        margin: const EdgeInsets.only(right: 6),
                        height: 44,
                        width: 44,
                        decoration: BoxDecoration(
                          color: MyColor.primaryColor.withValues(alpha: 0.12),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.mic_none_rounded,
                          color: MyColor.primaryColor,
                          size: 22,
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ).animatedEntrance(),
    );
  }
}

// ══════════════════════════════════════════════════════════
// Subcategory Filter Pills
// ══════════════════════════════════════════════════════════

class _SubCategoryPills extends StatelessWidget {
  final DeliveryController controller;
  final int? selected;
  final ValueChanged<int?> onSelect;

  const _SubCategoryPills({
    required this.controller,
    required this.selected,
    required this.onSelect,
  });

  @override
  Widget build(BuildContext context) {
    final subs = controller.categoryHomeSubCategories;
    return Container(
      color: Colors.transparent,
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: SizedBox(
        height: 105,
        child: ListView.separated(
          scrollDirection: Axis.horizontal,
          physics: const BouncingScrollPhysics(),
          itemCount: subs.length,
          separatorBuilder: (_, __) => const SizedBox(width: 14),
          itemBuilder: (ctx, i) {
            final sub = subs[i];
            final isSel = selected == sub.id;
            return GestureDetector(
              onTap: () => onSelect(sub.id),
              child: Column(
                children: [
                  AnimatedContainer(
                    duration: const Duration(milliseconds: 180),
                    width: 64,
                    height: 64,
                    decoration: BoxDecoration(
                      color: isSel ? MyColor.primaryColor : const Color(0xFF0F172B),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.04),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        )
                      ],
                    ),
                    padding: const EdgeInsets.all(10),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: MyImageWidget(
                        imageUrl: '${controller.categoryHomeSubCategoryImagePath}/${sub.image}',
                        boxFit: BoxFit.contain,
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    sub.name ?? '',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                    style: boldDefault.copyWith(
                      fontSize: 11,
                      color: isSel ? MyColor.primaryColor : MyColor.primaryTextColor.withValues(alpha: 0.8),
                      fontWeight: isSel ? FontWeight.w700 : FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ).animatedStagger(index: i);
          },
        ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════
// Premium Section (horizontal card rail per section)
// ══════════════════════════════════════════════════════════

class _ServiceSection extends StatelessWidget {
  final DeliveryController controller;
  final String keyName;
  final String title;
  final String subtitle;
  final String type;
  final List<dynamic> dataList;

  const _ServiceSection({
    required this.controller,
    required this.keyName,
    required this.title,
    required this.subtitle,
    required this.type,
    required this.dataList,
  });

  @override
  Widget build(BuildContext context) {
    final accent = _accentFor(keyName);
    final isProduct = type == 'product';

    return Padding(
      padding: const EdgeInsets.only(top: Dimensions.space24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Section header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.space16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Container(
                  width: 4, height: 28,
                  margin: const EdgeInsets.only(right: Dimensions.space10),
                  decoration: BoxDecoration(
                    color: MyColor.primaryColor, borderRadius: BorderRadius.circular(4)),
                ),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title,
                          style: boldLarge.copyWith(fontSize: 17, color: MyColor.primaryTextColor)),
                      if (subtitle.isNotEmpty)
                        Text(subtitle,
                             style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                    ],
                  ),
                ),
                GestureDetector(
                  onTap: () => Get.to(() => PremiumSectionDetailScreen(
                        keyName: keyName,
                        title: title,
                        subtitle: subtitle,
                        type: type,
                        dataList: dataList,
                        accentColor: accent,
                      )),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                    decoration: BoxDecoration(
                      color: MyColor.primaryColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text('Ver más',
                        style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 12)),
                  ),
                ),
              ],
            ),
          ).animatedEntrance(),
          const SizedBox(height: Dimensions.space12),
          // Horizontal cards
          SizedBox(
            height: isProduct ? 245 : 225,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: Dimensions.space16),
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              itemCount: dataList.length,
              separatorBuilder: (_, __) => const SizedBox(width: Dimensions.space12),
              itemBuilder: (ctx, i) {
                final item = dataList[i];
                if (isProduct) return _ServiceProductCard(controller: controller, item: item, accent: accent).animatedStagger(index: i);
                try {
                  final store = StoreModel.fromJson(item as Map<String, dynamic>);
                  return _ServiceStoreCard(controller: controller, store: store, accent: accent).animatedStagger(index: i);
                } catch (_) {
                  return const SizedBox.shrink();
                }
              },
            ),
          ),
        ],
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════
// Store Feature Card (horizontal rail)
// ══════════════════════════════════════════════════════════

class _ServiceStoreCard extends StatelessWidget {
  final DeliveryController controller;
  final StoreModel store;
  final Color accent;

  const _ServiceStoreCard({required this.controller, required this.store, required this.accent});

  @override
  Widget build(BuildContext context) {
    final isFavorite = controller.isStoreFavorite(store.id ?? 0);
    return GestureDetector(
      onTap: () => Get.to(() => StoreScreen(storeId: store.id ?? 0)),
      child: Container(
        width: MediaQuery.of(context).size.width * 0.65,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 12, offset: const Offset(0, 6))],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Stack(
                children: [
                  MyImageWidget(
                    imageUrl: store.coverImage != null
                        ? '${controller.storeCoverPath}/${store.coverImage}'
                        : '${controller.categoryHomeStoreImagePath}/${store.image}',
                    height: 110, width: double.infinity, boxFit: BoxFit.cover,
                  ),
                  // Heart favorite button floating top-right
                  Positioned(
                    top: 8, right: 8,
                    child: GestureDetector(
                      onTap: () {
                        controller.toggleFavoriteStore(store.id ?? 0);
                        controller.update();
                      },
                      child: Container(
                        width: 32, height: 32,
                        decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                        child: Icon(
                          isFavorite ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                          color: isFavorite ? MyColor.redCancelTextColor : Colors.grey.shade400,
                          size: 18,
                        ),
                      ),
                    ),
                  ),
                  // Time floating bottom-left
                  Positioned(
                    bottom: 8, left: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.access_time_filled_rounded, size: 12, color: Colors.grey.shade700),
                          const SizedBox(width: 4),
                          Text(
                            '${(store.id ?? 5) % 15 + 20} min',
                            style: boldDefault.copyWith(fontSize: 10, color: Colors.grey.shade800),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(store.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: boldDefault.copyWith(fontSize: 14, color: MyColor.primaryTextColor)),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            store.description?.isNotEmpty == true ? store.description! : store.address ?? '',
                            maxLines: 1, overflow: TextOverflow.ellipsis,
                            style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                          ),
                        ),
                        Icon(Icons.star_rounded, size: 12, color: Colors.amber.shade700),
                        const SizedBox(width: 2),
                                                Text(store.rating?.toStringAsFixed(1) ?? '0', style: boldDefault.copyWith(fontSize: 11, color: MyColor.primaryTextColor)),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Spacer(),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                          decoration: BoxDecoration(color: const Color(0xFF0F172B), borderRadius: BorderRadius.circular(12)),
                          child: Text(
                            'Pedir',
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
      ),
    );
  }
}

class _ServiceProductCard extends StatelessWidget {
  final DeliveryController controller;
  final dynamic item;
  final Color accent;

  const _ServiceProductCard({required this.controller, required this.item, required this.accent});

  @override
  Widget build(BuildContext context) {
    ProductModel? product;
    StoreModel? store;
    try {
      final map = item as Map<String, dynamic>;
      product = ProductModel.fromJson(map);
      if (map['store'] != null) store = StoreModel.fromJson(map['store'] as Map<String, dynamic>);
    } catch (_) {
      return const SizedBox.shrink();
    }

    final hasDiscount = product.discountPrice != null && product.discountPrice! < (product.price ?? 0);
    final discountPct = hasDiscount
        ? (((product.price! - product.discountPrice!) / product.price!) * 100).round()
        : 0;
    final isFavorite = store != null ? controller.isStoreFavorite(store.id ?? 0) : false;

    return GestureDetector(
      onTap: () { if (store != null) Get.to(() => StoreScreen(storeId: store?.id ?? 0)); },
      child: Container(
        width: 180,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 12, offset: const Offset(0, 6))],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Stack(
                children: [
                  MyImageWidget(
                    imageUrl: '${controller.categoryHomeProductImagePath}/${product.image}',
                    height: 130, width: double.infinity, boxFit: BoxFit.cover,
                  ),
                  // Heart favorite button floating top-right
                  Positioned(
                    top: 8, right: 8,
                    child: GestureDetector(
                      onTap: () {
                        if (store != null) {
                          controller.toggleFavoriteStore(store.id ?? 0);
                          controller.update();
                        }
                      },
                      child: Container(
                        width: 32, height: 32,
                        decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                        child: Icon(
                          isFavorite ? Icons.favorite_rounded : Icons.favorite_border_rounded,
                          color: isFavorite ? MyColor.redCancelTextColor : Colors.grey.shade400,
                          size: 18,
                        ),
                      ),
                    ),
                  ),
                  // Time floating bottom-left
                  Positioned(
                    bottom: 8, left: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.access_time_filled_rounded, size: 12, color: Colors.grey.shade700),
                          const SizedBox(width: 4),
                          Text(
                            '${(product.id ?? 5) % 15 + 20} min',
                            style: boldDefault.copyWith(fontSize: 10, color: Colors.grey.shade800),
                          ),
                        ],
                      ),
                    ),
                  ),
                  if (hasDiscount)
                    Positioned(
                      top: 8, left: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(color: MyColor.redCancelTextColor, borderRadius: BorderRadius.circular(8)),
                        child: Text('-$discountPct%', style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: 11)),
                      ),
                    ),
                  Positioned(
                    bottom: 8, right: 8,
                    child: GestureDetector(
                      onTap: () => controller.addToCart(product!, store: store),
                      child: Container(
                        width: 28, height: 28,
                        decoration: BoxDecoration(
                          color: MyColor.primaryColor,
                          shape: BoxShape.circle,
                          boxShadow: [BoxShadow(color: MyColor.primaryColor.withValues(alpha: 0.3), blurRadius: 4, offset: const Offset(0, 2))],
                        ),
                        child: const Icon(Icons.add_rounded, color: MyColor.colorWhite, size: 16),
                      ),
                    ),
                  ),
                ],
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(product.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis,
                        style: boldDefault.copyWith(fontSize: 14, color: MyColor.primaryTextColor)),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            store?.name ?? 'Restaurante',
                            maxLines: 1, overflow: TextOverflow.ellipsis,
                            style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                          ),
                        ),
                        Icon(Icons.star_rounded, size: 12, color: Colors.amber.shade700),
                        const SizedBox(width: 2),
                        Text(store?.rating?.toStringAsFixed(1) ?? '0', style: boldDefault.copyWith(fontSize: 11, color: MyColor.primaryTextColor)),
                      ],
                    ),
                    const SizedBox(height: 6),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Spacer(),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(color: const Color(0xFF0F172B), borderRadius: BorderRadius.circular(12)),
                          child: Text(
                            'S/ ${product.finalPrice.toStringAsFixed(2)}',
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
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════
// Store List Card (search results / full list)
// ══════════════════════════════════════════════════════════

class _ServiceStoreListCard extends StatelessWidget {
  final DeliveryController controller;
  final StoreModel store;

  const _ServiceStoreListCard({required this.controller, required this.store});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () => Get.to(() => StoreScreen(storeId: store.id ?? 0)),
      child: Container(
        padding: const EdgeInsets.all(8),
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
        child: Row(
          children: [
            Stack(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: MyImageWidget(
                    imageUrl: store.coverImage != null
                        ? '${controller.storeCoverPath}/${store.coverImage}'
                        : '${controller.categoryHomeStoreImagePath}/${store.image}',
                    height: 96,
                    width: 96,
                    boxFit: BoxFit.cover,
                  ),
                ),
                Positioned(
                  top: 6,
                  left: 6,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                    decoration: BoxDecoration(
                      color: (store.isOpenNow ? const Color(0xFF10B981) : MyColor.redCancelTextColor)
                          .withValues(alpha: 0.9),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      store.isOpenNow ? 'Abierto' : 'Cerrado',
                      style: semiBoldSmall.copyWith(color: Colors.white, fontSize: 8),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.only(right: 4),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      store.name ?? '',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: boldDefault.copyWith(fontSize: 14, color: MyColor.primaryTextColor),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      store.description?.isNotEmpty == true ? store.description! : store.address ?? 'Tienda cercana',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Wrap(
                            spacing: 8,
                            runSpacing: 4,
                            children: [
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(Icons.delivery_dining_rounded, color: MyColor.primaryColor, size: 13),
                                  const SizedBox(width: 3),
                                  Text(
                                    store.deliveryFee == 0 ? 'Gratis' : 'S/ ${(store.deliveryFee ?? 0).toStringAsFixed(1)}',
                                    style: semiBoldSmall.copyWith(color: MyColor.primaryColor, fontSize: 11),
                                  ),
                                ],
                              ),
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.timer_rounded, color: MyColor.bodyMutedTextColor, size: 12),
                                  const SizedBox(width: 2),
                                  Text(
                                    '${store.preparationTime ?? 20} min',
                                    style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: const Color(0xFF0F172B),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            'Pedir',
                            style: boldDefault.copyWith(color: Colors.white, fontSize: 11),
                          ),
                        ),
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

// ══════════════════════════════════════════════════════════
// Empty State
// ══════════════════════════════════════════════════════════

class _EmptyState extends StatelessWidget {
  final String categoryName;
  const _EmptyState({required this.categoryName});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 80, height: 80,
            decoration: BoxDecoration(
              color: MyColor.primaryColor.withValues(alpha: 0.08),
              shape: BoxShape.circle,
            ),
            child: Icon(Icons.storefront_rounded,
                size: 40, color: MyColor.primaryColor.withValues(alpha: 0.5)),
          ),
          const SizedBox(height: Dimensions.space16),
          Text('Sin tiendas en $categoryName',
              style: boldDefault.copyWith(color: MyColor.primaryTextColor)),
          const SizedBox(height: Dimensions.space8),
          Text('Intenta más tarde o cambia tu ubicación',
              style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
        ],
      ),
    );
  }
}
