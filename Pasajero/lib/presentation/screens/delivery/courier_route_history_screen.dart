import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';

class CourierRouteHistoryScreen extends StatelessWidget {
  final String orderNo;
  final List<Map<String, double>> routePoints;
  const CourierRouteHistoryScreen({
    super.key,
    required this.orderNo,
    required this.routePoints,
  });

  @override
  Widget build(BuildContext context) {
    final points = routePoints.map((p) => LatLng(p['lat']!, p['lng']!)).toList();
    final polyline = Polyline(
      polylineId: const PolylineId('courier_route'),
      points: points,
      color: MyColor.primaryColor,
      width: 5,
    );
    final startMarker = Marker(
      markerId: const MarkerId('start'),
      position: points.first,
      icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
      infoWindow: const InfoWindow(title: 'Inicio'),
    );
    final endMarker = Marker(
      markerId: const MarkerId('end'),
      position: points.last,
      icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
      infoWindow: const InfoWindow(title: 'Fin'),
    );

    LatLng? initialPos;
    try {
      initialPos = LatLng(
        (points.first.latitude + points.last.latitude) / 2,
        (points.first.longitude + points.last.longitude) / 2,
      );
    } catch (_) {
      initialPos = const LatLng(-12.0464, -77.0428);
    }

    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Ruta del pedido $orderNo', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
      ),
      body: Stack(
        children: [
          GoogleMap(
            initialCameraPosition: CameraPosition(target: initialPos!, zoom: 14),
            markers: {startMarker, endMarker},
            polylines: {polyline},
            zoomControlsEnabled: false,
          ),
          Positioned(
            bottom: Dimensions.space20,
            left: Dimensions.space20,
            right: Dimensions.space20,
            child: Container(
              padding: EdgeInsets.all(Dimensions.space12),
              decoration: BoxDecoration(
                color: MyColor.colorWhite,
                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 8, offset: const Offset(0, 2))],
              ),
              child: Row(children: [
                Container(
                  width: 12,
                  height: 12,
                  decoration: BoxDecoration(color: const Color(0xFF10B981), shape: BoxShape.circle),
                ),
                SizedBox(width: Dimensions.space6),
                Text('Inicio', style: regularSmall),
                SizedBox(width: Dimensions.space16),
                Container(
                  width: 12,
                  height: 12,
                  decoration: BoxDecoration(color: MyColor.redCancelTextColor, shape: BoxShape.circle),
                ),
                SizedBox(width: Dimensions.space6),
                Text('Fin', style: regularSmall),
                Spacer(),
                Text('${points.length} puntos', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
              ]),
            ),
          ),
        ],
      ),
    );
  }
}
