import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
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
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';


class SellerStoreAddEditScreen extends StatefulWidget {
  final Map<String, dynamic>? store;

  const SellerStoreAddEditScreen({super.key, this.store});

  @override
  State<SellerStoreAddEditScreen> createState() => _SellerStoreAddEditScreenState();
}

class _SellerStoreAddEditScreenState extends State<SellerStoreAddEditScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameCtrl;
  late TextEditingController _descCtrl;
  late TextEditingController _addrCtrl;
  late TextEditingController _deliveryFeeCtrl;
  late TextEditingController _minOrderCtrl;
  late TextEditingController _prepTimeCtrl;
  late TextEditingController _openTimeCtrl;
  late TextEditingController _closeTimeCtrl;
  late TextEditingController _latCtrl;
  late TextEditingController _lngCtrl;

  int? _selectedSubCategoryId;
  File? _logoImage;
  File? _coverImage;
  final ImagePicker _picker = ImagePicker();

  List<Prediction> _predictions = [];
  bool _isSearching = false;
  final MyDeBouncer _debouncer = MyDeBouncer(delay: const Duration(milliseconds: 600));

  @override
  void initState() {
    super.initState();
    final s = widget.store;
    _nameCtrl = TextEditingController(text: s?['name'] ?? '');
    _descCtrl = TextEditingController(text: s?['description'] ?? '');
    _addrCtrl = TextEditingController(text: s?['address'] ?? '');
    _deliveryFeeCtrl = TextEditingController(text: s?['delivery_fee'] != null ? s!['delivery_fee'].toString() : '0.00');
    _minOrderCtrl = TextEditingController(text: s?['min_order_amount'] != null ? s!['min_order_amount'].toString() : '0.00');
    _prepTimeCtrl = TextEditingController(text: s?['preparation_time'] != null ? s!['preparation_time'].toString() : '15');
    _openTimeCtrl = TextEditingController(text: s?['opening_time'] ?? '08:00:00');
    _closeTimeCtrl = TextEditingController(text: s?['closing_time'] ?? '22:00:00');
    _latCtrl = TextEditingController(text: s?['latitude'] != null ? s!['latitude'].toString() : '');
    _lngCtrl = TextEditingController(text: s?['longitude'] != null ? s!['longitude'].toString() : '');

    _selectedSubCategoryId = s?['sub_category_id'] is int ? s!['sub_category_id'] : int.tryParse(s?['sub_category_id']?.toString() ?? '');

    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<SellerController>().loadSubCategories();
      if (widget.store == null) {
        _autocompleteCurrentLocation();
      }
    });
  }

  Future<void> _autocompleteCurrentLocation() async {
    bool hasPermission = await MyUtils.checkAppLocationPermission();
    if (!hasPermission) return;
    try {
      Position pos = await MyUtils.getCurrentPosition();
      _latCtrl.text = pos.latitude.toString();
      _lngCtrl.text = pos.longitude.toString();
      
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
          _addrCtrl.text = address!;
        });
      }
    } catch (e) {
      print("Error autocompleting current location: $e");
    }
  }

  Future<void> _searchAddress(String query) async {
    if (query.isEmpty) {
      setState(() {
        _predictions = [];
      });
      return;
    }
    setState(() {
      _isSearching = true;
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
          _predictions = predictions;
        });
      }
    } catch (e) {
      print("Search error: $e");
    } finally {
      setState(() {
        _isSearching = false;
      });
    }
  }

  Future<void> _selectPrediction(Prediction prediction) async {
    MyUtils.closeKeyboard();
    try {
      final searchRepo = Get.find<LocationSearchRepo>();
      final response = await searchRepo.getPlaceDetailsFromPlaceId(prediction);
      final placeDetails = PlaceDetails.fromJson(response.responseJson);
      if (placeDetails.result != null) {
        final lat = placeDetails.result!.geometry!.location!.lat ?? 0.0;
        final lng = placeDetails.result!.geometry!.location!.lng ?? 0.0;
        setState(() {
          _latCtrl.text = lat.toString();
          _lngCtrl.text = lng.toString();
          _addrCtrl.text = prediction.description ?? '';
          _predictions = [];
        });
      }
    } catch (e) {
      print("Error getting place details: $e");
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descCtrl.dispose();
    _addrCtrl.dispose();
    _deliveryFeeCtrl.dispose();
    _minOrderCtrl.dispose();
    _prepTimeCtrl.dispose();
    _openTimeCtrl.dispose();
    _closeTimeCtrl.dispose();
    _latCtrl.dispose();
    _lngCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickImage(bool isLogo, ImageSource source) async {
    try {
      final pickedFile = await _picker.pickImage(source: source, imageQuality: 80);
      if (pickedFile != null) {
        setState(() {
          if (isLogo) {
            _logoImage = File(pickedFile.path);
          } else {
            _coverImage = File(pickedFile.path);
          }
        });
      }
    } catch (_) {}
  }

  void _showImagePicker(bool isLogo) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.all(20),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(isLogo ? 'Seleccionar Logo' : 'Seleccionar Portada', style: boldLarge),
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  GestureDetector(
                    onTap: () {
                      Navigator.pop(ctx);
                      _pickImage(isLogo, ImageSource.camera);
                    },
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(color: MyColor.primaryColor.withValues(alpha: 0.1), shape: BoxShape.circle),
                          child: Icon(Icons.camera_alt_rounded, color: MyColor.primaryColor, size: 30),
                        ),
                        const SizedBox(height: 8),
                        Text('Cámara', style: regularDefault),
                      ],
                    ),
                  ),
                  GestureDetector(
                    onTap: () {
                      Navigator.pop(ctx);
                      _pickImage(isLogo, ImageSource.gallery);
                    },
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(color: MyColor.primaryColor.withValues(alpha: 0.1), shape: BoxShape.circle),
                          child: Icon(Icons.photo_library_rounded, color: MyColor.primaryColor, size: 30),
                        ),
                        const SizedBox(height: 8),
                        Text('Galería', style: regularDefault),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
            ],
          ),
        );
      },
    );
  }

  Future<void> _submit(SellerController c) async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedSubCategoryId == null) {
      Get.snackbar('Error', 'Debe seleccionar una subcategoría de negocio.',
          backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      return;
    }

    final data = {
      'sub_category_id': _selectedSubCategoryId,
      'name': _nameCtrl.text.trim(),
      'description': _descCtrl.text.trim(),
      'address': _addrCtrl.text.trim(),
      'delivery_fee': double.tryParse(_deliveryFeeCtrl.text.trim()) ?? 0.0,
      'min_order_amount': double.tryParse(_minOrderCtrl.text.trim()) ?? 0.0,
      'preparation_time': int.tryParse(_prepTimeCtrl.text.trim()) ?? 15,
      'opening_time': _openTimeCtrl.text.trim(),
      'closing_time': _closeTimeCtrl.text.trim(),
    };

    if (_latCtrl.text.trim().isNotEmpty) data['latitude'] = double.tryParse(_latCtrl.text.trim()) ?? 0.0;
    if (_lngCtrl.text.trim().isNotEmpty) data['longitude'] = double.tryParse(_lngCtrl.text.trim()) ?? 0.0;

    bool success;
    if (widget.store == null) {
      success = await c.createStore(data, image: _logoImage, coverImage: _coverImage);
    } else {
      success = await c.updateStore(widget.store!['id'], data, image: _logoImage, coverImage: _coverImage);
    }

    if (success) {
      Get.back();
      Get.snackbar('Éxito', widget.store == null ? 'Negocio creado con éxito' : 'Negocio actualizado con éxito',
          backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
    } else {
      Get.snackbar('Error', c.errorMessage ?? 'Ocurrió un error al guardar',
          backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
    }
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text(widget.store == null ? 'Nuevo Negocio' : 'Editar Negocio', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
          ),
          body: c.isLoading
              ? const Center(child: CircularProgressIndicator())
              : Form(
                  key: _formKey,
                  child: ListView(
                    padding: const EdgeInsets.all(Dimensions.space16),
                    children: [
                      // Logo & Cover Pickers Row
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          Column(
                            children: [
                              Text('Logo', style: boldDefault),
                              const SizedBox(height: 8),
                              GestureDetector(
                                onTap: () => _showImagePicker(true),
                                child: Container(
                                  height: 80,
                                  width: 80,
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(16),
                                    border: Border.all(color: MyColor.neutral200),
                                  ),
                                  child: _logoImage != null
                                      ? ClipRRect(borderRadius: BorderRadius.circular(15), child: Image.file(_logoImage!, fit: BoxFit.cover))
                                      : widget.store?['image'] != null
                                          ? ClipRRect(borderRadius: BorderRadius.circular(15), child: MyImageWidget(imageUrl: '${c.storeImagePath}/${widget.store!['image']}', boxFit: BoxFit.cover))
                                          : Icon(Icons.add_photo_alternate_outlined, color: MyColor.primaryColor, size: 28),
                                ),
                              ),
                            ],
                          ),
                          Column(
                            children: [
                              Text('Portada', style: boldDefault),
                              const SizedBox(height: 8),
                              GestureDetector(
                                onTap: () => _showImagePicker(false),
                                child: Container(
                                  height: 80,
                                  width: 140,
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(16),
                                    border: Border.all(color: MyColor.neutral200),
                                  ),
                                  child: _coverImage != null
                                      ? ClipRRect(borderRadius: BorderRadius.circular(15), child: Image.file(_coverImage!, fit: BoxFit.cover))
                                      : widget.store?['cover_image'] != null
                                          ? ClipRRect(borderRadius: BorderRadius.circular(15), child: MyImageWidget(imageUrl: '${c.storeImagePath}/${widget.store!['cover_image']}', boxFit: BoxFit.cover))
                                          : Icon(Icons.add_photo_alternate_outlined, color: MyColor.primaryColor, size: 28),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                      const SizedBox(height: Dimensions.space20),

                      // Category Selector dropdown
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                          border: Border.all(color: MyColor.neutral200),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButtonFormField<int>(
                            initialValue: _selectedSubCategoryId,
                            hint: Text('Selecciona categoría de negocio', style: regularDefault),
                            decoration: const InputDecoration(border: InputBorder.none),
                            items: c.subCategories.map<DropdownMenuItem<int>>((sub) {
                              return DropdownMenuItem<int>(
                                value: sub['id'] is int ? sub['id'] : int.parse(sub['id'].toString()),
                                child: Text('${sub['general_category']?['name'] ?? ''} - ${sub['name']}', style: regularDefault),
                              );
                            }).toList(),
                            onChanged: (val) {
                              setState(() {
                                _selectedSubCategoryId = val;
                              });
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: Dimensions.space16),

                      TextFormField(
                        controller: _nameCtrl,
                        decoration: InputDecoration(
                          labelText: 'Nombre del Negocio',
                          fillColor: Colors.white,
                          filled: true,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                        ),
                        validator: (value) => value == null || value.trim().isEmpty ? 'Ingrese un nombre válido' : null,
                      ),
                      const SizedBox(height: Dimensions.space16),

                      TextFormField(
                        controller: _descCtrl,
                        maxLines: 2,
                        decoration: InputDecoration(
                          labelText: 'Descripción / Eslogan',
                          fillColor: Colors.white,
                          filled: true,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                        ),
                      ),
                      const SizedBox(height: Dimensions.space16),

                      TextFormField(
                        controller: _addrCtrl,
                        decoration: InputDecoration(
                          labelText: 'Dirección física',
                          fillColor: Colors.white,
                          filled: true,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                          suffixIcon: IconButton(
                            icon: const Icon(Icons.my_location, color: MyColor.primaryColor),
                            onPressed: _autocompleteCurrentLocation,
                          ),
                        ),
                        onChanged: (val) {
                          _debouncer.run(() {
                            _searchAddress(val);
                          });
                        },
                        validator: (value) => value == null || value.trim().isEmpty ? 'Ingrese una dirección' : null,
                      ),
                      if (_isSearching && _predictions.isEmpty) ...[
                        const SizedBox(height: 8),
                        const Center(child: SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))),
                      ] else if (_predictions.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Container(
                          constraints: const BoxConstraints(maxHeight: 200),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                            border: Border.all(color: MyColor.neutral200),
                          ),
                          child: ListView.builder(
                            shrinkWrap: true,
                            physics: const ClampingScrollPhysics(),
                            itemCount: _predictions.length,
                            itemBuilder: (context, idx) {
                              final item = _predictions[idx];
                              return ListTile(
                                leading: const Icon(Icons.location_on_rounded, color: MyColor.bodyTextColor),
                                title: Text(item.description ?? '', style: regularDefault),
                                onTap: () => _selectPrediction(item),
                              );
                            },
                          ),
                        ),
                      ],
                      const SizedBox(height: Dimensions.space16),

                      TextFormField(
                        controller: _minOrderCtrl,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        decoration: InputDecoration(
                          labelText: 'Pedido Mínimo (S/)',
                          fillColor: Colors.white,
                          filled: true,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                        ),
                      ),
                      const SizedBox(height: Dimensions.space16),

                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _prepTimeCtrl,
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                labelText: 'Tiempo Prep (mins)',
                                fillColor: Colors.white,
                                filled: true,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                              ),
                            ),
                          ),
                          const SizedBox(width: Dimensions.space12),
                          Expanded(
                            child: TextFormField(
                              controller: _openTimeCtrl,
                              decoration: InputDecoration(
                                labelText: 'Apertura (HH:MM)',
                                fillColor: Colors.white,
                                filled: true,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                              ),
                            ),
                          ),
                          const SizedBox(width: Dimensions.space12),
                          Expanded(
                            child: TextFormField(
                              controller: _closeTimeCtrl,
                              decoration: InputDecoration(
                                labelText: 'Cierre (HH:MM)',
                                fillColor: Colors.white,
                                filled: true,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: Dimensions.space30),

                      RoundedButton(
                        text: widget.store == null ? 'Crear Negocio' : 'Guardar Cambios',
                        isLoading: c.isLoading,
                        press: () => _submit(c),
                      ),
                    ],
                  ),
                ),
        );
      },
    );
  }
}
