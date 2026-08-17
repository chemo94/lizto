import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:speech_to_text/speech_to_text.dart' as stt;
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';
import 'package:lizto_delivery/data/controller/home/home_controller.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_models.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';
import 'package:lizto_delivery/presentation/components/shimmer_loaders.dart';
import 'package:lizto_delivery/presentation/screens/delivery/premium_section_detail_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/store_list_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/store_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/floating_cart_bar.dart';
import 'package:url_launcher/url_launcher.dart';

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
  bool _topOnly = false;
  bool _fastOnly = false;
  bool _highRatingOnly = false;
  String _sortBy = 'relevance';

  bool get _isPharmacy => widget.categoryName.toLowerCase().contains('farmacia');

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
      c.searchWithinCategory(widget.categoryId, value.trim(), top: _topOnly, fast: _fastOnly, highRating: _highRatingOnly, sort: _sortBy);
    });
  }

  void _clearSearch() {
    _searchCtrl.clear();
    _searchDebounce?.cancel();
    final c = Get.find<DeliveryController>();
    c.categoryHomeSearchQuery = '';
    c.update();
  }

  Future<void> _applyBackendFilters() => Get.find<DeliveryController>().loadCategoryHome(
        widget.categoryId,
        top: _topOnly,
        fast: _fastOnly,
        highRating: _highRatingOnly,
        sort: _sortBy,
      );

  @override
  Widget build(BuildContext context) {
    return GetBuilder<DeliveryController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: const Color(0xFFFFFBF7),
          body: RefreshIndicator(
            color: MyColor.primaryColor,
            onRefresh: () => _applyBackendFilters(),
            child: CustomScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              slivers: [
                // ── Sticky collapsing header ──
                _ServiceSliverHeader(
                  categoryName: widget.categoryName,
                  deliveryAddress: c.currentDeliveryAddress,
                  categoryImageUrl: widget.categoryImageUrl,
                  searchCtrl: _searchCtrl,
                  searchQuery: c.categoryHomeSearchQuery,
                  onSearchChanged: _onSearchChanged,
                  onClearSearch: _clearSearch,
                  onMicPressed: () => _startVoiceSearch(_searchCtrl, (v) {
                    _onSearchChanged(v);
                  }),
                ),

                SliverToBoxAdapter(
                    child: _RestaurantFilters(
                        topOnly: _topOnly,
                        fastOnly: _fastOnly,
                        highRatingOnly: _highRatingOnly,
                        sortBy: _sortBy,
                        onTopChanged: () {
                          setState(() => _topOnly = !_topOnly);
                          _applyBackendFilters();
                        },
                        onFastChanged: () {
                          setState(() => _fastOnly = !_fastOnly);
                          _applyBackendFilters();
                        },
                        onRatingChanged: () {
                          setState(() => _highRatingOnly = !_highRatingOnly);
                          _applyBackendFilters();
                        },
                        onSortChanged: (value) {
                          setState(() => _sortBy = value);
                          _applyBackendFilters();
                        })),

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
                                subCategoryName: c.categoryHomeSubCategories.firstWhere((s) => s.id == id, orElse: () => SubCategoryModel()).name ?? '',
                              ));
                        }
                      },
                    ),
                  ),

                if (_isPharmacy && c.categoryHomeSubCategories.isNotEmpty) SliverToBoxAdapter(child: _PharmacyQuickSections(controller: c)),

                const SliverToBoxAdapter(child: _RestaurantBannerCarousel()),

                if (_isPharmacy && c.categoryHomeStores.isNotEmpty) SliverToBoxAdapter(child: _ExclusivePharmacies(controller: c)),

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
                  if (c.categoryHomeStores.isNotEmpty) SliverToBoxAdapter(child: _AllCategoryStores(controller: c, categoryName: widget.categoryName, stores: _filteredStores(c.categoryHomeStores))),
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
          bottomNavigationBar: c.hasItemsInCart ? FloatingCartBar(controller: c) : null,
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
      final rawData = sec['data'] as List? ?? [];
      final maxDeliveryFee = _number(sec['max_delivery_fee']);
      final validFeeData = keyName == 'convenient_delivery' && maxDeliveryFee > 0
          ? rawData.where((item) {
              final fee = _number(_storeData(item, type)['delivery_fee']);
              return fee <= maxDeliveryFee;
            }).toList()
          : rawData;
      final dataList = _filteredSectionData(validFeeData, type);

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

  Map<dynamic, dynamic> _storeData(dynamic item, String type) {
    if (item is! Map) return const {};
    if (type == 'product' && item['store'] is Map) return item['store'] as Map;
    return item;
  }

  double _number(dynamic value) => value is num ? value.toDouble() : double.tryParse(value?.toString() ?? '') ?? 0;

  bool _matchesStoreData(Map<dynamic, dynamic> store) {
    final isTop = store['is_premium'] == true || store['is_premium'] == 1 || store['is_featured'] == true || store['is_featured'] == 1;
    final prep = _number(store['preparation_time']);
    final rating = _number(store['rating']);
    return (!_topOnly || isTop) && (!_fastOnly || (prep > 0 && prep <= 35)) && (!_highRatingOnly || rating >= 4.5);
  }

  List<dynamic> _filteredSectionData(List raw, String type) {
    final result = raw.where((item) => _matchesStoreData(_storeData(item, type))).toList();
    result.sort((a, b) {
      final aa = _storeData(a, type);
      final bb = _storeData(b, type);
      if (_sortBy == 'rating') return _number(bb['rating']).compareTo(_number(aa['rating']));
      if (_sortBy == 'fast') return _number(aa['preparation_time']).compareTo(_number(bb['preparation_time']));
      return 0;
    });
    return result;
  }

  List<StoreModel> _filteredStores(List<StoreModel> stores) {
    final result = stores.where((store) => _matchesStoreData(store.toJson())).toList();
    result.sort((a, b) {
      if (_sortBy == 'rating') return (b.rating ?? 0).compareTo(a.rating ?? 0);
      if (_sortBy == 'fast') return (a.preparationTime ?? 999).compareTo(b.preparationTime ?? 999);
      return 0;
    });
    return result;
  }

  // ── Builds the search result view ──
  Widget _buildSearchResults(DeliveryController c) {
    final query = c.categoryHomeSearchQuery.toLowerCase();
    final stores = c.categoryHomeStores.isEmpty ? <StoreModel>[] : c.categoryHomeStores.where((s) => (s.name?.toLowerCase().contains(query) ?? false) || (s.description?.toLowerCase().contains(query) ?? false) || (s.address?.toLowerCase().contains(query) ?? false)).toList();

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
            'id': s.id,
            'name': s.name,
            'image': s.image,
            'cover_image': s.coverImage,
            'description': s.description,
            'address': s.address,
            'delivery_fee': s.deliveryFee,
            'is_open': s.isOpenNow,
            'preparation_time': s.preparationTime,
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
              Text('Sin resultados para "$query"', textAlign: TextAlign.center, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
              const SizedBox(height: 4),
              Text('Prueba con otros términos', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor.withValues(alpha: 0.7))),
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
  final String deliveryAddress;
  final String? categoryImageUrl;
  final TextEditingController searchCtrl;
  final String searchQuery;
  final ValueChanged<String> onSearchChanged;
  final VoidCallback onClearSearch;
  final VoidCallback onMicPressed;

  const _ServiceSliverHeader({
    required this.categoryName,
    required this.deliveryAddress,
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
                    child: const Icon(Icons.arrow_back_ios_new_rounded, color: MyColor.primaryTextColor, size: 18),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        deliveryAddress.isEmpty ? 'Selecciona tu dirección' : deliveryAddress,
                        style: boldExtraLarge.copyWith(
                          color: MyColor.primaryTextColor,
                          fontSize: 20,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(categoryName, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
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
                        hintText: 'Buscar platos o restaurantes',
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
                        child: Icon(Icons.close_rounded, color: MyColor.bodyMutedTextColor, size: 20),
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
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════
// Subcategory Filter Pills
// ══════════════════════════════════════════════════════════

class _RestaurantFilters extends StatelessWidget {
  final bool topOnly;
  final bool fastOnly;
  final bool highRatingOnly;
  final String sortBy;
  final VoidCallback onTopChanged;
  final VoidCallback onFastChanged;
  final VoidCallback onRatingChanged;
  final ValueChanged<String> onSortChanged;

  const _RestaurantFilters({required this.topOnly, required this.fastOnly, required this.highRatingOnly, required this.sortBy, required this.onTopChanged, required this.onFastChanged, required this.onRatingChanged, required this.onSortChanged});

  @override
  Widget build(BuildContext context) {
    final sortLabel = switch (sortBy) { 'rating' => 'Mejor valoradas', 'fast' => 'Más ágiles', _ => 'Priorizar' };
    final filters = [
      (
        Icons.keyboard_arrow_down_rounded,
        sortLabel,
        sortBy != 'relevance',
        () => onSortChanged(sortBy == 'relevance'
            ? 'rating'
            : sortBy == 'rating'
                ? 'fast'
                : 'relevance')
      ),
      (Icons.emoji_events_outlined, 'Destacadas', topOnly, onTopChanged),
      (Icons.bolt_rounded, 'Entrega ágil', fastOnly, onFastChanged),
      (Icons.star_rounded, 'Muy valoradas', highRatingOnly, onRatingChanged),
    ];
    return SizedBox(
      height: 62,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
        scrollDirection: Axis.horizontal,
        physics: const BouncingScrollPhysics(),
        itemCount: filters.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (_, i) {
          final filter = filters[i];
          final icon = filter.$1;
          final label = filter.$2;
          final selected = filter.$3;
          final onTap = filter.$4;
          return GestureDetector(
            onTap: onTap,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 15),
              decoration: BoxDecoration(
                color: selected ? const Color(0xFFE8F7E8) : Colors.white,
                border: selected ? Border.all(color: MyColor.primaryColor.withValues(alpha: .4)) : null,
                borderRadius: BorderRadius.circular(24),
                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: .07), blurRadius: 12, offset: const Offset(0, 5))],
              ),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                Icon(icon, color: selected ? MyColor.primaryColor : MyColor.primaryTextColor, size: 21),
                const SizedBox(width: 7),
                Text(label, style: boldDefault.copyWith(fontSize: 15, color: selected ? MyColor.primaryColor : MyColor.primaryTextColor)),
              ]),
            ),
          );
        },
      ),
    );
  }
}

