import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/model/delivery/shopping_models.dart';
import 'package:liztogo/data/repo/delivery/shopping_repo.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'package:liztogo/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';
import 'package:liztogo/presentation/screens/delivery/favor_tracking_screen.dart';

class ShoppingListScreen extends StatefulWidget {
  final int favorId;
  final String storeName;
  const ShoppingListScreen({super.key, required this.favorId, required this.storeName});

  @override
  State<ShoppingListScreen> createState() => _ShoppingListScreenState();
}

class _ShoppingListScreenState extends State<ShoppingListScreen> {
  late final ShoppingRepo _repo;
  List<ShoppingListItem> _items = [];
  ShoppingSummary? _summary;
  bool _isLoading = true;
  bool _isSubmitting = false;

  final _nameCtrl = TextEditingController();
  final _qtyCtrl = TextEditingController(text: '1');
  final _priceCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _repo = ShoppingRepo(apiClient: Get.find<ApiClient>());
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() => _isLoading = true);
    try {
      final res = await _repo.getShoppingList(widget.favorId);
      if (res.statusCode == 200 && res.responseJson != null) {
        final data = res.responseJson['data'];
        if (data != null) {
          _items = (data['items'] as List? ?? []).map((e) => ShoppingListItem.fromJson(e)).toList();
          if (data['summary'] != null) {
            _summary = ShoppingSummary.fromJson(data['summary']);
          }
        }
      }
    } catch (e) {
      CustomSnackBar.error(errorList: ['Error al cargar la lista']);
    }
    setState(() => _isLoading = false);
  }

  Future<void> _addItem() async {
    if (_nameCtrl.text.trim().isEmpty) {
      CustomSnackBar.error(errorList: ['Ingresa el nombre del producto']);
      return;
    }

    final data = {
      'name': _nameCtrl.text.trim(),
      'quantity': int.tryParse(_qtyCtrl.text) ?? 1,
      if (_priceCtrl.text.isNotEmpty) 'unit_price': double.tryParse(_priceCtrl.text),
      if (_notesCtrl.text.isNotEmpty) 'notes': _notesCtrl.text.trim(),
    };

    try {
      final res = await _repo.addItem(widget.favorId, data);
      if (res.statusCode == 200 && res.responseJson?['status'] == 'success') {
        _nameCtrl.clear();
        _qtyCtrl.text = '1';
        _priceCtrl.clear();
        _notesCtrl.clear();
        await _loadData();
      } else {
        CustomSnackBar.error(errorList: ['Error al agregar item']);
      }
    } catch (e) {
      CustomSnackBar.error(errorList: ['Error de conexion']);
    }
  }

  Future<void> _removeItem(int itemId) async {
    try {
      final res = await _repo.removeItem(widget.favorId, itemId);
      if (res.statusCode == 200) {
        await _loadData();
      }
    } catch (e) {
      CustomSnackBar.error(errorList: ['Error al eliminar']);
    }
  }

  Future<void> _uploadImage(int itemId) async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.gallery, maxWidth: 800);
    if (picked == null) return;

    try {
      final res = await _repo.uploadItemImage(widget.favorId, itemId, File(picked.path));
      if (res.statusCode == 200) {
        await _loadData();
      }
    } catch (e) {
      CustomSnackBar.error(errorList: ['Error al subir imagen']);
    }
  }

  Future<void> _submitShopping() async {
    if (_items.isEmpty) {
      CustomSnackBar.error(errorList: ['Agrega al menos un producto']);
      return;
    }
    setState(() => _isSubmitting = true);
    try {
      final res = await _repo.submitShopping(widget.favorId);
      if (res.statusCode == 200 && res.responseJson?['status'] == 'success') {
        CustomSnackBar.success(successList: ['Solicitud enviada a repartidores']);
        Get.off(() => FavorTrackingScreen(favorId: widget.favorId));
      } else {
        CustomSnackBar.error(errorList: ['Error al enviar']);
      }
    } catch (e) {
      CustomSnackBar.error(errorList: ['Error de conexion']);
    }
    setState(() => _isSubmitting = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.getScreenBgColor(),
      body: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : ListView(
                    padding: EdgeInsets.all(Dimensions.space16),
                    children: [
                      _buildStoreCard(),
                      SizedBox(height: Dimensions.space16),
                      _buildItemForm(),
                      SizedBox(height: Dimensions.space16),
                      _buildItemList(),
                      SizedBox(height: Dimensions.space16),
                      _buildCoordinationCard(),
                      SizedBox(height: Dimensions.space20),
                      RoundedButton(
                        text: 'Enviar solicitud',
                        isLoading: _isSubmitting,
                        press: _submitShopping,
                      ),
                      SizedBox(height: Dimensions.space40),
                    ],
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: EdgeInsets.only(
        top: MediaQuery.of(context).padding.top + Dimensions.space12,
        bottom: Dimensions.space20,
      ),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [MyColor.primaryColor, MyColor.primaryColor.withValues(alpha: 0.85)],
        ),
      ),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded, color: MyColor.colorWhite, size: 20),
            onPressed: () => Get.back(),
          ),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Lista de compras', style: boldExtraLarge.copyWith(color: MyColor.colorWhite, fontSize: 20)),
                SizedBox(height: 2),
                Text(widget.storeName, style: regularDefault.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.8), fontSize: 13)),
              ],
            ),
          ),
          Container(
            margin: EdgeInsets.only(right: Dimensions.space12),
            padding: EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space6),
            decoration: BoxDecoration(
              color: MyColor.colorWhite.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text('${_items.length} items', style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: 12)),
          ),
        ],
      ),
    );
  }

  Widget _buildStoreCard() {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: MyColor.primaryColor.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(Icons.store_rounded, color: MyColor.primaryColor, size: 24),
          ),
          SizedBox(width: Dimensions.space12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.storeName, style: boldLarge),
                if (_summary != null)
                  Text(
                    '${_summary!.totalItems ?? 0} productos - ${_summary!.progressPercent.toStringAsFixed(0)}% completado',
                    style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildItemForm() {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: MyColor.primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                ),
                child: Icon(Icons.add_shopping_cart_rounded, color: MyColor.primaryColor, size: 20),
              ),
              SizedBox(width: Dimensions.space10),
              Text('Agregar producto', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
            ],
          ),
          SizedBox(height: Dimensions.space14),
          TextFormField(
            controller: _nameCtrl,
            decoration: _inputDeco('Nombre del producto', icon: Icons.inventory_2_outlined),
          ),
          SizedBox(height: Dimensions.space10),
          Row(
            children: [
              Expanded(
                flex: 2,
                child: TextFormField(
                  controller: _qtyCtrl,
                  decoration: _inputDeco('Cant.', icon: Icons.numbers),
                  keyboardType: TextInputType.number,
                ),
              ),
              SizedBox(width: Dimensions.space10),
              Expanded(
                flex: 3,
                child: TextFormField(
                  controller: _priceCtrl,
                  decoration: _inputDeco('Precio unit. (S/)', icon: Icons.attach_money_rounded),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                ),
              ),
            ],
          ),
          SizedBox(height: Dimensions.space10),
          TextFormField(
            controller: _notesCtrl,
            decoration: _inputDeco('Notas (ej: salsa BBQ, sin pepinillos)', icon: Icons.notes_rounded),
          ),
          SizedBox(height: Dimensions.space12),
          SizedBox(
            width: double.infinity,
            child: RoundedButton(
              text: 'Agregar a la lista',
              press: _addItem,
              isOutlined: true,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildItemList() {
    if (_items.isEmpty) {
      return Container(
        padding: EdgeInsets.all(Dimensions.space32),
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        ),
        child: Center(
          child: Column(
            children: [
              Icon(Icons.shopping_cart_outlined, size: 48, color: Colors.grey.shade300),
              SizedBox(height: Dimensions.space12),
              Text('Agrega productos a tu lista', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
            ],
          ),
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space16, Dimensions.space16, 0),
            child: Text('Tu lista', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
          ),
          ..._items.map((item) => _buildItemTile(item)),
        ],
      ),
    );
  }

  Widget _buildItemTile(ShoppingListItem item) {
    final statusColor = switch (item.status) {
      'found' => MyColor.greenSuccessColor,
      'not_found' => MyColor.redCancelTextColor,
      'substituted' => Colors.orange,
      _ => MyColor.bodyMutedTextColor,
    };

    return Container(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: Dimensions.space12),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
      ),
      child: Row(
        children: [
          Container(
            width: 36,
            height: 36,
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              item.isFound
                  ? Icons.check_rounded
                  : item.isNotFound
                      ? Icons.close_rounded
                      : Icons.shopping_bag_outlined,
              color: statusColor,
              size: 20,
            ),
          ),
          SizedBox(width: Dimensions.space12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item.name ?? '', style: boldDefault),
                if (item.notes != null && item.notes!.isNotEmpty) Text(item.notes!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 1),
                if (item.substituteName != null)
                  Text(
                    'Sustituto: ${item.substituteName}',
                    style: regularSmall.copyWith(color: Colors.orange, fontWeight: FontWeight.w500),
                  ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text('x${item.quantity}', style: boldDefault.copyWith(fontSize: 13)),
              if (item.unitPrice != null) Text('S/ ${item.unitPrice!.toStringAsFixed(2)}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
            ],
          ),
          SizedBox(width: Dimensions.space8),
          PopupMenuButton<String>(
            onSelected: (v) {
              if (v == 'delete') _removeItem(item.id!);
              if (v == 'image') _uploadImage(item.id!);
            },
            itemBuilder: (_) => [
              PopupMenuItem(value: 'image', child: Row(children: [Icon(Icons.camera_alt_outlined, size: 18), SizedBox(width: 8), Text('Foto')])),
              if (item.isPending) PopupMenuItem(value: 'delete', child: Row(children: [Icon(Icons.delete_outline, size: 18, color: MyColor.redCancelTextColor), SizedBox(width: 8), Text('Eliminar', style: TextStyle(color: MyColor.redCancelTextColor))])),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildCoordinationCard() {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: MyColor.primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                ),
                child: Icon(Icons.forum_outlined, color: MyColor.primaryColor, size: 20),
              ),
              SizedBox(width: Dimensions.space10),
              Text('Coordinación de compra', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
            ],
          ),
          SizedBox(height: Dimensions.space12),
          Text('Cuando un repartidor acepte tu solicitud, podrás conversar con él, recibir fotos y aprobar precios o sustituciones antes de la compra.', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor, height: 1.35)),
        ],
      ),
    );
  }

  InputDecoration _inputDeco(String label, {IconData? icon}) {
    return InputDecoration(
      labelText: label,
      hintStyle: regularDefault.copyWith(color: MyColor.bodyMutedTextColor.withValues(alpha: 0.6), fontSize: 13),
      prefixIcon: icon != null ? Icon(icon, size: 20, color: MyColor.primaryColor) : null,
      filled: true,
      fillColor: MyColor.getScreenBgColor(),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
        borderSide: BorderSide(color: Colors.grey.shade200),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
        borderSide: BorderSide(color: MyColor.primaryColor, width: 1.5),
      ),
      contentPadding: EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space14),
    );
  }
}
