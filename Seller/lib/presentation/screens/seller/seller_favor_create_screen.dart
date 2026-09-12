import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/core/utils/util.dart';
import 'package:lizto_store/core/utils/debouncer.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/repo/location/location_search_repo.dart';
import 'package:lizto_store/data/model/location/prediction.dart';
import 'package:lizto_store/data/model/location/place_details.dart';
import 'package:lizto_store/environment.dart';
import 'package:lizto_store/presentation/screens/seller/seller_favor_search_screen.dart';

class SellerFavorCreateScreen extends StatefulWidget {
  const SellerFavorCreateScreen({super.key});

  @override
  State<SellerFavorCreateScreen> createState() => _SellerFavorCreateScreenState();
}

class _SellerFavorMapPicker extends StatefulWidget {
  final String title;
  final LatLng initialPosition;

  const _SellerFavorMapPicker({required this.title, required this.initialPosition});

  @override
  State<_SellerFavorMapPicker> createState() => _SellerFavorMapPickerState();
}

class _SellerFavorMapPickerState extends State<_SellerFavorMapPicker> {
  late LatLng _selectedPosition;
  final _searchController = TextEditingController();
  final _searchDebouncer = MyDeBouncer(delay: const Duration(milliseconds: 450));
  GoogleMapController? _mapController;
  List<Prediction> _predictions = [];
  bool _searching = false;

  @override
  void initState() {
    super.initState();
    _selectedPosition = widget.initialPosition;
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _searchAddress(String query) async {
    if (query.trim().isEmpty) {
      if (mounted) setState(() => _predictions = []);
      return;
    }
    setState(() => _searching = true);
    try {
      final response = await Get.find<LocationSearchRepo>().searchAddressByLocationName(text: query.trim());
      if (!mounted) return;
      final json = response?.responseJson;
      setState(() {
        _predictions = json is Map<String, dynamic> ? (PlacesAutocompleteResponse.fromJson(json).predictions ?? []) : [];
      });
    } catch (_) {
      if (mounted) setState(() => _predictions = []);
    } finally {
      if (mounted) setState(() => _searching = false);
    }
  }

  Future<void> _selectPrediction(Prediction prediction) async {
    FocusScope.of(context).unfocus();
    try {
      final response = await Get.find<LocationSearchRepo>().getPlaceDetailsFromPlaceId(prediction);
      final details = PlaceDetails.fromJson(response.responseJson);
      final location = details.result?.geometry?.location;
      if (location?.lat == null || location?.lng == null || !mounted) return;
      final position = LatLng(location!.lat!, location.lng!);
      setState(() {
        _selectedPosition = position;
        _searchController.text = details.result?.formattedAddress ?? prediction.description ?? '';
        _predictions = [];
      });
      await _mapController?.animateCamera(CameraUpdate.newLatLngZoom(position, 17));
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text(widget.title, style: boldLarge.copyWith(color: Colors.white)),
      ),
      body: Stack(children: [
        GoogleMap(
          initialCameraPosition: CameraPosition(target: _selectedPosition, zoom: 16),
          onMapCreated: (controller) => _mapController = controller,
          markers: {Marker(markerId: const MarkerId('selected_location'), position: _selectedPosition)},
          myLocationButtonEnabled: true,
          myLocationEnabled: true,
          onTap: (position) => setState(() => _selectedPosition = position),
        ),
        Positioned(
          top: 16,
          left: 16,
          right: 16,
          child: Material(
            elevation: 4,
            borderRadius: BorderRadius.circular(12),
            child: Column(mainAxisSize: MainAxisSize.min, children: [
              TextField(
                controller: _searchController,
                decoration: InputDecoration(
                  hintText: 'Buscar direccion',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: _searching
                      ? const Padding(padding: EdgeInsets.all(14), child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)))
                      : _searchController.text.isEmpty
                          ? null
                          : IconButton(
                              icon: const Icon(Icons.close),
                              onPressed: () => setState(() {
                                    _searchController.clear();
                                    _predictions = [];
                                  })),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide.none),
                ),
                onChanged: (query) => _searchDebouncer.run(() => _searchAddress(query)),
              ),
              if (_predictions.isNotEmpty)
                ConstrainedBox(
                  constraints: const BoxConstraints(maxHeight: 230),
                  child: ListView.separated(
                    shrinkWrap: true,
                    itemCount: _predictions.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (_, index) {
                      final prediction = _predictions[index];
                      return ListTile(
                        dense: true,
                        leading: const Icon(Icons.location_on_outlined, color: MyColor.primaryColor),
                        title: Text(prediction.description ?? ''),
                        onTap: () => _selectPrediction(prediction),
                      );
                    },
                  ),
                ),
            ]),
          ),
        ),
        Positioned(
          left: 16,
          right: 16,
          bottom: 24,
          child: SafeArea(
            child: ElevatedButton.icon(
              style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, padding: const EdgeInsets.symmetric(vertical: 15)),
              onPressed: () => Get.back(result: _selectedPosition),
              icon: const Icon(Icons.check_circle_outline),
              label: const Text('Confirmar ubicacion'),
            ),
          ),
        ),
      ]),
    );
  }
}

