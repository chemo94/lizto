import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/url_container.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/core/helper/shared_preference_helper.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';
import 'package:lizto_delivery/data/controller/delivery/notification_inbox_controller.dart';
import 'package:lizto_delivery/data/controller/delivery/user_address_controller.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_models.dart';
import 'package:lizto_delivery/data/services/api_client.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';
import 'package:lizto_delivery/presentation/components/shimmer_loaders.dart';
import 'package:lizto_delivery/presentation/screens/delivery/notification_inbox_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/service_home_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/store_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/map_location_picker_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/all_categories_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/premium_section_detail_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/floating_cart_bar.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:lizto_delivery/presentation/components/animated_screen_entrance.dart';
import 'package:url_launcher/url_launcher.dart';
import 'dart:io' show Platform;
import 'package:lizto_delivery/presentation/screens/delivery/all_stores_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/stories_viewer_screen.dart';
import 'package:lizto_delivery/data/controller/home/home_controller.dart';
import 'package:speech_to_text/speech_to_text.dart' as stt;

import '../../../data/model/global/response_model/response_model.dart';

// ─── Category accent colors (Rappi-style pastels) ───
const List<Color> _catColors = [
  Color(0xFFFF6B6B),
  Color(0xFF4ECDC4),
  Color(0xFFFFB347),
  Color(0xFF6C63FF),
  Color(0xFF00C9A7),
  Color(0xFFFF6B9D),
  Color(0xFF3B82F6),
  Color(0xFF8B5CF6),
  Color(0xFFF59E0B),
  Color(0xFF10B981),
  Color(0xFFEC4899),
  Color(0xFF6366F1),
];

Color _catColor(int i) => _catColors[i % _catColors.length];

// ══════════════════════════════════════════════════════════════════════════════

class DeliveryHomeScreen extends StatefulWidget {
  final GlobalKey<ScaffoldState>? dashBoardScaffoldKey;
  const DeliveryHomeScreen({super.key, this.dashBoardScaffoldKey});

  @override
  State<DeliveryHomeScreen> createState() => _DeliveryHomeScreenState();
}

class _DeliveryHomeScreenState extends State<DeliveryHomeScreen> {
  final _searchCtrl = TextEditingController();
  int _subIdx = 0;
  late final Timer _timer;
  final _searchFocus = FocusNode();
  bool _searchFocused = false;

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

  static const _subtitles = [
    '¿Qué se te antoja hoy?',
    '¿Qué vas a pedir?',
    '¿Algo rico para comer?',
    '¿Necesitas algo de la tienda?',
    'Tu restaurante favorito te espera',
    '¿Un antojo de media tarde?',
    'Pide lo que quieras, sin salir de casa',
    '¿Se te acabó algo? Pídelo ya',
    'Hoy es buen día para consentirte',
  ];

