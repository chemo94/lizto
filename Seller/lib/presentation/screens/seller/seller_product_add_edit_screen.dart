import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/model/delivery/delivery_models.dart';
import 'package:lizto_store/presentation/components/buttons/rounded_button.dart';
import 'package:lizto_store/presentation/components/image/my_network_image_widget.dart';

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
  late TextEditingController _sunatCodeCtrl;
  late TextEditingController _costCtrl;
  late TextEditingController _stockCtrl;
  late TextEditingController _minStockCtrl;

  bool _isActive = true;
  String _selectedStockType = 'packaged';
  String _selectedUnit = 'NIU';
  String _selectedTaxType = 'exonerado';
  int? _selectedCategoryId;

  File? _selectedImage;
  final ImagePicker _picker = ImagePicker();

  List<Map<String, dynamic>> _variations = [];
  List<Map<String, dynamic>> _addons = [];

  final List<Map<String, String>> _sunatCatalog = [
    {'code': '50190000', 'name': 'Alimentos preparados / consumo general'},
    {'code': '50180101', 'name': 'Lomo de res / Carnes procesadas'},
    {'code': '50111500', 'name': 'Pollo / Aves de corral'},
    {'code': '50121500', 'name': 'Pescados y mariscos'},
    {'code': '50151500', 'name': 'Bebidas no alcohólicas (gaseosas, jugos)'},
    {'code': '50151600', 'name': 'Bebidas alcohólicas (cervezas, licores)'},
    {'code': '50250000', 'name': 'Platos preparados / comida rápida'},
    {'code': '50131700', 'name': 'Lácteos, quesos y yogurt'},
    {'code': '50202300', 'name': 'Café, té y infusiones'},
    {'code': '50161800', 'name': 'Confitería, postres y dulces'},
    {'code': '50192100', 'name': 'Salsas y condimentos'},
    {'code': '50101500', 'name': 'Frutas y verduras frescas'},
    {'code': '50192300', 'name': 'Panadería y pastelería'},
  ];

  static const List<Map<String, String>> _allSunatUnits = [
    // Unidades comunes
    {'code': 'NIU', 'name': 'Unidad (UND)'},
    {'code': 'DZN', 'name': 'Docena (DOC)'},
    {'code': 'HD', 'name': 'Media docena (1/2 DOC)'},
    {'code': 'QD', 'name': 'Cuarto de docena (1/4 DOC)'},
    {'code': 'C62', 'name': 'Piezas (PZ)'},
    {'code': 'PR', 'name': 'Par (PAR)'},
    {'code': 'SET', 'name': 'Juego (JGO)'},
    {'code': 'KT', 'name': 'Kit (KIT)'},

    // Peso
    {'code': 'KGM', 'name': 'Kilogramo (KG)'},
    {'code': 'GRM', 'name': 'Gramos (GR)'},
    {'code': 'TNE', 'name': 'Toneladas (TNL)'},
    {'code': 'LBR', 'name': 'Libras (LB)'},
    {'code': 'ONZ', 'name': 'Onzas (ONZ)'},
    {'code': 'MGM', 'name': 'Miligramos (MG)'},

    // Volumen
    {'code': 'LTR', 'name': 'Litro (LT)'},
    {'code': 'MLT', 'name': 'Mililitro (ML)'},
    {'code': 'GLL', 'name': 'Galon (GL)'},
    {'code': 'GLI', 'name': 'Galon ingles (GL)'},

    // Longitud
    {'code': 'MTR', 'name': 'Metro (M)'},
    {'code': 'CMT', 'name': 'Centimetro (CM)'},
    {'code': 'MMT', 'name': 'Milimetro (ML)'},
    {'code': 'FOT', 'name': 'Pies (PIE)'},
    {'code': 'INH', 'name': 'Pulgadas (INCH)'},
    {'code': 'YRD', 'name': 'Yarda (YD)'},
    {'code': 'KTM', 'name': 'Kilometro (KM)'},

    // Area / Volumen espacial
    {'code': 'MTK', 'name': 'Metro cuadrado (M2)'},
    {'code': 'MTQ', 'name': 'Metro cubico (M3)'},
    {'code': 'FTK', 'name': 'Pies cuadrados (PIE2)'},
    {'code': 'FTQ', 'name': 'Pies cubicos (PIE3)'},
    {'code': 'CMK', 'name': 'Centimetro cuadrado (CM2)'},
    {'code': 'CMQ', 'name': 'Centimetro cubico (CM3)'},
    {'code': 'MMK', 'name': 'Milimetro cuadrado (ML2)'},
    {'code': 'MMQ', 'name': 'Milimetro cubico (ML3)'},

    // Envases / Embalajes
    {'code': 'BO', 'name': 'Botellas (BOT)'},
    {'code': 'CA', 'name': 'Latas (LT)'},
    {'code': 'BX', 'name': 'Caja (CAJ)'},
    {'code': 'PK', 'name': 'Paquete (PQT)'},
    {'code': 'BG', 'name': 'Bolsa (BOLS)'},
    {'code': 'BE', 'name': 'Fardo (FARD)'},
    {'code': 'SA', 'name': 'Saco (SCO)'},
    {'code': 'CH', 'name': 'Envase (ENV)'},
    {'code': 'JR', 'name': 'Frasco (FCO)'},
    {'code': 'BLL', 'name': 'Barril (BRL)'},
    {'code': 'CY', 'name': 'Cilindro (CIL)'},
    {'code': 'BJ', 'name': 'Balde (BALD)'},
    {'code': 'JG', 'name': 'Jarra (JARR)'},
    {'code': 'CT', 'name': 'Carton (CTON)'},
    {'code': 'ST', 'name': 'Pliego (PLGO)'},
    {'code': 'TU', 'name': 'Tubos (TB)'},
    {'code': 'RL', 'name': 'Carrete (CRR)'},

    // Otros
    {'code': 'ZZ', 'name': 'Servicio (SERV)'},
    {'code': 'HUR', 'name': 'Hora (HR)'},
    {'code': 'SEC', 'name': 'Segundo (SEG)'},
    {'code': 'HT', 'name': 'Media hora (1/2 H)'},
    {'code': 'U2', 'name': 'Tableta o blister (BLIST)'},
    {'code': 'AV', 'name': 'Capsula (CAPS)'},
    {'code': 'PF', 'name': 'Paletas (PAL)'},
    {'code': 'PG', 'name': 'Placas (PLAC)'},
    {'code': 'RD', 'name': 'Varilla (VAR)'},
    {'code': 'LEF', 'name': 'Hoja (HOJA)'},
    {'code': 'RM', 'name': 'Resma (RESM)'},
    {'code': 'BT', 'name': 'Tornillo (TORN)'},
    {'code': 'UM', 'name': 'Millon (MILL)'},
    {'code': 'MIL', 'name': 'Millar (MIL)'},
    {'code': 'CEN', 'name': 'Centenar (CTO)'},
    {'code': 'KWH', 'name': 'Kilovatio hora (KWxH)'},
    {'code': 'MWH', 'name': 'Megavatio hora (MWxH)'},
  ];

  @override
  void initState() {
    super.initState();
    final p = widget.product;
    _nameCtrl = TextEditingController(text: p?.name ?? '');
    _descCtrl = TextEditingController(text: p?.description ?? '');
    _priceCtrl = TextEditingController(text: p?.price != null ? p!.price!.toStringAsFixed(2) : '');
    _discountPriceCtrl = TextEditingController(text: p?.discountPrice != null ? p!.discountPrice!.toStringAsFixed(2) : '');

    _isActive = p == null ? true : (p.status == 1);
    _selectedStockType = p?.stockType ?? 'packaged';
    _sunatCodeCtrl = TextEditingController(text: p?.sunatCode ?? '50190000');

    final validUnitCodes = _allSunatUnits.map((u) => u['code']).toSet();
    final unitCode = p?.unit ?? 'NIU';
    _selectedUnit = validUnitCodes.contains(unitCode) ? unitCode : 'NIU';

    final validTaxTypes = {'gravado', 'exonerado', 'inafecto'};
    final taxTypeVal = p?.taxType?.toLowerCase() ?? 'exonerado';
    _selectedTaxType = validTaxTypes.contains(taxTypeVal) ? taxTypeVal : 'exonerado';

    _costCtrl = TextEditingController(text: p?.cost != null ? p!.cost!.toStringAsFixed(2) : '0');
    _stockCtrl = TextEditingController(text: p?.initialStock != null ? p!.initialStock!.toStringAsFixed(0) : '0');
    _minStockCtrl = TextEditingController(text: p?.minStock != null ? p!.minStock!.toStringAsFixed(0) : '5');

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
    _sunatCodeCtrl.dispose();
    _costCtrl.dispose();
    _stockCtrl.dispose();
    _minStockCtrl.dispose();
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

  void _showSunatSearchModal() {
    TextEditingController searchFilterCtrl = TextEditingController();
    List<Map<String, String>> filteredList = List.from(_sunatCatalog);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                top: 20,
                left: 16,
                right: 16,
                bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Buscar Código SUNAT (UNSPSC)', style: boldLarge),
                  const SizedBox(height: 12),
                  TextField(
                    controller: searchFilterCtrl,
                    decoration: InputDecoration(
                      hintText: 'Ej: 50180101 o Lomo de res',
                      prefixIcon: const Icon(Icons.search),
                      filled: true,
                      fillColor: const Color(0xFFF1F5F9),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide.none,
                      ),
                    ),
                    onChanged: (query) {
                      setModalState(() {
                        if (query.trim().isEmpty) {
                          filteredList = List.from(_sunatCatalog);
                        } else {
                          final q = query.toLowerCase().trim();
                          filteredList = _sunatCatalog.where((item) {
                            return item['code']!.contains(q) || item['name']!.toLowerCase().contains(q);
                          }).toList();
                        }
                      });
                    },
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    height: 280,
                    child: filteredList.isEmpty
                        ? Center(
                            child: Text(
                              'No se encontraron códigos match con la búsqueda',
                              style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                            ),
                          )
                        : ListView.separated(
                            itemCount: filteredList.length,
                            separatorBuilder: (ctx, i) => const Divider(height: 1),
                            itemBuilder: (ctx, i) {
                              final item = filteredList[i];
                              return ListTile(
                                dense: true,
                                title: Text('${item['code']} - ${item['name']}', style: boldDefault),
                                onTap: () {
                                  setState(() {
                                    _sunatCodeCtrl.text = item['code']!;
                                  });
                                  Navigator.pop(ctx);
                                },
                              );
                            },
                          ),
                  ),
                ],
              ),
            );
          },
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

    final data = <String, dynamic>{
      'name': _nameCtrl.text.trim(),
      'description': _descCtrl.text.trim(),
      'price': double.parse(_priceCtrl.text.trim()),
      'store_category_id': _selectedCategoryId,
      'status': _isActive ? 1 : 0,
      'stock_type': _selectedStockType,
      'sunat_code': _sunatCodeCtrl.text.trim(),
      'unit': _selectedUnit,
      'tax_type': _selectedTaxType,
      'cost': double.tryParse(_costCtrl.text.trim()) ?? 0,
      'initial_stock': double.tryParse(_stockCtrl.text.trim()) ?? 0,
      'min_stock': double.tryParse(_minStockCtrl.text.trim()) ?? 5,
    };

    if (_discountPriceCtrl.text.trim().isNotEmpty) {
      data['discount_price'] = double.parse(_discountPriceCtrl.text.trim());
    }

    // Prepare variations
    for (int i = 0; i < _variations.length; i++) {
      data['variations[$i][name]'] = _variations[i]['name'];
      data['variations[$i][price]'] = double.tryParse(_variations[i]['price']?.toString() ?? '0') ?? 0.0;
    }

    // Prepare addons
    for (int i = 0; i < _addons.length; i++) {
      data['addons[$i][name]'] = _addons[i]['name'];
      data['addons[$i][price]'] = double.tryParse(_addons[i]['price']?.toString() ?? '0') ?? 0.0;
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
          backgroundColor: const Color(0xFFF8FAFC),
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            elevation: 0,
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
                      // Product Active Option Box (Matched with design image)
                      GestureDetector(
                        onTap: () {
                          setState(() {
                            _isActive = !_isActive;
                          });
                        },
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.02),
                                blurRadius: 4,
                                offset: const Offset(0, 2),
                              )
                            ],
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 22,
                                height: 22,
                                decoration: BoxDecoration(
                                  color: _isActive ? const Color(0xFF10B981) : Colors.white,
                                  borderRadius: BorderRadius.circular(5),
                                  border: Border.all(
                                    color: _isActive ? const Color(0xFF10B981) : const Color(0xFFCBD5E1),
                                    width: 2,
                                  ),
                                ),
                                child: _isActive
                                    ? const Icon(Icons.check, size: 16, color: Colors.white)
                                    : null,
                              ),
                              const SizedBox(width: 12),
                              Text(
                                'Producto activo (visible en el menú)',
                                style: boldDefault.copyWith(color: const Color(0xFF0F172A), fontSize: 14),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: Dimensions.space16),

                      // TIPO DE INVENTARIO Label & Dropdown
                      Text(
                        'TIPO DE INVENTARIO',
                        style: boldSmall.copyWith(color: const Color(0xFF475569), fontSize: 11, letterSpacing: 0.5),
                      ),
                      const SizedBox(height: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: _selectedStockType,
                            isExpanded: true,
                            icon: const Icon(Icons.keyboard_arrow_down_rounded, color: Color(0xFF64748B)),
                            items: const [
                              DropdownMenuItem(
                                value: 'packaged',
                                child: Text('Producto empaquetado (devoluble)', style: regularDefault),
                              ),
                              DropdownMenuItem(
                                value: 'prepared',
                                child: Text('Preparado/Comida (no devuelve stock)', style: regularDefault),
                              ),
                              DropdownMenuItem(
                                value: 'none',
                                child: Text('Sin inventario', style: regularDefault),
                              ),
                            ],
                            onChanged: (val) {
                              if (val != null) {
                                setState(() {
                                  _selectedStockType = val;
                                });
                              }
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: Dimensions.space16),

                      // CÓDIGO PRODUCTO SUNAT (UNSPSC) — 8 DÍGITOS
                      Row(
                        children: [
                          Text(
                            'CÓDIGO PRODUCTO SUNAT (UNSPSC) ',
                            style: boldSmall.copyWith(color: const Color(0xFF475569), fontSize: 11, letterSpacing: 0.5),
                          ),
                          Text(
                            '— 8 DÍGITOS',
                            style: regularSmall.copyWith(color: const Color(0xFF94A3B8), fontSize: 11),
                          ),
                        ],
                      ),
                      const SizedBox(height: 6),
                      TextFormField(
                        controller: _sunatCodeCtrl,
                        maxLength: 8,
                        keyboardType: TextInputType.number,
                        decoration: InputDecoration(
                          hintText: 'Buscar código...',
                          counterText: '',
                          filled: true,
                          fillColor: Colors.white,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          suffixIcon: IconButton(
                            icon: const Icon(Icons.search_rounded, color: Color(0xFF64748B)),
                            onPressed: _showSunatSearchModal,
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(color: MyColor.primaryColor),
                          ),
                        ),
                        onTap: () {
                          if (_sunatCodeCtrl.text.isEmpty) {
                            _showSunatSearchModal();
                          }
                        },
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Escribe para buscar por nombre o código. Ej: 50180101 (Lomo de res)',
                        style: regularSmall.copyWith(color: const Color(0xFF94A3B8), fontSize: 11),
                      ),
                      const SizedBox(height: Dimensions.space16),

                      // Card Box containing SUNAT Unit, Tax Type, Unit Cost, Stocks & Info
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9).withValues(alpha: 0.5),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Row 1: UNIDAD DE MEDIDA & TIPO TRIBUTARIO
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'UNIDAD DE MEDIDA (SUNAT) *',
                                        style: boldSmall.copyWith(color: const Color(0xFF475569), fontSize: 10),
                                      ),
                                      const SizedBox(height: 6),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10),
                                        decoration: BoxDecoration(
                                          color: Colors.white,
                                          borderRadius: BorderRadius.circular(10),
                                          border: Border.all(color: const Color(0xFFE2E8F0)),
                                        ),
                                        child: DropdownButtonHideUnderline(
                                          child: DropdownButton<String>(
                                            value: _selectedUnit,
                                            isExpanded: true,
                                            style: regularDefault.copyWith(fontSize: 13, color: const Color(0xFF0F172A)),
                                            items: _allSunatUnits.map((u) {
                                              return DropdownMenuItem<String>(
                                                value: u['code'],
                                                child: Text(u['name'] ?? '', overflow: TextOverflow.ellipsis),
                                              );
                                            }).toList(),
                                            onChanged: (val) {
                                              if (val != null) {
                                                setState(() {
                                                  _selectedUnit = val;
                                                });
                                              }
                                            },
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'TIPO TRIBUTARIO',
                                        style: boldSmall.copyWith(color: const Color(0xFF475569), fontSize: 10),
                                      ),
                                      const SizedBox(height: 6),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10),
                                        decoration: BoxDecoration(
                                          color: Colors.white,
                                          borderRadius: BorderRadius.circular(10),
                                          border: Border.all(color: const Color(0xFFE2E8F0)),
                                        ),
                                        child: DropdownButtonHideUnderline(
                                          child: DropdownButton<String>(
                                            value: _selectedTaxType,
                                            isExpanded: true,
                                            style: regularDefault.copyWith(fontSize: 13, color: const Color(0xFF0F172A)),
                                            items: const [
                                              DropdownMenuItem(value: 'gravado', child: Text('Gravado')),
                                              DropdownMenuItem(value: 'exonerado', child: Text('Exonerado')),
                                              DropdownMenuItem(value: 'inafecto', child: Text('Inafecto')),
                                            ],
                                            onChanged: (val) {
                                              if (val != null) {
                                                setState(() {
                                                  _selectedTaxType = val;
                                                });
                                              }
                                            },
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),

                            // Row 2: COSTO UNITARIO & STOCK ACTUAL / INICIAL
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'COSTO UNITARIO (S/)',
                                        style: boldSmall.copyWith(color: const Color(0xFF475569), fontSize: 10),
                                      ),
                                      const SizedBox(height: 6),
                                      TextFormField(
                                        controller: _costCtrl,
                                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                        style: regularDefault.copyWith(fontSize: 13),
                                        decoration: InputDecoration(
                                          filled: true,
                                          fillColor: Colors.white,
                                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                          enabledBorder: OutlineInputBorder(
                                            borderRadius: BorderRadius.circular(10),
                                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                                          ),
                                          focusedBorder: OutlineInputBorder(
                                            borderRadius: BorderRadius.circular(10),
                                            borderSide: BorderSide(color: MyColor.primaryColor),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'STOCK ACTUAL / INICIAL',
                                        style: boldSmall.copyWith(color: const Color(0xFF475569), fontSize: 10),
                                      ),
                                      const SizedBox(height: 6),
                                      TextFormField(
                                        controller: _stockCtrl,
                                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                        style: regularDefault.copyWith(fontSize: 13),
                                        decoration: InputDecoration(
                                          filled: true,
                                          fillColor: Colors.white,
                                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                          enabledBorder: OutlineInputBorder(
                                            borderRadius: BorderRadius.circular(10),
                                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                                          ),
                                          focusedBorder: OutlineInputBorder(
                                            borderRadius: BorderRadius.circular(10),
                                            borderSide: BorderSide(color: MyColor.primaryColor),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),

                            // Row 3: STOCK MÍNIMO (ALERTA)
                            Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'STOCK MÍNIMO (ALERTA)',
                                        style: boldSmall.copyWith(color: const Color(0xFF475569), fontSize: 10),
                                      ),
                                      const SizedBox(height: 6),
                                      TextFormField(
                                        controller: _minStockCtrl,
                                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                        style: regularDefault.copyWith(fontSize: 13),
                                        decoration: InputDecoration(
                                          filled: true,
                                          fillColor: Colors.white,
                                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                          enabledBorder: OutlineInputBorder(
                                            borderRadius: BorderRadius.circular(10),
                                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                                          ),
                                          focusedBorder: OutlineInputBorder(
                                            borderRadius: BorderRadius.circular(10),
                                            borderSide: BorderSide(color: MyColor.primaryColor),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const Expanded(child: SizedBox()),
                              ],
                            ),
                            const SizedBox(height: 16),

                            // Info Footer Notice
                            Row(
                              children: [
                                const Icon(Icons.info_outline_rounded, size: 16, color: Color(0xFF64748B)),
                                const SizedBox(width: 6),
                                Expanded(
                                  child: Text(
                                    'Se crea automáticamente el insumo en inventario',
                                    style: regularSmall.copyWith(color: const Color(0xFF64748B), fontSize: 12),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: Dimensions.space24),

                      // Product Image Selector
                      Center(
                        child: GestureDetector(
                          onTap: _showImagePickerBottomSheet,
                          child: Container(
                            height: 110,
                            width: 110,
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
                              border: Border.all(color: const Color(0xFFE2E8F0)),
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
                                    : Column(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          Icon(Icons.add_photo_alternate_outlined, color: MyColor.primaryColor, size: 36),
                                          const SizedBox(height: 4),
                                          Text('Subir Imagen', style: regularSmall.copyWith(fontSize: 11)),
                                        ],
                                      ),
                          ),
                        ),
                      ),
                      const SizedBox(height: Dimensions.space20),

                      // Category Dropdown
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
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
                          labelText: 'Nombre del Plato / Producto *',
                          fillColor: Colors.white,
                          filled: true,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                          ),
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
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                          ),
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
                                labelText: 'Precio Base (S/) *',
                                fillColor: Colors.white,
                                filled: true,
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                                ),
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
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                                ),
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

                      // VARIACIONES Section (Matched with design image)
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Text(
                                'VARIACIONES ',
                                style: boldDefault.copyWith(color: const Color(0xFF1E293B), fontSize: 13),
                              ),
                              Text(
                                '(TALLA, SABOR, ETC.)',
                                style: regularSmall.copyWith(color: const Color(0xFF94A3B8), fontSize: 11),
                              ),
                            ],
                          ),
                          OutlinedButton.icon(
                            onPressed: _addVariation,
                            icon: const Icon(Icons.add, size: 16),
                            label: const Text('Agregar', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFF1E293B),
                              side: const BorderSide(color: Color(0xFFCBD5E1)),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: Dimensions.space8),
                      if (_variations.isEmpty)
                        const SizedBox()
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
                                      initialValue: _variations[index]['name']?.toString(),
                                      decoration: InputDecoration(
                                        labelText: 'Nombre',
                                        filled: true,
                                        fillColor: Colors.white,
                                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                      ),
                                      onChanged: (val) => _variations[index]['name'] = val,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    flex: 2,
                                    child: TextFormField(
                                      initialValue: _variations[index]['price']?.toString(),
                                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                      decoration: InputDecoration(
                                        labelText: 'Precio (S/)',
                                        filled: true,
                                        fillColor: Colors.white,
                                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                      ),
                                      onChanged: (val) => _variations[index]['price'] = val,
                                    ),
                                  ),
                                  IconButton(
                                    icon: Icon(Icons.delete_outline_rounded, color: MyColor.redCancelTextColor),
                                    onPressed: () => _removeVariation(index),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                      const SizedBox(height: Dimensions.space24),

                      // EXTRAS / ADD-ONS Section (Matched with design image)
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            'EXTRAS / ADD-ONS',
                            style: boldDefault.copyWith(color: const Color(0xFF1E293B), fontSize: 13),
                          ),
                          OutlinedButton.icon(
                            onPressed: _addAddon,
                            icon: const Icon(Icons.add, size: 16),
                            label: const Text('Agregar', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFF1E293B),
                              side: const BorderSide(color: Color(0xFFCBD5E1)),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: Dimensions.space8),
                      if (_addons.isEmpty)
                        const SizedBox()
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
                                      initialValue: _addons[index]['name']?.toString(),
                                      decoration: InputDecoration(
                                        labelText: 'Nombre',
                                        filled: true,
                                        fillColor: Colors.white,
                                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                      ),
                                      onChanged: (val) => _addons[index]['name'] = val,
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    flex: 2,
                                    child: TextFormField(
                                      initialValue: _addons[index]['price']?.toString(),
                                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                      decoration: InputDecoration(
                                        labelText: 'Precio (S/)',
                                        filled: true,
                                        fillColor: Colors.white,
                                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                      ),
                                      onChanged: (val) => _addons[index]['price'] = val,
                                    ),
                                  ),
                                  IconButton(
                                    icon: Icon(Icons.delete_outline_rounded, color: MyColor.redCancelTextColor),
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
