import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/model/delivery/shopping_models.dart';
import 'package:lizto_delivery/data/repo/delivery/shopping_repo.dart';
import 'package:lizto_delivery/data/services/api_client.dart';
import 'package:lizto_delivery/presentation/components/buttons/rounded_button.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';
import 'package:lizto_delivery/presentation/components/snack_bar/show_custom_snackbar.dart';

class ShoppingChecklistScreen extends StatefulWidget {
  final int favorId;
  const ShoppingChecklistScreen({super.key, required this.favorId});

  @override
  State<ShoppingChecklistScreen> createState() => _ShoppingChecklistScreenState();
}

class _ShoppingChecklistScreenState extends State<ShoppingChecklistScreen> {
  late final ShoppingRepo _repo;
  List<ShoppingListItem> _items = [];
  ShoppingBudget? _budget;
  String? _storeName;
  String? _storeAddress;
  bool _isLoading = true;
  bool _isStoreConfirmed = false;
  bool _isUploadingReceipt = false;

  final _priceCtrl = TextEditingController();
  final _totalCtrl = TextEditingController();
  final _subNameCtrl = TextEditingController();
  final _subPriceCtrl = TextEditingController();
  final _subNotesCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _repo = ShoppingRepo(apiClient: Get.find<ApiClient>());
    _loadChecklist();
  }

  Future<void> _loadChecklist() async {
    setState(() => _isLoading = true);
    try {
      final res = await _repo.getChecklist(widget.favorId);
      if (res.statusCode == 200 && res.responseJson != null) {
        final data = res.responseJson['data'];
        if (data != null) {
          _items = (data['items'] as List? ?? []).map((e) => ShoppingListItem.fromJson(e)).toList();
          if (data['budget'] != null) _budget = ShoppingBudget.fromJson(data['budget']);
          _storeName = data['store_name']?.toString();
          _storeAddress = data['store_address']?.toString();
          _isStoreConfirmed = data['shopping_status'] != 'submitted';
        }
      }
    } catch (e) {
      CustomSnackBar.error(errorList: ['Error al cargar checklist']);
    }
    setState(() => _isLoading = false);
  }

  Future<void> _confirmStoreArrival() async {
    try {
      final res = await _repo.storeConfirm(widget.favorId);
      if (res.statusCode == 200) {
        setState(() => _isStoreConfirmed = true);
        CustomSnackBar.success(successList: ['Llegada confirmada']);
      }
    } catch (e) {
      CustomSnackBar.error(errorList: ['Error']);
    }
  }

  Future<void> _confirmItem(ShoppingListItem item) async {
    _priceCtrl.text = item.unitPrice?.toStringAsFixed(2) ?? '';
    final result = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('Confirmar: ${item.name}'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text('Precio real en la tienda:'),
            SizedBox(height: 8),
            TextField(
              controller: _priceCtrl,
              decoration: InputDecoration(prefixText: 'S/ ', border: OutlineInputBorder()),
              keyboardType: TextInputType.numberWithOptions(decimal: true),
            ),
          ],
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: Text('Cancelar')),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, {'price': double.tryParse(_priceCtrl.text)}),
            child: Text('Confirmar'),
          ),
        ],
      ),
    );

    if (result != null) {
      try {
        final res = await _repo.confirmItem(widget.favorId, item.id!, data: {
          if (result['price'] != null) 'actual_price': result['price'],
        });
        if (res.statusCode == 200) {
          await _loadChecklist();
        }
      } catch (e) {
        CustomSnackBar.error(errorList: ['Error']);
      }
    }
  }

  Future<void> _markNotFound(ShoppingListItem item) async {
    try {
      final res = await _repo.notFoundItem(widget.favorId, item.id!);
      if (res.statusCode == 200) {
        await _loadChecklist();
      }
    } catch (e) {
      CustomSnackBar.error(errorList: ['Error']);
    }
  }

  Future<void> _proposeSubstitute(ShoppingListItem item) async {
    _subNameCtrl.clear();
    _subPriceCtrl.clear();
    _subNotesCtrl.clear();

    final result = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('Proponer sustituto para: ${item.name}'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(controller: _subNameCtrl, decoration: InputDecoration(labelText: 'Nombre del sustituto')),
              SizedBox(height: 8),
              TextField(controller: _subPriceCtrl, decoration: InputDecoration(labelText: 'Precio', prefixText: 'S/ '), keyboardType: TextInputType.number),
              SizedBox(height: 8),
              TextField(controller: _subNotesCtrl, decoration: InputDecoration(labelText: 'Notas (opcional)'), maxLines: 2),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text('Cancelar')),
          ElevatedButton(
            onPressed: () {
              if (_subNameCtrl.text.isNotEmpty && _subPriceCtrl.text.isNotEmpty) {
                Navigator.pop(context, true);
              }
            },
            child: Text('Proponer'),
          ),
        ],
      ),
    );

    if (result == true) {
      try {
        final res = await _repo.proposeSubstitute(widget.favorId, item.id!, {
          'substitute_name': _subNameCtrl.text,
          'substitute_price': double.parse(_subPriceCtrl.text),
          'substitute_notes': _subNotesCtrl.text.isNotEmpty ? _subNotesCtrl.text : null,
        });
        if (res.statusCode == 200) {
          await _loadChecklist();
        }
      } catch (e) {
        CustomSnackBar.error(errorList: ['Error']);
      }
    }
  }

  Future<void> _uploadReceipt() async {
    final picker = ImagePicker();
    final productPhoto = await picker.pickImage(source: ImageSource.camera, maxWidth: 1024);
    if (productPhoto == null) return;

    final receiptPhoto = await picker.pickImage(source: ImageSource.camera, maxWidth: 1024);

    _totalCtrl.text = _calculateTotal().toStringAsFixed(2);
    final result = await showDialog<double>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('Total de la compra'),
        content: TextField(
          controller: _totalCtrl,
          decoration: InputDecoration(prefixText: 'S/ ', border: OutlineInputBorder()),
          keyboardType: TextInputType.numberWithOptions(decimal: true),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: Text('Cancelar')),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, double.tryParse(_totalCtrl.text)),
            child: Text('Subir'),
          ),
        ],
      ),
    );

    if (result != null) {
      setState(() => _isUploadingReceipt = true);
      try {
        final res = await _repo.uploadReceipt(
          widget.favorId,
          {'actual_total': result},
          image: File(productPhoto.path),
          receiptImage: receiptPhoto != null ? File(receiptPhoto.path) : null,
        );
        if (res.statusCode == 200) {
          CustomSnackBar.success(successList: ['Comprobante subido. Esperando aprobacion.']);
          Navigator.pop(context);
        }
      } catch (e) {
        CustomSnackBar.error(errorList: ['Error al subir']);
      }
      setState(() => _isUploadingReceipt = false);
    }
  }

  double _calculateTotal() {
    double total = 0;
    for (final item in _items) {
      if (item.status == 'found' && item.unitPrice != null) {
        total += item.unitPrice! * (item.quantity ?? 1);
      } else if (item.status == 'substituted' && item.substitutePrice != null) {
        total += item.substitutePrice! * (item.quantity ?? 1);
      }
    }
    return total;
  }

  @override
  Widget build(BuildContext context) {
    final progress = _items.isEmpty ? 0.0 :
        _items.where((i) => i.status == 'found' || i.status == 'substituted').length / _items.length;

    return Scaffold(
      backgroundColor: MyColor.getScreenBgColor(),
      body: Column(
        children: [
          _buildHeader(progress),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : ListView(
                    padding: EdgeInsets.all(Dimensions.space16),
                    children: [
                      _buildStoreCard(),
                      SizedBox(height: Dimensions.space16),
                      if (!_isStoreConfirmed)
                        RoundedButton(
                          text: 'Llegue a la tienda',
                          press: _confirmStoreArrival,
                        ),
                      if (_isStoreConfirmed) ...[
                        _buildChecklist(),
                        SizedBox(height: Dimensions.space16),
                        _buildTotalSection(),
                        SizedBox(height: Dimensions.space16),
                        RoundedButton(
                          text: 'Subir comprobante y finalizar compra',
                          isLoading: _isUploadingReceipt,
                          press: _uploadReceipt,
                        ),
                      ],
                      SizedBox(height: Dimensions.space40),
                    ],
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(double progress) {
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
      child: Column(
        children: [
          Row(
            children: [
              IconButton(
                icon: const Icon(Icons.arrow_back_ios_rounded, color: MyColor.colorWhite, size: 20),
                onPressed: () => Get.back(),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Comprar en tienda', style: boldExtraLarge.copyWith(color: MyColor.colorWhite, fontSize: 20)),
                    SizedBox(height: 2),
                    Text(
                      '${_items.where((i) => i.status == "found" || i.status == "substituted").length}/${_items.length} productos',
                      style: regularDefault.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.8), fontSize: 13),
                    ),
                  ],
                ),
              ),
            ],
          ),
          SizedBox(height: Dimensions.space12),
          Padding(
            padding: EdgeInsets.symmetric(horizontal: Dimensions.space16),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: progress,
                backgroundColor: Colors.white.withValues(alpha: 0.3),
                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                minHeight: 6,
              ),
            ),
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
            width: 44, height: 44,
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
                Text(_storeName ?? 'Tienda', style: boldLarge),
                if (_storeAddress != null)
                  Text(_storeAddress!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 2),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildChecklist() {
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
            child: Text('Productos', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
          ),
          ..._items.map((item) => _buildItemTile(item)),
        ],
      ),
    );
  }

  Widget _buildItemTile(ShoppingListItem item) {
    final statusColor = switch (item.status) {
      'found'       => MyColor.greenSuccessColor,
      'not_found'   => MyColor.redCancelTextColor,
      'substituted' => Colors.orange,
      _             => MyColor.bodyMutedTextColor,
    };

    final statusIcon = switch (item.status) {
      'found'       => Icons.check_circle_rounded,
      'not_found'   => Icons.cancel_rounded,
      'substituted' => Icons.swap_horiz_rounded,
      _             => Icons.radio_button_unchecked,
    };

    return Container(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: Dimensions.space12),
      decoration: BoxDecoration(
        border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(statusIcon, color: statusColor, size: 24),
              SizedBox(width: Dimensions.space12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item.name ?? '', style: boldDefault),
                    if (item.notes != null && item.notes!.isNotEmpty)
                      Text(item.notes!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                    if (item.substituteName != null)
                      Padding(
                        padding: EdgeInsets.only(top: 4),
                        child: Text(
                          '→ ${item.substituteName} (S/ ${item.substitutePrice?.toStringAsFixed(2) ?? "?"})',
                          style: regularSmall.copyWith(color: Colors.orange, fontWeight: FontWeight.w500),
                        ),
                      ),
                  ],
                ),
              ),
              Text('x${item.quantity}', style: boldDefault.copyWith(fontSize: 13)),
            ],
          ),
          if (item.isPending && _isStoreConfirmed) ...[
            SizedBox(height: Dimensions.space10),
            Row(
              children: [
                Expanded(
                  child: RoundedButton(
                    text: 'Encontrado',
                    press: () => _confirmItem(item),
                    isOutlined: true,
                  ),
                ),
                SizedBox(width: Dimensions.space8),
                Expanded(
                  child: RoundedButton(
                    text: 'No encontrado',
                    press: () => _markNotFound(item),
                    isOutlined: true,
                  ),
                ),
              ],
            ),
          ],
          if (item.isNotFound && _isStoreConfirmed) ...[
            SizedBox(height: Dimensions.space10),
            SizedBox(
              width: double.infinity,
              child: RoundedButton(
                text: 'Proponer sustituto',
                press: () => _proposeSubstitute(item),
                isOutlined: true,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildTotalSection() {
    final total = _calculateTotal();
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.primaryColor.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        border: Border.all(color: MyColor.primaryColor.withValues(alpha: 0.2)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text('Total estimado', style: boldLarge),
          Text(
            'S/ ${total.toStringAsFixed(2)}',
            style: boldExtraLarge.copyWith(color: MyColor.primaryColor, fontSize: 22),
          ),
        ],
      ),
    );
  }
}