  @override
  void dispose() {
    _searchCtrl.dispose();
    _timer.cancel();
    _searchFocus.dispose();
    _speech.stop();
    super.dispose();
  }

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 5), (_) {
      if (mounted) setState(() => _subIdx = (_subIdx + 1) % _subtitles.length);
    });
    _searchFocus.addListener(() {
      if (mounted) setState(() => _searchFocused = _searchFocus.hasFocus);
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<DeliveryController>();
      c.loadGeneralCategories();
      c.loadNearbyStores();
      c.loadFavoriteStores();
      c.loadStories();
      _loadHeaderAddress(c);
      if (Get.isRegistered<NotificationInboxController>()) {
        Get.find<NotificationInboxController>().loadNotificationsFromBackend();
      }
    });
  }

  Future<void> _loadHeaderAddress(DeliveryController c) async {
    final hasStoredSelection = c.userLat != null && c.userLng != null && c.currentDeliveryAddress.trim().isNotEmpty && c.currentDeliveryAddress != 'Ubicación actual detectada';
    if (hasStoredSelection) return;
    if (!Get.isRegistered<UserAddressController>()) return;
    final a = Get.find<UserAddressController>();
    await a.loadAddresses();
    final def = a.addresses.where((x) => x.isDefault).toList();
    final saved = def.isNotEmpty ? def.first : (a.addresses.isNotEmpty ? a.addresses.first : null);
    if (saved != null) {
      c.setDeliveryAddress(saved.address ?? '', lat: saved.latitude, lng: saved.longitude);
      await c.loadNearbyStores();
    }
  }

  Widget _buildWelcomeHeader(DeliveryController c) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 10),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        height: 58,
        decoration: BoxDecoration(
          color: const Color(0xFFF7F8F7),
          borderRadius: BorderRadius.circular(28),
          border: Border.all(color: const Color(0xFFE9EDEA)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.025),
              blurRadius: 12,
              offset: const Offset(0, 4),
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
                focusNode: _searchFocus,
                controller: _searchCtrl,
                style: const TextStyle(color: MyColor.primaryTextColor, fontSize: 16),
                onChanged: (v) {
                  c.deliverySearchQuery = v;
                  setState(() {});
                },
                onSubmitted: (v) => c.loadNearbyStores(query: v.trim()),
                decoration: InputDecoration(
                  hintText: '¿Qué necesitas hoy?',
                  hintStyle: TextStyle(
                    color: Colors.grey.shade400,
                    fontSize: 15,
                  ),
                  suffixIcon: _searchCtrl.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.close_rounded, color: MyColor.bodyMutedTextColor, size: 18),
                          onPressed: () {
                            _searchCtrl.clear();
                            c.deliverySearchQuery = '';
                            c.loadNearbyStores();
                            setState(() {});
                            _searchFocus.unfocus();
                          },
                        )
                      : null,
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 14),
                ),
              ),
            ),
            GestureDetector(
              onTap: () => _startVoiceSearch(_searchCtrl, (v) {
                c.deliverySearchQuery = v;
                c.loadNearbyStores(query: v);
                setState(() {});
              }),
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
      ).animatedEntrance(),
    );
  }

  @override
  Widget build(BuildContext context) => GetBuilder<DeliveryController>(
        builder: (c) => Scaffold(
          backgroundColor: const Color(0xFFFFFFFF),
          bottomNavigationBar: c.hasItemsInCart ? FloatingCartBar(controller: c) : null,
          body: Column(
            children: [
              _HeaderBar(controller: c, onMenu: () {}, showMenu: false),
              Expanded(
                child: RefreshIndicator(
                  color: MyColor.primaryColor,
                  onRefresh: () async {
                    await c.loadGeneralCategories();
                    await c.loadNearbyStores();
                    await c.loadStories();
                  },
                  child: CustomScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    slivers: [
                      SliverToBoxAdapter(
                        child: _buildWelcomeHeader(c),
                      ),
                      if (c.isLoading && c.generalCategories.isEmpty)
                        const SliverToBoxAdapter(child: ShimmerGridLoader(itemCount: 6))
                      else ...[
                        _CategoriesRow(controller: c),
                        _PromoSlider(controller: c),
                        SliverToBoxAdapter(child: _HomeStoriesSection(controller: c)),
                        ..._buildPremiumSections(c),
                        SliverToBoxAdapter(child: _FavoriteStoresRow(controller: c)),
                        _NearbyStores(controller: c),
                        SliverToBoxAdapter(child: SizedBox(height: widget.dashBoardScaffoldKey == null ? (c.hasItemsInCart ? 120 : 24) : (c.hasItemsInCart ? 160 : 100))),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      );

  List<Widget> _buildPremiumSections(DeliveryController c) {
    final q = c.deliverySearchQuery.trim().toLowerCase();
    final searching = q.isNotEmpty;
    final sections = c.premiumSections
        .map((sec) {
          final key = sec['key']?.toString() ?? '';
          final title = sec['title']?.toString() ?? '';
          final sub = sec['subtitle']?.toString() ?? '';
          final type = sec['type']?.toString() ?? 'store';
          final raw = (sec['data'] as List? ?? []).whereType<Map>();
          final filtered = searching
              ? raw.where((m) {
                  final n = (m['name']?.toString().toLowerCase() ?? '');
                  final d = (m['description']?.toString().toLowerCase() ?? '');
                  final a = type == 'product' ? '' : (m['address']?.toString().toLowerCase() ?? '');
                  return n.contains(q) || d.contains(q) || a.contains(q);
                }).toList()
              : raw.toList();
          return (filtered.isNotEmpty) ? (key, title, sub, type, filtered) : null;
        })
        .whereType<(String, String, String, String, List<Map>)>()
        .toList();

    if (sections.isEmpty && searching) {
      return [
        SliverToBoxAdapter(
            child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 60),
                child: Center(
                    child: Column(children: [
                  Icon(Icons.search_off_rounded, size: 48, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.4)),
                  SizedBox(height: 12),
                  Text('Sin resultados', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                  Text('Intenta con otros términos', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor.withValues(alpha: 0.7))),
                ]))))
      ];
    }

    return sections.map((s) => SliverToBoxAdapter(child: _PremiumRow(controller: c, keyName: s.$1, title: s.$2, subtitle: s.$3, type: s.$4, items: s.$5))).toList();
  }
}

// ═══════════════════ HEADER WITH GRADIENT ════════════════════════════════════

class _HeaderBar extends StatelessWidget {
  final DeliveryController controller;
  final VoidCallback onMenu;
  final bool showMenu;
  const _HeaderBar({required this.controller, required this.onMenu, required this.showMenu});

  @override
  Widget build(BuildContext ctx) {
    final top = MediaQuery.of(ctx).padding.top;
    final ctrl = controller;
    return Container(
      padding: EdgeInsets.fromLTRB(20, top + 10, 16, 6),
      color: Colors.transparent,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Expanded(
            child: GestureDetector(
              onTap: () async {
                final result = await Get.to<Map<String, dynamic>>(() => MapLocationPickerScreen(
                      initialLat: ctrl.userLat,
                      initialLng: ctrl.userLng,
                      title: 'Dirección de entrega',
                    ));
                if (result != null) {
                  final lat = result['lat'] as double?;
                  final lng = result['lng'] as double?;
                  final addr = result['address'] as String?;
                  if (lat != null && lng != null && addr != null) {
                    ctrl.setDeliveryAddress(addr, lat: lat, lng: lng);
                    ctrl.loadNearbyStores();
                  }
                }
              },
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: const BoxDecoration(color: Color(0xFFE8F7E8), shape: BoxShape.circle),
                    child: const Icon(Icons.location_on_rounded, size: 20, color: MyColor.primaryColor),
                  ),
                  const SizedBox(width: 9),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text('ENTREGAR EN', style: boldDefault.copyWith(fontSize: 10, letterSpacing: .7, color: MyColor.bodyMutedTextColor)),
                        Row(children: [
                          Flexible(child: Text(ctrl.currentDeliveryAddress.isEmpty ? 'Selecciona tu dirección' : ctrl.currentDeliveryAddress, maxLines: 1, overflow: TextOverflow.ellipsis, style: boldDefault.copyWith(color: MyColor.primaryTextColor, fontSize: 16, fontWeight: FontWeight.w700))),
                          const Icon(Icons.keyboard_arrow_down_rounded, size: 19, color: MyColor.primaryTextColor),
                        ]),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 12),
          GetBuilder<NotificationInboxController>(
            builder: (ib) {
              final n = ib.unreadCount;
              return GestureDetector(
                onTap: () => Get.to(() => const NotificationInboxScreen()),
                child: Stack(
                  clipBehavior: Clip.none,
                  children: [
                    Container(
                      width: 42,
                      height: 42,
                      decoration: const BoxDecoration(color: Color(0xFFF5F6F5), shape: BoxShape.circle),
                      child: const Icon(Icons.notifications_none_rounded, color: MyColor.primaryTextColor, size: 24),
                    ),
                    if (n > 0)
                      Positioned(
                        right: 2,
                        top: 2,
                        child: Container(
                          width: 8,
                          height: 8,
                          decoration: const BoxDecoration(
                            color: Colors.red,
                            shape: BoxShape.circle,
                          ),
                        ),
                      ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}

// ═══════════════════ PROMO SLIDER ════════════════════════════════════════════

class _PromoSlider extends StatefulWidget {
  final DeliveryController controller;
  const _PromoSlider({required this.controller});

  @override
  State<_PromoSlider> createState() => _PromoSliderState();
}

class _PromoSliderState extends State<_PromoSlider> {
  late final PageController _pageController;
  late final Timer _timer;
  int _currentPage = 0;

  static const _banners = [
    _BannerData('Promos del día', 'Descuentos y entregas rápidas para ti.', Icons.local_offer_rounded, Color(0xFFFF6B6B), 'Ver promos', _BannerAction.promos),
    _BannerData('Favor Express', 'Recogemos compras, documentos o encargos.', Icons.delivery_dining_rounded, Color(0xFF4ECDC4), 'Solicitar favor', _BannerAction.favor),
    _BannerData('Pide a tu tienda', 'Explora restaurantes, bodegas y más cerca de ti.', Icons.store_rounded, Color(0xFF6C63FF), 'Ver tiendas', _BannerAction.stores),
    _BannerData('Gana con Lizto', '¿Tienes moto o auto? Gana dinero repartiendo pedidos.', Icons.motorcycle_rounded, Color(0xFFF59E0B), 'Ser repartidor', _BannerAction.courier),
  ];

  @override
  void initState() {
    super.initState();
    _pageController = PageController(viewportFraction: 0.92);
    _timer = Timer.periodic(const Duration(seconds: 4), (timer) {
      if (_pageController.hasClients) {
        final homeCtrl = Get.find<HomeController>();
        final count = homeCtrl.deliveryBannersList.length;
        if (count == 0) return;
        int next = (_currentPage + 1) % count;
        _pageController.animateToPage(
          next,
          duration: const Duration(milliseconds: 600),
          curve: Curves.easeInOutQuint,
        );
      }
    });
  }

  @override
  void dispose() {
    _timer.cancel();
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext ctx) {
    final homeController = Get.find<HomeController>();
    final dynamicBanners = homeController.deliveryBannersList;
    final bannerImagePath = homeController.bannerImagePath;
    final count = dynamicBanners.length;

    // La promoción es contenido administrado. Si no hay publicaciones en API,
    // no se muestran campañas locales ficticias.
    if (count == 0) return const SliverToBoxAdapter(child: SizedBox.shrink());

    return SliverToBoxAdapter(
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 18, bottom: 12),
            child: SizedBox(
              height: 210,
              child: PageView.builder(
                controller: _pageController,
                onPageChanged: (index) {
                  setState(() {
                    _currentPage = index;
                  });
                },
                itemCount: count,
                itemBuilder: (context, i) {
                  if (dynamicBanners.isNotEmpty) {
                    final banner = dynamicBanners[i];
                    final imageUrl = '$bannerImagePath/${banner.image}';
                    return Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 6),
                      child: GestureDetector(
                        onTap: () async {
                          if (banner.link != null && banner.link!.isNotEmpty) {
                            final uri = Uri.parse(banner.link!);
                            if (await canLaunchUrl(uri)) {
                              await launchUrl(uri, mode: LaunchMode.externalApplication);
                            }
                          }
                        },
                        child: Container(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(24),
                            color: Colors.white,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.04),
                                blurRadius: 16,
                                offset: const Offset(0, 6),
                              )
                            ],
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(24),
                            child: MyImageWidget(
                              imageUrl: imageUrl,
                              width: double.infinity,
                              height: 210,
                              radius: 24,
                              boxFit: BoxFit.cover,
                            ),
                          ),
                        ),
                      ),
                    );
                  }

                  final b = _banners[i];
                  // Use primary color or brand gradients instead of generic red/green/purple
                  final bannerColor = i == 0 ? MyColor.primaryColor : b.color;
                  return Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 6),
                    child: Container(
                      padding: const EdgeInsets.all(18),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            bannerColor,
                            bannerColor.withValues(alpha: 0.8),
                          ],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(24),
                        boxShadow: [
                          BoxShadow(
                            color: bannerColor.withValues(alpha: 0.15),
                            blurRadius: 12,
                            offset: const Offset(0, 6),
                          )
                        ],
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  b.title,
                                  style: boldLarge.copyWith(color: MyColor.colorWhite, fontSize: 18),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  b.subtitle,
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: regularSmall.copyWith(
                                    color: MyColor.colorWhite.withValues(alpha: 0.85),
                                    height: 1.3,
                                  ),
                                ),
                                const SizedBox(height: 12),
                                GestureDetector(
                                  onTap: () {
                                    switch (b.action) {
                                      case _BannerAction.promos:
                                        Get.to(() => const AllCategoriesScreen());
                                      case _BannerAction.favor:
                                        Get.offAllNamed('/dashboard_screen', arguments: 1);
                                      case _BannerAction.stores:
                                        Get.to(() => const AllStoresScreen());
                                      case _BannerAction.courier:
                                        _openAppStore();
                                    }
                                  },
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                    decoration: BoxDecoration(
                                      color: Colors.black,
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: Text(
                                      b.buttonText,
                                      style: boldDefault.copyWith(
                                        color: Colors.white,
                                        fontSize: 12,
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 12),
                          Container(
                            width: 66,
                            height: 66,
                            decoration: BoxDecoration(
                              color: MyColor.colorWhite.withValues(alpha: 0.15),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(b.icon, color: MyColor.colorWhite, size: 32),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(count, (idx) {
              final active = _currentPage == idx;
              return AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                margin: const EdgeInsets.symmetric(horizontal: 3),
                height: 9,
                width: 9,
                decoration: BoxDecoration(
                  color: active ? Colors.black : const Color(0xFFAEB7C3),
                  shape: BoxShape.circle,
                ),
              );
            }),
          ),
          const SizedBox(height: 8),
        ],
      ).animatedEntrance(),
    );
  }
}

class _BannerData {
  final String title, subtitle, buttonText;
  final IconData icon;
  final Color color;
  final _BannerAction action;
  const _BannerData(this.title, this.subtitle, this.icon, this.color, this.buttonText, this.action);
}

enum _BannerAction { promos, favor, stores, courier }

class _CategoriesRow extends StatelessWidget {
  final DeliveryController controller;
  const _CategoriesRow({required this.controller});

  @override
  Widget build(BuildContext ctx) {
    final cats = controller.generalCategories;
    if (cats.isEmpty) return const SliverToBoxAdapter(child: SizedBox.shrink());
    final visibleCats = cats.take(7).toList();
    final featured = visibleCats.take(2).toList();
    final shortcuts = visibleCats.skip(2).toList();
    return SliverToBoxAdapter(
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
          child: Row(children: [
            Expanded(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Explora cerca de ti', style: boldExtraLarge.copyWith(fontSize: 21)),
              const SizedBox(height: 2),
              Text('Todo lo que necesitas, en un solo lugar', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
            ])),
            GestureDetector(
              onTap: () => Get.to(() => const AllCategoriesScreen()),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
                decoration: BoxDecoration(color: const Color(0xFFE8F7E8), borderRadius: BorderRadius.circular(18)),
                child: Text('Ver todo', style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 12)),
              ),
            ),
          ]),
        ).animatedEntrance(),
        const SizedBox(height: 14),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(
            children: List.generate(featured.length, (i) => Expanded(child: Padding(padding: EdgeInsets.only(right: i == 0 && featured.length > 1 ? 12 : 0), child: _MarketplaceCategoryTile(controller: controller, category: featured[i], index: i).animatedStagger(index: i)))),
          ),
        ),
        if (shortcuts.isNotEmpty) ...[
          const SizedBox(height: 14),
          SizedBox(
            height: 100,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              physics: const BouncingScrollPhysics(),
              itemCount: shortcuts.length,
              separatorBuilder: (_, __) => const SizedBox(width: 11),
              itemBuilder: (_, i) => _MarketplaceCategoryTile(controller: controller, category: shortcuts[i], index: i + 2, compact: true).animatedStagger(index: i + 2),
            ),
          ),
        ],
      ]),
    );
  }
}

