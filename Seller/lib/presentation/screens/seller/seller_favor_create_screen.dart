import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/core/utils/util.dart';
import 'package:lizto_store/core/utils/debouncer.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/repo/location/location_search_repo.dart';
import 'package:lizto_store/data/model/location/prediction.dart';
import 'package:lizto_store/data/model/location/place_details.dart';
import 'package:lizto_store/environment.dart';
import 'package:lizto_store/presentation/components/buttons/rounded_button.dart';
import 'package:lizto_store/presentation/screens/seller/seller_favor_search_screen.dart';

class SellerFavorCreateScreen extends StatefulWidget {
  final String sellerToken;
  const SellerFavorCreateScreen({super.key, required this.sellerToken});

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
      print("Error autocompleting pickup current location: $e");
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
      print("Search pickup error: $e");
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
      print("Search delivery error: $e");
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
      print("Error getting pickup place details: $e");
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
      print("Error getting delivery place details: $e");
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
    if (_descCtrl.text.trim().isEmpty || _pickupCtrl.text.trim().isEmpty || _deliveryCtrl.text.trim().isEmpty) {
      Get.snackbar('Error', 'Completa todos los campos requeridos', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      return;
    }
    if (_pickupLat == null || _pickupLng == null || _deliveryLat == null || _deliveryLng == null || _feeEstimate == null) {
      Get.snackbar('Ubicaciones requeridas', 'Selecciona el recojo y el destino desde las sugerencias para calcular la tarifa real.', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      return;
    }
    setState(() => _loading = true);

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
        Get.off(() => SellerFavorSearchScreen(favorId: favorId.toInt(), orderNo: favor?['order_no']?.toString() ?? ''));
      } else {
        Get.snackbar('Error', controller.errorMessage ?? 'No se pudo crear la solicitud', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      }
    } catch (_) {
      Get.snackbar('Error', 'No se pudo crear la solicitud', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
    }
    setState(() => _loading = false);
  }

  InputDecoration _fieldDecoration({required String hint, IconData? icon, Widget? suffixIcon}) {
    return InputDecoration(
      hintText: hint,
      hintStyle: regularDefault.copyWith(color: const Color(0xFF94A3B8)),
      prefixIcon: icon == null ? null : Icon(icon, color: MyColor.primaryColor),
      suffixIcon: suffixIcon,
      filled: true,
      fillColor: const Color(0xFFF8FAFC),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: MyColor.primaryColor, width: 1.5)),
    );
  }

  Widget _feeCard() {
    final fee = _asDouble(_feeEstimate?['delivery_fee']);
    final distance = _feeEstimate?['distance_km'];
    final time = _feeEstimate?['time_min'];
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: [Color(0xFF0F766E), Color(0xFF16A34A)]),
        borderRadius: BorderRadius.circular(18),
      ),
      child: _isCalculatingFee
          ? const Row(children: [SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)), SizedBox(width: 12), Text('Calculando tarifa real…', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600))])
          : Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [const Icon(Icons.verified_rounded, color: Color(0xFFFDE68A)), const SizedBox(width: 7), Text('Tarifa calculada por Lizto', style: boldLarge.copyWith(color: Colors.white, fontSize: 14))]),
              const SizedBox(height: 12),
              Text(_feeEstimate == null ? 'Selecciona ambas ubicaciones' : 'S/ ${fee.toStringAsFixed(2)}', style: const TextStyle(color: Colors.white, fontSize: 29, fontWeight: FontWeight.w800)),
              const SizedBox(height: 5),
              Text(_feeEstimate == null ? (_feeError ?? 'La tarifa se actualiza al elegir el destino.') : '${distance ?? '—'} km · ${time ?? '—'} min estimados', style: const TextStyle(color: Color(0xFFD1FAE5), fontSize: 13)),
            ]),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Solicitar repartidor', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: false,
      ),
      body: ListView(
        padding: EdgeInsets.all(Dimensions.space16),
        children: [
          Container(
            padding: const EdgeInsets.all(18),
            margin: EdgeInsets.only(bottom: Dimensions.space16),
            decoration: BoxDecoration(
              gradient: LinearGradient(colors: [MyColor.primaryColor.withValues(alpha: .12), const Color(0xFFDCFCE7)]),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Row(children: [
              Container(width: 42, height: 42, decoration: BoxDecoration(color: MyColor.primaryColor, borderRadius: BorderRadius.circular(14)), child: const Icon(Icons.two_wheeler_rounded, color: Colors.white)),
              const SizedBox(width: 12),
              Expanded(child: Text('Coordina una entrega propia. Verás la tarifa real antes de enviarla.', style: regularSmall.copyWith(color: MyColor.bodyTextColor))),
            ]),
          ),
          Text('¿Qué necesitas entregar?', style: boldLarge),
          SizedBox(height: Dimensions.space8),
          TextField(
            controller: _descCtrl,
            decoration: _fieldDecoration(hint: 'Ej. Documentos, caja pequeña, pedido listo', icon: Icons.inventory_2_outlined),
            maxLines: 3,
          ),
          SizedBox(height: Dimensions.space16),
          Text('Dirección de recogida', style: boldLarge),
          SizedBox(height: Dimensions.space8),
          TextField(
            controller: _pickupCtrl,
            decoration: InputDecoration(
              hintText: '¿De dónde recoge el repartidor?',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
              suffixIcon: IconButton(
                icon: const Icon(Icons.my_location, color: MyColor.primaryColor),
                onPressed: _autocompletePickupCurrentLocation,
              ),
            ),
            maxLines: 2,
            onChanged: (val) {
              setState(() {
                _feeRequestId++;
                _pickupLat = null;
                _pickupLng = null;
                _feeEstimate = null;
              });
              _debouncer.run(() {
                _searchPickup(val);
              });
            },
          ),
          Align(
            alignment: Alignment.centerRight,
            child: TextButton.icon(
              onPressed: () => _selectLocationFromMap(isPickup: true),
              icon: const Icon(Icons.map_outlined, size: 18),
              label: const Text('Elegir en el mapa'),
            ),
          ),
          if (_isSearchingPickup && _pickupPredictions.isEmpty) ...[
            const SizedBox(height: 8),
            const Center(child: SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))),
          ] else if (_pickupPredictions.isNotEmpty) ...[
            const SizedBox(height: 8),
            Container(
              constraints: const BoxConstraints(maxHeight: 180),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                border: Border.all(color: MyColor.neutral200),
              ),
              child: ListView.builder(
                shrinkWrap: true,
                physics: const ClampingScrollPhysics(),
                itemCount: _pickupPredictions.length,
                itemBuilder: (context, idx) {
                  final item = _pickupPredictions[idx];
                  return ListTile(
                    leading: const Icon(Icons.location_on_rounded, color: MyColor.bodyTextColor),
                    title: Text(item.description ?? '', style: regularDefault),
                    onTap: () => _selectPickupPrediction(item),
                  );
                },
              ),
            ),
          ],
          SizedBox(height: Dimensions.space16),
          Text('Dirección de entrega', style: boldLarge),
          SizedBox(height: Dimensions.space8),
          TextField(
            controller: _deliveryCtrl,
            decoration: InputDecoration(
              hintText: '¿A dónde debe entregar?',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
            ),
            maxLines: 2,
            onChanged: (val) {
              setState(() {
                _feeRequestId++;
                _deliveryLat = null;
                _deliveryLng = null;
                _feeEstimate = null;
              });
              _debouncer.run(() {
                _searchDelivery(val);
              });
            },
          ),
          Align(
            alignment: Alignment.centerRight,
            child: TextButton.icon(
              onPressed: () => _selectLocationFromMap(isPickup: false),
              icon: const Icon(Icons.map_outlined, size: 18),
              label: const Text('Elegir en el mapa'),
            ),
          ),
          if (_isSearchingDelivery && _deliveryPredictions.isEmpty) ...[
            const SizedBox(height: 8),
            const Center(child: SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))),
          ] else if (_deliveryPredictions.isNotEmpty) ...[
            const SizedBox(height: 8),
            Container(
              constraints: const BoxConstraints(maxHeight: 180),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                border: Border.all(color: MyColor.neutral200),
              ),
              child: ListView.builder(
                shrinkWrap: true,
                physics: const ClampingScrollPhysics(),
                itemCount: _deliveryPredictions.length,
                itemBuilder: (context, idx) {
                  final item = _deliveryPredictions[idx];
                  return ListTile(
                    leading: const Icon(Icons.location_on_rounded, color: MyColor.bodyTextColor),
                    title: Text(item.description ?? '', style: regularDefault),
                    onTap: () => _selectDeliveryPrediction(item),
                  );
                },
              ),
            ),
          ],
          SizedBox(height: Dimensions.space16),
          Text('Tarifa de delivery (S/)', style: boldLarge),
          SizedBox(height: Dimensions.space8),
          _feeCard(),
          SizedBox(height: Dimensions.space32),
          RoundedButton(
            text: 'Solicitar Repartidor',
            isLoading: _loading,
            press: _submit,
            isOutlined: false,
          ),
        ],
      ),
    );
  }
}
