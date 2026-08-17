import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/seller/seller_controller.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_models.dart';
import 'package:lizto_delivery/presentation/components/buttons/rounded_button.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';

class SellerProductAddEditScreen extends StatefulWidget {
  final int storeId;
  final ProductModel? product;

  const SellerProductAddEditScreen({
    super.key,
    required this.storeId,
    this.product,
  });

  @override
  State<SellerProductAddEditScreen> createState() => _SellerProductAddEditScreenState();
}

class _SellerProductAddEditScreenState extends State<SellerProductAddEditScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameCtrl;
  late TextEditingController _descCtrl;
  late TextEditingController _priceCtrl;
  late TextEditingController _discountPriceCtrl;

  int? _selectedCategoryId;
  File? _selectedImage;
  final ImagePicker _picker = ImagePicker();

  List<Map<String, dynamic>> _variations = [];
  List<Map<String, dynamic>> _addons = [];

  @override
  void initState() {
    super.initState();
    final p = widget.product;
    _nameCtrl = TextEditingController(text: p?.name ?? '');
    _descCtrl = TextEditingController(text: p?.description ?? '');
    _priceCtrl = TextEditingController(text: p?.price != null ? p!.price!.toStringAsFixed(2) : '');
    _discountPriceCtrl = TextEditingController(text: p?.discountPrice != null ? p!.discountPrice!.toStringAsFixed(2) : '');

    _selectedCategoryId = p?.storeCategoryId;

    if (p?.variations != null) {
      _variations = p!.variations!.map((v) => {'name': v.name, 'price': v.price?.toString() ?? '0'}).toList();
    }
    if (p?.addons != null) {
      _addons = p!.addons!.map((a) => {'name': a.name, 'price': a.price?.toString() ?? '0'}).toList();
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _descCtrl.dispose();
    _priceCtrl.dispose();
    _discountPriceCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final pickedFile = await _picker.pickImage(source: source, imageQuality: 80);
      if (pickedFile != null) {
        setState(() {
          _selectedImage = File(pickedFile.path);
        });
      }
    } catch (_) {}
  }

  void _showImagePickerBottomSheet() {
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
              Text('Seleccionar Imagen', style: boldLarge),
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  GestureDetector(
                    onTap: () {
                      Navigator.pop(ctx);
                      _pickImage(ImageSource.camera);
                    },
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: MyColor.primaryColor.withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
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
                      _pickImage(ImageSource.gallery);
                    },
                    child: Column(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: MyColor.primaryColor.withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
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

  void _addVariation() {
    setState(() {
      _variations.add({'name': '', 'price': '0'});
    });
  }

  void _removeVariation(int index) {
    setState(() {
      _variations.removeAt(index);
    });
  }

  void _addAddon() {
    setState(() {
      _addons.add({'name': '', 'price': '0'});
    });
  }

  void _removeAddon(int index) {
    setState(() {
      _addons.removeAt(index);
    });
  }

  Future<void> _submit(SellerController c) async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedCategoryId == null) {
      Get.snackbar('Error', 'Debe seleccionar una categoría de menú.',
          backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      return;
    }

    final data = {
      'name': _nameCtrl.text.trim(),
      'description': _descCtrl.text.trim(),
      'price': double.parse(_priceCtrl.text.trim()),
      'store_category_id': _selectedCategoryId,
    };

    if (_discountPriceCtrl.text.trim().isNotEmpty) {
      data['discount_price'] = double.parse(_discountPriceCtrl.text.trim());
    }

    // Prepare variations
    for (int i = 0; i < _variations.length; i++) {
      data['variations[$i][name]'] = _variations[i]['name'];
      data['variations[$i][price]'] = double.tryParse(_variations[i]['price']) ?? 0.0;
    }

    // Prepare addons
    for (int i = 0; i < _addons.length; i++) {
      data['addons[$i][name]'] = _addons[i]['name'];
      data['addons[$i][price]'] = double.tryParse(_addons[i]['price']) ?? 0.0;
    }

    bool success;
    if (widget.product == null) {
      success = await c.createProduct(widget.storeId, data, image: _selectedImage);
    } else {
      success = await c.updateProduct(widget.product!.id!, widget.storeId, data, image: _selectedImage);
    }

    if (success) {
      Get.back();
      Get.snackbar('Éxito', widget.product == null ? 'Producto creado con éxito' : 'Producto actualizado con éxito',
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
            title: Text(
              widget.product == null ? 'Nuevo Producto' : 'Editar Producto',
              style: boldLarge.copyWith(color: MyColor.colorWhite),
            ),
            centerTitle: true,
          ),
          body: c.isLoading
              ? const Center(child: CircularProgressIndicator())
              : Form(
                  key: _formKey,
                  child: ListView(
                    padding: const EdgeInsets.all(Dimensions.space16),
                    children: [
                      // Product Image Selector
                      Center(
                        child: GestureDetector(
                          onTap: _showImagePickerBottomSheet,
                          child: Container(
                            height: 120,
                            width: 120,
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.05),
                                  blurRadius: 10,
                                  offset: const Offset(0, 4),
                                )
                              ],
                              border: Border.all(color: MyColor.neutral200),
                            ),
                            child: _selectedImage != null
                                ? ClipRRect(
                                    borderRadius: BorderRadius.circular(15),
                                    child: Image.file(_selectedImage!, fit: BoxFit.cover),
                                  )
                                : widget.product?.image != null
                                    ? ClipRRect(
                                        borderRadius: BorderRadius.circular(15),
                                        child: MyImageWidget(
                                          imageUrl: '${c.productImagePath}/${widget.product!.image}',
                                          boxFit: BoxFit.cover,
                                        ),
                                      )
                                    : Icon(Icons.add_photo_alternate_outlined, color: MyColor.primaryColor, size: 40),
                          ),
                        ),
                      ),
                      const SizedBox(height: Dimensions.space20),

                      // Category Dropdown
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                          border: Border.all(color: MyColor.neutral200),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButtonFormField<int>(
                            initialValue: _selectedCategoryId,
                            hint: Text('Selecciona una categoría de menú', style: regularDefault),
                            decoration: const InputDecoration(border: InputBorder.none),
                            items: c.storeCategories.map<DropdownMenuItem<int>>((cat) {
                              return DropdownMenuItem<int>(
                                value: cat['id'] is int ? cat['id'] : int.parse(cat['id'].toString()),
                                child: Text(cat['name']?.toString() ?? '', style: regularDefault),
                              );
                            }).toList(),
                            onChanged: (val) {
                              setState(() {
                                _selectedCategoryId = val;
                              });
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: Dimensions.space16),

                      // Name Input
                      TextFormField(
                        controller: _nameCtrl,
                        decoration: InputDecoration(
                          labelText: 'Nombre del Plato / Producto',
                          fillColor: Colors.white,
                          filled: true,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                        ),
                        validator: (value) => value == null || value.trim().isEmpty ? 'Ingrese un nombre válido' : null,
                      ),
                      const SizedBox(height: Dimensions.space16),

                      // Description Input
                      TextFormField(
                        controller: _descCtrl,
                        maxLines: 3,
                        decoration: InputDecoration(
                          labelText: 'Descripción del plato',
                          fillColor: Colors.white,
                          filled: true,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                        ),
                      ),
                      const SizedBox(height: Dimensions.space16),

                      // Prices Row
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _priceCtrl,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: InputDecoration(
                                labelText: 'Precio Base (S/)',
                                fillColor: Colors.white,
                                filled: true,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                              ),
                              validator: (value) {
                                if (value == null || value.trim().isEmpty) return 'Requerido';
                                if (double.tryParse(value) == null) return 'Número inválido';
                                return null;
                              },
                            ),
                          ),
                          const SizedBox(width: Dimensions.space12),
                          Expanded(
                            child: TextFormField(
                              controller: _discountPriceCtrl,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              decoration: InputDecoration(
                                labelText: 'Precio Oferta (S/)',
                                fillColor: Colors.white,
                                filled: true,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
                              ),
                              validator: (value) {
                                if (value != null && value.trim().isNotEmpty) {
                                  final disc = double.tryParse(value);
                                  final price = double.tryParse(_priceCtrl.text);
                                  if (disc == null) return 'Inválido';
                                  if (price != null && disc >= price) return 'Debe ser menor';
                                }
                                return null;
                              },
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: Dimensions.space24),

                      // Variations Section
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Variaciones (ej: Mediano, Grande)', style: boldDefault),
                          TextButton.icon(
                            onPressed: _addVariation,
                            icon: const Icon(Icons.add, size: 18),
                            label: const Text('Agregar'),
                          ),
                        ],
                      ),
                      const SizedBox(height: Dimensions.space8),
                      if (_variations.isEmpty)
                        Text('Sin variaciones', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))
                      else
                        ListView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: _variations.length,
                          itemBuilder: (context, index) {
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 8),
                              child: Row(
                                children: [
                                  Expanded(
                                    flex: 3,
                                    child: TextFormField(
                                      initialValue: _variations[index]['name'],
                                      decoration: const InputDecoration(
                                        labelText: 'Nombre',
                                        filled: true,
                                        fillColor: Colors.white,
                                        border: OutlineInputBorder(),
                                      ),
                                      onChanged: (val) => _variations[index]['name'] = val,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    flex: 2,
                                    child: TextFormField(
                                      initialValue: _variations[index]['price'],
                                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                      decoration: const InputDecoration(
                                        labelText: 'Precio (S/)',
                                        filled: true,
                                        fillColor: Colors.white,
                                        border: OutlineInputBorder(),
                                      ),
                                      onChanged: (val) => _variations[index]['price'] = val,
                                    ),
                                  ),
                                  IconButton(
                                    icon: Icon(Icons.delete, color: MyColor.redCancelTextColor),
                                    onPressed: () => _removeVariation(index),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                      const SizedBox(height: Dimensions.space24),

                      // Addons Section
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text('Adicionales / Extras', style: boldDefault),
                          TextButton.icon(
                            onPressed: _addAddon,
                            icon: const Icon(Icons.add, size: 18),
                            label: const Text('Agregar'),
                          ),
                        ],
                      ),
                      const SizedBox(height: Dimensions.space8),
                      if (_addons.isEmpty)
                        Text('Sin adicionales', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))
                      else
                        ListView.builder(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: _addons.length,
                          itemBuilder: (context, index) {
                            return Padding(
                              padding: const EdgeInsets.only(bottom: 8),
                              child: Row(
                                children: [
                                  Expanded(
                                    flex: 3,
                                    child: TextFormField(
                                      initialValue: _addons[index]['name'],
                                      decoration: const InputDecoration(
                                        labelText: 'Nombre',
                                        filled: true,
                                        fillColor: Colors.white,
                                        border: OutlineInputBorder(),
                                      ),
                                      onChanged: (val) => _addons[index]['name'] = val,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    flex: 2,
                                    child: TextFormField(
                                      initialValue: _addons[index]['price'],
                                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                      decoration: const InputDecoration(
                                        labelText: 'Precio (S/)',
                                        filled: true,
                                        fillColor: Colors.white,
                                        border: OutlineInputBorder(),
                                      ),
                                      onChanged: (val) => _addons[index]['price'] = val,
                                    ),
                                  ),
                                  IconButton(
                                    icon: Icon(Icons.delete, color: MyColor.redCancelTextColor),
                                    onPressed: () => _removeAddon(index),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),

                      const SizedBox(height: Dimensions.space40),
                      RoundedButton(
                        text: widget.product == null ? 'Crear Producto' : 'Guardar Cambios',
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