class _MarketplaceCategoryTile extends StatelessWidget {
  final DeliveryController controller;
  final dynamic category;
  final int index;
  final bool compact;
  const _MarketplaceCategoryTile({required this.controller, required this.category, required this.index, this.compact = false});

  static const _backgrounds = [Color(0xFFFFF1E8), Color(0xFFE9F8E5), Color(0xFFEAF2FF), Color(0xFFFFF6D9), Color(0xFFF3ECFF), Color(0xFFE5F8F7)];

  @override
  Widget build(BuildContext context) {
    final isFavor = category.isFavorCategory == true;
    return GestureDetector(
      onTap: () => isFavor ? Get.offAllNamed('/dashboard_screen', arguments: 1) : Get.to(() => ServiceHomeScreen(categoryId: category.id ?? 0, categoryName: category.name ?? '', categoryImageUrl: '${controller.categoryImagePath}/${category.image}')),
      child: Container(
        width: compact ? 94 : null,
        height: compact ? 100 : 152,
        padding: compact ? const EdgeInsets.fromLTRB(10, 8, 10, 7) : const EdgeInsets.fromLTRB(14, 13, 9, 11),
        decoration: BoxDecoration(color: _backgrounds[index % _backgrounds.length], borderRadius: BorderRadius.circular(22)),
        child: compact
            ? Column(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                SizedBox(width: 47, height: 47, child: MyImageWidget(imageUrl: '${controller.categoryImagePath}/${category.image}', boxFit: BoxFit.contain)),
                Text(category.name?.toString() ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, textAlign: TextAlign.center, style: boldDefault.copyWith(fontSize: 11, color: MyColor.primaryTextColor)),
              ])
            : Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Expanded(child: Align(alignment: Alignment.topRight, child: SizedBox(width: 76, height: 76, child: MyImageWidget(imageUrl: '${controller.categoryImagePath}/${category.image}', boxFit: BoxFit.contain)))),
                Text(category.name?.toString() ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: boldLarge.copyWith(fontSize: 18, color: MyColor.primaryTextColor)),
                const SizedBox(height: 2),
                Text(index == 0 ? 'Pide ahora' : 'Ver tiendas', style: regularSmall.copyWith(fontSize: 11, color: MyColor.bodyMutedTextColor)),
              ]),
      ),
    );
  }
}

