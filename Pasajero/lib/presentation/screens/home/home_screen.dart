import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/connectivity_controller.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/data/controller/delivery/delivery_notification_service.dart';
import 'package:liztogo/data/controller/delivery/favor_controller.dart';
import 'package:liztogo/data/controller/delivery/notification_inbox_controller.dart';
import 'package:liztogo/data/controller/delivery/user_address_controller.dart';
import 'package:liztogo/data/controller/delivery/wallet_controller.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/data/controller/location/app_location_controller.dart';
import 'package:liztogo/core/route/route.dart';

import 'package:liztogo/data/controller/map/home_map_controller.dart';
import 'package:liztogo/data/repo/delivery/delivery_repo.dart';
import 'package:liztogo/data/repo/delivery/favor_repo.dart';
import 'package:liztogo/data/repo/delivery/wallet_repo.dart';
import 'package:liztogo/data/repo/home/home_repo.dart';
import 'package:liztogo/environment.dart';

import 'package:liztogo/presentation/screens/home/section/ride_create_form.dart';
import 'package:liztogo/presentation/screens/home/section/ride_service_section.dart';
import 'package:liztogo/presentation/screens/home/widgets/location_pickup_widget.dart';
import 'package:liztogo/presentation/screens/home/widgets/banner_slider.dart';



class HomeScreen extends StatefulWidget {
  final GlobalKey<ScaffoldState>? dashBoardScaffoldKey;

  const HomeScreen({super.key, this.dashBoardScaffoldKey});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> with SingleTickerProviderStateMixin {
  final ValueNotifier<double> _sheetSizeNotifier = ValueNotifier<double>(0.52);
  final DraggableScrollableController _dssController = DraggableScrollableController();
  final DraggableScrollableController _homeDssController = DraggableScrollableController();

  final List<String> _securityTips = [
    'Seguridad',
    'Monitoreo GPS 24/7',
    'Conductores Verificados',
    'Soporte SOS Activo',
    'Ruta Protegida',
  ];
  int _currentTipIndex = 0;
  Timer? _securityTimer;

  @override
  void initState() {
    Get.put(HomeRepo(apiClient: Get.find()));
    Get.put(AppLocationController());
    final controller = Get.put(
      HomeController(homeRepo: Get.find(), appLocationController: Get.find()),
    );
    Get.put(
      HomeMapController(homeRepo: Get.find(), homeController: Get.find()),
    );
    Get.put(DeliveryRepo(apiClient: Get.find()));
    Get.put(DeliveryController(deliveryRepo: Get.find()));
    Get.put(FavorRepo(apiClient: Get.find()));
    Get.put(FavorController(favorRepo: Get.find()));
    Get.put(UserAddressRepo(apiClient: Get.find()));
    Get.put(UserAddressController(repo: Get.find()));
    Get.put(ConnectivityController());
    Get.put(NotificationInboxController());
    Get.put(WalletRepo(apiClient: Get.find()));
    Get.put(WalletController(walletRepo: Get.find()));
    super.initState();

    // Ambos sheets actualizan _sheetSizeNotifier para mover el botón Seguridad.
    _homeDssController.addListener(() {
      if (_homeDssController.isAttached) {
        _sheetSizeNotifier.value = _homeDssController.size;
      }
    });
    _dssController.addListener(() {
      if (_dssController.isAttached) {
        _sheetSizeNotifier.value = _dssController.size;
      }
    });

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      controller.initialData(shouldLoad: true);
      DeliveryNotificationService.init();
      Get.find<DeliveryNotificationService>().subscribeAll();
    });