class _RestaurantBannerCarousel extends StatefulWidget {
  const _RestaurantBannerCarousel();

  @override
  State<_RestaurantBannerCarousel> createState() => _RestaurantBannerCarouselState();
}

class _RestaurantBannerCarouselState extends State<_RestaurantBannerCarousel> {
  int _page = 0;

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<HomeController>()) return const SizedBox.shrink();
    return GetBuilder<HomeController>(builder: (home) {
      final banners = home.deliveryBannersList;
      if (banners.isEmpty) return const SizedBox.shrink();
      return Column(children: [
        SizedBox(
          height: 170,
          child: PageView.builder(
            controller: PageController(viewportFraction: .9),
            itemCount: banners.length,
            onPageChanged: (value) => setState(() => _page = value),
            itemBuilder: (_, i) {
              final banner = banners[i];
              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 6),
                child: GestureDetector(
                  onTap: () async {
                    if (banner.link == null || banner.link!.isEmpty) return;
                    final uri = Uri.tryParse(banner.link!);
                    if (uri != null && await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
                  },
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(26),
                    child: MyImageWidget(imageUrl: '${home.bannerImagePath}/${banner.image}', width: double.infinity, height: 170, boxFit: BoxFit.cover),
                  ),
                ),
              );
            },
          ),
        ),
        const SizedBox(height: 9),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(
              banners.length,
              (i) => Container(
                    width: 9,
                    height: 9,
                    margin: const EdgeInsets.symmetric(horizontal: 3),
                    decoration: BoxDecoration(color: i == _page ? Colors.black : const Color(0xFFAEB7C3), shape: BoxShape.circle),
                  )),
        ),
      ]);
    });
  }
}

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
      padding: const EdgeInsets.fromLTRB(16, 18, 16, 16),
      child: SizedBox(
        height: 122,
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
                    width: 84,
                    height: 78,
                    decoration: const BoxDecoration(color: Colors.transparent),
                    padding: const EdgeInsets.all(2),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(10),
                      child: MyImageWidget(
                        imageUrl: '${controller.categoryHomeStoreImagePath}/${sub.image}',
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
                      fontSize: 13,
                      color: MyColor.primaryTextColor,
                      fontWeight: isSel ? FontWeight.w700 : FontWeight.w500,
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════
// Premium Section (horizontal card rail per section)
// ══════════════════════════════════════════════════════════

class _PharmacyQuickSections extends StatelessWidget {
  final DeliveryController controller;
  const _PharmacyQuickSections({required this.controller});

  @override
  Widget build(BuildContext context) {
    final sections = controller.categoryHomeSubCategories;
    if (sections.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 14),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Text('Encuentra lo que necesitas', style: boldExtraLarge.copyWith(fontSize: 21)),
        ),
        const SizedBox(height: 12),
        SizedBox(
          height: 108,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: sections.length,
            separatorBuilder: (_, __) => const SizedBox(width: 12),
            itemBuilder: (_, i) {
              final section = sections[i];
              return GestureDetector(
                onTap: () => Get.to(() => StoreListScreen(subCategoryId: section.id ?? 0, subCategoryName: section.name ?? '')),
                child: SizedBox(
                  width: 92,
                  child: Column(children: [
                    ClipOval(child: MyImageWidget(imageUrl: '${controller.categoryHomeStoreImagePath}/${section.image}', width: 66, height: 66, boxFit: BoxFit.contain)),
                    const SizedBox(height: 7),
                    Text(section.name ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, textAlign: TextAlign.center, style: boldDefault.copyWith(fontSize: 12)),
                  ]),
                ),
              );
            },
          ),
        ),
      ]),
    );
  }
}