class _MarketplaceValueStrip extends StatelessWidget {
  const _MarketplaceValueStrip();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 0),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        decoration: BoxDecoration(
          color: const Color(0xFF123D2A),
          borderRadius: BorderRadius.circular(22),
        ),
        child: Row(children: [
          Container(
            width: 42,
            height: 42,
            decoration: const BoxDecoration(color: Color(0xFFB9F547), shape: BoxShape.circle),
            child: const Icon(Icons.bolt_rounded, color: Color(0xFF123D2A), size: 24),
          ),
          const SizedBox(width: 12),
          Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Tu barrio, a un toque', style: boldDefault.copyWith(color: Colors.white, fontSize: 15)),
            const SizedBox(height: 2),
            Text('Comida, súper, farmacia y mucho más.', style: regularSmall.copyWith(color: Colors.white.withValues(alpha: .74), fontSize: 12)),
          ])),
        ]),
      ),
    ).animatedEntrance(delay: const Duration(milliseconds: 100));
  }
}

// ═══════════════════ PREMIUM ROW ═════════════════════════════════════════════

class _PremiumRow extends StatelessWidget {
  final DeliveryController controller;
  final String keyName, title, subtitle, type;
  final List<Map> items;
  const _PremiumRow({required this.controller, required this.keyName, required this.title, required this.subtitle, required this.type, required this.items});

