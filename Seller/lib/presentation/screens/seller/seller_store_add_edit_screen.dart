import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:async';
import 'package:image_picker/image_picker.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/environment.dart';
import 'package:lizto_store/presentation/components/buttons/rounded_button.dart';

class SellerStoreAddEditScreen extends StatefulWidget {
  final Map<String, dynamic>? store;
  const SellerStoreAddEditScreen({super.key, this.store});

  @override
  State<SellerStoreAddEditScreen> createState() => _SellerStoreAddEditScreenState();
}

class _SellerStoreAddEditScreenState extends State<SellerStoreAddEditScreen> {
  final _formKey = GlobalKey<FormState>();
  late TextEditingController _nameCtrl, _descCtrl, _addrCtrl, _deliveryFeeCtrl, _minOrderCtrl, _prepTimeCtrl;
  File? _logoImage, _coverImage;
  final ImagePicker _picker = ImagePicker();
  bool _saving = false;
  bool _locating = false;
  double? _lat, _lng;

  // Categories
  List<Map<String, dynamic>> _generalCategories = [];
  List<Map<String, dynamic>> _subCategories = [];
  Set<int> _selectedGeneralCategoryIds = {};
  Set<int> _selectedSubCategoryIds = {};
  bool _loadingCategories = true;
  bool _expandedByCategory = true;

  // Address search
  final _addrFocus = FocusNode();
  List<_PlacePrediction> _predictions = [];
  bool _showPredictions = false;
  Timer? _debounce;

  // Schedules
  List<Map<String, dynamic>> _schedules = [];
  bool _loadingSchedules = false;

  // Opening/Closing time
  late TextEditingController _openTimeCtrl, _closeTimeCtrl;

  bool get _isEditing => widget.store != null;

  @override
  void initState() {
    super.initState();
    _nameCtrl = TextEditingController(text: _isEditing ? widget.store!['name']?.toString() ?? '' : '');
    _descCtrl = TextEditingController(text: _isEditing ? widget.store!['description']?.toString() ?? '' : '');
    _addrCtrl = TextEditingController(text: _isEditing ? widget.store!['address']?.toString() ?? '' : '');
    _deliveryFeeCtrl = TextEditingController(text: _isEditing ? widget.store!['delivery_fee']?.toString() ?? '' : '');
    _minOrderCtrl = TextEditingController(text: _isEditing ? widget.store!['min_order_amount']?.toString() ?? '' : '');
    _prepTimeCtrl = TextEditingController(text: _isEditing ? widget.store!['preparation_time']?.toString() ?? '20' : '20');
    _openTimeCtrl = TextEditingController(text: _isEditing ? widget.store!['opening_time']?.toString() ?? '08:00' : '08:00');
    _closeTimeCtrl = TextEditingController(text: _isEditing ? widget.store!['closing_time']?.toString() ?? '22:00' : '22:00');
    if (_isEditing) {
      _lat = widget.store!['latitude'] is num ? (widget.store!['latitude'] as num).toDouble() : null;
      _lng = widget.store!['longitude'] is num ? (widget.store!['longitude'] as num).toDouble() : null;
      if (widget.store!['general_categories'] != null) {
        for (var c in widget.store!['general_categories']) {
          _selectedGeneralCategoryIds.add(c['id'] as int);
        }
      }
      if (widget.store!['sub_categories'] != null) {
        for (var c in widget.store!['sub_categories']) {
          _selectedSubCategoryIds.add(c['id'] as int);
        }
      }
    }
    _addrCtrl.addListener(_onAddressChanged);
    _loadCategories();
    if (_isEditing) _loadSchedules();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _addrCtrl.removeListener(_onAddressChanged);
    _nameCtrl.dispose(); _descCtrl.dispose(); _addrCtrl.dispose();
    _deliveryFeeCtrl.dispose(); _minOrderCtrl.dispose(); _prepTimeCtrl.dispose();
    _openTimeCtrl.dispose(); _closeTimeCtrl.dispose();
    _addrFocus.dispose();
    super.dispose();
  }

