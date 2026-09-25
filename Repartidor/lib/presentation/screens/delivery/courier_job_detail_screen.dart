import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';
import 'package:liztogo_repartidor/data/services/directions_service.dart';
import 'package:liztogo_repartidor/environment.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/courier_chat_screen.dart';
import 'package:liztogo_repartidor/utils/delivery_constants.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:qr_flutter/qr_flutter.dart';

class CourierJobDetailScreen extends StatefulWidget {
  final int jobId;
  final CourierJobModel? jobData;
  final String? jobType;
  const CourierJobDetailScreen({super.key, required this.jobId, this.jobData, this.jobType});

  @override
  State<CourierJobDetailScreen> createState() => _CourierJobDetailScreenState();
}

class _CourierJobDetailScreenState extends State<CourierJobDetailScreen> {
  GoogleMapController? _mapController;
  final Set<Marker> _markers = {};
  final Set<Polyline> _polylines = {};
  bool _routeLoaded = false;
  bool _mapUpdated = false;
  BitmapDescriptor? _courierMarkerIcon;
  StreamSubscription<Position>? _positionSub;

  @override
  void initState() {
    super.initState();
    _markers.clear();
    _polylines.clear();
    _routeLoaded = false;
    _mapUpdated = false;
    final c = Get.find<CourierController>();
    printX('JobDetail: jobId=${widget.jobId}, jobData.id=${widget.jobData?.id}, jobData.orderNo=${widget.jobData?.orderNo}');
    c.selectedJob = widget.jobData;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      c.loadJobDetail(widget.jobId, type: widget.jobData?.type ?? widget.jobType);
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<CourierController>(
      builder: (c) {
        final job = c.selectedJob;

        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          body: c.isLoading && job == null
              ? const Center(child: CircularProgressIndicator())
              : job == null
                  ? const Center(child: Text('Pedido no encontrado'))
                  : Stack(
                      children: [
                        // --- Full screen Google Map ---
                        Positioned.fill(
                          child: GoogleMap(
                            initialCameraPosition: CameraPosition(
                              target: LatLng(job.pickupLat ?? -12.0464, job.pickupLng ?? -77.0428),
                              zoom: 14,
                            ),
                            markers: _markers,
                            polylines: _polylines,
                            onMapCreated: (controller) {
                              _mapController = controller;
                              _updateMap(job);
                            },
                            myLocationEnabled: true,
                            zoomControlsEnabled: false,
                            myLocationButtonEnabled: false,
                          ),
                        ),

                        // --- Floating AppBar ---
                        Positioned(
                          top: 0,
                          left: 0,
                          right: 0,
                          child: SafeArea(
                            child: Container(
                              margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 4),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.12),
                                    blurRadius: 12,
                                    offset: const Offset(0, 4),
                                  ),
                                ],
                              ),
                              child: Row(
                                children: [
                                  IconButton(
                                    onPressed: () => Get.back(),
                                    icon: const Icon(Icons.arrow_back_rounded, color: MyColor.primaryTextColor),
                                  ),
                                  Expanded(
                                    child: Column(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text(
                                          job.orderNo ?? 'Pedido',
                                          style: boldDefault.copyWith(color: MyColor.primaryTextColor),
                                          textAlign: TextAlign.center,
                                        ),
                                        if (job.isFavor)
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                            margin: const EdgeInsets.only(top: 2),
                                            decoration: BoxDecoration(
                                              color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
                                              borderRadius: BorderRadius.circular(8),
                                            ),
                                            child: Text('Favor', style: regularSmall.copyWith(color: const Color(0xFFF59E0B), fontWeight: FontWeight.w600, fontSize: 10)),
                                          ),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 48),
                                ],
                              ),
                            ),
                          ),
                        ),