    _securityTimer = Timer.periodic(const Duration(seconds: 5), (timer) {
      if (mounted) {
        setState(() {
          _currentTipIndex = (_currentTipIndex + 1) % _securityTips.length;
        });
      }
    });
  }

  @override
  void dispose() {
    _securityTimer?.cancel();
    _dssController.dispose();
    _homeDssController.dispose();
    _sheetSizeNotifier.dispose();
    super.dispose();
  }

  void openDrawer() {
    if (widget.dashBoardScaffoldKey != null) {
      widget.dashBoardScaffoldKey?.currentState?.openDrawer();
    }
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<HomeController>(
      builder: (controller) {
        return Scaffold(
          extendBody: true,
          backgroundColor: MyColor.cardBgColor,
          body: Stack(
              alignment: Alignment.bottomCenter,
              children: [
                Positioned.fill(
                  child: GetBuilder<HomeMapController>(
                    builder: (mapCtrl) {
                      return GoogleMap(
                        mapType: MapType.normal,
                        initialCameraPosition: CameraPosition(
                          target: controller.currentPosition != null ? LatLng(controller.currentPosition!.latitude - 0.0035, controller.currentPosition!.longitude) : const LatLng(-12.1333 - 0.0035, -77.0000),
                          zoom: Environment.mapDefaultZoom,
                        ),
                        onMapCreated: mapCtrl.onMapCreated,
                        myLocationEnabled: true,
                        myLocationButtonEnabled: false,
                        zoomControlsEnabled: false,
                        markers: {
                          ...mapCtrl.getDriverMarkers(),
                          ...mapCtrl.routeMarkers.values,
                        },
                        polylines: mapCtrl.homePolylines.values.toSet(),
                      );
                    },
                  ),
                ),
                Positioned(
                  left: 16,
                  bottom: 0,
                  child: ValueListenableBuilder<double>(
                    valueListenable: _sheetSizeNotifier,
                    builder: (context, sheetSize, child) {
                      final bottomNavBarHeight = 60.0;
                      final remainingHeight = MediaQuery.of(context).size.height - bottomNavBarHeight;
                      final bottomOffset = controller.selectedLocations.length < 2
                          ? bottomNavBarHeight + (remainingHeight * sheetSize) + 12
                          : bottomNavBarHeight + 325 + 12;
                      return Padding(
                        padding: EdgeInsets.only(bottom: bottomOffset),
                        child: GestureDetector(
                          onTap: () {
                            Get.snackbar(
                              'Seguridad',
                              'Viaja tranquilo. Tu ruta y datos están totalmente protegidos.',
                              backgroundColor: Colors.white,
                              colorText: Colors.black87,
                              icon: const Icon(Icons.shield_rounded, color: Colors.blue),
                            );
                          },
                          child: AnimatedSize(
                            duration: const Duration(milliseconds: 400),
                            curve: Curves.easeInOut,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(24),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.1),
                                    blurRadius: 8,
                                    offset: const Offset(0, 2),
                                  ),
                                ],
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(
                                    Icons.shield_rounded,
                                    color: Colors.blue,
                                    size: 18,
                                  ),
                                  const SizedBox(width: 8),
                                  AnimatedSwitcher(
                                    duration: const Duration(milliseconds: 500),
                                    transitionBuilder: (Widget child, Animation<double> animation) {
                                      return FadeTransition(opacity: animation, child: child);
                                    },
                                    child: Text(
                                      _securityTips[_currentTipIndex],
                                      key: ValueKey<int>(_currentTipIndex),
                                      style: boldDefault.copyWith(
                                        fontSize: 13,
                                        color: MyColor.primaryTextColor,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
                Positioned(
                  right: 16,
                  bottom: 0,
                  child: ValueListenableBuilder<double>(
                    valueListenable: _sheetSizeNotifier,
                    builder: (context, sheetSize, child) {
                      final bottomNavBarHeight = 60.0;
                      final remainingHeight = MediaQuery.of(context).size.height - bottomNavBarHeight;
                      final bottomOffset = controller.selectedLocations.length < 2
                          ? bottomNavBarHeight + (remainingHeight * sheetSize) + 12
                          : bottomNavBarHeight + 325 + 12;
                      return Padding(
                        padding: EdgeInsets.only(bottom: bottomOffset),
                        child: GetBuilder<HomeMapController>(
                          builder: (mapCtrl) {
                            return SizedBox(
                              width: 40,
                              height: 40,
                              child: FloatingActionButton(
                                heroTag: 'my_location_btn',
                                onPressed: () => mapCtrl.animateToCurrentLocation(),
                                backgroundColor: Colors.white,
                                elevation: 4,
                                shape: const CircleBorder(),
                                child: const Icon(
                                  Icons.my_location_rounded,
                                  color: MyColor.primaryColor,
                                  size: 20,
                                ),
                              ),
                            );
                          },
                        ),
                      );
                    },
                  ),
                ),  // 3. Floating Back Button (Overlays hamburger menu in State 2)
                if (controller.selectedLocations.length >= 2)
                  Positioned(
                    top: MediaQuery.of(context).padding.top + 12,
                    left: 16,
                    child: GestureDetector(
                      onTap: () {
                        if (controller.selectedLocations.length > 1) {
                          controller.selectedLocations.removeAt(1);
                        }
                        controller.clearData();
                        try {
                          Get.find<HomeMapController>().clearRoute();
                        } catch (_) {}
                        controller.update();
                      },
                      child: Container(
                        height: 44,
                        width: 44,
                        decoration: BoxDecoration(
                          color: MyColor.cardBgColor,
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.15),
                              blurRadius: 8,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.arrow_back_ios_new_rounded,
                          color: MyColor.primaryColor,
                          size: 18,
                        ),
                      ),
                    ),
                  ),
  
                // 4. Conditional Bottom Panel (DiDi Home vs Booking Draggable Sheet)
                if (controller.selectedLocations.length < 2)
                  Positioned(
                    top: 0,
                    left: 0,
                    right: 0,
                    bottom: 60,
                    child: _buildDidiHomeSheet(context, controller),
                  )
                else
                  Positioned(
                    left: 0,
                    right: 0,
                    bottom: 60,
                    child: _buildRideBookingSheet(context, controller),
                  ),
              ],
            ),
        );
      },
    );
  }

  // ── Didi style Home Sheet (draggable) ──
  Widget _buildDidiHomeSheet(BuildContext context, HomeController controller) {
    return DraggableScrollableSheet(
        controller: _homeDssController,
        initialChildSize: 0.52,
        minChildSize: 0.18,
        maxChildSize: 0.75,
        expand: true,
        snap: true,
        snapSizes: const [0.18, 0.52, 0.75],
        builder: (context, scrollController) {
          return Container(
            decoration: const BoxDecoration(
              color: Color(0xFFF3F4F6),
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(28),
                topRight: Radius.circular(28),
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black12,
                  blurRadius: 16,
                  offset: Offset(0, -4),
                ),
              ],
            ),
            child: ListView(
              controller: scrollController,
              padding: const EdgeInsets.only(bottom: 24),
              children: [
                // Drag handle pill
                Center(
                  child: Container(
                    margin: const EdgeInsets.symmetric(vertical: 10),
                    height: 4,
                    width: 32,
                    decoration: BoxDecoration(
                      color: Colors.black12,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),

                // Search Card
                Container(
                  margin: const EdgeInsets.symmetric(horizontal: 16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.04),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Column(
                    children: [
                      InkWell(
                        onTap: () {
                          controller.updateIsServiceShake(false);
                          Get.toNamed(RouteHelper.locationPickUpScreen, arguments: [1])?.then((v) {
                            if (controller.selectedLocations.length > 1) {
                              controller.getRideFare(defaultToCheapest: true);
                              final dest = controller.selectedLocations[1];
                              final lat = dest.latitude;
                              final lng = dest.longitude;
                              if (lat != null && lng != null && lat != 0 && lng != 0) {
                                try {
                                  Get.find<HomeMapController>().drawRouteTo(LatLng(lat, lng));
                                } catch (_) {}
                              }
                            }
                          });
                        },
                        borderRadius: const BorderRadius.only(
                          topLeft: Radius.circular(16),
                          topRight: Radius.circular(16),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Row(
                            children: [
                              const Icon(
                                Icons.search,
                                size: 24,
                                color: Colors.black87,
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      '¿A dónde vamos?',
                                      style: boldDefault.copyWith(
                                        fontSize: 18,
                                        color: Colors.black87,
                                      ),
                                    ),
                                    if (controller.taxiCoupon != null) ...[
                                      const SizedBox(height: 2),
                                      Text(
                                        controller.taxiCoupon!.type == 'percentage'
                                            ? 'Cupón ${controller.taxiCoupon!.code} de ${controller.taxiCoupon!.value?.toStringAsFixed(0)}% OFF disponible'
                                            : 'Cupón ${controller.taxiCoupon!.code} de S/ ${controller.taxiCoupon!.value?.toStringAsFixed(0)} OFF disponible',
                                        style: regularDefault.copyWith(
                                          fontSize: 12,
                                          color: Colors.green,
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),

                      ...controller.recentDestinationsList.map((loc) {
                        return Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Divider(height: 1, thickness: 0.5, color: Colors.black12, indent: 16, endIndent: 16),
                            _buildRecentDestinationItem(
                              title: loc.address ?? '',
                              subtitle: loc.fullAddress ?? '',
                              onTap: () {
                                      controller.addLocationAtIndex(loc, 1, getFareData: true);
                                      // Dibujar ruta en el mapa del home
                                      final lat = loc.latitude;
                                      final lng = loc.longitude;
                                      if (lat != null && lng != null && lat != 0 && lng != 0) {
                                        try {
                                          Get.find<HomeMapController>().drawRouteTo(
                                            LatLng(lat, lng),
                                          );
                                        } catch (_) {}
                                      }
                              },
                            ),
                          ],
                        );
                      }),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16),
                  child: BannerSlider(),
                ),
              ],
            ),
          );
        },
      );
  }

  Widget _buildRecentDestinationItem({
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.grey.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.history, size: 20, color: Colors.black54),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: regularDefault.copyWith(
                      fontSize: 14,
                      color: Colors.black87,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  if (subtitle.isNotEmpty && subtitle.trim().toLowerCase() != title.trim().toLowerCase()) ...[
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: regularDefault.copyWith(
                        fontSize: 12,
                        color: Colors.black38,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const Icon(
              Icons.chevron_right_rounded,
              color: Colors.black38,
              size: 20,
            ),
          ],
        ),
      ),
    );
  }


  // ── Ride Booking Sheet (State 2) ──
  Widget _buildRideBookingSheet(BuildContext context, HomeController controller) {
    return Container(
      decoration: const BoxDecoration(
        color: MyColor.cardBgColor,
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(32),
          topRight: Radius.circular(32),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black12,
            blurRadius: 24,
            offset: Offset(0, -8),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Center(
            child: Container(
              margin: const EdgeInsets.symmetric(vertical: 12),
              height: 4,
              width: 36,
              decoration: BoxDecoration(
                color: MyColor.neutral200,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          LocationPickUpHomeWidget(controller: controller),
          const SizedBox(height: Dimensions.space8),
          Divider(height: 1, color: MyColor.naturalTextColor.withValues(alpha: 0.15), indent: Dimensions.space16, endIndent: Dimensions.space16),
          const RideServiceSection(),
          Divider(height: 1, color: MyColor.naturalTextColor.withValues(alpha: 0.15), indent: Dimensions.space16, endIndent: Dimensions.space16),
          const Padding(
            padding: EdgeInsets.fromLTRB(
              Dimensions.space16,
              Dimensions.space12,
              Dimensions.space16,
              Dimensions.space16,
            ),
            child: RideCreateForm(),
          ),
        ],
      ),
    );
  }
}

