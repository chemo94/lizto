import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/repo/seller/seller_repo.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:lizto_store/presentation/components/buttons/rounded_button.dart';

class SellerStoriesScreen extends StatefulWidget {
  const SellerStoriesScreen({super.key});

  @override
  State<SellerStoriesScreen> createState() => _SellerStoriesScreenState();
}

class _SellerStoriesScreenState extends State<SellerStoriesScreen> {
  late SellerRepo _repo;
  List<dynamic> _stories = [];
  bool _loading = true;
  String _mediaPath = '';

  @override
  void initState() {
    super.initState();
    _repo = SellerRepo(prefs: Get.find<SharedPreferences>());
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final r = await _repo.getStories();
    if (r.statusCode == 200 && r.responseJson['status'] == 'success') {
      setState(() {
        _stories = r.responseJson['data']['stories'] ?? [];
        _mediaPath = r.responseJson['data']['media_path'] ?? '';
      });
    }
    setState(() => _loading = false);
  }

  Future<void> _pause(int id) async {
    await _repo.pauseStory(id);
    _load();
  }

  Future<void> _resume(int id) async {
    await _repo.resumeStory(id);
    _load();
  }

  Future<void> _delete(int id) async {
    await _repo.deleteStory(id);
    _load();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: MyColor.cardBgColor,
    appBar: AppBar(
      backgroundColor: MyColor.primaryColor,
      title: Text('Historias', style: boldLarge.copyWith(color: MyColor.colorWhite)),
      centerTitle: true,
    ),
    floatingActionButton: FloatingActionButton(
      backgroundColor: MyColor.primaryColor,
      onPressed: () => Get.to(() => _CreateStoryScreen(repo: _repo))?.then((_) => _load()),
      child: const Icon(Icons.add, color: MyColor.colorWhite),
    ),
    body: _loading
        ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
        : _stories.isEmpty
            ? Center(child: Text('No tienes historias', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
            : RefreshIndicator(
                onRefresh: _load,
                child: ListView.separated(
                  padding: EdgeInsets.all(16),
                  itemCount: _stories.length,
                  separatorBuilder: (_, __) => SizedBox(height: 12),
                  itemBuilder: (_, i) {
                    final s = _stories[i];
                    final impressions = s['total_impressions'] ?? 0;
                    final consumed = s['consumed_impressions'] ?? 0;
                    final progress = impressions > 0 ? consumed / impressions : 0.0;
                    final status = s['status']?.toString() ?? '';

                    return Container(
                      padding: EdgeInsets.all(14),
                      decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(16), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8)]),
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Row(children: [
                          _statusBadge(status),
                          Spacer(),
                          PopupMenuButton<String>(
                            onSelected: (v) {
                              final id = s['id'] as int;
                              if (v == 'pause') _pause(id);
                              else if (v == 'resume') _resume(id);
                              else if (v == 'delete') _delete(id);
                            },
                            itemBuilder: (_) => [
                              if (status == 'active') PopupMenuItem(value: 'pause', child: Text('Pausar')),
                              if (status == 'paused') PopupMenuItem(value: 'resume', child: Text('Reanudar')),
                              if (status != 'active') PopupMenuItem(value: 'delete', child: Text('Eliminar', style: TextStyle(color: Colors.red))),
                            ],
                          ),
                        ]),
                        SizedBox(height: 8),
                        if (s['caption'] != null) Text(s['caption'], style: boldDefault.copyWith(fontSize: 14)),
                        SizedBox(height: 8),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: LinearProgressIndicator(value: progress, backgroundColor: Colors.grey.shade200, color: MyColor.primaryColor, minHeight: 6),
                        ),
                        SizedBox(height: 6),
                        Row(children: [
                          Text('$consumed / $impressions vistas', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                          Spacer(),
                          Text('S/ ${(s['budget'] ?? 0).toStringAsFixed(2)}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
                        ]),
                      ]),
                    );
                  },
                ),
              ),
  );

  Widget _statusBadge(String status) {
    final colors = {'active': const Color(0xFF10B981), 'paused': const Color(0xFFF59E0B), 'completed': const Color(0xFF6B7280), 'pending': const Color(0xFF3B82F6)};
    final labels = {'active': 'Activa', 'paused': 'Pausada', 'completed': 'Completada', 'pending': 'Pendiente'};
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: (colors[status] ?? Colors.grey).withOpacity(0.1), borderRadius: BorderRadius.circular(8)),
      child: Text(labels[status] ?? status, style: boldDefault.copyWith(color: colors[status], fontSize: 11)),
    );
  }
}

class _CreateStoryScreen extends StatefulWidget {
  final SellerRepo repo;
  const _CreateStoryScreen({required this.repo});

  @override
  State<_CreateStoryScreen> createState() => _CreateStoryScreenState();
}

class _CreateStoryScreenState extends State<_CreateStoryScreen> {
  final _captionCtrl = TextEditingController();
  final _budgetCtrl = TextEditingController();
  final _hoursCtrl = TextEditingController();
  int? _storeId;
  File? _media;
  bool _saving = false;
  List<dynamic> _stores = [];
  bool _loadingStores = true;

  @override
  void initState() {
    super.initState();
    _loadStores();
  }