                        // --- My Location FAB ---
                        Positioned(
                          bottom: 300,
                          right: 16,
                          child: GestureDetector(
                            onTap: _centerOnCourier,
                            child: Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: Colors.white,
                                shape: BoxShape.circle,
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.15),
                                    blurRadius: 8,
                                    offset: const Offset(0, 2),
                                  ),
                                ],
                              ),
                              child: const Icon(Icons.my_location_rounded, color: MyColor.primaryColor, size: 22),
                            ),
                          ),
                        ),

                        // --- Bottom Sheet ---
                        Positioned.fill(
                          child: DraggableScrollableSheet(
                            initialChildSize: 0.48,
                            minChildSize: 0.22,
                            maxChildSize: 0.92,
                            builder: (context, scrollController) {
                              return Container(
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withValues(alpha: 0.15),
                                      blurRadius: 20,
                                      offset: const Offset(0, -4),
                                    ),
                                  ],
                                ),
                                child: Column(
                                  children: [
                                    // --- Handle ---
                                    Container(
                                      margin: const EdgeInsets.only(top: 10, bottom: 4),
                                      width: 40,
                                      height: 4,
                                      decoration: BoxDecoration(
                                        color: Colors.grey[300],
                                        borderRadius: BorderRadius.circular(2),
                                      ),
                                    ),

                                    // --- Scrollable content ---
                                    Expanded(
                                      child: ListView(
                                        controller: scrollController,
                                        padding: const EdgeInsets.symmetric(horizontal: 16),
                                        children: [
                                          const SizedBox(height: 12),
                                          _buildStatusCard(job),
                                          const SizedBox(height: 12),
                                          _buildCustomerCard(job),
                                          const SizedBox(height: 12),
                                          _buildDetailCard(job),
                                          const SizedBox(height: 16),

                                          // --- Slide Action Button ---
                                          if (job.nextStatus != null)
                                            SlideToActionButton(
                                              label: job.nextAction,
                                              icon: job.statusIcon,
                                              color: job.statusColor,
                                              isLoading: c.updatingStatus,
                                              onSlideComplete: () => _updateStatus(c, job),
                                            ),
                                          if (_canCancel(job)) ...[
                                            const SizedBox(height: 12),
                                            OutlinedButton.icon(
                                              onPressed: c.updatingStatus ? null : () => _showCancelDialog(c, job),
                                              icon: const Icon(Icons.cancel_outlined),
                                              label: const Text('Cancelar pedido'),
                                              style: OutlinedButton.styleFrom(
                                                foregroundColor: MyColor.redCancelTextColor,
                                                side: const BorderSide(color: MyColor.redCancelTextColor),
                                                minimumSize: const Size.fromHeight(48),
                                              ),
                                            ),
                                          ],
                                          const SizedBox(height: 20),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
                      ],
                    ),
        );
      },
    );
  }

  void _centerOnCourier() async {
    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
      );
      _mapController?.animateCamera(
        CameraUpdate.newLatLngZoom(
          LatLng(position.latitude, position.longitude),
          16,
        ),
      );
    } catch (_) {}
  }

  void _updateMap(CourierJobModel job) async {
    if (_mapController == null || _mapUpdated) return;
    _mapUpdated = true;
    _markers.clear();
    _polylines.clear();

    LatLng? pickup;
    LatLng? delivery;
    LatLng? courierPos;

    if (job.pickupLat != null && job.pickupLng != null) {
      pickup = LatLng(job.pickupLat!, job.pickupLng!);
      _markers.add(Marker(
        markerId: const MarkerId('pickup'),
        position: pickup,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
        infoWindow: const InfoWindow(title: 'Recogida'),
      ));
    }
    if (job.deliveryLat != null && job.deliveryLng != null) {
      delivery = LatLng(job.deliveryLat!, job.deliveryLng!);
      _markers.add(Marker(
        markerId: const MarkerId('delivery'),
        position: delivery,
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
        infoWindow: const InfoWindow(title: 'Entrega'),
      ));
    }

    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.high),
      );
      courierPos = LatLng(position.latitude, position.longitude);
    } catch (_) {}

    if (courierPos != null) {
      _courierMarkerIcon ??= await _loadCourierIcon();
      _markers.add(Marker(
        markerId: const MarkerId('courier'),
        position: courierPos,
        icon: _courierMarkerIcon ?? BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
        infoWindow: const InfoWindow(title: 'Mi ubicación'),
        anchor: const Offset(0.5, 0.5),
      ));
    }

    // Keep the courier marker following the device location in real time
    _startLocationStream();

    if (pickup != null && delivery != null && !_routeLoaded) {
      _routeLoaded = true;
      final result = await DirectionsService.getDirections(
        originLat: pickup.latitude,
        originLng: pickup.longitude,
        destLat: delivery.latitude,
        destLng: delivery.longitude,
        apiKey: Environment.mapKey,
      );
      if (result != null) {
        _polylines.add(Polyline(
          polylineId: const PolylineId('route'),
          points: result.polylinePoints,
          color: MyColor.primaryColor,
          width: 4,
        ));
      }
    }

    if (mounted) setState(() {});

    final positions = <LatLng>[];
    if (pickup != null) positions.add(pickup);
    if (delivery != null) positions.add(delivery);
    if (courierPos != null) positions.add(courierPos);

    if (positions.length >= 2) {
      double minLat = positions.first.latitude;
      double maxLat = positions.first.latitude;
      double minLng = positions.first.longitude;
      double maxLng = positions.first.longitude;
      for (var p in positions) {
        if (p.latitude < minLat) minLat = p.latitude;
        if (p.latitude > maxLat) maxLat = p.latitude;
        if (p.longitude < minLng) minLng = p.longitude;
        if (p.longitude > maxLng) maxLng = p.longitude;
      }
      final bounds = LatLngBounds(
        southwest: LatLng(minLat, minLng),
        northeast: LatLng(maxLat, maxLng),
      );
      _mapController!.animateCamera(CameraUpdate.newLatLngBounds(bounds, 60));
    }
  }

  Future<BitmapDescriptor?> _loadCourierIcon() async {
    try {
      return await BitmapDescriptor.asset(
        const ImageConfiguration(size: Size(48, 48)),
        'assets/images/delivery_man_marker.png',
      );
    } catch (_) {
      return null;
    }
  }

  Future<void> _setCourierMarker(LatLng pos) async {
    _courierMarkerIcon ??= await _loadCourierIcon();
    _markers.removeWhere((m) => m.markerId.value == 'courier');
    _markers.add(Marker(
      markerId: const MarkerId('courier'),
      position: pos,
      icon: _courierMarkerIcon ?? BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
      infoWindow: const InfoWindow(title: 'Mi ubicación'),
      anchor: const Offset(0.5, 0.5),
    ));
    if (mounted) setState(() {});
  }

  void _startLocationStream() {
    if (_positionSub != null) return;
    try {
      _positionSub = Geolocator.getPositionStream(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          distanceFilter: 5,
        ),
      ).listen(
        (position) => _setCourierMarker(LatLng(position.latitude, position.longitude)),
        onError: (_) {},
      );
    } catch (_) {}
  }

  @override
  void dispose() {
    _positionSub?.cancel();
    super.dispose();
  }

  Widget _buildCustomerCard(CourierJobModel job) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        border: Border.all(color: MyColor.borderColor.withValues(alpha: 0.5)),
      ),
      child: Row(children: [
        CircleAvatar(
          radius: 22,
          backgroundColor: MyColor.primaryColor.withValues(alpha: 0.1),
          child: Icon(Icons.person, color: MyColor.primaryColor, size: 24),
        ),
        SizedBox(width: Dimensions.space10),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(job.customerName ?? 'Cliente', style: boldDefault),
            if (job.customerPhone != null) Text(job.customerPhone!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
          ]),
        ),
        IconButton(
          icon: const Icon(Icons.call_rounded, color: Color(0xFF10B981), size: 22),
          onPressed: () {
            final phone = job.customerPhone;
            if (phone != null && phone.isNotEmpty) {
              launchUrl(Uri.parse('tel:$phone'));
            }
          },
        ),
        IconButton(
          icon: const Icon(Icons.chat_rounded, color: MyColor.primaryColor, size: 22),
          onPressed: () => Get.to(() => CourierChatScreen(jobId: job.id ?? 0, customerName: job.customerName ?? 'Cliente')),
        ),
      ]),
    );
  }

  Widget _buildStatusCard(CourierJobModel job) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: job.statusColor.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        border: Border.all(color: job.statusColor.withValues(alpha: 0.3)),
      ),
      child: Column(children: [
        Row(children: [
          Icon(job.statusIcon, color: job.statusColor, size: 28),
          SizedBox(width: Dimensions.space10),
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(job.statusLabel, style: boldLarge.copyWith(color: job.statusColor)),
              SizedBox(height: 2),
              Text(job.isFavor ? 'Servicio de favor' : 'Pedido de delivery', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
            ]),
          ),
          Text('S/ ${job.totalEarning?.toStringAsFixed(2) ?? "0.00"}', style: boldLarge.copyWith(color: const Color(0xFF10B981))),
        ]),
        if (job.isExpress) ...[
          SizedBox(height: 8),
          Container(
            padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.bolt_rounded, size: 16, color: const Color(0xFFF59E0B)),
                SizedBox(width: 4),
                Text('EXPRESS', style: regularSmall.copyWith(color: const Color(0xFFF59E0B), fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        ],
        if (job.shipmentType != null) ...[
          SizedBox(height: 8),
          Container(
            padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: const Color(0xFF3B82F6).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(ShipmentType.icon(job.shipmentType), style: TextStyle(fontSize: 14)),
                SizedBox(width: 4),
                Text(ShipmentType.label(job.shipmentType), style: regularSmall.copyWith(color: const Color(0xFF3B82F6), fontWeight: FontWeight.w600)),
              ],
            ),
          ),
        ],
        if (job.isHeavy || job.isTemperatureControlled) ...[
          SizedBox(height: 8),
          Wrap(
            spacing: 6,
            runSpacing: 4,
            children: [
              if (job.isHeavy)
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.orange.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text('🏋️', style: TextStyle(fontSize: 13)),
                      SizedBox(width: 4),
                      Text('Pesado', style: regularSmall.copyWith(color: Colors.orange, fontWeight: FontWeight.w600)),
                    ],
                  ),
                ),
              if (job.isTemperatureControlled)
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: const Color(0xFF06B6D4).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text('🧊', style: TextStyle(fontSize: 13)),
                      SizedBox(width: 4),
                      Text('Refrigerado', style: regularSmall.copyWith(color: const Color(0xFF06B6D4), fontWeight: FontWeight.w600)),
                    ],
                  ),
                ),
            ],
          ),
        ],
        if (job.hasReturn) ...[
          SizedBox(height: 8),
          Container(
            padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.orange.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.swap_horiz_rounded, size: 16, color: Colors.orange),
                SizedBox(width: 4),
                Text(job.returnStatusLabel, style: regularSmall.copyWith(color: Colors.orange, fontWeight: FontWeight.w700)),
              ],
            ),
          ),
        ],
        if (job.etaText != null && job.isActive) ...[
          SizedBox(height: 8),
          Container(
            padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: const Color(0xFF10B981).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.schedule_rounded, size: 16, color: const Color(0xFF10B981)),
                SizedBox(width: 4),
                Text('ETA: ${job.etaText}', style: regularSmall.copyWith(color: const Color(0xFF10B981), fontWeight: FontWeight.w600)),
                if (job.etaDistanceText != null) Text(' (${job.etaDistanceText})', style: regularSmall.copyWith(color: const Color(0xFF10B981))),
              ],
            ),
          ),
        ],
      ]),
    );
  }

  Widget _buildDetailCard(CourierJobModel job) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        border: Border.all(color: MyColor.borderColor.withValues(alpha: 0.5)),
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('Detalles del pedido', style: boldLarge),
        SizedBox(height: Dimensions.space8),
        _row('Recogida', job.pickupAddress),
        SizedBox(height: Dimensions.space6),
        _row('Entrega', job.deliveryAddress),
        if (job.description != null) ...[
          SizedBox(height: Dimensions.space6),
          _row('Descripción', job.description),
        ],
        if (job.storeName != null) ...[
          SizedBox(height: Dimensions.space6),
          _row('Tienda', job.storeName),
        ],
        if (job.requestedAtText != null) ...[
          SizedBox(height: Dimensions.space6),
          _row('Fecha del pedido', job.requestedAtText),
        ],
        if (job.status == 'delivered' && job.deliveredAtText != null) ...[
          SizedBox(height: Dimensions.space6),
          _row('Fecha de entrega', job.deliveredAtText),
        ],
        if (job.packageWeightKg != null || job.isFragile || job.itemValue != null) ...[
          SizedBox(height: Dimensions.space12),
          Divider(color: MyColor.borderColor),
          SizedBox(height: Dimensions.space8),
          Text('Paquete', style: boldDefault.copyWith(color: MyColor.primaryColor)),
          SizedBox(height: Dimensions.space6),
          if (job.packageWeightKg != null) _row('Peso', '${job.packageWeightKg!.toStringAsFixed(1)} kg'),
          if (job.packageDimensions != null) ...[
            SizedBox(height: Dimensions.space6),
            _row('Dimensiones', job.packageDimensions),
          ],
          if (job.isFragile) ...[
            SizedBox(height: Dimensions.space6),
            Row(
              children: [
                SizedBox(width: 90, child: Text('Cuidado', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))),
                Icon(Icons.warning_amber_rounded, size: 16, color: Colors.red),
                SizedBox(width: 4),
                Text('Frágil — Manejar con cuidado', style: regularDefault.copyWith(color: Colors.red, fontWeight: FontWeight.w600)),
              ],
            ),
          ],
          if (job.itemValue != null) ...[
            SizedBox(height: Dimensions.space6),
            _row('Valor', 'S/ ${job.itemValue!.toStringAsFixed(2)}'),
          ],
        ],
        if (job.scheduledAt != null || job.timeSlot != null) ...[
          SizedBox(height: Dimensions.space12),
          Divider(color: MyColor.borderColor),
          SizedBox(height: Dimensions.space8),
          Text('Programación', style: boldDefault.copyWith(color: MyColor.primaryColor)),
          SizedBox(height: Dimensions.space6),
          if (job.timeSlot != null) _row('Franja', TimeSlots.slots[job.timeSlot] ?? job.timeSlot!),
          if (job.scheduledAt != null) _row('Programado para', job.scheduledAt!),
        ],
        SizedBox(height: Dimensions.space12),
        Divider(color: MyColor.borderColor),
        SizedBox(height: Dimensions.space8),
        SizedBox(height: Dimensions.space6),
        _row('Delivery', 'S/ ${job.deliveryFee?.toStringAsFixed(2) ?? "0.00"}'),
        if (job.isDelivery) ...[
          SizedBox(height: Dimensions.space6),
          _row('Método de pago', job.paymentMethodName ?? 'Efectivo', bold: true),
          if (job.isDigitalWallet) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: (job.paymentWallet == 'yape' ? const Color(0xFF7000FF) : const Color(0xFF00A3E0)).withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(
                  color: (job.paymentWallet == 'yape' ? const Color(0xFF7000FF) : const Color(0xFF00A3E0)).withValues(alpha: 0.3),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        job.paymentWallet == 'yape' ? Icons.qr_code_2_rounded : Icons.qr_code_scanner_rounded,
                        color: job.paymentWallet == 'yape' ? const Color(0xFF7000FF) : const Color(0xFF00A3E0),
                        size: 22,
                      ),
                      const SizedBox(width: 8),
                      Text(
                        'QR de Tienda (${job.paymentWallet?.toUpperCase() ?? "YAPE / PLIN"})',
                        style: boldDefault.copyWith(
                          color: job.paymentWallet == 'yape' ? const Color(0xFF7000FF) : const Color(0xFF00A3E0),
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  if (job.paymentQrString != null && job.paymentQrString!.isNotEmpty)
                    Center(
                      child: Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          boxShadow: const [BoxShadow(color: Colors.black12, blurRadius: 6)],
                        ),
                        child: QrImageView(
                          data: job.paymentQrString!,
                          version: QrVersions.auto,
                          size: 160.0,
                        ),
                      ),
                    )
                  else
                    Text(
                      'Cobrar con ${job.paymentWallet?.toUpperCase() ?? "QR"}: S/ ${(job.amount ?? 0.0).toStringAsFixed(2)}',
                      style: boldDefault.copyWith(color: MyColor.primaryTextColor, fontSize: 12),
                    ),
                  const SizedBox(height: 6),
                  Center(
                    child: Text(
                      'Muestra este código QR al cliente al momento de entregar',
                      style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                    ),
                  ),
                ],
              ),
            ),
          ],
          SizedBox(height: Dimensions.space6),
          _row('Total a cobrar', 'S/ ${job.amount?.toStringAsFixed(2) ?? "0.00"}', bold: true, color: const Color(0xFFDC2626)),
        ],
        if (job.isFavor) ...[
          SizedBox(height: Dimensions.space6),
          _row('Quién paga', PayerType.displayName(job.payerType), bold: true),
          if (job.payerType == 'recipient') ...[
            SizedBox(height: Dimensions.space6),
            _row('Cobrar al cliente', 'S/ ${job.codAmount?.toStringAsFixed(2) ?? job.deliveryFee?.toStringAsFixed(2) ?? "0.00"}', bold: true, color: const Color(0xFFDC2626)),
          ],
        ],
        if (job.evidenceType != null) ...[
          SizedBox(height: Dimensions.space6),
          _row('Evidencia', EvidenceType.label(job.evidenceType)),
        ],
        if (job.commission != null) ...[
          SizedBox(height: Dimensions.space6),
          _rowCommission('Comisión', '- S/ ${job.commission!.toStringAsFixed(2)}'),
        ],
        SizedBox(height: Dimensions.space6),
        _row('Total a ganar', 'S/ ${job.totalEarning?.toStringAsFixed(2) ?? "0.00"}', bold: true, color: const Color(0xFF10B981)),
      ]),
    );
  }

  Widget _row(String label, String? value, {bool bold = false, Color? color}) {
    if (value == null) return const SizedBox.shrink();
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(width: 90, child: Text(label, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))),
        Expanded(child: Text(value, style: (bold ? boldDefault : regularDefault).copyWith(color: color))),
      ],
    );
  }

  Widget _rowCommission(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(width: 90, child: Text(label, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))),
        Icon(Icons.arrow_downward_rounded, size: 14, color: MyColor.redCancelTextColor),
        SizedBox(width: 2),
        Expanded(child: Text(value, style: boldDefault.copyWith(color: MyColor.redCancelTextColor))),
      ],
    );
  }

  void _updateStatus(CourierController c, CourierJobModel job) {
    if (job.nextStatus == 'return_picked_up') {
      _confirmReturnPickup(c, job);
      return;
    }
    if (job.nextStatus == 'return_completed') {
      _confirmReturnComplete(c, job);
      return;
    }

    if (job.nextStatus == 'delivered') {
      if (job.isDelivery) {
        _showPaymentDialog(c, job);
      } else {
        _showProofDialog(c, job);
      }
    } else {
      _confirmStatus(c, job);
    }
  }

  bool _canCancel(CourierJobModel job) {
    return const {'accepted', 'on_way', 'on_way_to_pickup'}.contains(job.status);
  }

  Future<void> _showCancelDialog(CourierController c, CourierJobModel job) async {
    const reasons = <String, String>{
      'vehicle_issue': 'Problema con mi vehículo',
      'route_or_distance': 'Ruta o distancia no viable',
      'store_delay': 'Demora excesiva en el punto de recojo',
      'personal_emergency': 'Emergencia personal',
      'other': 'Otro motivo',
    };
    String selectedReason = 'vehicle_issue';
    final detailController = TextEditingController();

    await showDialog<void>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: const Text('¿Cancelar pedido?'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'El pedido se ofrecerá nuevamente a otros repartidores. No podrás aceptar nuevos pedidos durante 30 min la primera vez, 2 horas la segunda y 24 horas desde la tercera cancelación en 7 días.',
                ),
                const SizedBox(height: 12),
                const Text('Selecciona un motivo'),
                ...reasons.entries.map((entry) => RadioListTile<String>(
                      value: entry.key,
                      groupValue: selectedReason,
                      contentPadding: EdgeInsets.zero,
                      title: Text(entry.value),
                      onChanged: (value) => setDialogState(() => selectedReason = value!),
                    )),
                TextField(
                  controller: detailController,
                  maxLength: 500,
                  maxLines: 2,
                  decoration: const InputDecoration(hintText: 'Detalle opcional'),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.of(dialogContext).pop(), child: const Text('Volver')),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: MyColor.redCancelTextColor, foregroundColor: Colors.white),
              onPressed: c.updatingStatus
                  ? null
                  : () async {
                      Navigator.of(dialogContext).pop();
                      final ok = await c.cancelJob(job.id ?? 0, job.type ?? 'delivery', selectedReason, reasonDetail: detailController.text);
                      if (!mounted) return;
                      if (ok) {
                        Get.back();
                        Get.snackbar('Pedido liberado', 'Otros repartidores ya fueron notificados.', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
                      } else {
                        Get.snackbar('No se pudo cancelar', c.statusError ?? 'Inténtalo nuevamente.', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
                      }
                    },
              child: const Text('Cancelar pedido'),
            ),
          ],
        ),
      ),
    );
    detailController.dispose();
  }

  void _confirmStatus(CourierController c, CourierJobModel job) {
    bool confirming = false;
    Get.defaultDialog(
      title: job.nextAction,
      middleText: '¿Confirmas ${job.nextAction.toLowerCase()}?',
      textConfirm: 'Confirmar',
      textCancel: 'Cancelar',
      confirmTextColor: MyColor.colorWhite,
      onConfirm: () async {
        if (confirming) return;
        confirming = true;
        if (Navigator.of(context).canPop()) {
          Navigator.of(context).pop();
        }
        bool ok = await c.updateJobStatus(job.id ?? 0, job.nextStatus ?? '', type: job.type);
        if (ok) {
          Get.snackbar('Actualizado', 'Estado actualizado correctamente', backgroundColor: job.statusColor, colorText: MyColor.colorWhite);
        } else if (c.statusError != null) {
          Get.snackbar(
            'Error',
            c.statusError!,
            backgroundColor: MyColor.redCancelTextColor,
            colorText: MyColor.colorWhite,
            duration: const Duration(seconds: 5),
            mainButton: c.statusError!.contains('saldo') || c.statusError!.contains('Recarga')
                ? TextButton(
                    onPressed: () => Get.toNamed(RouteHelper.newDepositScreenScreen),
                    child: Text('Recargar', style: boldDefault.copyWith(color: MyColor.colorWhite)),
                  )
                : null,
          );
        }
      },
    );
  }

  void _showPaymentDialog(CourierController c, CourierJobModel job) {
    final mustCollect = job.requiresPaymentCollection;
    final hasQr = job.paymentQrString?.trim().isNotEmpty == true;
    final walletName = (job.paymentWallet ?? job.paymentMethodName ?? 'Pago').toUpperCase();

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        title: const Text('Cobro del pedido'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(job.paymentMethodName ?? 'Efectivo', style: boldLarge.copyWith(color: MyColor.primaryColor)),
              SizedBox(height: Dimensions.space8),
              Text('S/ ${job.amount?.toStringAsFixed(2) ?? "0.00"}', style: boldLarge.copyWith(fontSize: 30, color: const Color(0xFFDC2626))),
              SizedBox(height: Dimensions.space8),
              Text(
                mustCollect ? 'Cobra al cliente el monto total antes de confirmar la entrega.' : 'Este pedido ya figura como pagado. No vuelvas a cobrar al cliente.',
                textAlign: TextAlign.center,
                style: regularDefault,
              ),
              if (mustCollect && job.isDigitalWallet) ...[
                SizedBox(height: Dimensions.space16),
                if (hasQr) ...[
                  Container(
                    color: Colors.white,
                    padding: const EdgeInsets.all(10),
                    child: QrImageView(data: job.paymentQrString!.trim(), size: 220, backgroundColor: Colors.white),
                  ),
                  SizedBox(height: Dimensions.space8),
                  Text('Muestra este QR de $walletName al cliente', textAlign: TextAlign.center, style: boldDefault),
                ] else
                  Container(
                    padding: EdgeInsets.all(Dimensions.space12),
                    decoration: BoxDecoration(color: Colors.orange.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)),
                    child: const Text('La tienda no configuró la cadena QR. Coordina otro medio de cobro con el cliente.', textAlign: TextAlign.center),
                  ),
              ],
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancelar')),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(ctx);
              _showProofDialog(c, job, paymentConfirmed: mustCollect);
            },
            style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, foregroundColor: MyColor.colorWhite),
            child: Text(mustCollect ? 'Confirmar pago recibido' : 'Continuar'),
          ),
        ],
      ),
    );
  }

  void _showProofDialog(CourierController c, CourierJobModel job, {bool paymentConfirmed = false}) {
    final descCtrl = TextEditingController();
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => _ProofDialog(
        job: job,
        c: c,
        descCtrl: descCtrl,
        paymentConfirmed: paymentConfirmed,
      ),
    );
  }

  void _confirmReturnPickup(CourierController c, CourierJobModel job) {
    bool confirming = false;
    Get.defaultDialog(
      title: 'Recoger devolución',
      middleText: '¿Confirmas que recogiste el paquete para devolución?',
      textConfirm: 'Confirmar',
      textCancel: 'Cancelar',
      confirmTextColor: MyColor.colorWhite,
      onConfirm: () async {
        if (confirming) return;
        confirming = true;
        if (Navigator.of(context).canPop()) {
          Navigator.of(context).pop();
        }
        bool ok = await c.pickupReturn(job.id ?? 0, type: job.type ?? 'favor');
        if (ok) {
          Get.snackbar('Devolución', 'Devolución en tránsito', backgroundColor: const Color(0xFFF59E0B), colorText: MyColor.colorWhite);
        }
      },
    );
  }

  void _confirmReturnComplete(CourierController c, CourierJobModel job) {
    bool confirming = false;
    Get.defaultDialog(
      title: 'Completar devolución',
      middleText: '¿Confirmas que entregaste el paquete de vuelta en la tienda?',
      textConfirm: 'Confirmar',
      textCancel: 'Cancelar',
      confirmTextColor: MyColor.colorWhite,
      onConfirm: () async {
        if (confirming) return;
        confirming = true;
        if (Navigator.of(context).canPop()) {
          Navigator.of(context).pop();
        }
        bool ok = await c.completeReturn(job.id ?? 0, type: job.type ?? 'favor');
        if (ok) {
          Get.snackbar('Devolución', 'Devolución completada', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
        }
      },
    );
  }
}

