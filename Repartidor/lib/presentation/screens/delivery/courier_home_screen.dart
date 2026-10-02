import 'dart:async';
import 'dart:math' as math;
import 'dart:typed_data';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo_repartidor/core/utils/audio_utils.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/my_images.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_notification_service.dart';
import 'package:liztogo_repartidor/data/repo/account/profile_repo.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/courier_job_detail_screen.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/widgets/courier_active_order_card.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/widgets/courier_order_radar_card.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/widgets/courier_batch_radar_card.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/widgets/courier_offer_modal.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/widgets/auto_accept_settings_modal.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/widgets/insufficient_balance_modal.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';

import 'courier_earnings_screen.dart';

class CourierHomeScreen extends StatefulWidget {
  const CourierHomeScreen({super.key});

  @override
  State<CourierHomeScreen> createState() => _CourierHomeScreenState();
}

class _CourierHomeScreenState extends State<CourierHomeScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  GoogleMapController? _mapController;
  Position? _currentPosition;
  StreamSubscription<Position>? _positionSub;

  BitmapDescriptor? _courierMarkerIcon;
  BitmapDescriptor? _storeMarkerIcon;
  BitmapDescriptor? _customerMarkerIcon;

  int _greetingIdx = 0;
  late Timer _greetingTimer;
  String _walletBalance = '0.00';
  bool _balanceLoaded = false;
  bool _showHeatmap = false;

  static const _greetings = [
    '¡Buen día para repartir!', '¿Listo para ganar hoy?',
    'Nuevos pedidos te esperan', '¡A rodar se ha dicho!',
    'Cada entrega cuenta', 'Tu esfuerzo, tu recompensa',
  ];

  static const CameraPosition _initialCamera = CameraPosition(
    target: LatLng(-6.486714, -76.368310), // Tarapoto, Perú default fallback
    zoom: 14.5,
  );

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _greetingTimer = Timer.periodic(const Duration(seconds: 6), (_) {
      if (mounted) setState(() => _greetingIdx = (_greetingIdx + 1) % _greetings.length);
    });

    CourierNotificationService.init();
    _loadWalletBalance().then((_) {
      if (mounted) _checkLowBalance();
    });

    _loadCustomMarkerIcons();
    _initLocationTracking();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<CourierController>();
      c.loadOnlineStatus();
      c.loadPendingJobs();
      c.loadActiveJobs();
      c.loadActiveBatch();
      c.loadAutoAcceptSettings();
      c.loadPendingOffers().then((_) => _checkTargetedOffers(c));
      c.loadEconomicStatus().then((_) => _checkEconomicPolicy(c));
      Get.find<CourierNotificationService>().subscribeAll();
      AudioUtils.stop();
    });
  }

  Future<void> _loadCustomMarkerIcons() async {
    try {
      _courierMarkerIcon = await BitmapDescriptor.asset(
        const ImageConfiguration(size: Size(38, 38)),
        MyImages.deliveryManMarker,
      );
    } catch (_) {
      try {
        _courierMarkerIcon = await BitmapDescriptor.fromAssetImage(
          const ImageConfiguration(size: Size(38, 38)),
          MyImages.deliveryManMarker,
        );
      } catch (_) {}
    }

    try {
      _storeMarkerIcon = await _createCustomIconWithBadge(
        iconData: Icons.storefront_rounded,
        badgeColor: const Color(0xFF2563EB), // Store Royal Blue Badge
        iconColor: Colors.white,
      );

      _customerMarkerIcon = await _createCustomIconWithBadge(
        iconData: Icons.person_pin_circle_rounded,
        badgeColor: const Color(0xFFEF4444), // Customer Crimson Red Badge
        iconColor: Colors.white,
      );
    } catch (_) {}

    if (mounted) setState(() {});
  }

  Future<BitmapDescriptor> _createCustomIconWithBadge({
    required IconData iconData,
    required Color badgeColor,
    required Color iconColor,
    int size = 56,
  }) async {
    final ui.PictureRecorder pictureRecorder = ui.PictureRecorder();
    final Canvas canvas = Canvas(pictureRecorder);
    final double radius = size / 2;

    final Paint paintBg = Paint()..color = badgeColor;
    canvas.drawCircle(Offset(radius, radius), radius - 2, paintBg);

    final Paint paintBorder = Paint()
      ..color = Colors.white
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    canvas.drawCircle(Offset(radius, radius), radius - 3, paintBorder);

    TextPainter textPainter = TextPainter(textDirection: TextDirection.ltr);
    textPainter.text = TextSpan(
      text: String.fromCharCode(iconData.codePoint),
      style: TextStyle(
        fontSize: size * 0.52,
        fontFamily: iconData.fontFamily,
        package: iconData.fontPackage,
        color: iconColor,
      ),
    );
    textPainter.layout();
    textPainter.paint(
      canvas,
      Offset(
        radius - (textPainter.width / 2),
        radius - (textPainter.height / 2),
      ),
    );

    final ui.Image image = await pictureRecorder.endRecording().toImage(size, size);
    final ByteData? byteData = await image.toByteData(format: ui.ImageByteFormat.png);
    final Uint8List uint8List = byteData!.buffer.asUint8List();

    return BitmapDescriptor.bytes(uint8List);
  }

  Future<void> _initLocationTracking() async {
    try {
      LocationPermission perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) {
        perm = await Geolocator.requestPermission();
      }
      if (perm == LocationPermission.whileInUse || perm == LocationPermission.always) {
        final pos = await Geolocator.getCurrentPosition();
        if (mounted) {
          setState(() {
            _currentPosition = pos;
          });
          _animateToLocation(pos.latitude, pos.longitude);
        }

        _positionSub = Geolocator.getPositionStream(
          locationSettings: const LocationSettings(accuracy: LocationAccuracy.high, distanceFilter: 10),
        ).listen((pos) {
          if (mounted) {
            setState(() {
              _currentPosition = pos;
            });
          }
        });
      }
    } catch (_) {}
  }

  void _animateToLocation(double lat, double lng) {
    if (_mapController != null) {
      _mapController!.animateCamera(
        CameraUpdate.newCameraPosition(
          CameraPosition(target: LatLng(lat, lng), zoom: 15.5),
        ),
      );
    }
  }

  Set<Marker> _buildMapMarkers(CourierController c) {
    final Set<Marker> markers = {};

    // 1. Current Driver Position Custom Marker
    if (_currentPosition != null) {
      markers.add(
        Marker(
          markerId: const MarkerId('driver_current_pos'),
          position: LatLng(_currentPosition!.latitude, _currentPosition!.longitude),
          infoWindow: const InfoWindow(title: 'Repartidor (Tu Ubicación)'),
          icon: _courierMarkerIcon ?? BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
        ),
      );
    }

    // 2. Pending Orders Store Markers
    for (var job in c.pendingJobs) {
      if (job.pickupLat != null && job.pickupLng != null) {
        markers.add(
          Marker(
            markerId: MarkerId('pending_pickup_${job.id}'),
            position: LatLng(job.pickupLat!, job.pickupLng!),
            infoWindow: InfoWindow(
              title: '🏬 Tienda: ${job.storeName ?? "Pedido #${job.orderNo ?? job.id}"}',
              snippet: 'Ganancia: S/ ${(job.totalEarning ?? 0.0).toStringAsFixed(2)} • Toca para ver',
              onTap: () {
                _showJobQuickPreviewModal(context, c, job);
              },
            ),
            icon: _storeMarkerIcon ?? BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueOrange),
            onTap: () {
              _showJobQuickPreviewModal(context, c, job);
            },
          ),
        );
      }
    }

    // 3. Active Orders Store & Customer Markers
    for (var job in c.activeJobs) {
      // Pickup Store Location
      if (job.pickupLat != null && job.pickupLng != null) {
        markers.add(
          Marker(
            markerId: MarkerId('active_pickup_${job.id}'),
            position: LatLng(job.pickupLat!, job.pickupLng!),
            infoWindow: InfoWindow(
              title: '🏬 Recoger: ${job.storeName ?? "Origen"}',
              snippet: 'Toca para gestionar pedido #${job.orderNo ?? job.id}',
              onTap: () {
                Get.to(() => CourierJobDetailScreen(key: ValueKey('job_${job.id}'), jobId: job.id ?? 0, jobData: job));
              },
            ),
            icon: _storeMarkerIcon ?? BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueAzure),
            onTap: () {
              Get.to(() => CourierJobDetailScreen(key: ValueKey('job_${job.id}'), jobId: job.id ?? 0, jobData: job));
            },
          ),
        );
      }
      // Delivery Customer Location
      if (job.deliveryLat != null && job.deliveryLng != null) {
        markers.add(
          Marker(
            markerId: MarkerId('active_delivery_${job.id}'),
            position: LatLng(job.deliveryLat!, job.deliveryLng!),
            infoWindow: InfoWindow(
              title: '🏠 Entregar: ${job.customerName ?? "Destino"}',
              snippet: 'Toca para ver detalle del pedido #${job.orderNo ?? job.id}',
              onTap: () {
                Get.to(() => CourierJobDetailScreen(key: ValueKey('job_${job.id}'), jobId: job.id ?? 0, jobData: job));
              },
            ),
            icon: _customerMarkerIcon ?? BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
            onTap: () {
              Get.to(() => CourierJobDetailScreen(key: ValueKey('job_${job.id}'), jobId: job.id ?? 0, jobData: job));
            },
          ),
        );
      }
    }

    return markers;
  }

  Set<Circle> _buildHeatmapCircles(CourierController c) {
    if (!_showHeatmap) return {};

    final Set<Circle> circles = {};
    int idCounter = 0;

    var points = List<Map<String, dynamic>>.from(c.heatmapPoints);

    if (points.isEmpty && c.pendingJobs.isNotEmpty) {
      for (var job in c.pendingJobs) {
        if (job.pickupLat != null && job.pickupLng != null) {
          points.add({
            'lat': job.pickupLat,
            'lng': job.pickupLng,
            'weight': 1.8,
            'radius': 350.0,
          });
        }
      }
    }

    for (var point in points) {
      final lat = (point['lat'] as num?)?.toDouble();
      final lng = (point['lng'] as num?)?.toDouble();
      final weight = (point['weight'] as num?)?.toDouble() ?? 1.5;
      final radius = (point['radius'] as num?)?.toDouble() ?? (300.0 * weight);

      if (lat != null && lng != null) {
        // Capa 1: Resplandor Infrarrojo Externo (Gradiente suave)
        circles.add(
          Circle(
            circleId: CircleId('heat_glow_${idCounter++}'),
            center: LatLng(lat, lng),
            radius: radius * 1.4,
            fillColor: const Color(0xFFEF4444).withValues(alpha: 0.16),
            strokeColor: Colors.transparent,
            strokeWidth: 0,
          ),
        );
        // Capa 2: Campo Medio de Demanda (Naranja / Ámbar)
        circles.add(
          Circle(
            circleId: CircleId('heat_mid_${idCounter++}'),
            center: LatLng(lat, lng),
            radius: radius * 0.85,
            fillColor: const Color(0xFFF97316).withValues(alpha: 0.35),
            strokeColor: const Color(0xFFF97316).withValues(alpha: 0.5),
            strokeWidth: 1,
          ),
        );
        // Capa 3: Núcleo Intenso de Alta Densidad (Rojo Encendido)
        circles.add(
          Circle(
            circleId: CircleId('heat_core_${idCounter++}'),
            center: LatLng(lat, lng),
            radius: radius * 0.45,
            fillColor: const Color(0xFFDC2626).withValues(alpha: 0.65),
            strokeColor: const Color(0xFFDC2626),
            strokeWidth: 2,
          ),
        );
      }
    }

    return circles;
  }

  Future<void> _loadWalletBalance() async {
    try {
      final repo = ProfileRepo(apiClient: Get.find());
      final profileModel = await repo.loadProfileInfo();
      if (profileModel.data?.driver != null) {
        final balance = profileModel.data!.driver!.balance ?? '0.00';
        if (mounted) setState(() { _walletBalance = balance; _balanceLoaded = true; });
      }
    } catch (_) {}
  }

  void _checkLowBalance() {
    if (!_balanceLoaded) return;
    final wb = double.tryParse(_walletBalance);
    if (wb != null && wb < 3) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        Get.snackbar(
          'Saldo bajo',
          'Tu saldo es S/ ${wb.toStringAsFixed(2)}. Recarga para seguir aceptando pedidos.',
          backgroundColor: const Color(0xFFEF4444),
          colorText: MyColor.colorWhite,
          duration: const Duration(seconds: 5),
          icon: const Icon(Icons.warning_amber_rounded, color: Colors.white),
          mainButton: TextButton(
            onPressed: () => Get.toNamed(RouteHelper.newDepositScreenScreen),
            child: Text('Recargar', style: boldDefault.copyWith(color: MyColor.colorWhite)),
          ),
        );
      });
    }
  }

  void _checkEconomicPolicy(CourierController c) {
    if (!mounted) return;
    final status = c.economicStatus;
    if (status != null && status['allowed'] == false) {
      final currentBalance = (status['balance'] is num) ? (status['balance'] as num).toDouble() : 0.0;
      final minRecharge = (status['min_recharge'] is num) ? (status['min_recharge'] as num).toDouble() : 8.0;
      final reason = status['reason']?.toString();

      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        InsufficientBalanceModal.show(
          context: context,
          currentBalance: currentBalance,
          minRecharge: minRecharge,
          reason: reason,
        );
      });
    }
  }

  void _checkTargetedOffers(CourierController c) {
    if (!mounted || c.pendingOffers.isEmpty) return;
    final offer = c.pendingOffers.first;
    if (!offer.isExpired) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        CourierOfferModal.show(
          context: context,
          offer: offer,
          onAccept: (offerId) => c.acceptOffer(offerId),
          onReject: (offerId) => c.rejectOffer(offerId),
        );
      });
    }
  }

  @override
  void dispose() {
    _greetingTimer.cancel();
    _tabController.dispose();
    _positionSub?.cancel();
    _mapController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return GetBuilder<CourierController>(
      builder: (c) {
        return Scaffold(
          body: Stack(
            children: [
              // 1. Full Screen Interactive Map Background
              GoogleMap(
                initialCameraPosition: _currentPosition != null
                    ? CameraPosition(
                        target: LatLng(_currentPosition!.latitude, _currentPosition!.longitude),
                        zoom: 15.5,
                      )
                    : _initialCamera,
                markers: _buildMapMarkers(c),
                circles: _buildHeatmapCircles(c),
                myLocationEnabled: true,
                myLocationButtonEnabled: false,
                zoomControlsEnabled: false,
                compassEnabled: true,
                mapToolbarEnabled: false,
                onMapCreated: (controller) {
                  _mapController = controller;
                  if (_currentPosition != null) {
                    _animateToLocation(_currentPosition!.latitude, _currentPosition!.longitude);
                  }
                },
              ),

              // 2. Floating Top Header Bar (Translucent Glassmorphism style)
              SafeArea(
                child: Container(
                  margin: const EdgeInsets.all(Dimensions.space12),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.space15,
                    vertical: Dimensions.space10,
                  ),
                  decoration: BoxDecoration(
                    color: (isDark ? const Color(0xFF1E293B) : MyColor.colorWhite).withValues(alpha: 0.92),
                    borderRadius: BorderRadius.circular(Dimensions.space20),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withValues(alpha: 0.15),
                        blurRadius: 12,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            'Centro de Repartos',
                            style: boldLarge.copyWith(
                              color: isDark ? Colors.white : MyColor.primaryTextColor,
                              fontSize: Dimensions.fontLarge - 2,
                            ),
                          ),
                          AnimatedSwitcher(
                            duration: const Duration(milliseconds: 500),
                            child: Text(
                              _greetings[_greetingIdx],
                              key: ValueKey(_greetings[_greetingIdx]),
                              style: regularDefault.copyWith(
                                color: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor,
                                fontSize: Dimensions.fontExtraSmall,
                              ),
                            ),
                          ),
                        ],
                      ),
                      Row(
                        children: [
                          IconButton(
                            icon: Icon(
                              Icons.bolt_rounded,
                              color: c.autoAcceptSettings.autoAcceptEnabled ? const Color(0xFFF59E0B) : (isDark ? Colors.white70 : Colors.black54),
                            ),
                            tooltip: 'Autoaceptación Inteligente',
                            onPressed: () async {
                              await c.loadAutoAcceptSettings();
                              if (context.mounted) {
                                AutoAcceptSettingsModal.show(
                                  context: context,
                                  initialSettings: c.autoAcceptSettings,
                                  onSave: (enabled, minEarning, maxDist) async {
                                    await c.saveAutoAcceptSettings(enabled, minEarning, maxDist);
                                  },
                                );
                              }
                            },
                          ),
                          IconButton(
                            icon: const Icon(Icons.verified_user_outlined),
                            color: isDark ? Colors.white : MyColor.primaryTextColor,
                            onPressed: () => Get.toNamed(RouteHelper.driverProfileVerificationScreen),
                            tooltip: 'Verificar perfil',
                          ),
                          IconButton(
                            icon: const Icon(Icons.directions_car_outlined),
                            color: isDark ? Colors.white : MyColor.primaryTextColor,
                            onPressed: () => Get.toNamed(RouteHelper.vehicleVerificationScreen),
                            tooltip: 'Verificar vehículo',
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),

              // 3. Floating Map Controls Stack (Recenter & Heatmap Toggle Buttons)
              Positioned(
                right: 16,
                top: MediaQuery.of(context).padding.top + 75,
                child: Column(
                  children: [
                    FloatingActionButton.small(
                      heroTag: 'recenter_map_btn',
                      backgroundColor: isDark ? const Color(0xFF1E293B) : MyColor.colorWhite,
                      foregroundColor: MyColor.primaryColor,
                      elevation: 4,
                      onPressed: () {
                        if (_currentPosition != null) {
                          _animateToLocation(_currentPosition!.latitude, _currentPosition!.longitude);
                        }
                      },
                      child: const Icon(Icons.my_location_rounded),
                    ),
                    const SizedBox(height: 10),
                    FloatingActionButton.small(
                      heroTag: 'heatmap_toggle_btn',
                      backgroundColor: _showHeatmap ? const Color(0xFFDC2626) : (isDark ? const Color(0xFF1E293B) : MyColor.colorWhite),
                      foregroundColor: _showHeatmap ? Colors.white : const Color(0xFFDC2626),
                      elevation: 4,
                      tooltip: 'Mapa de Calor de Demanda',
                      onPressed: () async {
                        setState(() {
                          _showHeatmap = !_showHeatmap;
                        });
                        if (_showHeatmap) {
                          await c.fetchHeatmapData();
                          if (mounted) setState(() {});
                        }
                      },
                      child: const Icon(Icons.whatshot_rounded),
                    ),
                  ],
                ),
              ),

              // 4. Active Heatmap Legend Badge
              if (_showHeatmap)
                Positioned(
                  left: 16,
                  top: MediaQuery.of(context).padding.top + 75,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFFDC2626).withValues(alpha: 0.92),
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: const [BoxShadow(color: Colors.black26, blurRadius: 8, offset: Offset(0, 2))],
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.local_fire_department_rounded, color: Colors.amber, size: 16),
                        const SizedBox(width: 6),
                        Text(
                          'ZONAS DE ALTA DEMANDA',
                          style: boldSmall.copyWith(color: Colors.white, fontSize: 10, letterSpacing: 0.5),
                        ),
                      ],
                    ),
                  ),
                ),

              // 4. Draggable Bottom Sheet with Order Details & Earnings
              DraggableScrollableSheet(
                initialChildSize: 0.45,
                minChildSize: 0.18,
                maxChildSize: 0.88,
                builder: (context, scrollController) {
                  return Container(
                    decoration: BoxDecoration(
                      color: isDark ? const Color(0xFF0F172A) : MyColor.screenBgColor,
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.25),
                          blurRadius: 16,
                          offset: const Offset(0, -4),
                        ),
                      ],
                    ),
                    child: ListView(
                      controller: scrollController,
                      padding: EdgeInsets.zero,
                      children: [
                        // Drag Handle
                        Center(
                          child: Container(
                            margin: const EdgeInsets.symmetric(vertical: 10),
                            width: 42,
                            height: 5,
                            decoration: BoxDecoration(
                              color: isDark ? Colors.grey[700] : Colors.grey[300],
                              borderRadius: BorderRadius.circular(3),
                            ),
                          ),
                        ),

                        // Subtle Balance & Status Strip
                        CourierSubtleBalanceStrip(
                          walletBalance: _walletBalance,
                          isOnline: c.isOnline,
                          onToggleOnline: () => c.toggleOnline(),
                        ),

                        // TabBar Navigation for Orders
                        Container(
                          margin: const EdgeInsets.symmetric(
                            horizontal: Dimensions.space15,
                            vertical: Dimensions.space6,
                          ),
                          decoration: BoxDecoration(
                            color: isDark ? const Color(0xFF1E293B) : MyColor.colorWhite,
                            borderRadius: BorderRadius.circular(Dimensions.space12),
                            boxShadow: [
                              BoxShadow(
                                color: isDark ? Colors.black.withValues(alpha: 0.2) : MyColor.shadowColor,
                                blurRadius: 8,
                              ),
                            ],
                          ),
                          child: TabBar(
                            controller: _tabController,
                            indicator: BoxDecoration(
                              color: MyColor.primaryColor,
                              borderRadius: BorderRadius.circular(Dimensions.space10),
                            ),
                            indicatorSize: TabBarIndicatorSize.tab,
                            labelColor: MyColor.colorWhite,
                            unselectedLabelColor: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor,
                            labelStyle: boldDefault.copyWith(fontSize: Dimensions.fontSmall + 1),
                            dividerColor: Colors.transparent,
                            tabs: [
                              Tab(text: 'Radar / Disponibles (${c.pendingJobs.length})'),
                              Tab(text: 'Activos (${c.activeJobs.length})'),
                            ],
                          ),
                        ),

                        // Tab Bar Contents List
                        SizedBox(
                          height: 380,
                          child: TabBarView(
                            controller: _tabController,
                            children: [
                              _buildPendingTab(c),
                              _buildActiveTab(c),
                            ],
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
      },
    );
  }

  Widget _buildPendingTab(CourierController c) {
    if (c.isLoading) return const Center(child: CircularProgressIndicator(color: MyColor.primaryColor));
    if (!c.isOnline) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 70,
              height: 70,
              decoration: BoxDecoration(
                color: MyColor.primaryColor.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.wifi_off_rounded, size: 36, color: MyColor.primaryColor.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 12),
            Text(
              'Estás fuera de línea',
              style: boldLarge.copyWith(color: Theme.of(context).brightness == Brightness.dark ? Colors.white : MyColor.primaryTextColor),
            ),
            const SizedBox(height: 4),
            Text(
              'Activa el modo en línea para recibir pedidos',
              style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
            ),
          ],
        ),
      );
    }

    final hasActiveBatch = c.activeBatch != null;
    final extraBatchItem = hasActiveBatch ? 1 : 0;

    return RefreshIndicator(
      onRefresh: () async {
        await c.loadPendingJobs();
        await c.loadActiveBatch();
        await c.loadPendingOffers();
        _checkTargetedOffers(c);
      },
      child: ListView.builder(
        padding: const EdgeInsets.only(bottom: 95),
        itemCount: c.pendingJobs.length + 1 + extraBatchItem,
        itemBuilder: (_, i) {
          if (i == 0) {
            return CourierRealRadarCard(
              isOnline: c.isOnline,
              pendingCount: c.pendingJobs.length,
            );
          }
          if (hasActiveBatch && i == 1) {
            return CourierBatchRadarCard(
              batch: c.activeBatch!,
              onAccept: (b) {},
            );
          }
          final jobIndex = hasActiveBatch ? (i - 2) : (i - 1);
          final job = c.pendingJobs[jobIndex];
          return CourierOrderRadarCard(
            job: job,
            onAccept: (j) => _acceptJob(c, j),
          );
        },
      ),
    );
  }

  Widget _buildActiveTab(CourierController c) {
    if (c.isLoading) return const Center(child: CircularProgressIndicator(color: MyColor.primaryColor));
    if (c.activeJobs.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.local_shipping_rounded, size: 54, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3)),
            const SizedBox(height: 10),
            Text('Sin pedidos activos en curso', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => c.loadActiveJobs(),
      child: ListView.builder(
        padding: const EdgeInsets.only(bottom: 95),
        itemCount: c.activeJobs.length,
        itemBuilder: (_, i) {
          final job = c.activeJobs[i];
          return CourierActiveOrderCard(
            job: job,
            onTapDetail: () => Get.to(() => CourierJobDetailScreen(key: ValueKey('job_${job.id}'), jobId: job.id ?? 0, jobData: job)),
          );
        },
      ),
    );
  }

  void _acceptJob(CourierController c, CourierJobModel job) async {
    AudioUtils.stop();
    bool ok = await c.acceptJob(job.id ?? 0, job.type ?? 'delivery');
    if (ok) {
      _tabController.animateTo(1);
      Get.snackbar(
        '¡Pedido Aceptado!',
        'El pedido #${job.orderNo ?? job.id} ya está en tus pedidos activos.',
        backgroundColor: const Color(0xFF10B981),
        colorText: MyColor.colorWhite,
        icon: const Icon(Icons.check_circle_rounded, color: Colors.white),
      );
    }
  }

  void _showJobQuickPreviewModal(BuildContext context, CourierController c, CourierJobModel job) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) {
        final isDark = Theme.of(context).brightness == Brightness.dark;
        return Container(
          decoration: BoxDecoration(
            color: isDark ? const Color(0xFF0F172A) : Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
            boxShadow: const [
              BoxShadow(color: Colors.black26, blurRadius: 16, offset: Offset(0, -4)),
            ],
          ),
          padding: const EdgeInsets.only(top: 12, bottom: 20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: isDark ? Colors.grey[700] : Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.storefront_rounded, color: MyColor.primaryColor, size: 22),
                        const SizedBox(width: 8),
                        Text(
                          job.storeName ?? 'Tienda de Recogida',
                          style: boldLarge.copyWith(
                            color: isDark ? Colors.white : MyColor.primaryTextColor,
                            fontSize: 16,
                          ),
                        ),
                      ],
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded),
                      onPressed: () => Navigator.pop(context),
                    ),
                  ],
                ),
              ),
              CourierOrderRadarCard(
                job: job,
                onAccept: (j) {
                  Navigator.pop(context);
                  _acceptJob(c, j);
                },
              ),
            ],
          ),
        );
      },
    );
  }
}