  Future<void> _loadStores() async {
    final r = await widget.repo.getStores();
    if (r.statusCode == 200 && r.responseJson['status'] == 'success') {
      setState(() => _stores = r.responseJson['data']['stores'] ?? []);
    }
    setState(() => _loadingStores = false);
  }

  Future<void> _pickMedia() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.gallery, imageQuality: 85);
    if (picked != null) setState(() => _media = File(picked.path));
  }

  Future<void> _save() async {
    if (_storeId == null) {
      Get.snackbar('Error', 'Selecciona una tienda', backgroundColor: Colors.red, colorText: MyColor.colorWhite);
      return;
    }
    if (_media == null) {
      Get.snackbar('Error', 'Selecciona una imagen para la historia', backgroundColor: Colors.red, colorText: MyColor.colorWhite);
      return;
    }
    final budgetStr = _budgetCtrl.text.trim();
    if (budgetStr.isEmpty) {
      Get.snackbar('Error', 'Ingresa un presupuesto', backgroundColor: Colors.red, colorText: MyColor.colorWhite);
      return;
    }
    final budget = double.tryParse(budgetStr);
    if (budget == null || budget < 0.5) {
      Get.snackbar('Error', 'El presupuesto mínimo es S/ 0.50', backgroundColor: Colors.red, colorText: MyColor.colorWhite);
      return;
    }
    final hoursStr = _hoursCtrl.text.trim();
    if (hoursStr.isEmpty) {
      Get.snackbar('Error', 'Ingresa la duración en horas', backgroundColor: Colors.red, colorText: MyColor.colorWhite);
      return;
    }
    final hours = int.tryParse(hoursStr);
    if (hours == null || hours < 1) {
      Get.snackbar('Error', 'La duración debe ser de al menos 1 hora', backgroundColor: Colors.red, colorText: MyColor.colorWhite);
      return;
    }

    setState(() => _saving = true);
    final data = {
      'store_id': _storeId.toString(),
      'media_type': 'image',
      if (_captionCtrl.text.isNotEmpty) 'caption': _captionCtrl.text,
      'budget': budgetStr,
      'hours': hoursStr,
    };
    final r = await widget.repo.createStory(_storeId!, data, media: _media);
    setState(() => _saving = false);
    if (r.statusCode == 200 && r.responseJson['status'] == 'success') {
      Get.back();
      Get.snackbar('Creada', 'Historia creada exitosamente', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
    } else {
      String errMsg = 'No se pudo crear la historia';
      if (r.responseJson != null && r.responseJson['message'] != null) {
        if (r.responseJson['message'] is List) {
          errMsg = (r.responseJson['message'] as List).join('\n');
        } else {
          errMsg = r.responseJson['message'].toString();
        }
      } else if (r.message.isNotEmpty) {
        errMsg = r.message;
      }
      Get.snackbar('Error', errMsg, backgroundColor: Colors.red, colorText: MyColor.colorWhite);
    }
  }

  @override
  void dispose() {
    _captionCtrl.dispose(); _budgetCtrl.dispose(); _hoursCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: MyColor.cardBgColor,
    appBar: AppBar(backgroundColor: MyColor.primaryColor, title: Text('Nueva Historia', style: boldLarge.copyWith(color: MyColor.colorWhite)), centerTitle: true),
    body: ListView(padding: EdgeInsets.all(16), children: [
      if (_loadingStores) Center(child: CircularProgressIndicator()) else ...[
        DropdownButtonFormField<int>(
          value: _storeId,
          decoration: _deco('Seleccionar tienda'),
          items: _stores.map<DropdownMenuItem<int>>((s) => DropdownMenuItem(value: s['id'] as int, child: Text(s['name']?.toString() ?? ''))).toList(),
          onChanged: (v) => setState(() => _storeId = v),
        ),
      ],
      SizedBox(height: 14),
      GestureDetector(
        onTap: _pickMedia,
        child: Container(
          height: 180,
          decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(16), border: Border.all(color: MyColor.primaryColor.withOpacity(0.2))),
          child: _media != null ? ClipRRect(borderRadius: BorderRadius.circular(15), child: Image.file(_media!, fit: BoxFit.cover, width: double.infinity)) : Column(mainAxisAlignment: MainAxisAlignment.center, children: [Icon(Icons.add_a_photo, size: 40, color: MyColor.primaryColor.withOpacity(0.4)), SizedBox(height: 8), Text('Tocar para seleccionar imagen', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor))]),
        ),
      ),
      SizedBox(height: 14),
      TextFormField(controller: _captionCtrl, decoration: _deco('Texto promocional (opcional)'), maxLines: 2),
      SizedBox(height: 14),
      TextFormField(controller: _budgetCtrl, decoration: _deco('Presupuesto (S/)'), keyboardType: TextInputType.number),
      SizedBox(height: 14),
      TextFormField(controller: _hoursCtrl, decoration: _deco('Duración (horas)'), keyboardType: TextInputType.number),
      SizedBox(height: 24),
      RoundedButton(text: 'Publicar historia', isLoading: _saving, press: _save, isOutlined: false),
    ]),
  );

  InputDecoration _deco(String label) => InputDecoration(
    labelText: label, border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)), filled: true, fillColor: MyColor.colorWhite,
  );
}
