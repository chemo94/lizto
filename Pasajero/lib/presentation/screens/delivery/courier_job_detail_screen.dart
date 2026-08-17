import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/courier_controller.dart';
import 'package:liztogo/data/repo/delivery/courier_repo.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'package:liztogo/data/model/delivery/courier_models.dart';

class CourierJobDetailScreen extends StatefulWidget {
  final int jobId;
  const CourierJobDetailScreen({super.key, required this.jobId});

  @override
  State<CourierJobDetailScreen> createState() => _CourierJobDetailScreenState();
}

class _CourierJobDetailScreenState extends State<CourierJobDetailScreen> {
  GoogleMapController? _mapController;
  final Set<Marker> _markers = {};
  File? _proofImage;
  bool _uploadingProof = false;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<CourierController>()) {
      Get.put(CourierController(courierRepo: CourierRepo(apiClient: Get.find<ApiClient>())));
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<CourierController>();
      c.loadJobDetail(widget.jobId).then((_) {
        final job = c.selectedJob;
        if (job != null && (job.status == 'on_way' || job.status == 'accepted' || job.status == 'on_way_to_pickup' || job.status == 'at_pickup' || job.status == 'on_way_to_delivery')) {
          c.startLocationBroadcast(widget.jobId);
        }
      });
    });
  }

  @override
  void dispose() {
    Get.find<CourierController>().stopLocationBroadcast();
    _mapController?.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<CourierController>(
      builder: (c) {
        final job = c.selectedJob;
        if (job != null) {
          WidgetsBinding.instance.addPostFrameCallback((_) => _updateMap(job));
        }

        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text(job?.orderNo ?? 'Pedido', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            actions: [
              if (job != null && job.isFavor)
                Padding(
                  padding: EdgeInsets.only(right: Dimensions.space4),
                  child: Container(
                    padding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    margin: EdgeInsets.symmetric(vertical: Dimensions.space12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF59E0B).withValues(alpha: 0.2),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text('Favor', style: regularSmall.copyWith(color: const Color(0xFFF59E0B), fontWeight: FontWeight.w600)),
                  ),
                ),
            ],
          ),
          body: c.isLoading
              ? const Center(child: CircularProgressIndicator())
              : job == null
                  ? const Center(child: Text('Pedido no encontrado'))
                  : Column(
                      children: [
                        SizedBox(
                          height: MediaQuery.of(context).size.height * 0.35,
                          child: GoogleMap(
                            initialCameraPosition: CameraPosition(
                              target: LatLng(job.pickupLat ?? -12.0464, job.pickupLng ?? -77.0428),
                              zoom: 14,
                            ),
                            markers: _markers,
                            onMapCreated: (controller) {
                              _mapController = controller;
                              _updateMap(job);
                            },
                            myLocationEnabled: true,
                            zoomControlsEnabled: false,
                          ),
                        ),
                        Expanded(
                          child: ListView(
                            padding: EdgeInsets.all(Dimensions.space16),
                            children: [
                              _buildCustomerCard(job),
                              SizedBox(height: Dimensions.space16),
                              _buildStatusCard(job),
                              SizedBox(height: Dimensions.space16),
                              _buildDetailCard(job),
                            ],
                          ),
                        ),
                      ],
                    ),
          bottomNavigationBar: job != null && job.nextStatus != null
              ? Container(
                  padding: EdgeInsets.all(Dimensions.space16),
                  decoration: BoxDecoration(
                    color: MyColor.colorWhite,
                    boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 8, offset: const Offset(0, -2))],
                  ),
                  child: SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: c.updatingStatus ? null : () => _updateStatus(c, job),
                      icon: c.updatingStatus
                          ? SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: MyColor.colorWhite))
                          : Icon(job.statusIcon, size: 20),
                      label: Text(job.nextAction, style: boldDefault.copyWith(color: MyColor.colorWhite)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: job.statusColor,
                        foregroundColor: MyColor.colorWhite,
                        padding: EdgeInsets.symmetric(vertical: Dimensions.space14),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                      ),
                    ),
                  ),
                )
              : null,
        );
      },
    );
  }

  void _updateMap(CourierJobModel job) {
    if (_mapController == null) return;
    _markers.clear();
    if (job.pickupLat != null && job.pickupLng != null) {
      _markers.add(Marker(
        markerId: const MarkerId('pickup'),
        position: LatLng(job.pickupLat!, job.pickupLng!),
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
        infoWindow: const InfoWindow(title: 'Recogida'),
      ));
    }
    if (job.deliveryLat != null && job.deliveryLng != null) {
      _markers.add(Marker(
        markerId: const MarkerId('delivery'),
        position: LatLng(job.deliveryLat!, job.deliveryLng!),
        icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
        infoWindow: const InfoWindow(title: 'Entrega'),
      ));
    }
  }

  Widget _buildCustomerCard(var job) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Row(children: [
        CircleAvatar(
          radius: 24,
          backgroundColor: MyColor.primaryColor.withValues(alpha: 0.1),
          child: Icon(Icons.person, color: MyColor.primaryColor, size: 28),
        ),
        SizedBox(width: Dimensions.space12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(job.customerName ?? 'Cliente', style: boldDefault),
            if (job.customerPhone != null)
              Text(job.customerPhone!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
          ]),
        ),
        IconButton(
          icon: Icon(Icons.call_rounded, color: const Color(0xFF10B981)),
          onPressed: () {},
        ),
        IconButton(
          icon: Icon(Icons.chat_rounded, color: MyColor.primaryColor),
          onPressed: () {},
        ),
      ]),
    );
  }

  Widget _buildStatusCard(var job) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: job.statusColor.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        border: Border.all(color: job.statusColor.withValues(alpha: 0.3)),
      ),
      child: Row(children: [
        Icon(job.statusIcon, color: job.statusColor, size: 32),
        SizedBox(width: Dimensions.space12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(job.statusLabel, style: boldLarge.copyWith(color: job.statusColor)),
            SizedBox(height: 2),
            Text(job.isFavor ? 'Servicio de favor' : 'Pedido de delivery', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
          ]),
        ),
        Text('S/ ${job.totalEarning?.toStringAsFixed(2) ?? "0.00"}', style: boldLarge.copyWith(color: const Color(0xFF10B981))),
      ]),
    );
  }

  Widget _buildDetailCard(var job) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
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
        SizedBox(height: Dimensions.space6),
        _row('Delivery', 'S/ ${job.deliveryFee?.toStringAsFixed(2) ?? "0.00"}'),
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

  void _updateStatus(CourierController c, var job) {
    if (job.nextStatus == 'delivered') {
      _showProofDialog(c, job);
    } else {
      _confirmStatus(c, job);
    }
  }

  void _confirmStatus(CourierController c, var job) {
    Get.defaultDialog(
      title: '${job.nextAction}',
      middleText: '¿Confirmas ${job.nextAction.toLowerCase()}?',
      textConfirm: 'Confirmar',
      textCancel: 'Cancelar',
      confirmTextColor: MyColor.colorWhite,
      onConfirm: () async {
        Get.back();
        bool ok = await c.updateJobStatus(job.id ?? 0, job.nextStatus ?? '');
        if (ok) {
          Get.snackbar('Actualizado', 'Estado actualizado correctamente',
              backgroundColor: job.statusColor, colorText: MyColor.colorWhite);
          final nextS = job.nextStatus;
          if (nextS == 'on_way' || nextS == 'accepted' || nextS == 'on_way_to_pickup' || nextS == 'at_pickup' || nextS == 'on_way_to_delivery') {
            c.startLocationBroadcast(job.id ?? 0);
          } else if (nextS == 'delivered' || nextS == 'cancelled') {
            c.stopLocationBroadcast();
          }
        }
      },
    );
  }

  void _showProofDialog(CourierController c, var job) {
    final descCtrl = TextEditingController();
    Get.defaultDialog(
      title: 'Confirmar entrega',
      content: StatefulBuilder(
        builder: (ctx, setDialogState) {
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('Sube una foto del comprobante de entrega', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
              SizedBox(height: Dimensions.space12),
              if (_proofImage != null)
                Stack(
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                      child: Image.file(_proofImage!, height: 120, width: double.infinity, fit: BoxFit.cover),
                    ),
                    Positioned(
                      top: 4, right: 4,
                      child: GestureDetector(
                        onTap: () => setDialogState(() => _proofImage = null),
                        child: Container(
                          padding: EdgeInsets.all(4),
                          decoration: BoxDecoration(color: MyColor.redCancelTextColor, shape: BoxShape.circle),
                          child: Icon(Icons.close, size: 14, color: MyColor.colorWhite),
                        ),
                      ),
                    ),
                  ],
                )
              else
                GestureDetector(
                  onTap: () async {
                    FilePickerResult? result = await FilePicker.platform.pickFiles(
                      allowMultiple: false,
                      type: FileType.custom,
                      allowedExtensions: ['jpg', 'jpeg', 'png'],
                    );
                    if (result != null) {
                      setDialogState(() => _proofImage = File(result.files.single.path!));
                    }
                  },
                  child: Container(
                    width: double.infinity, height: 120,
                    decoration: BoxDecoration(
                      border: Border.all(color: MyColor.borderColor, style: BorderStyle.solid),
                      borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                      color: MyColor.cardBgColor,
                    ),
                    child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                      Icon(Icons.camera_alt_rounded, size: 36, color: MyColor.bodyMutedTextColor),
                      SizedBox(height: 4),
                      Text('Toca para tomar foto', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                    ]),
                  ),
                ),
              SizedBox(height: Dimensions.space8),
              TextField(
                controller: descCtrl,
                decoration: InputDecoration(
                  hintText: 'Nota adicional (opcional)',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                  isDense: true,
                ),
              ),
            ],
          );
        },
      ),
      textConfirm: 'Confirmar entrega',
      textCancel: 'Cancelar',
      confirmTextColor: MyColor.colorWhite,
      onConfirm: () async {
        if (_proofImage == null) {
          Get.snackbar('Falta foto', 'Sube una foto del comprobante',
              backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
          return;
        }
        Get.back();
        setState(() => _uploadingProof = true);
        await c.uploadProofImage(job.id ?? 0, _proofImage!);
        bool ok = await c.updateJobStatus(job.id ?? 0, job.nextStatus ?? '');
        _proofImage = null;
        _uploadingProof = false;
        if (mounted) setState(() {});
        if (ok) {
          Get.snackbar('Entregado', 'Pedido completado correctamente',
              backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
        }
      },
    );
  }
}