class _SellerFavorCreateScreenState extends State<SellerFavorCreateScreen> {
  final _descCtrl = TextEditingController();
  final _pickupCtrl = TextEditingController();
  final _deliveryCtrl = TextEditingController();
  bool _loading = false;
  bool _isCalculatingFee = false;
  String? _feeError;
  Map<String, dynamic>? _feeEstimate;
  int _feeRequestId = 0;

  double? _pickupLat;
  double? _pickupLng;
  double? _deliveryLat;
  double? _deliveryLng;

  List<Prediction> _pickupPredictions = [];
  List<Prediction> _deliveryPredictions = [];
  bool _isSearchingPickup = false;
  bool _isSearchingDelivery = false;

  final MyDeBouncer _debouncer = MyDeBouncer(delay: const Duration(milliseconds: 600));

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _autocompletePickupCurrentLocation();
    });
  }

  @override
  void dispose() {
    _descCtrl.dispose();
    _pickupCtrl.dispose();
    _deliveryCtrl.dispose();
    super.dispose();
  }

  Future<void> _autocompletePickupCurrentLocation() async {
    bool hasPermission = await MyUtils.checkAppLocationPermission();
    if (!hasPermission) return;
    try {
      Position pos = await MyUtils.getCurrentPosition();
      _pickupLat = pos.latitude;
      _pickupLng = pos.longitude;

      String? address;
      if (Environment.addressPickerFromGoogleMapApi) {
        final searchRepo = Get.find<LocationSearchRepo>();
        address = await searchRepo.getActualAddress(pos.latitude, pos.longitude);
      } else {
        final placemarks = await placemarkFromCoordinates(pos.latitude, pos.longitude);
        if (placemarks.isNotEmpty) {
          final placemark = placemarks.first;
          address = [placemark.street, placemark.subLocality, placemark.locality, placemark.country].where((p) => p != null && p.isNotEmpty).join(', ');
        }
      }
      if (address != null && address.isNotEmpty) {
        setState(() {
          _pickupCtrl.text = address!;
        });
        _calculateAndSetFee();
      }
    } catch (e) {
      debugPrint("Error autocompleting pickup current location: $e");
    }
  }

  Future<void> _calculateAndSetFee() async {
    if (_pickupLat == null || _pickupLng == null || _deliveryLat == null || _deliveryLng == null) {
      return;
    }
    final requestId = ++_feeRequestId;
    setState(() {
      _isCalculatingFee = true;
      _feeError = null;
    });
    final estimate = await Get.find<SellerController>().estimateStoreFavorFee({
      'pickup_lat': _pickupLat,
      'pickup_lng': _pickupLng,
      'delivery_lat': _deliveryLat,
      'delivery_lng': _deliveryLng,
    });
    if (!mounted || requestId != _feeRequestId) return;
    setState(() {
      _isCalculatingFee = false;
      _feeEstimate = estimate;
      if (estimate == null) {
        _feeError = Get.find<SellerController>().errorMessage ?? 'No se pudo calcular la tarifa';
      } else {}
    });
  }

  double _asDouble(dynamic value) => value is num ? value.toDouble() : double.tryParse(value?.toString() ?? '') ?? 0;

  Future<void> _searchPickup(String query) async {
    if (query.isEmpty) {
      setState(() {
        _pickupPredictions = [];
      });
      return;
    }
    setState(() {
      _isSearchingPickup = true;
    });
    try {
      final searchRepo = Get.find<LocationSearchRepo>();
      Position? position;
      try {
        position = await MyUtils.getCurrentPosition();
      } catch (_) {}
      final response = await searchRepo.searchAddressByLocationName(
        text: query,
        position: position,
      );
      if (response != null) {
        final json = response.responseJson;
        final predictions = PlacesAutocompleteResponse.fromJson(json).predictions ?? [];
        setState(() {
          _pickupPredictions = predictions;
        });
      }
    } catch (e) {
      debugPrint("Search pickup error: $e");
    } finally {
      setState(() {
        _isSearchingPickup = false;
      });
    }
  }

  Future<void> _searchDelivery(String query) async {
    if (query.isEmpty) {
      setState(() {
        _deliveryPredictions = [];
      });
      return;
    }
    setState(() {
      _isSearchingDelivery = true;
    });
    try {
      final searchRepo = Get.find<LocationSearchRepo>();
      Position? position;
      try {
        position = await MyUtils.getCurrentPosition();
      } catch (_) {}
      final response = await searchRepo.searchAddressByLocationName(
        text: query,
        position: position,
      );
      if (response != null) {
        final json = response.responseJson;
        final predictions = PlacesAutocompleteResponse.fromJson(json).predictions ?? [];
        setState(() {
          _deliveryPredictions = predictions;
        });
      }
    } catch (e) {
      debugPrint("Search delivery error: $e");
    } finally {
      setState(() {
        _isSearchingDelivery = false;
      });
    }
  }

  Future<void> _selectPickupPrediction(Prediction prediction) async {
    MyUtils.closeKeyboard();
    try {
      final searchRepo = Get.find<LocationSearchRepo>();
      final response = await searchRepo.getPlaceDetailsFromPlaceId(prediction);
      final placeDetails = PlaceDetails.fromJson(response.responseJson);
      if (placeDetails.result != null) {
        final lat = placeDetails.result!.geometry!.location!.lat ?? 0.0;
        final lng = placeDetails.result!.geometry!.location!.lng ?? 0.0;
        setState(() {
          _pickupLat = lat;
          _pickupLng = lng;
          _pickupCtrl.text = prediction.description ?? '';
          _pickupPredictions = [];
        });
        _calculateAndSetFee();
      }
    } catch (e) {
      debugPrint("Error getting pickup place details: $e");
    }
  }

  Future<void> _selectDeliveryPrediction(Prediction prediction) async {
    MyUtils.closeKeyboard();
    try {
      final searchRepo = Get.find<LocationSearchRepo>();
      final response = await searchRepo.getPlaceDetailsFromPlaceId(prediction);
      final placeDetails = PlaceDetails.fromJson(response.responseJson);
      if (placeDetails.result != null) {
        final lat = placeDetails.result!.geometry!.location!.lat ?? 0.0;
        final lng = placeDetails.result!.geometry!.location!.lng ?? 0.0;
        setState(() {
          _deliveryLat = lat;
          _deliveryLng = lng;
          _deliveryCtrl.text = prediction.description ?? '';
          _deliveryPredictions = [];
        });
        _calculateAndSetFee();
      }
    } catch (e) {
      debugPrint("Error getting delivery place details: $e");
    }
  }

  Future<String?> _addressFromCoordinates(double latitude, double longitude) async {
    try {
      if (Environment.addressPickerFromGoogleMapApi) {
        final address = await Get.find<LocationSearchRepo>().getActualAddress(latitude, longitude);
        if (address != null && address.isNotEmpty) return address;
      }
      final places = await placemarkFromCoordinates(latitude, longitude);
      if (places.isNotEmpty) {
        final place = places.first;
        return [place.street, place.subLocality, place.locality, place.country].where((part) => part != null && part.isNotEmpty).join(', ');
      }
    } catch (_) {}
    return null;
  }

  Future<void> _selectLocationFromMap({required bool isPickup}) async {
    final latitude = isPickup ? _pickupLat : _deliveryLat;
    final longitude = isPickup ? _pickupLng : _deliveryLng;
    final selected = await Get.to<LatLng>(() => _SellerFavorMapPicker(
          title: isPickup ? 'Ubicacion de recogida' : 'Ubicacion de entrega',
          initialPosition: latitude != null && longitude != null ? LatLng(latitude, longitude) : const LatLng(-12.0464, -77.0428),
        ));
    if (selected == null || !mounted) return;

    final address = await _addressFromCoordinates(selected.latitude, selected.longitude);
    if (!mounted) return;
    final coordinateText = '${selected.latitude.toStringAsFixed(6)}, ${selected.longitude.toStringAsFixed(6)}';
    setState(() {
      _feeRequestId++;
      if (isPickup) {
        _pickupLat = selected.latitude;
        _pickupLng = selected.longitude;
        _pickupCtrl.text = address?.isNotEmpty == true ? address! : coordinateText;
        _pickupPredictions = [];
      } else {
        _deliveryLat = selected.latitude;
        _deliveryLng = selected.longitude;
        _deliveryCtrl.text = address?.isNotEmpty == true ? address! : coordinateText;
        _deliveryPredictions = [];
      }
    });
    _calculateAndSetFee();
  }

  Future<void> _submit() async {
    if (_loading) return;
    if (_descCtrl.text.trim().isEmpty || _pickupCtrl.text.trim().isEmpty || _deliveryCtrl.text.trim().isEmpty) {
      Get.snackbar('Error', 'Completa todos los campos requeridos', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      return;
    }
    if (_pickupLat == null || _pickupLng == null || _deliveryLat == null || _deliveryLng == null || _feeEstimate == null) {
      Get.snackbar('Ubicaciones requeridas', 'Selecciona el recojo y el destino desde las sugerencias para calcular la tarifa real.', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      return;
    }
    setState(() => _loading = true);

    var navigated = false;
    try {
      final controller = Get.find<SellerController>();
      final data = {
        'description': _descCtrl.text.trim(),
        'pickup_address': _pickupCtrl.text.trim(),
        'pickup_lat': _pickupLat,
        'pickup_lng': _pickupLng,
        'delivery_address': _deliveryCtrl.text.trim(),
        'delivery_lat': _deliveryLat,
        'delivery_lng': _deliveryLng,
      };
      final result = await controller.createStoreFavorAndStartSearch(data);
      if (result != null) {
        final favor = result['favor'] as Map?;
        final favorId = favor?['id'];
        if (favorId is! num) {
          Get.snackbar('Error', 'No se pudo iniciar el seguimiento de la búsqueda', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
          return;
        }
        Get.snackbar('Solicitud enviada', 'Repartidores cercanos recibirán tu solicitud', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
        navigated = true;
        Get.off(() => SellerFavorSearchScreen(favorId: favorId.toInt(), orderNo: favor?['order_no']?.toString() ?? ''));
      } else {
        Get.snackbar('Error', controller.errorMessage ?? 'No se pudo crear la solicitud', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      }
    } catch (_) {
      Get.snackbar('Error', 'No se pudo crear la solicitud. Inténtalo nuevamente.', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
    } finally {
      if (mounted && !navigated) setState(() => _loading = false);
    }
  }

  bool get _hasPickup => _pickupLat != null && _pickupLng != null;
  bool get _hasDelivery => _deliveryLat != null && _deliveryLng != null;
  bool get _canSubmit => !_loading && _feeEstimate != null && _descCtrl.text.trim().isNotEmpty;

  Widget _predictionList(List<Prediction> predictions, ValueChanged<Prediction> onSelected) {
    return Container(
      margin: const EdgeInsets.only(top: 8),
      constraints: const BoxConstraints(maxHeight: 210),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: const [BoxShadow(color: Color(0x140F172A), blurRadius: 18, offset: Offset(0, 8))],
      ),
      child: ListView.separated(
        shrinkWrap: true,
        padding: const EdgeInsets.symmetric(vertical: 6),
        itemCount: predictions.length,
        separatorBuilder: (_, __) => const Divider(height: 1, indent: 54),
        itemBuilder: (_, index) => ListTile(
          dense: true,
          leading: Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(color: const Color(0xFFF1F5F9), borderRadius: BorderRadius.circular(10)),
            child: const Icon(Icons.place_outlined, size: 18, color: Color(0xFF475569)),
          ),
          title: Text(predictions[index].description ?? '', maxLines: 2, overflow: TextOverflow.ellipsis, style: regularSmall.copyWith(color: const Color(0xFF334155), height: 1.3)),
          onTap: () => onSelected(predictions[index]),
        ),
      ),
    );
  }

  Widget _routeField({
    required bool pickup,
    required TextEditingController controller,
    required String hint,
    required bool searching,
    required List<Prediction> predictions,
  }) {
    final selected = pickup ? _hasPickup : _hasDelivery;
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(pickup ? 'PUNTO DE RECOJO' : 'PUNTO DE ENTREGA', style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: .9, color: Color(0xFF64748B))),
      const SizedBox(height: 7),
      TextField(
        controller: controller,
        minLines: 1,
        maxLines: 2,
        style: regularDefault.copyWith(color: const Color(0xFF0F172A), fontWeight: FontWeight.w600),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: regularDefault.copyWith(color: const Color(0xFF94A3B8), fontWeight: FontWeight.w400),
          filled: true,
          fillColor: selected ? const Color(0xFFF0FDF4) : const Color(0xFFF8FAFC),
          contentPadding: const EdgeInsets.fromLTRB(14, 16, 8, 16),
          suffixIcon: searching
              ? const Padding(padding: EdgeInsets.all(15), child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)))
              : IconButton(
                  tooltip: pickup ? 'Usar mi ubicación' : 'Elegir en el mapa',
                  onPressed: pickup ? _autocompletePickupCurrentLocation : () => _selectLocationFromMap(isPickup: false),
                  icon: Icon(pickup ? Icons.my_location_rounded : Icons.map_outlined, color: MyColor.primaryColor, size: 21),
                ),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide(color: selected ? const Color(0xFF86EFAC) : const Color(0xFFE2E8F0))),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: MyColor.primaryColor, width: 1.6)),
        ),
        onChanged: (value) {
          setState(() {
            _feeRequestId++;
            _feeEstimate = null;
            if (pickup) {
              _pickupLat = null;
              _pickupLng = null;
            } else {
              _deliveryLat = null;
              _deliveryLng = null;
            }
          });
          _debouncer.run(() => pickup ? _searchPickup(value) : _searchDelivery(value));
        },
      ),
      Row(children: [
        TextButton.icon(
          style: TextButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 6), visualDensity: VisualDensity.compact),
          onPressed: () => _selectLocationFromMap(isPickup: pickup),
          icon: const Icon(Icons.near_me_outlined, size: 16),
          label: const Text('Marcar en el mapa', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
        ),
        const Spacer(),
        if (selected) const Row(children: [Icon(Icons.check_circle_rounded, size: 16, color: Color(0xFF16A34A)), SizedBox(width: 4), Text('Ubicación confirmada', style: TextStyle(fontSize: 11, color: Color(0xFF15803D), fontWeight: FontWeight.w600))]),
      ]),
      if (predictions.isNotEmpty) _predictionList(predictions, pickup ? _selectPickupPrediction : _selectDeliveryPrediction),
    ]);
  }

  Widget _routeCard() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24), border: Border.all(color: const Color(0xFFE2E8F0)), boxShadow: const [BoxShadow(color: Color(0x0D0F172A), blurRadius: 24, offset: Offset(0, 10))]),
      child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(
          padding: const EdgeInsets.only(top: 27),
          child: Column(children: [
            Container(width: 14, height: 14, decoration: BoxDecoration(color: _hasPickup ? const Color(0xFF22C55E) : Colors.white, shape: BoxShape.circle, border: Border.all(color: const Color(0xFF22C55E), width: 3))),
            Container(width: 2, height: 116, color: const Color(0xFFCBD5E1)),
            Container(width: 14, height: 14, decoration: BoxDecoration(color: _hasDelivery ? const Color(0xFF0F172A) : Colors.white, shape: BoxShape.circle, border: Border.all(color: const Color(0xFF0F172A), width: 3))),
          ]),
        ),
        const SizedBox(width: 14),
        Expanded(
            child: Column(children: [
          _routeField(pickup: true, controller: _pickupCtrl, hint: '¿Dónde recogemos el envío?', searching: _isSearchingPickup, predictions: _pickupPredictions),
          const SizedBox(height: 10),
          _routeField(pickup: false, controller: _deliveryCtrl, hint: '¿A dónde lo llevamos?', searching: _isSearchingDelivery, predictions: _deliveryPredictions),
        ])),
      ]),
    );
  }

  Widget _feeCard() {
    final fee = _asDouble(_feeEstimate?['delivery_fee']);
    final distance = _feeEstimate?['distance_km'];
    final time = _feeEstimate?['time_min'];
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(24),
        boxShadow: const [BoxShadow(color: Color(0x330F172A), blurRadius: 22, offset: Offset(0, 10))],
      ),
      child: _isCalculatingFee
          ? const Row(children: [SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Color(0xFF4ADE80), strokeWidth: 2)), SizedBox(width: 12), Text('Calculando la mejor tarifa…', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600))])
          : Row(children: [
              Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                const Text('TOTAL DEL ENVÍO', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: .9)),
                const SizedBox(height: 7),
                Text(_feeEstimate == null ? 'Por calcular' : 'S/ ${fee.toStringAsFixed(2)}', style: const TextStyle(color: Colors.white, fontSize: 27, fontWeight: FontWeight.w800, letterSpacing: -.5)),
                const SizedBox(height: 5),
                Text(_feeEstimate == null ? (_feeError ?? 'Completa la ruta para ver la tarifa') : '${distance ?? '—'} km  •  ${time ?? '—'} min aprox.', style: TextStyle(color: _feeError == null ? const Color(0xFFCBD5E1) : const Color(0xFFFCA5A5), fontSize: 12)),
              ])),
              Container(width: 48, height: 48, decoration: BoxDecoration(color: const Color(0xFF22C55E).withValues(alpha: .16), borderRadius: BorderRadius.circular(16)), child: const Icon(Icons.receipt_long_rounded, color: Color(0xFF4ADE80))),
            ]),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F7F9),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0F172A),
        foregroundColor: Colors.white,
        elevation: 0,
        title: const Text('Nuevo envío', style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w700)),
        centerTitle: false,
      ),
      body: ListView(padding: EdgeInsets.zero, children: [
        Container(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 30),
          decoration: const BoxDecoration(color: Color(0xFF0F172A), borderRadius: BorderRadius.vertical(bottom: Radius.circular(32))),
          child: Row(children: [
            Container(width: 54, height: 54, decoration: BoxDecoration(color: const Color(0xFF22C55E), borderRadius: BorderRadius.circular(18)), child: const Icon(Icons.delivery_dining_rounded, color: Colors.white, size: 30)),
            const SizedBox(width: 15),
            const Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('Envíalo con Lizto', style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800, letterSpacing: -.4)), SizedBox(height: 4), Text('Un repartidor cercano se encargará del resto.', style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 13, height: 1.35))])),
          ]),
        ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 26),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Text('Traza la ruta', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: Color(0xFF0F172A), letterSpacing: -.3)),
            const SizedBox(height: 5),
            const Text('Confirma ambos puntos para calcular el precio.', style: TextStyle(fontSize: 13, color: Color(0xFF64748B))),
            const SizedBox(height: 14),
            _routeCard(),
            const SizedBox(height: 24),
            const Text('Detalles del paquete', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: Color(0xFF0F172A), letterSpacing: -.3)),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), border: Border.all(color: const Color(0xFFE2E8F0))),
              child: TextField(
                controller: _descCtrl,
                maxLines: 3,
                onChanged: (_) => setState(() {}),
                style: regularDefault.copyWith(color: const Color(0xFF0F172A)),
                decoration: InputDecoration(
                  hintText: '¿Qué transportará el repartidor?\nEj. Pedido listo, caja pequeña o documentos',
                  hintStyle: regularDefault.copyWith(color: const Color(0xFF94A3B8), height: 1.45),
                  prefixIcon: const Padding(padding: EdgeInsets.only(right: 12, bottom: 35), child: Icon(Icons.inventory_2_outlined, color: MyColor.primaryColor)),
                  prefixIconConstraints: const BoxConstraints(minWidth: 34),
                  border: InputBorder.none,
                ),
              ),
            ),
            const SizedBox(height: 20),
            _feeCard(),
            const SizedBox(height: 12),
            const Row(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.shield_outlined, size: 15, color: Color(0xFF64748B)), SizedBox(width: 5), Text('Tu envío será monitoreado en tiempo real', style: TextStyle(fontSize: 11, color: Color(0xFF64748B)))]),
          ]),
        ),
      ]),
      bottomNavigationBar: SafeArea(
        top: false,
        child: Container(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
          decoration: const BoxDecoration(color: Colors.white, border: Border(top: BorderSide(color: Color(0xFFE2E8F0)))),
          child: SizedBox(
            height: 56,
            child: ElevatedButton(
              onPressed: _canSubmit ? _submit : null,
              style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, disabledBackgroundColor: const Color(0xFFE2E8F0), foregroundColor: Colors.white, disabledForegroundColor: const Color(0xFF94A3B8), elevation: 0, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(17))),
              child: _loading
                  ? const SizedBox(width: 23, height: 23, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.4))
                  : Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                      Text(_feeEstimate == null ? 'Completa los datos del envío' : 'Buscar repartidor', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                      if (_feeEstimate != null) ...[const SizedBox(width: 9), const Icon(Icons.arrow_forward_rounded, size: 21)]
                    ]),
            ),
          ),
        ),
      ),
    );
  }
}
