import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:http/http.dart' as http;
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/core/utils/url_container.dart';
import 'package:lizto_delivery/environment.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';

class MapLocationPickerScreen extends StatefulWidget {
  final double? initialLat;
  final double? initialLng;
  final String title;
  const MapLocationPickerScreen({
    super.key,
    this.initialLat,
    this.initialLng,
    this.title = 'Seleccionar ubicacion',
  });

  @override
  State<MapLocationPickerScreen> createState() => _MapLocationPickerScreenState();
}

class _MapLocationPickerScreenState extends State<MapLocationPickerScreen> {
  GoogleMapController? _mapController;
  LatLng? _selectedLocation;
  String _address = 'Moviendo el mapa...';
  final _searchCtrl = TextEditingController();
  List<_PlacePrediction> _predictions = [];
  bool _showPredictions = false;
  Timer? _debounce;
  bool _locating = false;

  @override
  void dispose() {
    _searchCtrl.dispose();
    _debounce?.cancel();
    _mapController?.dispose();
    super.dispose();
  }

  void _onSearchChanged(String q) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () => _searchPlace(q));
  }

  Future<void> _searchPlace(String q) async {
    if (q.length < 3) {
      setState(() {
        _predictions = [];
        _showPredictions = false;
      });
      return;
    }
    try {
      final url = '${UrlContainer.googleMapLocationSearch}/place/autocomplete/json?'
          'input=${Uri.encodeComponent(q)}'
          '&key=${Environment.mapKey}'
          '&language=es'
          '&components=country:pe';
      final r = await http.get(Uri.parse(url));
      if (r.statusCode == 200) {
        final data = jsonDecode(r.body);
        final list = (data['predictions'] as List? ?? [])
            .map((p) => _PlacePrediction(
                  description: p['description']?.toString() ?? '',
                  placeId: p['place_id']?.toString() ?? '',
                ))
            .toList();
        if (mounted)
          setState(() {
            _predictions = list;
            _showPredictions = list.isNotEmpty;
          });
      }
    } catch (_) {}
  }

  Future<void> _selectPrediction(_PlacePrediction p) async {
    _searchCtrl.text = p.description;
    setState(() {
      _showPredictions = false;
      _predictions = [];
    });
    FocusScope.of(context).unfocus();
    try {
      final url = '${UrlContainer.googleMapLocationSearch}/place/details/json?'
          'place_id=${p.placeId}'
          '&key=${Environment.mapKey}'
          '&fields=geometry,formatted_address';
      final r = await http.get(Uri.parse(url));
      if (r.statusCode == 200) {
        final data = jsonDecode(r.body);
        final loc = data['result']?['geometry']?['location'];
        final addr = data['result']?['formatted_address']?.toString() ?? p.description;
        if (loc != null) {
          final latLng = LatLng((loc['lat'] as num).toDouble(), (loc['lng'] as num).toDouble());
          setState(() {
            _selectedLocation = latLng;
            _address = addr;
          });
          _mapController?.animateCamera(CameraUpdate.newLatLngZoom(latLng, 17));
        }
      }
    } catch (_) {}
  }

  Future<void> _getCurrentLocation() async {
    setState(() => _locating = true);
    try {
      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.best),
      );
      final latLng = LatLng(pos.latitude, pos.longitude);
      _mapController?.animateCamera(CameraUpdate.newLatLngZoom(latLng, 17));
      setState(() {
        _selectedLocation = latLng;
      });
      await _reverseGeocode(latLng);
    } catch (_) {
      if (mounted) Get.snackbar('Error', 'No se pudo obtener la ubicacion', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
    }
    if (mounted) setState(() => _locating = false);
  }

  Future<void> _reverseGeocode(LatLng pos) async {
    try {
      final placemarks = await placemarkFromCoordinates(pos.latitude, pos.longitude);
      if (placemarks.isNotEmpty) {
        final p = placemarks.first;
        final streetFull = (p.subThoroughfare != null && p.subThoroughfare!.isNotEmpty && !(p.street ?? '').contains(p.subThoroughfare!)) ? '${p.street ?? ''} ${p.subThoroughfare}'.trim() : (p.street ?? '');
        final addr = '$streetFull, ${p.locality ?? ''}, ${p.country ?? ''}'.replaceAll(RegExp(r'^,\s*'), '').replaceAll(RegExp(r',\s*,'), ',');
        if (addr.isNotEmpty && addr != ', ,') {
          setState(() => _address = addr);
          return;
        }
      }
    } catch (_) {}
    setState(() => _address = '${pos.latitude.toStringAsFixed(4)}, ${pos.longitude.toStringAsFixed(4)}');
  }

  Future<void> _updateAddress() async {
    if (_selectedLocation == null) return;
    _reverseGeocode(_selectedLocation!);
  }

  void _confirm() {
    if (_selectedLocation != null) {
      Navigator.pop(context, {
        'lat': _selectedLocation!.latitude,
        'lng': _selectedLocation!.longitude,
        'address': _address,
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final lat = widget.initialLat ?? -12.0464;
    final lng = widget.initialLng ?? -77.0428;

    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text(widget.title, style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
        leading: IconButton(icon: const Icon(Icons.close, color: MyColor.colorWhite), onPressed: () => Get.back()),
      ),
      body: Stack(
        children: [
          GoogleMap(
            initialCameraPosition: CameraPosition(target: LatLng(lat, lng), zoom: 16),
            onMapCreated: (c) {
              _mapController = c;
              _selectedLocation = LatLng(lat, lng);
              _reverseGeocode(_selectedLocation!);
            },
            onCameraMove: (pos) => _selectedLocation = pos.target,
            onCameraIdle: () => _updateAddress(),
            markers: _selectedLocation != null ? {Marker(markerId: const MarkerId('sel'), position: _selectedLocation!, icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed))} : {},
            myLocationEnabled: true,
            zoomControlsEnabled: false,
          ),
          Center(child: Icon(Icons.location_on, color: MyColor.redCancelTextColor, size: 48)),
          // Search bar
          Positioned(
              top: 12,
              left: 16,
              right: 16,
              child: Column(children: [
                Container(
                  decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(14), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 10, offset: Offset(0, 4))]),
                  child: TextField(
                    controller: _searchCtrl,
                    onChanged: _onSearchChanged,
                    decoration: InputDecoration(
                      hintText: 'Buscar direccion...',
                      hintStyle: regularDefault.copyWith(color: Colors.grey.shade400),
                      prefixIcon: Icon(Icons.search_rounded, color: MyColor.primaryColor),
                      suffixIcon: IconButton(icon: Icon(Icons.my_location_rounded, color: MyColor.primaryColor), onPressed: _locating ? null : _getCurrentLocation),
                      border: InputBorder.none,
                      contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    ),
                  ),
                ),
                if (_showPredictions)
                  Container(
                    margin: EdgeInsets.only(top: 4),
                    decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(14), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 8)]),
                    constraints: BoxConstraints(maxHeight: 220),
                    child: ListView.separated(
                      shrinkWrap: true,
                      padding: EdgeInsets.zero,
                      itemCount: _predictions.length,
                      separatorBuilder: (_, __) => Divider(height: 1, color: Colors.grey.shade100),
                      itemBuilder: (ctx, i) => InkWell(
                        onTap: () => _selectPrediction(_predictions[i]),
                        child: Padding(
                            padding: EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                            child: Row(children: [
                              Icon(Icons.location_on_outlined, size: 18, color: MyColor.primaryColor),
                              SizedBox(width: 10),
                              Expanded(child: Text(_predictions[i].description, style: regularDefault.copyWith(fontSize: 14), maxLines: 2, overflow: TextOverflow.ellipsis)),
                            ])),
                      ),
                    ),
                  ),
              ])),
          // Address bar
          Positioned(
              top: _showPredictions ? 290 : 80,
              left: 16,
              right: 16,
              child: Container(
                padding: EdgeInsets.all(14),
                decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(14), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 8)]),
                child: Row(children: [
                  Icon(Icons.location_on_rounded, color: MyColor.primaryColor, size: 20),
                  SizedBox(width: 8),
                  Expanded(child: Text(_address, style: regularDefault.copyWith(fontSize: 14), maxLines: 2, overflow: TextOverflow.ellipsis)),
                ]),
              )),
          // Confirm button
          Positioned(
              bottom: 30,
              left: 20,
              right: 20,
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _confirm,
                  icon: Icon(Icons.check_rounded, color: MyColor.colorWhite),
                  label: Text('Confirmar ubicacion', style: boldDefault.copyWith(color: MyColor.colorWhite)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: MyColor.primaryColor,
                    padding: EdgeInsets.all(16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                ),
              )),
        ],
      ),
    );
  }
}

class _PlacePrediction {
  final String description;
  final String placeId;
  _PlacePrediction({required this.description, required this.placeId});
}