  Future<void> _loadCategories() async {
    try {
      final c = Get.find<SellerController>();
      final response = await c.sellerRepo.getSubCategories();
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        final data = response.responseJson['data'];
        setState(() {
          _generalCategories = (data['general_categories'] as List?)?.map<Map<String, dynamic>>((x) => x as Map<String, dynamic>).toList() ?? [];
          _subCategories = (data['subcategories'] as List?)?.map<Map<String, dynamic>>((x) => x as Map<String, dynamic>).toList() ?? [];
        });
      }
    } catch (_) {}
    setState(() => _loadingCategories = false);
  }

  Future<void> _loadSchedules() async {
    if (widget.store == null) return;
    setState(() => _loadingSchedules = true);
    try {
      final c = Get.find<SellerController>();
      final storeId = widget.store!['id'] as int;
      final response = await c.sellerRepo.getSchedules(storeId);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        setState(() => _schedules = (response.responseJson['data']['schedules'] as List?)?.map<Map<String, dynamic>>((x) => x as Map<String, dynamic>).toList() ?? []);
      }
    } catch (_) {}
    setState(() => _loadingSchedules = false);
  }

  // ── Address Search ──
  void _onAddressChanged() { _debounce?.cancel(); _debounce = Timer(const Duration(milliseconds: 500), () => _searchPlace(_addrCtrl.text)); }

  Future<void> _searchPlace(String q) async {
    if (q.length < 3) { setState(() { _predictions = []; _showPredictions = false; }); return; }
    try {
      final url = '${UrlContainer.googleMapLocationSearch}/place/autocomplete/json?input=${Uri.encodeComponent(q)}&key=${Environment.mapKey}&language=es&components=country:pe';
      final r = await http.get(Uri.parse(url));
      if (r.statusCode == 200 && mounted) {
        final data = jsonDecode(r.body);
        final list = (data['predictions'] as List? ?? []).map((p) => _PlacePrediction(description: p['description']?.toString() ?? '', placeId: p['place_id']?.toString() ?? '')).toList();
        setState(() { _predictions = list; _showPredictions = list.isNotEmpty; });
      }
    } catch (_) {}
  }

  Future<void> _selectPrediction(_PlacePrediction p) async {
    _addrCtrl.text = p.description;
    setState(() { _showPredictions = false; _predictions = []; });
    _addrFocus.unfocus();
    try {
      final url = '${UrlContainer.googleMapLocationSearch}/place/details/json?place_id=${p.placeId}&key=${Environment.mapKey}&fields=geometry';
      final r = await http.get(Uri.parse(url));
      if (r.statusCode == 200) {
        final data = jsonDecode(r.body);
        final loc = data['result']?['geometry']?['location'];
        if (loc != null) setState(() { _lat = (loc['lat'] as num).toDouble(); _lng = (loc['lng'] as num).toDouble(); });
      }
    } catch (_) {}
  }

  Future<void> _getCurrentLocation() async {
    setState(() => _locating = true);
    try {
      final pos = await Geolocator.getCurrentPosition(locationSettings: const LocationSettings(accuracy: LocationAccuracy.best));
      final placemarks = await placemarkFromCoordinates(pos.latitude, pos.longitude);
      String addr = '';
      if (placemarks.isNotEmpty) {
        final p = placemarks.first;
        addr = [p.street, p.subLocality, p.locality].where((e) => e != null && e.isNotEmpty).join(', ');
      }
      if (addr.isEmpty) addr = '${pos.latitude.toStringAsFixed(6)}, ${pos.longitude.toStringAsFixed(6)}';
      setState(() { _addrCtrl.text = addr; _lat = pos.latitude; _lng = pos.longitude; });
    } catch (_) { if (mounted) Get.snackbar('Error', 'No se pudo obtener ubicación'); }
    setState(() => _locating = false);
  }

  Future<void> _pickImage(bool isLogo) async {
    final picked = await _picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (picked != null) setState(() => isLogo ? _logoImage = File(picked.path) : _coverImage = File(picked.path));
  }

  // ── Save ──
  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final c = Get.find<SellerController>();
    final data = {
      'name': _nameCtrl.text.trim(),
      'description': _descCtrl.text.trim(),
      'address': _addrCtrl.text.trim(),
      'delivery_fee': _deliveryFeeCtrl.text.trim(),
      'min_order_amount': _minOrderCtrl.text.trim(),
      'preparation_time': _prepTimeCtrl.text.trim(),
      'opening_time': _openTimeCtrl.text.trim(),
      'closing_time': _closeTimeCtrl.text.trim(),
      if (_lat != null) 'latitude': _lat.toString(),
      if (_lng != null) 'longitude': _lng.toString(),
      'general_category_ids[]': _selectedGeneralCategoryIds.toList(),
      'sub_category_ids[]': _selectedSubCategoryIds.toList(),
    };

    bool ok = _isEditing
        ? await c.updateStore(widget.store!['id'] as int, data, image: _logoImage, coverImage: _coverImage)
        : await c.createStore(data, image: _logoImage, coverImage: _coverImage);

    setState(() => _saving = false);
    if (ok && mounted) Get.back();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: MyColor.cardBgColor,
    appBar: AppBar(backgroundColor: MyColor.primaryColor, title: Text(_isEditing ? 'Editar tienda' : 'Nueva tienda', style: boldLarge.copyWith(color: MyColor.colorWhite)), centerTitle: true),
    body: Form(key: _formKey, child: ListView(padding: EdgeInsets.all(16), children: [
      _imageRow(),
      SizedBox(height: 16),
      _field('Nombre *', _nameCtrl, Icons.store, required: true),
      SizedBox(height: 12),
      _field('Descripción', _descCtrl, Icons.description, maxLines: 3),
      SizedBox(height: 12),
      _addressRow(),
      if (_showPredictions && _predictions.isNotEmpty) _predictionsList(),
      SizedBox(height: 12),
      _field('Delivery fee (S/)', _deliveryFeeCtrl, Icons.attach_money, keyboard: TextInputType.number),
      SizedBox(height: 12),
      _field('Pedido mínimo (S/)', _minOrderCtrl, Icons.shopping_cart, keyboard: TextInputType.number),
      SizedBox(height: 12),
      _field('Tiempo preparación (min)', _prepTimeCtrl, Icons.timer, keyboard: TextInputType.number),
      SizedBox(height: 12),
      _timeRow(),
      SizedBox(height: 16),
      _categoriesSection(),
      if (_isEditing) ...[SizedBox(height: 16), _schedulesSection()],
      SizedBox(height: 24),
      RoundedButton(text: _isEditing ? 'Guardar cambios' : 'Crear tienda', isLoading: _saving, press: _save, isOutlined: false),
    ])),
  );

  Widget _imageRow() => Row(children: [
    Expanded(child: _imagePicker('Logo', _logoImage, () => _pickImage(true))),
    SizedBox(width: 12),
    Expanded(child: _imagePicker('Portada', _coverImage, () => _pickImage(false))),
  ]);

  Widget _imagePicker(String label, File? file, VoidCallback tap) => GestureDetector(
    onTap: tap,
    child: Container(height: 120, decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(16), border: Border.all(color: MyColor.primaryColor.withOpacity(0.2))),
      child: file != null ? ClipRRect(borderRadius: BorderRadius.circular(15), child: Image.file(file, fit: BoxFit.cover, width: double.infinity))
          : Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.add_a_photo, size: 32, color: MyColor.primaryColor.withOpacity(0.4)), SizedBox(height: 8), Text(label, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))])),
  );

  Widget _addressRow() => Row(children: [
    Expanded(child: _field('Dirección *', _addrCtrl, Icons.location_on, required: true, focusNode: _addrFocus)),
    SizedBox(width: 8),
    Container(decoration: BoxDecoration(color: MyColor.primaryColor.withOpacity(0.1), borderRadius: BorderRadius.circular(14)), child: _locating ? SizedBox(width: 48, height: 48, child: Center(child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: MyColor.primaryColor)))) : IconButton(icon: Icon(Icons.my_location_rounded, color: MyColor.primaryColor, size: 22), onPressed: _getCurrentLocation)),
  ]);

  Widget _predictionsList() => Container(margin: EdgeInsets.only(top: 8), decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(14), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 8)]), constraints: BoxConstraints(maxHeight: 200), child: ListView.separated(shrinkWrap: true, padding: EdgeInsets.zero, itemCount: _predictions.length, separatorBuilder: (_, __) => Divider(height: 1), itemBuilder: (_, i) => ListTile(leading: Icon(Icons.location_on_outlined, color: MyColor.primaryColor, size: 20), title: Text(_predictions[i].description, style: regularDefault.copyWith(fontSize: 14)), dense: true, onTap: () => _selectPrediction(_predictions[i]))));

  Widget _timeRow() => Row(children: [
    Expanded(child: _field('Apertura', _openTimeCtrl, Icons.access_time, hint: '08:00', keyboard: TextInputType.datetime)),
    SizedBox(width: 12),
    Expanded(child: _field('Cierre', _closeTimeCtrl, Icons.access_time, hint: '22:00', keyboard: TextInputType.datetime)),
  ]);

  Widget _categoriesSection() {
    if (_loadingCategories) return Center(child: CircularProgressIndicator(color: MyColor.primaryColor));
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text('Categorías', style: boldLarge.copyWith(fontSize: 16, color: MyColor.primaryTextColor)),
      SizedBox(height: 4),
      Text('Selecciona las categorías de tu tienda', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
      SizedBox(height: 10),
      // General categories
      if (_generalCategories.isNotEmpty) ...[
        Text('Categorías principales', style: boldDefault.copyWith(fontSize: 14)),
        SizedBox(height: 6),
        Wrap(spacing: 8, runSpacing: 6, children: _generalCategories.map((cat) => FilterChip(
          label: Text(cat['name']?.toString() ?? ''), selected: _selectedGeneralCategoryIds.contains(cat['id'] as int),
          onSelected: (sel) => setState(() => sel ? _selectedGeneralCategoryIds.add(cat['id'] as int) : _selectedGeneralCategoryIds.remove(cat['id'] as int)),
          selectedColor: MyColor.primaryColor.withOpacity(0.2), checkmarkColor: MyColor.primaryColor,
        )).toList()),
      ],
      SizedBox(height: 12),
      // Sub categories
      if (_subCategories.isNotEmpty) ...[
        Text('Sub-categorías', style: boldDefault.copyWith(fontSize: 14)),
        SizedBox(height: 6),
        Wrap(spacing: 8, runSpacing: 6, children: _subCategories.map((cat) => FilterChip(
          label: Text('${cat['name']} (${cat['general_category_name'] ?? ''})'), selected: _selectedSubCategoryIds.contains(cat['id'] as int),
          onSelected: (sel) => setState(() => sel ? _selectedSubCategoryIds.add(cat['id'] as int) : _selectedSubCategoryIds.remove(cat['id'] as int)),
          selectedColor: MyColor.primaryColor.withOpacity(0.2), checkmarkColor: MyColor.primaryColor,
        )).toList()),
      ],
    ]);
  }

  Widget _schedulesSection() {
    if (_loadingSchedules) return Center(child: CircularProgressIndicator(color: MyColor.primaryColor));
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Row(children: [
        Text('Horarios', style: boldLarge.copyWith(fontSize: 16)),
        Spacer(),
        TextButton.icon(onPressed: _addSchedule, icon: Icon(Icons.add, size: 18), label: Text('Agregar', style: regularSmall.copyWith(color: MyColor.primaryColor))),
      ]),
      SizedBox(height: 8),
      if (_schedules.isEmpty) Text('Sin horarios configurados', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))
      else ..._schedules.asMap().entries.map((e) {
        final s = e.value;
        final slots = (s['slots'] as List?) ?? [];
        return Card(
          margin: EdgeInsets.only(bottom: 8),
          child: Padding(padding: EdgeInsets.all(12), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(children: [Text(s['day_name']?.toString() ?? '', style: boldDefault), Spacer(), IconButton(icon: Icon(Icons.delete_outline, color: Colors.red, size: 20), onPressed: () => _deleteScheduleSlot(s['slots']?.isNotEmpty == true ? (s['slots'] as List).first['id'] : null))]),
            if (slots.isNotEmpty) ...slots.map((slot) => Text('${slot['open_time']} - ${slot['close_time']}', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))),
          ])),
        );
      }),
    ]);
  }

  Future<void> _addSchedule() async {
    final dayCtrl = TextEditingController();
    final openCtrl = TextEditingController(text: '08:00');
    final closeCtrl = TextEditingController(text: '22:00');
    final result = await showDialog<bool>(context: context, builder: (_) => AlertDialog(
      title: Text('Agregar horario'), content: Column(mainAxisSize: MainAxisSize.min, children: [
      TextField(controller: dayCtrl, decoration: InputDecoration(labelText: 'Día (0=Dom, 1=Lun...)'), keyboardType: TextInputType.number),
      TextField(controller: openCtrl, decoration: InputDecoration(labelText: 'Apertura (HH:MM)')),
      TextField(controller: closeCtrl, decoration: InputDecoration(labelText: 'Cierre (HH:MM)')),
    ]), actions: [
      TextButton(onPressed: () => Get.back(), child: Text('Cancelar')),
      TextButton(onPressed: () => Get.back(result: true), child: Text('Guardar')),
    ]));
    if (result == true && _isEditing) {
      try {
        final c = Get.find<SellerController>();
        await c.sellerRepo.createSchedule({
          'store_id': widget.store!['id'].toString(),
          'day': dayCtrl.text,
          'open_time': openCtrl.text,
          'close_time': closeCtrl.text,
        });
        _loadSchedules();
      } catch (_) {}
    }
  }

  Future<void> _deleteScheduleSlot(dynamic id) async {
    if (id == null || !_isEditing) return;
    try {
      final c = Get.find<SellerController>();
      await c.sellerRepo.deleteSchedule(id as int);
      _loadSchedules();
    } catch (_) {}
  }

  Widget _field(String label, TextEditingController ctrl, IconData icon, {bool required = false, int maxLines = 1, String? hint, TextInputType keyboard = TextInputType.text, FocusNode? focusNode}) => TextFormField(
    controller: ctrl, keyboardType: keyboard, maxLines: maxLines, focusNode: focusNode,
    decoration: InputDecoration(labelText: label, hintText: hint, prefixIcon: Icon(icon, size: 20, color: MyColor.primaryColor),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide(color: Colors.grey.shade200)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide(color: MyColor.primaryColor, width: 1.5)),
      filled: true, fillColor: MyColor.colorWhite),
    validator: required ? (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null : null,
  );
}

class _PlacePrediction { final String description, placeId; _PlacePrediction({required this.description, required this.placeId}); }