  static const _palette = [Color(0xFF6C63FF), Color(0xFFFF6B6B), Color(0xFF00C9A7), Color(0xFFFFB347), Color(0xFF4ECDC4), Color(0xFFFF6B9D)];
  Color get _accent => _palette[keyName.codeUnits.fold(0, (a, b) => a + b) % _palette.length];

  @override
  Widget build(BuildContext ctx) {
    final isProduct = type == 'product';
    return Container(
        margin: const EdgeInsets.only(top: 34),
        padding: EdgeInsets.only(top: isProduct ? 22 : 0, bottom: isProduct ? 20 : 0),
        color: isProduct ? const Color(0xFFEAF8E8) : Colors.transparent,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(crossAxisAlignment: CrossAxisAlignment.center, children: [
                Container(
                  width: 32,
                  height: 32,
                  margin: const EdgeInsets.only(right: 9),
                  decoration: BoxDecoration(color: isProduct ? const Color(0xFF1D6B38) : const Color(0xFFFFF1E8), shape: BoxShape.circle),
                  child: Icon(isProduct ? Icons.bolt_rounded : Icons.storefront_outlined, size: 18, color: isProduct ? Colors.white : MyColor.primaryColor),
                ),
                Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text(title, style: boldLarge.copyWith(fontSize: 18)),
                  if (subtitle.isNotEmpty) Text(subtitle, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                ])),
                GestureDetector(
                  onTap: () => Get.to(() => PremiumSectionDetailScreen(keyName: keyName, title: title, subtitle: subtitle, type: type, dataList: items, accentColor: _accent)),
                  child: Container(padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9), decoration: BoxDecoration(color: isProduct ? Colors.white : const Color(0xFFF1F3F2), borderRadius: BorderRadius.circular(22)), child: Text('Ver más', style: boldDefault.copyWith(color: MyColor.primaryTextColor, fontSize: 13))),
                ),
              ])).animatedEntrance(),
          const SizedBox(height: 14),
          SizedBox(
              height: isProduct ? 256 : 230,
              child: ListView.separated(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                scrollDirection: Axis.horizontal,
                physics: const BouncingScrollPhysics(),
                itemCount: items.length,
                separatorBuilder: (_, __) => const SizedBox(width: 14),
                itemBuilder: (_, i) {
                  final item = items[i];
                  if (isProduct) {
                    ProductModel? p;
                    StoreModel? s;
                    try {
                      p = ProductModel.fromJson(Map<String, dynamic>.from(item));
                      if (item['store'] != null) s = StoreModel.fromJson(Map<String, dynamic>.from(item['store'] as Map));
                    } catch (_) {
                      return const SizedBox.shrink();
                    }
                    if (p == null) return const SizedBox.shrink();
                    final prod = p;
                    final hasDisc = prod.discountPrice != null && prod.discountPrice! < (prod.price ?? 0);
                    final pct = hasDisc ? (((prod.price! - prod.discountPrice!) / prod.price!) * 100).round() : 0;
                    final isFavorite = controller.isStoreFavorite(s?.id ?? 0);
                    return GestureDetector(
                      onTap: () {
                        if (s != null) {
                          final sid = s.id ?? 0;
                          Get.to(() => StoreScreen(storeId: sid));
                        }
                      },
                      child: Container(
                        width: 168,
                        decoration: BoxDecoration(
                          color: MyColor.colorWhite,
                          borderRadius: BorderRadius.circular(18),
                        ),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(18),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Stack(
                                children: [
                                  ClipRRect(
                                    borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
                                    child: MyImageWidget(imageUrl: '${controller.productImagePath}/${prod.image}', height: 126, width: double.infinity, boxFit: BoxFit.cover),
                                  ),
                                  // Heart favorite button floating top-right
                                  Positioned(
                                    top: 8,
                                    right: 8,
                                    child: GestureDetector(
                                      onTap: () {
                                        if (s != null) {
                                          controller.toggleFavoriteStore(s.id ?? 0);
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
                                            '${(prod.id ?? 5) % 15 + 20} min',
                                            style: boldDefault.copyWith(fontSize: 10, color: Colors.grey.shade800),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                  if (hasDisc)
                                    Positioned(
                                      top: 8,
                                      left: 8,
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                        decoration: BoxDecoration(color: MyColor.redCancelTextColor, borderRadius: BorderRadius.circular(8)),
                                        child: Text('-$pct%', style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: 11)),
                                      ),
                                    ),
                                  Positioned(
                                    bottom: 8,
                                    right: 8,
                                    child: GestureDetector(
                                      onTap: () {
                                        final hasVariations = (prod.variations != null && prod.variations!.isNotEmpty);
                                        if (hasVariations && s != null) {
                                          final sid = s.id ?? 0;
                                          Get.to(() => StoreScreen(storeId: sid));
                                        } else {
                                          controller.addToCart(prod, store: s);
                                        }
                                      },
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
                                    Text(prod.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: boldDefault.copyWith(fontSize: 14, color: MyColor.primaryTextColor)),
                                    const SizedBox(height: 2),
                                    Row(
                                      children: [
                                        Expanded(child: Text(s?.name ?? 'Restaurante', maxLines: 1, overflow: TextOverflow.ellipsis, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11))),
                                        Icon(Icons.star_rounded, size: 12, color: Colors.amber.shade700),
                                        const SizedBox(width: 2),
                                        Text(s?.rating?.toStringAsFixed(1) ?? '0', style: boldDefault.copyWith(fontSize: 11, color: MyColor.primaryTextColor)),
                                      ],
                                    ),
                                    const SizedBox(height: 6),
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        const Spacer(),
                                        Text(
                                          'S/ ${(hasDisc ? prod.discountPrice! : (prod.price ?? 0)).toStringAsFixed(2)}',
                                          style: boldLarge.copyWith(color: MyColor.primaryTextColor, fontSize: 15),
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
                  } else {
                    StoreModel store;
                    try {
                      store = StoreModel.fromJson(Map<String, dynamic>.from(item));
                    } catch (_) {
                      return const SizedBox.shrink();
                    }
                    final isFavorite = controller.isStoreFavorite(store.id ?? 0);
                    return GestureDetector(
                      onTap: () => Get.to(() => StoreScreen(storeId: store.id ?? 0)),
                      child: Container(
                        width: MediaQuery.of(ctx).size.width * 0.64,
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(18),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Stack(
                                children: [
                                  ClipRRect(
                                    borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
                                    child: ColorFiltered(
                                      colorFilter: store.isOpenNow ? const ColorFilter.mode(Colors.transparent, BlendMode.srcOver) : const ColorFilter.matrix(<double>[0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0, 0, 0, 1, 0]),
                                      child: MyImageWidget(imageUrl: store.coverImage != null ? '${controller.storeCoverPath}/${store.coverImage}' : '${controller.storeImagePath}/${store.image}', height: 142, width: double.infinity, boxFit: BoxFit.cover),
                                    ),
                                  ),
                                  if (!store.isOpenNow) const Positioned.fill(child: ColoredBox(color: Color(0x66000000))),
                                  if (!store.isOpenNow)
                                    Positioned(
                                      top: 8,
                                      left: 8,
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(color: const Color(0xFF4B5563), borderRadius: BorderRadius.circular(10)),
                                        child: Text('Cerrado', style: boldDefault.copyWith(color: Colors.white, fontSize: 10)),
                                      ),
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
                                padding: const EdgeInsets.fromLTRB(2, 10, 2, 2),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(store.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: boldLarge.copyWith(fontSize: 17, color: MyColor.primaryTextColor)),
                                    const SizedBox(height: 4),
                                    Row(
                                      children: [
                                        Icon(Icons.bolt_rounded, size: 16, color: MyColor.primaryColor),
                                        const SizedBox(width: 2),
                                        Text('${(store.id ?? 5) % 15 + 15} min', style: regularSmall.copyWith(color: MyColor.bodyTextColor, fontSize: 12)),
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
                },
              )),
        ]));
  }
}

class _HomeStoriesSection extends StatelessWidget {
  final DeliveryController controller;
  const _HomeStoriesSection({required this.controller});

  @override
  Widget build(BuildContext context) {
    if (controller.stories.isEmpty && !controller.isLoadingStories) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 30),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(children: [
            const Icon(Icons.auto_awesome_rounded, color: Color(0xFFFFA000), size: 22),
            const SizedBox(width: 8),
            Text('Novedades de tiendas', style: boldExtraLarge.copyWith(fontSize: 20)),
          ]),
        ),
        const SizedBox(height: 10),
        _StoriesBar(controller: controller),
      ]),
    );
  }
}

class _FavoriteStoresRow extends StatelessWidget {
  final DeliveryController controller;
  const _FavoriteStoresRow({required this.controller});

  @override
  Widget build(BuildContext context) {
    final stores = controller.favoriteStores;
    if (stores.isEmpty) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 34),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Row(children: [
            const Icon(Icons.favorite_rounded, color: Color(0xFFFF4F5E), size: 22),
            const SizedBox(width: 8),
            Expanded(child: Text('Tus tiendas favoritas', style: boldExtraLarge.copyWith(fontSize: 20))),
            GestureDetector(
              onTap: () => Get.to(() => const AllStoresScreen()),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 9),
                decoration: BoxDecoration(color: const Color(0xFFF1F3F2), borderRadius: BorderRadius.circular(22)),
                child: Text('Ver más', style: boldDefault.copyWith(fontSize: 13, color: MyColor.primaryTextColor)),
              ),
            ),
          ]),
        ),
        const SizedBox(height: 15),
        SizedBox(
          height: 210,
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            physics: const BouncingScrollPhysics(),
            itemCount: stores.length,
            separatorBuilder: (_, __) => const SizedBox(width: 14),
            itemBuilder: (_, i) {
              final store = stores[i];
              return GestureDetector(
                onTap: () => Get.to(() => StoreScreen(storeId: store.id ?? 0)),
                child: SizedBox(
                  width: 205,
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(18),
                      child: MyImageWidget(
                        imageUrl: store.coverImage != null ? '${controller.storeCoverPath}/${store.coverImage}' : '${controller.storeImagePath}/${store.image}',
                        width: double.infinity,
                        height: 132,
                        boxFit: BoxFit.cover,
                      ),
                    ),
                    const SizedBox(height: 9),
                    Text(store.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: boldLarge.copyWith(fontSize: 16)),
                    const SizedBox(height: 4),
                    Row(children: [
                      Icon(Icons.bolt_rounded, size: 16, color: MyColor.primaryColor),
                      Text('${(store.id ?? 5) % 15 + 15} min', style: regularSmall.copyWith(fontSize: 12, color: MyColor.bodyTextColor)),
                      const SizedBox(width: 8),
                      Icon(Icons.star_rounded, size: 15, color: Colors.amber.shade700),
                      const SizedBox(width: 2),
                      Text(store.rating?.toStringAsFixed(1) ?? '0', style: boldDefault.copyWith(fontSize: 12)),
                    ]),
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

// ═══════════════════ NEARBY STORES ═══════════════════════════════════════════

class _NearbyStores extends StatelessWidget {
  final DeliveryController controller;
  const _NearbyStores({required this.controller});

  @override
  Widget build(BuildContext ctx) {
    final stores = controller.nearbyStores;
    if (stores.isEmpty) return const SliverToBoxAdapter(child: SizedBox.shrink());
    return SliverToBoxAdapter(
      child: Padding(
        padding: const EdgeInsets.only(top: 34),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Row(children: [
                Container(width: 32, height: 32, margin: const EdgeInsets.only(right: 9), decoration: const BoxDecoration(color: Color(0xFFEAF2FF), shape: BoxShape.circle), child: const Icon(Icons.near_me_outlined, size: 18, color: MyColor.primaryColor)),
                Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Tiendas cerca de ti', style: boldExtraLarge.copyWith(fontSize: 20)),
                  const SizedBox(height: 2),
                  Text('Descubre negocios de tu zona', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                ])),
                GestureDetector(
                  onTap: () => Get.to(() => const AllStoresScreen()),
                  child: Container(width: 38, height: 38, decoration: const BoxDecoration(color: Color(0xFFF1F3F2), shape: BoxShape.circle), child: const Icon(Icons.arrow_forward_ios_rounded, size: 16, color: MyColor.primaryTextColor)),
                ),
              ])).animatedEntrance(),
          const SizedBox(height: 14),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Column(
              children: List.generate(stores.length, (i) {
                final s = stores[i];
                final isFavorite = controller.isStoreFavorite(s.id ?? 0);
                return Padding(
                  padding: const EdgeInsets.only(bottom: 24),
                  child: GestureDetector(
                    onTap: () => Get.to(() => StoreScreen(storeId: s.id ?? 0)),
                    child: Container(
                      width: double.infinity,
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(18),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Stack(
                              children: [
                                ClipRRect(
                                  borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
                                  child: ColorFiltered(
                                    colorFilter: s.isOpenNow ? const ColorFilter.mode(Colors.transparent, BlendMode.srcOver) : const ColorFilter.matrix(<double>[0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0.2126, 0.7152, 0.0722, 0, 0, 0, 0, 0, 1, 0]),
                                    child: MyImageWidget(imageUrl: s.coverImage != null ? '${controller.storeCoverPath}/${s.coverImage}' : '${controller.storeImagePath}/${s.image}', height: 205, width: double.infinity, boxFit: BoxFit.cover),
                                  ),
                                ),
                                if (!s.isOpenNow) const Positioned.fill(child: ColoredBox(color: Color(0x66000000))),
                                if (!s.isOpenNow)
                                  Positioned(
                                    top: 8,
                                    left: 8,
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(color: const Color(0xFF4B5563), borderRadius: BorderRadius.circular(10)),
                                      child: Text('Cerrado', style: boldDefault.copyWith(color: Colors.white, fontSize: 10)),
                                    ),
                                  ),
                                // Heart favorite button floating top-right
                                Positioned(
                                  top: 8,
                                  right: 8,
                                  child: GestureDetector(
                                    onTap: () {
                                      controller.toggleFavoriteStore(s.id ?? 0);
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
                                          '${(s.id ?? 5) % 15 + 20} min',
                                          style: boldDefault.copyWith(fontSize: 10, color: Colors.grey.shade800),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            Padding(
                              padding: const EdgeInsets.fromLTRB(2, 12, 2, 3),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(s.name ?? '', maxLines: 1, overflow: TextOverflow.ellipsis, style: boldExtraLarge.copyWith(fontSize: 20, color: MyColor.primaryTextColor)),
                                  const SizedBox(height: 4),
                                  Row(
                                    children: [
                                      Icon(Icons.bolt_rounded, size: 16, color: MyColor.primaryColor),
                                      const SizedBox(width: 2),
                                      Text('${(s.id ?? 5) % 15 + 15} min', style: regularSmall.copyWith(color: MyColor.bodyTextColor, fontSize: 12)),
                                      const SizedBox(width: 10),
                                      Text(s.deliveryFee == 0 ? 'Envío gratis' : 'S/ ${(s.deliveryFee ?? 0).toStringAsFixed(0)}', style: regularSmall.copyWith(color: MyColor.bodyTextColor, fontSize: 12)),
                                      const Spacer(),
                                      Icon(Icons.star_rounded, size: 12, color: Colors.amber.shade700),
                                      const SizedBox(width: 2),
                                      Text(s.rating?.toStringAsFixed(1) ?? '0', style: boldDefault.copyWith(fontSize: 11, color: MyColor.primaryTextColor)),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ).animatedStagger(index: i, staggerDelay: const Duration(milliseconds: 80));
              }),
            ),
          ),
        ]),
      ),
    );
  }
}

// ═══════════════════ ADDRESS PICKER BOTTOM SHEET ═════════════════════════════

class _AddressPicker extends StatelessWidget {
  final DeliveryController controller;
  final List<dynamic> addresses;
  const _AddressPicker({required this.controller, required this.addresses});

  @override
  Widget build(BuildContext ctx) => Container(
        decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
        padding: EdgeInsets.all(20),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(width: 40, height: 4, decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2))),
          SizedBox(height: 16),
          Text('Seleccionar dirección', style: boldLarge),
          SizedBox(height: 16),
          if (addresses.isEmpty)
            Padding(padding: EdgeInsets.symmetric(vertical: 24), child: Text('No tienes direcciones guardadas', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
          else
            ...addresses.map((a) => ListTile(
                  leading: Icon(Icons.location_on_rounded, color: a.isDefault ? MyColor.primaryColor : MyColor.bodyMutedTextColor),
                  title: Text(a.address ?? 'Sin direccion', style: regularDefault.copyWith(fontWeight: a.isDefault ? FontWeight.bold : FontWeight.normal)),
                  trailing: a.isDefault ? Icon(Icons.check_circle_rounded, color: MyColor.primaryColor, size: 22) : null,
                  onTap: () {
                    controller.setDeliveryAddress(a.address ?? '', lat: a.latitude, lng: a.longitude);
                    controller.loadNearbyStores();
                    Get.back();
                  },
                )),
          SizedBox(height: 12),
        ]),
      );
}

// ═══════════════════ COVERAGE ZONE BANNER ══════════════════════════════════════

class _CoverageZoneBanner extends StatelessWidget {
  final DeliveryController controller;
  const _CoverageZoneBanner({required this.controller});

  @override
  Widget build(BuildContext ctx) => Container(
        margin: const EdgeInsets.fromLTRB(16, 8, 16, 0),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFFFFF3E0),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFFF9800).withValues(alpha: 0.3), width: 1),
        ),
        child: Row(
          children: [
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: const Color(0xFFFF9800).withValues(alpha: 0.15),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.location_off_rounded, color: Color(0xFFFF9800), size: 24),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    controller.coverageMessage ?? 'Fuera de zona de cobertura',
                    style: boldDefault.copyWith(color: const Color(0xFFE65100), fontSize: 14),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Cambia tu ubicación para ver tiendas disponibles',
                    style: regularSmall.copyWith(color: const Color(0xFFBF360C).withValues(alpha: 0.8), fontSize: 12),
                  ),
                ],
              ),
            ),
            GestureDetector(
              onTap: () async {
                final result = await Get.to<Map<String, dynamic>>(() => MapLocationPickerScreen(
                      initialLat: controller.userLat,
                      initialLng: controller.userLng,
                      title: 'Seleccionar ubicación',
                    ));
                if (result != null) {
                  final lat = result['lat'] as double?;
                  final lng = result['lng'] as double?;
                  final addr = result['address'] as String?;
                  if (lat != null && lng != null && addr != null) {
                    controller.setDeliveryAddress(addr, lat: lat, lng: lng);
                    await controller.loadNearbyStores();
                  }
                }
              },
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: const Color(0xFFFF9800),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text('Cambiar', style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: 12)),
              ),
            ),
          ],
        ),
      );
}

