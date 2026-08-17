import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/core/utils/util.dart';
import 'package:liztogo/core/utils/debouncer.dart';
import 'package:liztogo/data/controller/seller/seller_controller.dart';
import 'package:liztogo/data/repo/location/location_search_repo.dart';
import 'package:liztogo/data/model/location/prediction.dart';
import 'package:liztogo/data/model/location/place_details.dart';
import 'package:liztogo/environment.dart';
import 'package:liztogo/presentation/components/buttons/rounded_button.dart';

class SellerFavorCreateScreen extends StatefulWidget {
  final String sellerToken;
  const SellerFavorCreateScreen({super.key, required this.sellerToken});

  @override
  State<SellerFavorCreateScreen> createState() => _SellerFavorCreateScreenState();
}

class _SellerFavorCreateScreenState extends State<SellerFavorCreateScreen> {
  final _descCtrl        = TextEditingController();
  final _pickupCtrl      = TextEditingController();
  final _deliveryCtrl    = TextEditingController();
  final _feeCtrl         = TextEditingController(text: '0.00');
  bool _loading = false;

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
    _descCtrl.dispose(); _pickupCtrl.dispose(); _deliveryCtrl.dispose(); _feeCtrl.dispose();
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
          final streetFull = (placemark.subThoroughfare != null && placemark.subThoroughfare!.isNotEmpty && !(placemark.street ?? '').contains(placemark.subThoroughfare!))
              ? '${placemark.street ?? ''} ${placemark.subThoroughfare}'.trim()
              : (placemark.street ?? '');
          address = [streetFull, placemark.subLocality, placemark.locality, placemark.country]
              .where((p) => p != null && p.isNotEmpty)
              .join(', ');
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

  void _calculateAndSetFee() {
    if (_pickupLat == null || _pickupLng == null || _deliveryLat == null || _deliveryLng == null) {
      return;
    }
    try {
      double distance = MyUtils().calculateDistance(
        _pickupLat!,
        _pickupLng!,
        _deliveryLat!,
        _deliveryLng!,
      );
      double fee = 5.0 + (distance * 2.5);
      if (fee < 5.0) fee = 5.0;
      setState(() {
        _feeCtrl.text = fee.toStringAsFixed(2);
      });
    } catch (e) {
      print("Error calculating fee: $e");
    }
  }

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

  Future<void> _submit() async {
    if (_descCtrl.text.trim().isEmpty || _pickupCtrl.text.trim().isEmpty || _deliveryCtrl.text.trim().isEmpty) {
      Get.snackbar('Error', 'Completa todos los campos requeridos',
          backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      return;
    }
    setState(() => _loading = true);

    try {
      final controller = Get.find<SellerController>();
      final data = {
        'description': _descCtrl.text.trim(),
        'pickup_address': _pickupCtrl.text.trim(),
        'delivery_address': _deliveryCtrl.text.trim(),
        'delivery_fee': double.tryParse(_feeCtrl.text.trim()) ?? 10.00,
      };
      final ok = await controller.createStoreFavor(data);
      if (ok) {
        Get.snackbar('Solicitud enviada', 'Repartidores cercanos recibirán tu solicitud',
            backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
        Get.back();
      } else {
        Get.snackbar('Error', controller.errorMessage ?? 'No se pudo crear la solicitud',
            backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      }
    } catch (_) {
      Get.snackbar('Error', 'No se pudo crear la solicitud',
          backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
    }
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Solicitar Repartidor', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
      ),
      body: ListView(
        padding: EdgeInsets.all(Dimensions.space16),
        children: [
          Container(
            padding: EdgeInsets.all(Dimensions.space12),
            margin: EdgeInsets.only(bottom: Dimensions.space16),
            decoration: BoxDecoration(
              color: const Color(0xFFF59E0B).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
            ),
            child: Row(children: [
              Icon(Icons.info_outline, color: const Color(0xFFF59E0B), size: 20),
              SizedBox(width: Dimensions.space8),
              Expanded(child: Text('Solicita un repartidor para tus entregas fuera de la plataforma.',
                  style: regularSmall.copyWith(color: const Color(0xFFF59E0B)))),
            ]),
          ),
          Text('¿Qué necesitas entregar?', style: boldLarge),
          SizedBox(height: Dimensions.space8),
          TextField(
            controller: _descCtrl,
            decoration: InputDecoration(
              hintText: 'Describe el paquete o producto a entregar',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
            ),
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
              _debouncer.run(() {
                _searchPickup(val);
              });
            },
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
              _debouncer.run(() {
                _searchDelivery(val);
              });
            },
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
          TextField(
            controller: _feeCtrl,
            decoration: InputDecoration(
              prefixText: 'S/ ',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
            ),
            keyboardType: TextInputType.numberWithOptions(decimal: true),
          ),
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