class _ProofDialog extends StatefulWidget {
  final CourierJobModel job;
  final dynamic c;
  final TextEditingController descCtrl;
  final bool paymentConfirmed;

  const _ProofDialog({
    required this.job,
    required this.c,
    required this.descCtrl,
    required this.paymentConfirmed,
  });

  @override
  State<_ProofDialog> createState() => _ProofDialogState();
}

class _ProofDialogState extends State<_ProofDialog> {
  File? _proofImage;
  bool _submitting = false;
  final pinController = TextEditingController();

  @override
  void dispose() {
    widget.descCtrl.dispose();
    pinController.dispose();
    super.dispose();
  }

  Widget _pickOption(IconData icon, String label, Future<void> Function() onTap) {
    return GestureDetector(
      onTap: () => onTap(),
      child: Column(
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: MyColor.primaryColor.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, size: 30, color: MyColor.primaryColor),
          ),
          SizedBox(height: 6),
          Text(label, style: regularSmall.copyWith(color: MyColor.primaryTextColor)),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final evidenceType = widget.job.evidenceType ?? 'photo';
    final requiresPhoto = EvidenceType.requiresPhoto(evidenceType);
    final requiresPin = EvidenceType.requiresPin(evidenceType) || widget.job.requiresPin;

    final imageSection = (requiresPhoto && _proofImage == null)
        ? <Widget>[
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                _pickOption(Icons.camera_alt_rounded, 'Cámara', () async {
                  final xFile = await ImagePicker().pickImage(source: ImageSource.camera, imageQuality: 80);
                  if (xFile != null && mounted) setState(() => _proofImage = File(xFile.path));
                }),
                SizedBox(width: 24),
                _pickOption(Icons.photo_library_rounded, 'Galería', () async {
                  final xFile = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80);
                  if (xFile != null && mounted) setState(() => _proofImage = File(xFile.path));
                }),
              ],
            ),
            SizedBox(height: Dimensions.space8),
          ]
        : (requiresPhoto && _proofImage != null)
            ? <Widget>[
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                      child: ConstrainedBox(
                        constraints: BoxConstraints(maxHeight: 180, maxWidth: MediaQuery.of(context).size.width * 0.65),
                        child: Image.file(
                          _proofImage!,
                          fit: BoxFit.contain,
                          errorBuilder: (ctx, e, stack) {
                            return Container(height: 120, color: MyColor.cardBgColor, child: Icon(Icons.broken_image, size: 40, color: MyColor.bodyMutedTextColor));
                          },
                        ),
                      ),
                    ),
                    Positioned(
                      top: -8,
                      right: -8,
                      child: GestureDetector(
                        onTap: () => setState(() => _proofImage = null),
                        child: Container(
                          padding: EdgeInsets.all(4),
                          decoration: BoxDecoration(color: MyColor.redCancelTextColor, shape: BoxShape.circle),
                          child: Icon(Icons.close, size: 14, color: MyColor.colorWhite),
                        ),
                      ),
                    ),
                  ],
                ),
                SizedBox(height: Dimensions.space8),
              ]
            : <Widget>[];

    return AlertDialog(
      title: Text('Confirmar entrega'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text('Sube una foto del comprobante de entrega', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
          SizedBox(height: Dimensions.space12),
          ...imageSection,
          SizedBox(height: Dimensions.space8),
          TextField(
            controller: widget.descCtrl,
            decoration: InputDecoration(
              hintText: 'Nota adicional (opcional)',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
              isDense: true,
            ),
          ),
          if (requiresPin) ...[
            SizedBox(height: Dimensions.space12),
            Container(
              padding: EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF8B5CF6).withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFF8B5CF6).withValues(alpha: 0.3)),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      Icon(Icons.lock_outline, size: 18, color: const Color(0xFF8B5CF6)),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'Requiere PIN del cliente',
                          style: regularSmall.copyWith(fontWeight: FontWeight.w600, color: const Color(0xFF8B5CF6)),
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 8),
                  Text(
                    'Solicita el código PIN de 4 dígitos al cliente para confirmar la entrega.',
                    style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'El cliente recibe este PIN del vendedor.',
                    style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10, fontStyle: FontStyle.italic),
                  ),
                  SizedBox(height: 8),
                  TextField(
                    controller: pinController,
                    keyboardType: TextInputType.number,
                    maxLength: 4,
                    textAlign: TextAlign.center,
                    style: boldLarge.copyWith(fontSize: 24, letterSpacing: 8),
                    decoration: InputDecoration(
                      hintText: 'PIN',
                      counterText: '',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(10),
                        borderSide: BorderSide(color: const Color(0xFF8B5CF6)),
                      ),
                      isDense: true,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
      actions: [
        TextButton(
          onPressed: _submitting ? null : () => Navigator.pop(context),
          child: Text('Cancelar'),
        ),
        ElevatedButton(
          onPressed: _submitting
              ? null
              : () async {
                  if (requiresPhoto && _proofImage == null) {
                    Get.snackbar('Falta foto', 'Sube una foto del comprobante', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
                    return;
                  }
                  if (requiresPin && pinController.text.length != 4) {
                    Get.snackbar('Falta PIN', 'Ingresa el PIN de 4 dígitos del cliente', backgroundColor: const Color(0xFF8B5CF6), colorText: MyColor.colorWhite);
                    return;
                  }

                  setState(() => _submitting = true);

                  if (_proofImage != null) {
                    final uploaded = await widget.c.uploadProofImage(widget.job.id ?? 0, _proofImage!);
                    if (!uploaded) {
                      if (mounted) setState(() => _submitting = false);
                      Get.snackbar('Error al subir foto', 'No se pudo subir la foto del comprobante. Verifica tu conexión e inténtalo de nuevo.', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite, duration: const Duration(seconds: 5));
                      return;
                    }
                  }

                  final pinCode = requiresPin ? pinController.text : null;
                  if (!mounted) return;
                  Navigator.pop(context);

                  bool ok = await widget.c.updateJobStatusWithPin(
                    widget.job.id ?? 0,
                    widget.job.nextStatus ?? '',
                    type: widget.job.type,
                    paymentConfirmed: widget.paymentConfirmed,
                    pinCode: pinCode,
                  );
                  if (ok) {
                    Get.snackbar('Entregado', 'Pedido completado correctamente', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
                  } else if (widget.c.statusError != null) {
                    Get.snackbar('Error', widget.c.statusError!, backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite, duration: const Duration(seconds: 5));
                  } else if (widget.c.pinError != null) {
                    Get.snackbar('PIN incorrecto', widget.c.pinError!, backgroundColor: const Color(0xFF8B5CF6), colorText: MyColor.colorWhite, duration: const Duration(seconds: 5));
                  }
                },
          style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, foregroundColor: MyColor.colorWhite),
          child: _submitting ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Text('Confirmar entrega'),
        ),
      ],
    );
  }
}