void _openAppStore() async {
  final url = Platform.isAndroid ? 'https://play.google.com/store/apps/details?id=com.liztogo.repartidor' : 'https://apps.apple.com/app/id123456789';
  if (await canLaunchUrl(Uri.parse(url))) {
    await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
  }
}

class _StoriesBar extends StatelessWidget {
  final DeliveryController controller;
  const _StoriesBar({required this.controller});

  @override
  Widget build(BuildContext context) {
    if (controller.isLoadingStories) {
      return const SizedBox(
        height: 110,
        child: Center(child: CircularProgressIndicator(color: MyColor.primaryColor)),
      );
    }

    if (controller.stories.isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      height: 110,
      margin: const EdgeInsets.only(bottom: 8),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: controller.stories.length,
        itemBuilder: (context, index) {
          final store = controller.stories[index];
          final storeId = store['store_id'] is int ? store['store_id'] as int : int.tryParse(store['store_id']?.toString() ?? '');
          final storeName = store['store_name']?.toString() ?? '';
          final storeImg = store['store_image']?.toString() ?? '';
          final unseenCount = store['unseen_count'] as int? ?? 0;
          final previewImage = store['preview_image']?.toString() ?? '';

          final mediaUrl = previewImage.isNotEmpty ? '${controller.storyBasePath}$previewImage' : '${UrlContainer.domainUrl}/assets/images/store/$storeImg';

          if (storeId == null) return const SizedBox.shrink();
          return GestureDetector(
            onTap: () async {
              ResponseModel r = await controller.deliveryRepo.apiClient.request(
                '${UrlContainer.baseUrl}delivery/stories/$storeId',
                'get',
                null,
                passHeader: true,
              );
              if (r.statusCode == 200) {
                final json = r.responseJson;
                if (json['status'] == 'success' && json['data'] != null) {
                  final storiesList = json['data']['stories'] as List? ?? [];
                  if (storiesList.isNotEmpty) {
                    Get.to(() => StoriesViewerScreen(
                          storeId: storeId,
                          storeName: storeName,
                          storeImage: '${UrlContainer.domainUrl}/assets/images/store/$storeImg',
                          stories: storiesList,
                          mediaPath: controller.storyBasePath,
                        ))?.then((_) {
                      controller.loadStories();
                    });
                  }
                }
              }
            },
            child: Container(
              margin: const EdgeInsets.only(right: 16),
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: LinearGradient(
                        colors: unseenCount > 0 ? [const Color(0xFFFF4B2B), const Color(0xFFFF416C)] : [Colors.grey.shade300, Colors.grey.shade400],
                      ),
                    ),
                    child: Container(
                      padding: const EdgeInsets.all(2),
                      decoration: const BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                      ),
                      child: ClipOval(
                        child: MyImageWidget(
                          imageUrl: mediaUrl,
                          width: 64,
                          height: 64,
                          boxFit: BoxFit.cover,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 6),
                  SizedBox(
                    width: 72,
                    child: Text(
                      storeName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: regularSmall.copyWith(
                        color: MyColor.primaryTextColor,
                        fontWeight: unseenCount > 0 ? FontWeight.bold : FontWeight.normal,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