class CourierSubtleBalanceStrip extends StatelessWidget {
  final String walletBalance;
  final bool isOnline;
  final VoidCallback onToggleOnline;

  const CourierSubtleBalanceStrip({
    super.key,
    required this.walletBalance,
    required this.isOnline,
    required this.onToggleOnline,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 15, vertical: 4),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: (isDark ? const Color(0xFF1E293B) : Colors.white).withValues(alpha: 0.95),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: isDark ? Colors.black.withValues(alpha: 0.2) : MyColor.shadowColor.withValues(alpha: 0.4),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Wallet Balance Subtle Chip
          GestureDetector(
            onTap: () => Get.to(() => const CourierEarningsScreen()),
            child: Row(
              children: [
                const Icon(Icons.account_balance_wallet_rounded, color: MyColor.primaryColor, size: 18),
                const SizedBox(width: 6),
                Text(
                  'Saldo: ',
                  style: regularSmall.copyWith(color: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor),
                ),
                Text(
                  'S/ $walletBalance',
                  style: boldDefault.copyWith(color: isDark ? Colors.white : MyColor.primaryTextColor, fontSize: 13),
                ),
                const SizedBox(width: 8),
                InkWell(
                  onTap: () => Get.toNamed(RouteHelper.newDepositScreenScreen),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: MyColor.primaryColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      'Recargar',
                      style: boldSmall.copyWith(color: MyColor.primaryColor, fontSize: 10),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Elegant Switch for Online / Offline Status
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                isOnline ? 'En línea' : 'Desconectado',
                style: boldSmall.copyWith(
                  color: isOnline ? const Color(0xFF10B981) : Colors.redAccent,
                  fontSize: 11,
                ),
              ),
              const SizedBox(width: 2),
              Transform.scale(
                scale: 0.75,
                child: Switch.adaptive(
                  value: isOnline,
                  activeColor: const Color(0xFF10B981),
                  activeTrackColor: const Color(0xFF10B981).withValues(alpha: 0.3),
                  inactiveThumbColor: Colors.redAccent,
                  inactiveTrackColor: Colors.red.withValues(alpha: 0.2),
                  onChanged: (_) => onToggleOnline(),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class CourierRealRadarCard extends StatefulWidget {
  final bool isOnline;
  final int pendingCount;

  const CourierRealRadarCard({
    super.key,
    required this.isOnline,
    required this.pendingCount,
  });

  @override
  State<CourierRealRadarCard> createState() => _CourierRealRadarCardState();
}

class _CourierRealRadarCardState extends State<CourierRealRadarCard> with SingleTickerProviderStateMixin {
  late AnimationController _sweepController;

  @override
  void initState() {
    super.initState();
    _sweepController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 3),
    )..repeat();
  }

  @override
  void dispose() {
    _sweepController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final hasJobs = widget.pendingCount > 0;
    final themeColor = !widget.isOnline
        ? Colors.grey
        : (hasJobs ? const Color(0xFF10B981) : MyColor.primaryColor);

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 15, vertical: 10),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: isDark ? Colors.black.withValues(alpha: 0.3) : MyColor.shadowColor.withValues(alpha: 0.5),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
        border: Border.all(color: themeColor.withValues(alpha: 0.3), width: 1.5),
      ),
      child: Row(
        children: [
          // Authentic 360° Radar Animated Sweep Dish (CustomPainter)
          SizedBox(
            width: 105,
            height: 105,
            child: AnimatedBuilder(
              animation: _sweepController,
              builder: (context, child) {
                return CustomPaint(
                  painter: RealRadarSweepPainter(
                    sweepAngle: _sweepController.value * math.pi * 2,
                    themeColor: themeColor,
                    targetCount: widget.pendingCount,
                  ),
                );
              },
            ),
          ),
          const SizedBox(width: 16),

          // Radar Status & Metrics Column
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      decoration: BoxDecoration(
                        color: widget.isOnline
                            ? (hasJobs ? const Color(0xFF10B981) : MyColor.primaryColor)
                            : Colors.redAccent,
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 6),
                    Text(
                      !widget.isOnline
                          ? 'RADAR EN PAUSA'
                          : (hasJobs ? 'RADAR: PEDIDOS DETECTADOS' : 'RADAR: ESCANEANDO VIVO'),
                      style: boldDefault.copyWith(
                        color: themeColor,
                        fontSize: 11,
                        letterSpacing: 0.6,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  !widget.isOnline
                      ? 'Activa el modo en línea para iniciar el escaneo'
                      : (hasJobs
                          ? '${widget.pendingCount} ${widget.pendingCount == 1 ? "pedido encontrado" : "pedidos encontrados"} en tu zona'
                          : 'Escaneando pedidos a la redonda...'),
                  style: boldLarge.copyWith(
                    color: isDark ? Colors.white : MyColor.primaryTextColor,
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Actualización automática en vivo',
                  style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class RealRadarSweepPainter extends CustomPainter {
  final double sweepAngle;
  final Color themeColor;
  final int targetCount;

  RealRadarSweepPainter({
    required this.sweepAngle,
    required this.themeColor,
    required this.targetCount,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;

    // 1. Background Circle
    final bgPaint = Paint()..color = themeColor.withValues(alpha: 0.08);
    canvas.drawCircle(center, radius, bgPaint);

    // 2. Concentric Distance Rings
    final ringPaint = Paint()
      ..color = themeColor.withValues(alpha: 0.25)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.2;

    canvas.drawCircle(center, radius * 0.33, ringPaint);
    canvas.drawCircle(center, radius * 0.66, ringPaint);
    canvas.drawCircle(center, radius * 0.98, ringPaint);

    // 3. Crosshairs
    final linePaint = Paint()
      ..color = themeColor.withValues(alpha: 0.2)
      ..strokeWidth = 1.0;
    canvas.drawLine(Offset(center.dx, 0), Offset(center.dx, size.height), linePaint);
    canvas.drawLine(Offset(0, center.dy), Offset(size.width, center.dy), linePaint);

    // 4. Rotating Radial Sweep Beam Gradient
    final sweepGradient = SweepGradient(
      center: Alignment.center,
      startAngle: 0.0,
      endAngle: math.pi * 2,
      colors: [
        themeColor.withValues(alpha: 0.0),
        themeColor.withValues(alpha: 0.05),
        themeColor.withValues(alpha: 0.4),
      ],
      stops: const [0.0, 0.7, 1.0],
      transform: GradientRotation(sweepAngle),
    );

    final sweepPaint = Paint()
      ..shader = sweepGradient.createShader(Rect.fromCircle(center: center, radius: radius));
    canvas.drawCircle(center, radius, sweepPaint);

    // 5. Sweep Leading Line
    final lineEndX = center.dx + radius * math.cos(sweepAngle);
    final lineEndY = center.dy + radius * math.sin(sweepAngle);
    final beamLinePaint = Paint()
      ..color = themeColor
      ..strokeWidth = 2.0;
    canvas.drawLine(center, Offset(lineEndX, lineEndY), beamLinePaint);

    // 6. Blinking Target Blips (Points when orders exist)
    if (targetCount > 0) {
      final blipPaint = Paint()
        ..color = const Color(0xFF10B981)
        ..style = PaintingStyle.fill;
      final blipGlow = Paint()
        ..color = const Color(0xFF34D399).withValues(alpha: 0.5)
        ..style = PaintingStyle.fill;

      final offsets = [
        Offset(center.dx + radius * 0.4, center.dy - radius * 0.3),
        Offset(center.dx - radius * 0.5, center.dy + radius * 0.4),
        Offset(center.dx + radius * 0.2, center.dy + radius * 0.6),
        Offset(center.dx - radius * 0.3, center.dy - radius * 0.5),
      ];

      for (int i = 0; i < math.min(targetCount, offsets.length); i++) {
        canvas.drawCircle(offsets[i], 6, blipGlow);
        canvas.drawCircle(offsets[i], 3.5, blipPaint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant RealRadarSweepPainter oldDelegate) {
    return oldDelegate.sweepAngle != sweepAngle ||
        oldDelegate.targetCount != targetCount ||
        oldDelegate.themeColor != themeColor;
  }
}