class SlideToActionButton extends StatefulWidget {
  final String label;
  final IconData icon;
  final Color color;
  final bool isLoading;
  final VoidCallback onSlideComplete;

  const SlideToActionButton({
    super.key,
    required this.label,
    required this.icon,
    required this.color,
    required this.isLoading,
    required this.onSlideComplete,
  });

  @override
  State<SlideToActionButton> createState() => _SlideToActionButtonState();
}

class _SlideToActionButtonState extends State<SlideToActionButton> {
  double _dragPosition = 0.0;
  bool _isCompleted = false;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final maxDrag = constraints.maxWidth - 56;
        return Container(
          height: 56,
          decoration: BoxDecoration(
            color: widget.color.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(28),
            border: Border.all(color: widget.color.withValues(alpha: 0.3)),
          ),
          child: Stack(
            children: [
              Center(
                child: widget.isLoading
                    ? SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(strokeWidth: 2.5, color: widget.color),
                      )
                    : Text(
                        _isCompleted ? '¡PROCESANDO...!' : 'DESLIZA: ${widget.label.toUpperCase()}',
                        style: boldDefault.copyWith(
                          color: widget.color,
                          fontSize: Dimensions.fontSmall + 1,
                          letterSpacing: 0.8,
                        ),
                      ),
              ),
              Positioned(
                left: _dragPosition,
                top: 4,
                bottom: 4,
                child: GestureDetector(
                  onHorizontalDragUpdate: (details) {
                    if (widget.isLoading) return;
                    setState(() {
                      _dragPosition += details.delta.dx;
                      if (_dragPosition < 0) _dragPosition = 0;
                      if (_dragPosition > maxDrag) _dragPosition = maxDrag;
                    });
                  },
                  onHorizontalDragEnd: (details) {
                    if (widget.isLoading) return;
                    if (_dragPosition >= maxDrag * 0.70) {
                      setState(() {
                        _dragPosition = maxDrag;
                        _isCompleted = true;
                      });
                      widget.onSlideComplete();
                      Future.delayed(const Duration(milliseconds: 600), () {
                        if (mounted) {
                          setState(() {
                            _dragPosition = 0.0;
                            _isCompleted = false;
                          });
                        }
                      });
                    } else {
                      setState(() {
                        _dragPosition = 0.0;
                        _isCompleted = false;
                      });
                    }
                  },
                  child: Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: widget.color,
                      shape: BoxShape.circle,
                      boxShadow: const [
                        BoxShadow(
                          color: Colors.black26,
                          blurRadius: 6,
                          offset: Offset(2, 2),
                        ),
                      ],
                    ),
                    child: Icon(
                      widget.icon,
                      color: Colors.white,
                      size: 24,
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