class _ExclusivePharmacies extends StatelessWidget {
  final DeliveryController controller;
  const _ExclusivePharmacies({required this.controller});

  @override
  Widget build(BuildContext context) {
    final stores = controller.categoryHomeStores.where((store) => store.isPremium == true || store.isFeatured == true).toList();
    if (stores.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 28),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(children: [
            const Icon(Icons.verified_rounded, size: 21, color: Color(0xFF1474C4)),
            const SizedBox(width: 8),
            Expanded(child: Text('Farmacias exclusivas', style: boldExtraLarge.copyWith(fontSize: 21))),
          ]),
        ),
        const SizedBox(height: 14),
        SizedBox(
          height: 228,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: stores.length,
            separatorBuilder: (_, __) => const SizedBox(width: 14),
            itemBuilder: (_, i) => _ServiceStoreCard(controller: controller, store: stores[i], accent: const Color(0xFF1474C4)),
          ),
        ),
      ]),
    );
  }
}

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

    return Container(
      margin: const EdgeInsets.only(top: Dimensions.space30),
      padding: EdgeInsets.only(top: isProduct ? Dimensions.space20 : 0, bottom: isProduct ? Dimensions.space16 : 0),
      color: isProduct ? const Color(0xFFEAF8E8) : Colors.transparent,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Section header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.space16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                Container(width: 31, height: 31, margin: const EdgeInsets.only(right: 9), decoration: BoxDecoration(color: isProduct ? const Color(0xFF1D6B38) : const Color(0xFFFFF1E8), shape: BoxShape.circle), child: Icon(isProduct ? Icons.bolt_rounded : Icons.restaurant_rounded, color: isProduct ? Colors.white : MyColor.primaryColor, size: 17)),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: boldExtraLarge.copyWith(fontSize: 22, letterSpacing: isProduct ? .2 : .5, color: MyColor.primaryTextColor)),
                      if (subtitle.isNotEmpty) Text(subtitle, style: regularSmall.copyWith(letterSpacing: .25, color: MyColor.bodyMutedTextColor)),
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
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
                    decoration: BoxDecoration(
                      color: isProduct ? Colors.white : const Color(0xFFF1F3F2),
                      borderRadius: BorderRadius.circular(22),
                    ),
                    child: Text('Ver más', style: boldDefault.copyWith(color: MyColor.primaryTextColor, fontSize: 13)),
                  ),
                ),
              ],
            ),
          ),
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
                if (isProduct) return _ServiceProductCard(controller: controller, item: item, accent: accent);
                try {
                  final store = StoreModel.fromJson(item as Map<String, dynamic>);
                  return _ServiceStoreCard(controller: controller, store: store, accent: accent);
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
        width: MediaQuery.of(context).size.width * 0.64,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Stack(
                children: [
                  ColorFiltered(
                    colorFilter: store.isOpenNow ? const ColorFilter.mode(Colors.transparent, BlendMode.srcOver) : const ColorFilter.matrix(<double>[0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0, 0, 0, 1, 0]),
                    child: MyImageWidget(
                      imageUrl: store.coverImage != null ? '${controller.storeCoverPath}/${store.coverImage}' : '${controller.categoryHomeStoreImagePath}/${store.image}',
                      height: 145,
                      width: double.infinity,
                      boxFit: BoxFit.cover,
                    ),
                  ),
                  if (!store.isOpenNow) const Positioned.fill(child: ColoredBox(color: Color(0x66000000))),
                  if (!store.isOpenNow)
                    Positioned(
                      left: 9,
                      top: 9,
                      child: _ClosedStoreBadge(),
                    ),
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
                  // Time floating bottom-left
                  Positioned(
                    bottom: 8,
                    left: 8,
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
                padding: const EdgeInsets.fromLTRB(2, 10, 2, 3),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(store.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: boldLarge.copyWith(fontSize: 17, color: MyColor.primaryTextColor)),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Icon(Icons.bolt_rounded, size: 16, color: MyColor.primaryColor),
                        const SizedBox(width: 2),
                        Text('${store.preparationTime ?? 25} min', style: regularSmall.copyWith(color: MyColor.bodyTextColor, fontSize: 12)),
                        const SizedBox(width: 10),
                        Text(store.deliveryFee == 0 ? 'Envío gratis' : 'S/ ${(store.deliveryFee ?? 0).toStringAsFixed(0)}', style: regularSmall.copyWith(color: MyColor.bodyTextColor, fontSize: 12)),
                        const Spacer(),
                        Icon(Icons.star_rounded, size: 12, color: Colors.amber.shade700),
                        const SizedBox(width: 2),
                        Text(store.rating?.toStringAsFixed(1) ?? '0', style: boldDefault.copyWith(fontSize: 11, color: MyColor.primaryTextColor)),
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
    final discountPct = hasDiscount ? (((product.price! - product.discountPrice!) / product.price!) * 100).round() : 0;
    final isFavorite = store != null ? controller.isStoreFavorite(store.id ?? 0) : false;

    return GestureDetector(
      onTap: () {
        if (store != null) Get.to(() => StoreScreen(storeId: store?.id ?? 0));
      },
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
                    height: 130,
                    width: double.infinity,
                    boxFit: BoxFit.cover,
                  ),
                  // Heart favorite button floating top-right
                  Positioned(
                    top: 8,
                    right: 8,
                    child: GestureDetector(
                      onTap: () {
                        if (store != null) {
                          controller.toggleFavoriteStore(store.id ?? 0);
                          controller.update();
                        }
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
                  // Time floating bottom-left
                  Positioned(
                    bottom: 8,
                    left: 8,
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
                      top: 8,
                      left: 8,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(color: MyColor.redCancelTextColor, borderRadius: BorderRadius.circular(8)),
                        child: Text('-$discountPct%', style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: 11)),
                      ),
                    ),
                  Positioned(
                    bottom: 8,
                    right: 8,
                    child: GestureDetector(
                      onTap: () => controller.addToCart(product!, store: store),
                      child: Container(
                        width: 28,
                        height: 28,
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
                    Text(product.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: boldDefault.copyWith(fontSize: 14, color: MyColor.primaryTextColor)),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            store?.name ?? 'Restaurante',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
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

class _AllCategoryStores extends StatelessWidget {
  final DeliveryController controller;
  final String categoryName;
  final List<StoreModel> stores;
  const _AllCategoryStores({required this.controller, required this.categoryName, required this.stores});

  @override
  Widget build(BuildContext context) {
    if (stores.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 34, 16, 0),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('Más $categoryName para ti', style: boldExtraLarge.copyWith(fontSize: 22)),
        const SizedBox(height: 18),
        ...stores.map((store) => Padding(
              padding: const EdgeInsets.only(bottom: 26),
              child: GestureDetector(
                onTap: () => Get.to(() => StoreScreen(storeId: store.id ?? 0)),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Stack(children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(22),
                      child: ColorFiltered(
                        colorFilter: store.isOpenNow ? const ColorFilter.mode(Colors.transparent, BlendMode.srcOver) : const ColorFilter.matrix(<double>[0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0, 0, 0, 1, 0]),
                        child: MyImageWidget(
                          imageUrl: store.coverImage != null ? '${controller.storeCoverPath}/${store.coverImage}' : '${controller.categoryHomeStoreImagePath}/${store.image}',
                          height: 205,
                          width: double.infinity,
                          boxFit: BoxFit.cover,
                        ),
                      ),
                    ),
                    if (!store.isOpenNow) const Positioned.fill(child: ColoredBox(color: Color(0x66000000))),
                    if (!store.isOpenNow) const Positioned(top: 10, left: 10, child: _ClosedStoreBadge()),
                    if (store.isPremium == true)
                      Positioned(
                        top: 10,
                        right: 10,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                          decoration: BoxDecoration(color: Colors.black, borderRadius: BorderRadius.circular(14)),
                          child: Row(mainAxisSize: MainAxisSize.min, children: [
                            const Icon(Icons.emoji_events_rounded, color: Color(0xFFFFC928), size: 15),
                            const SizedBox(width: 4),
                            Text('TOP', style: boldDefault.copyWith(color: Colors.white, fontSize: 11)),
                          ]),
                        ),
                      ),
                  ]),
                  const SizedBox(height: 10),
                  Row(children: [
                    Expanded(child: Text(store.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: boldLarge.copyWith(fontSize: 20))),
                    const Icon(Icons.star_rounded, color: Colors.black, size: 19),
                    const SizedBox(width: 3),
                    Text(store.rating?.toStringAsFixed(1) ?? '0', style: boldDefault.copyWith(fontSize: 15)),
                  ]),
                  const SizedBox(height: 5),
                  Row(children: [
                    Icon(Icons.bolt_rounded, color: MyColor.primaryColor, size: 18),
                    Text('${store.preparationTime ?? 25} min', style: regularDefault.copyWith(fontSize: 14)),
                    const SizedBox(width: 12),
                    const Icon(Icons.delivery_dining_rounded, size: 19),
                    const SizedBox(width: 3),
                    Text(store.deliveryFee == 0 ? 'Envío gratis' : 'S/ ${(store.deliveryFee ?? 0).toStringAsFixed(2)}', style: regularDefault.copyWith(fontSize: 14)),
                    if (store.distanceFormatted != null) ...[
                      const SizedBox(width: 12),
                      Text(store.distanceFormatted!, style: regularDefault.copyWith(fontSize: 14)),
                    ],
                  ]),
                ]),
              ),
            )),
      ]),
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
                  child: ColorFiltered(
                    colorFilter: store.isOpenNow ? const ColorFilter.mode(Colors.transparent, BlendMode.srcOver) : const ColorFilter.matrix(<double>[0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0, 0, 0, 1, 0]),
                    child: MyImageWidget(
                      imageUrl: store.coverImage != null ? '${controller.storeCoverPath}/${store.coverImage}' : '${controller.categoryHomeStoreImagePath}/${store.image}',
                      height: 96,
                      width: 96,
                      boxFit: BoxFit.cover,
                    ),
                  ),
                ),
                Positioned(
                  top: 6,
                  left: 6,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                    decoration: BoxDecoration(
                      color: (store.isOpenNow ? const Color(0xFF10B981) : MyColor.redCancelTextColor).withValues(alpha: 0.9),
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
            ),
          ],
        ),
      ),
    );
  }
}

class _ClosedStoreBadge extends StatelessWidget {
  const _ClosedStoreBadge();

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
        decoration: BoxDecoration(color: const Color(0xFF4B5563), borderRadius: BorderRadius.circular(10)),
        child: Text('Cerrado', style: boldDefault.copyWith(color: Colors.white, fontSize: 10)),
      );
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
            width: 80,
            height: 80,
            decoration: BoxDecoration(
              color: MyColor.primaryColor.withValues(alpha: 0.08),
              shape: BoxShape.circle,
            ),
            child: Icon(Icons.storefront_rounded, size: 40, color: MyColor.primaryColor.withValues(alpha: 0.5)),
          ),
          const SizedBox(height: Dimensions.space16),
          Text('Sin tiendas en $categoryName', style: boldDefault.copyWith(color: MyColor.primaryTextColor)),
          const SizedBox(height: Dimensions.space8),
          Text('Intenta más tarde o cambia tu ubicación', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
        ],
      ),
    );
  }
}
