import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/model/seller/panel_models.dart';
import 'package:lizto_store/presentation/screens/seller/subscription_guard.dart';

class MozoOrderScreen extends StatefulWidget {
  final PanelTableModel? table;
  const MozoOrderScreen({super.key, this.table});

  @override
  State<MozoOrderScreen> createState() => _MozoOrderScreenState();
}

class _MozoOrderScreenState extends State<MozoOrderScreen> {
  late SellerPanelController panelCtrl;
  late SellerController sellerCtrl;

  List<PanelProductModel> _products = [];
  List<Map<String, dynamic>> _categories = [];
  int? _selectedCategoryId;
  bool _loadingProducts = false;
  bool _sendingOrder = false;

  final List<MozoCartItem> _cart = [];
  MozoOrderModel? _activeOrder;
  bool _loadingActiveOrder = false;

  String _selectedOrderType = 'dine_in';
  PanelTableModel? _selectedTable;

  final TextEditingController _kitchenNotesCtrl = TextEditingController();
  final TextEditingController _customerNameCtrl = TextEditingController();
  final TextEditingController _customerPhoneCtrl = TextEditingController();
  final TextEditingController _deliveryAddressCtrl = TextEditingController();
  final TextEditingController _searchCtrl = TextEditingController();
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    panelCtrl = Get.find<SellerPanelController>();
    sellerCtrl = Get.find<SellerController>();
    _selectedTable = widget.table;
    _selectedOrderType = widget.table != null ? 'dine_in' : 'takeaway';
    WidgetsBinding.instance.addPostFrameCallback((_) => _init());
  }

  Future<void> _init() async {
    await _loadProducts();
    if (_selectedTable != null) {
      await _loadActiveOrder();
    }
  }

  Future<void> _loadProducts() async {
    setState(() => _loadingProducts = true);
    try {
      ResponseModel r = await panelCtrl.repo.products();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          _products = (json['data']['products'] as List?)
                  ?.map((x) => PanelProductModel.fromJson(x))
                  .toList() ?? [];
          _categories = (json['data']['categories'] as List?)
                  ?.map((x) => {'id': x['id'], 'name': x['name']})
                  .toList() ?? [];
        }
      }
    } catch (e) {
      print('Error loading products: $e');
    }
    setState(() => _loadingProducts = false);
  }

  Future<void> _loadActiveOrder() async {
    if (_selectedTable?.id == null) return;
    setState(() => _loadingActiveOrder = true);
    try {
      ResponseModel r = await panelCtrl.repo.getActiveTableOrder(_selectedTable!.id!);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          var orderData = json['data']['order'];
          if (orderData != null) {
            _activeOrder = MozoOrderModel.fromJson(orderData);
          } else {
            _activeOrder = null;
          }
        }
      }
    } catch (e) {
      print('Error loading active order: $e');
    }
    setState(() => _loadingActiveOrder = false);
  }

  List<PanelProductModel> get _filteredProducts {
    var list = _products;
    if (_selectedCategoryId != null) {
      list = list.where((p) => p.categoryId == _selectedCategoryId).toList();
    }
    if (_searchQuery.isNotEmpty) {
      final q = _searchQuery.toLowerCase();
      list = list.where((p) =>
        (p.name?.toLowerCase().contains(q) ?? false) ||
        (p.barcode?.contains(q) ?? false)
      ).toList();
    }
    return list;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.screenBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          children: [
            Text(
              _selectedOrderType == 'dine_in'
                  ? (_selectedTable != null ? 'Mesa: ${_selectedTable!.name}' : 'Mesa: Sin Seleccionar')
                  : _selectedOrderType == 'takeaway'
                      ? 'Para Llevar'
                      : _selectedOrderType == 'lizto_delivery'
                          ? 'Delivery (LIZTO)'
                          : _selectedOrderType == 'courtesy'
                              ? 'Cortesía'
                              : _selectedOrderType == 'daz'
                                  ? 'DAZ DAZ'
                                  : 'LLAMA FOOD',
              style: boldLarge.copyWith(color: Colors.white, fontSize: 16),
            ),
            if (_selectedOrderType == 'dine_in' && _selectedTable != null)
              Text(
                _selectedTable!.isOccupied ? 'Ocupada' : 'Libre',
                style: regularSmall.copyWith(
                  color: _selectedTable!.isOccupied
                      ? const Color(0xFFFCD34D)
                      : const Color(0xFF6EE7B7),
                  fontSize: 11,
                ),
              ),
          ],
        ),
        centerTitle: true,
        actions: [
          if (_activeOrder != null)
            Container(
              margin: const EdgeInsets.only(right: 8),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.receipt_rounded, size: 14, color: Colors.white),
                  const SizedBox(width: 4),
                  Text(
                    _activeOrder!.orderNo ?? '',
                    style: regularSmall.copyWith(color: Colors.white, fontSize: 11),
                  ),
                ],
              ),
            ),
        ],
      ),
      body: Column(
        children: [
          _buildChannelSelectorBar(),
          _buildSearchBar(),
          _buildCategoryTabs(),
          Expanded(child: _buildProductGrid()),
          _buildCartSection(),
        ],
      ),
    );
  }

  Widget _buildSearchBar() {
    return Container(
      padding: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space10, Dimensions.space16, 0),
      child: TextField(
        controller: _searchCtrl,
        onChanged: (v) => setState(() => _searchQuery = v),
        decoration: InputDecoration(
          hintText: 'Buscar producto o escanear código...',
          hintStyle: regularDefault.copyWith(color: MyColor.neutral500),
          prefixIcon: Icon(Icons.search_rounded, color: MyColor.neutral500, size: 22),
          suffixIcon: _searchQuery.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.close_rounded, size: 20),
                  onPressed: () {
                    _searchCtrl.clear();
                    setState(() => _searchQuery = '');
                  },
                )
              : null,
          filled: true,
          fillColor: MyColor.colorWhite,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
        ),
      ),
    );
  }

  Widget _buildCategoryTabs() {
    return Container(
      height: 44,
      margin: EdgeInsets.only(top: Dimensions.space10),
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: Dimensions.space16),
        children: [
          _CategoryTab(
            label: 'Todos',
            isSelected: _selectedCategoryId == null,
            onTap: () => setState(() => _selectedCategoryId = null),
          ),
          ..._categories.map((cat) => _CategoryTab(
                label: cat['name'] ?? '',
                isSelected: _selectedCategoryId == cat['id'],
                onTap: () => setState(() => _selectedCategoryId = cat['id']),
              )),
        ],
      ),
    );
  }

  Widget _buildProductGrid() {
    if (_loadingProducts) {
      return const Center(child: CircularProgressIndicator(color: MyColor.primaryColor));
    }

    final products = _filteredProducts;

    if (products.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.inventory_2_rounded, size: 48, color: MyColor.neutral300),
            const SizedBox(height: 12),
            Text('Sin productos', style: boldDefault.copyWith(color: MyColor.bodyMutedTextColor)),
            const SizedBox(height: 4),
            Text('No se encontraron productos', style: regularSmall.copyWith(color: MyColor.neutral500)),
          ],
        ),
      );
    }

    return GridView.builder(
      padding: EdgeInsets.all(Dimensions.space16),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        crossAxisSpacing: 8,
        mainAxisSpacing: 8,
        childAspectRatio: 0.78,
      ),
      itemCount: products.length,
      itemBuilder: (context, index) => _buildProductCard(products[index]),
    );
  }

  Widget _buildProductCard(PanelProductModel product) {
    final hasVariations = product.variations.isNotEmpty;
    final hasAddons = product.addons.isNotEmpty;
    final needsModal = hasVariations || hasAddons;

    return GestureDetector(
      onTap: () {
        if (needsModal) {
          _showProductModal(product);
        } else {
          _addToCart(product);
        }
      },
      child: Container(
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
          boxShadow: [
            BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2)),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: Container(
                decoration: BoxDecoration(
                  color: MyColor.neutral100,
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(Dimensions.largeRadius)),
                ),
                child: product.image != null && product.image!.isNotEmpty
                    ? ClipRRect(
                        borderRadius: const BorderRadius.vertical(top: Radius.circular(Dimensions.largeRadius)),
                        child: Image.network(
                          product.image!,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => _buildProductIcon(),
                        ),
                      )
                    : _buildProductIcon(),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(6),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.name ?? '',
                    style: boldSmall.copyWith(fontSize: 11),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Text(
                    'S/ ${(product.price ?? 0).toStringAsFixed(2)}',
                    style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 12),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildProductIcon() {
    return Center(
      child: Icon(Icons.fastfood_rounded, size: 32, color: MyColor.neutral300),
    );
  }

  void _showProductModal(PanelProductModel product) {
    int? selectedVariationId;
    String? selectedVariationName;
    double selectedPrice = product.price ?? 0;
    if (product.variations.isNotEmpty) {
      final firstVar = product.variations.first;
      selectedVariationId = firstVar.id;
      selectedVariationName = firstVar.name;
      selectedPrice = (firstVar.price != null && firstVar.price! > 0) ? firstVar.price! : (product.price ?? 0);
    }
    final Set<int> selectedAddonIds = {};
    final List<String> selectedAddonNames = [];
    double addonsTotal = 0;
    final notesCtrl = TextEditingController();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => Container(
          height: MediaQuery.of(context).size.height * 0.6,
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            children: [
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: MyColor.neutral300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(product.name ?? '', style: boldLarge.copyWith(fontSize: 18)),
                      Text(
                        'S/ ${(product.price ?? 0).toStringAsFixed(2)}',
                        style: boldLarge.copyWith(color: MyColor.primaryColor, fontSize: 20),
                      ),
                      const SizedBox(height: 20),

                      if (product.variations.isNotEmpty) ...[
                        Text('Variaciones', style: boldDefault.copyWith(fontSize: 14)),
                        const SizedBox(height: 8),
                        ...product.variations.map((v) => GestureDetector(
                          onTap: () {
                            setModalState(() {
                              selectedVariationId = v.id;
                              selectedVariationName = v.name;
                              selectedPrice = (v.price != null && v.price! > 0) ? v.price! : (product.price ?? 0);
                            });
                          },
                          child: Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: selectedVariationId == v.id
                                  ? MyColor.primaryColor.withValues(alpha: 0.1)
                                  : MyColor.neutral50,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: selectedVariationId == v.id
                                    ? MyColor.primaryColor
                                    : MyColor.neutral200,
                              ),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  selectedVariationId == v.id
                                      ? Icons.radio_button_checked
                                      : Icons.radio_button_off,
                                  color: selectedVariationId == v.id
                                      ? MyColor.primaryColor
                                      : MyColor.neutral500,
                                  size: 20,
                                ),
                                const SizedBox(width: 10),
                                Expanded(child: Text(v.name ?? '', style: regularDefault)),
                                Builder(builder: (_) {
                                  final varPrice = v.price ?? 0;
                                  final prodPrice = product.price ?? 0;
                                  final diff = varPrice - prodPrice;
                                  String text = 'S/ ${varPrice.toStringAsFixed(2)}';
                                  if (diff.abs() > 0.01) {
                                    text += ' (${diff > 0 ? "+S/ " : "-S/ "}${diff.abs().toStringAsFixed(2)})';
                                  }
                                  return Text(
                                    text,
                                    style: boldDefault.copyWith(color: MyColor.primaryColor),
                                  );
                                }),
                              ],
                            ),
                          ),
                        )),
                        const SizedBox(height: 16),
                      ],

                      if (product.addons.isNotEmpty) ...[
                        Text('Adicionales', style: boldDefault.copyWith(fontSize: 14)),
                        const SizedBox(height: 8),
                        ...product.addons.map((a) => GestureDetector(
                          onTap: () {
                            setModalState(() {
                              if (selectedAddonIds.contains(a.id)) {
                                selectedAddonIds.remove(a.id);
                                selectedAddonNames.remove(a.name);
                                addonsTotal -= a.price ?? 0;
                              } else {
                                selectedAddonIds.add(a.id!);
                                selectedAddonNames.add(a.name ?? '');
                                addonsTotal += a.price ?? 0;
                              }
                            });
                          },
                          child: Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: selectedAddonIds.contains(a.id)
                                  ? MyColor.primaryColor.withValues(alpha: 0.1)
                                  : MyColor.neutral50,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: selectedAddonIds.contains(a.id)
                                    ? MyColor.primaryColor
                                    : MyColor.neutral200,
                              ),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  selectedAddonIds.contains(a.id)
                                      ? Icons.check_box
                                      : Icons.check_box_outline_blank,
                                  color: selectedAddonIds.contains(a.id)
                                      ? MyColor.primaryColor
                                      : MyColor.neutral500,
                                  size: 20,
                                ),
                                const SizedBox(width: 10),
                                Expanded(child: Text(a.name ?? '', style: regularDefault)),
                                Text(
                                  '+ S/ ${(a.price ?? 0).toStringAsFixed(2)}',
                                  style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 13),
                                ),
                              ],
                            ),
                          ),
                        )),
                        const SizedBox(height: 16),
                      ],

                      Text('Notas', style: boldDefault.copyWith(fontSize: 14)),
                      const SizedBox(height: 8),
                      TextField(
                        controller: notesCtrl,
                        maxLines: 2,
                        decoration: InputDecoration(
                          hintText: 'Ej: Sin cebolla, poco cocido...',
                          hintStyle: regularDefault.copyWith(color: MyColor.neutral500),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide(color: MyColor.neutral200),
                          ),
                          contentPadding: const EdgeInsets.all(12),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Container(
                padding: EdgeInsets.fromLTRB(24, 12, 24, MediaQuery.of(context).padding.bottom + 12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  boxShadow: [
                    BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, -2)),
                  ],
                ),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () {
                      final finalPrice = selectedPrice + addonsTotal;
                      final name = selectedVariationName != null
                          ? '${product.name} ($selectedVariationName)'
                          : product.name ?? '';
                      _addToCartFromModal(
                        product: product,
                        name: name,
                        price: finalPrice,
                        variationId: selectedVariationId,
                        variationName: selectedVariationName,
                        addonIds: selectedAddonIds.toList(),
                        addonNames: selectedAddonNames,
                        notes: notesCtrl.text.isNotEmpty ? notesCtrl.text : null,
                      );
                      Navigator.pop(ctx);
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: MyColor.primaryColor,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: Text(
                      'Agregar al pedido  ·  S/ ${((selectedPrice + addonsTotal)).toStringAsFixed(2)}',
                      style: boldDefault.copyWith(color: Colors.white),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _addToCart(PanelProductModel product) {
    setState(() {
      final existingIndex = _cart.indexWhere((item) =>
        item.productId == product.id && item.variationId == null && item.addonIds.isEmpty);

      if (existingIndex >= 0) {
        _cart[existingIndex].quantity++;
      } else {
        _cart.add(MozoCartItem(
          productId: product.id ?? 0,
          name: product.name ?? '',
          price: product.price ?? 0,
        ));
      }
    });
  }

  void _addToCartFromModal({
    required PanelProductModel product,
    required String name,
    required double price,
    int? variationId,
    String? variationName,
    required List<int> addonIds,
    required List<String> addonNames,
    String? notes,
  }) {
    setState(() {
      _cart.add(MozoCartItem(
        productId: product.id ?? 0,
        name: name,
        price: price,
        variationId: variationId,
        variationName: variationName,
        addonIds: addonIds,
        addonNames: addonNames,
        notes: notes,
      ));
    });
  }

  Widget _buildCartSection() {
    if (_cart.isEmpty && _activeOrder == null) return const SizedBox.shrink();

    final cartTotal = _cart.fold<double>(0, (sum, item) => sum + item.total);
    final activeTotal = _activeOrder?.total ?? 0;
    final grandTotal = cartTotal + activeTotal;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 16, offset: const Offset(0, -4)),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          GestureDetector(
            onTap: () => _showCartSheet(),
            child: Container(
              padding: EdgeInsets.fromLTRB(Dimensions.space16, 12, Dimensions.space16, 8),
              child: Row(
                children: [
                  Stack(
                    children: [
                      Icon(Icons.shopping_cart_rounded, size: 28, color: MyColor.primaryColor),
                      if (_cart.isNotEmpty)
                        Positioned(
                          right: 0,
                          top: 0,
                          child: Container(
                            padding: const EdgeInsets.all(4),
                            decoration: const BoxDecoration(
                              color: Colors.red,
                              shape: BoxShape.circle,
                            ),
                            child: Text(
                              '${_cart.length}',
                              style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (_activeOrder != null)
                          Text(
                            'Pedido activo: S/ ${activeTotal.toStringAsFixed(2)}',
                            style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                          ),
                        Text(
                          _cart.isEmpty ? 'Agregar productos' : '${_cart.length} producto(s) en carrito',
                          style: boldDefault.copyWith(color: MyColor.primaryTextColor),
                        ),
                      ],
                    ),
                  ),
                  Text(
                    'S/ ${grandTotal.toStringAsFixed(2)}',
                    style: boldLarge.copyWith(color: MyColor.primaryColor, fontSize: 18),
                  ),
                  const SizedBox(width: 8),
                  Icon(Icons.keyboard_arrow_up_rounded, color: MyColor.neutral500),
                ],
              ),
            ),
          ),
          if (_cart.isNotEmpty)
            Padding(
              padding: EdgeInsets.fromLTRB(Dimensions.space16, 0, Dimensions.space16, Dimensions.space12),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _sendingOrder ? null : _sendOrder,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF10B981),
                    disabledBackgroundColor: MyColor.neutral300,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: _sendingOrder
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : Text(
                          _activeOrder != null ? 'Agregar a comanda' : 'Enviar comanda',
                          style: boldDefault.copyWith(color: Colors.white, fontSize: 15),
                        ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  void _showCartSheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => DraggableScrollableSheet(
          initialChildSize: 0.6,
          minChildSize: 0.3,
          maxChildSize: 0.9,
          builder: (ctx, scrollCtrl) => Container(
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: Column(
              children: [
                Container(
                  margin: const EdgeInsets.only(top: 12),
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(color: MyColor.neutral300, borderRadius: BorderRadius.circular(2)),
                ),
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Text('Mi Pedido', style: boldLarge.copyWith(fontSize: 18)),
                      const Spacer(),
                      if (_activeOrder != null)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: MyColor.primaryColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            'Activo: ${_activeOrder!.orderNo}',
                            style: regularSmall.copyWith(color: MyColor.primaryColor),
                          ),
                        ),
                    ],
                  ),
                ),
                Expanded(
                  child: ListView(
                    controller: scrollCtrl,
                    padding: EdgeInsets.symmetric(horizontal: Dimensions.space16),
                    children: [
                      if (_activeOrder != null && _activeOrder!.items.isNotEmpty) ...[
                        Text('Pedido actual', style: boldDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                        const SizedBox(height: 8),
                        ..._activeOrder!.items.map((item) => _buildActiveOrderItem(item)),
                        const Divider(height: 24),
                      ],
                      if (_cart.isNotEmpty) ...[
                        Text('Nuevos ítems', style: boldDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                        const SizedBox(height: 8),
                        ..._cart.asMap().entries.map((entry) => _buildCartItem(entry.key, entry.value, setModalState)),
                      ],
                      const SizedBox(height: 16),
                      _buildNotesField(),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildActiveOrderItem(MozoOrderItemModel item) {
    final statusColor = item.status == 'pending'
        ? const Color(0xFFF59E0B)
        : item.status == 'preparing'
            ? const Color(0xFF3B82F6)
            : const Color(0xFF10B981);

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: MyColor.neutral50,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              '${item.quantity}x',
              style: boldSmall.copyWith(color: statusColor, fontSize: 11),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(item.productName ?? '', style: regularDefault),
                if (item.notes != null && item.notes!.isNotEmpty)
                  Text(item.notes!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 1),
              ],
            ),
          ),
          Text(
            'S/ ${(item.totalPrice ?? 0).toStringAsFixed(2)}',
            style: boldDefault.copyWith(color: MyColor.primaryTextColor),
          ),
        ],
      ),
    );
  }

  Widget _buildCartItem(int index, MozoCartItem item, StateSetter setModalState) {
    return Dismissible(
      key: Key('cart_$index'),
      direction: DismissDirection.endToStart,
      background: Container(
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 20),
        decoration: BoxDecoration(
          color: MyColor.redCancelTextColor,
          borderRadius: BorderRadius.circular(12),
        ),
        child: const Icon(Icons.delete_rounded, color: Colors.white),
      ),
      onDismissed: (_) {
        setState(() => _cart.removeAt(index));
        setModalState(() {});
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: item.isCourtesy ? const Color(0xFFF3E8FF) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: item.isCourtesy
                ? const Color(0xFF9333EA).withValues(alpha: 0.3)
                : item.isTakeaway
                    ? const Color(0xFFF97316).withValues(alpha: 0.3)
                    : MyColor.neutral200,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(item.name, style: boldDefault.copyWith(fontSize: 13)),
                          ),
                          if (item.isCourtesy)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFF9333EA).withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.card_giftcard_rounded, size: 12, color: Color(0xFF9333EA)),
                                  const SizedBox(width: 3),
                                  Text('Cortesía', style: boldSmall.copyWith(color: const Color(0xFF9333EA), fontSize: 10)),
                                ],
                              ),
                            ),
                          if (item.isTakeaway && !item.isCourtesy)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF97316).withValues(alpha: 0.15),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.shopping_bag_rounded, size: 12, color: Color(0xFFF97316)),
                                  const SizedBox(width: 3),
                                  Text('Llevar', style: boldSmall.copyWith(color: const Color(0xFFF97316), fontSize: 10)),
                                ],
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        item.isCourtesy
                            ? 'S/ 0.00 (Cortesía)'
                            : 'S/ ${item.price.toStringAsFixed(2)} c/u',
                        style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                _QuantityButton(
                  quantity: item.quantity,
                  onDecrease: item.quantity > 1
                      ? () {
                          setState(() => item.quantity--);
                          setModalState(() {});
                        }
                      : null,
                  onIncrease: () {
                    setState(() => item.quantity++);
                    setModalState(() {});
                  },
                ),
                const SizedBox(width: 10),
                Text(
                  'S/ ${item.total.toStringAsFixed(2)}',
                  style: boldDefault.copyWith(
                    color: item.isCourtesy ? const Color(0xFF9333EA) : MyColor.primaryColor,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                _CartToggle(
                  label: 'Llevar',
                  icon: Icons.shopping_bag_rounded,
                  isActive: item.isTakeaway,
                  activeColor: const Color(0xFFF97316),
                  onTap: () {
                    setState(() => item.isTakeaway = !item.isTakeaway);
                    setModalState(() {});
                  },
                ),
                const SizedBox(width: 8),
                _CartToggle(
                  label: 'Cortesía',
                  icon: Icons.card_giftcard_rounded,
                  isActive: item.isCourtesy,
                  activeColor: const Color(0xFF9333EA),
                  onTap: () {
                    setState(() {
                      item.isCourtesy = !item.isCourtesy;
                      if (item.isCourtesy) item.isTakeaway = false;
                    });
                    setModalState(() {});
                  },
                ),
              ],
            ),
            const SizedBox(height: 8),
            TextFormField(
              initialValue: item.notes,
              onChanged: (val) {
                item.notes = val.isNotEmpty ? val : null;
              },
              decoration: InputDecoration(
                hintText: 'Notas de cocina para este ítem...',
                hintStyle: regularSmall.copyWith(color: MyColor.neutral500),
                prefixIcon: const Icon(Icons.edit_note_rounded, size: 20, color: MyColor.neutral500),
                isDense: true,
                contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: BorderSide(color: MyColor.neutral200),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: BorderSide(color: MyColor.neutral200),
                ),
              ),
              style: regularSmall,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildChannelSelectorBar() {
    final channels = [
      {'id': 'dine_in', 'name': 'Mesa', 'icon': Icons.restaurant_rounded, 'color': const Color(0xFF16A34A)},
      {'id': 'takeaway', 'name': 'Para Llevar', 'icon': Icons.shopping_bag_outlined, 'color': const Color(0xFF475569)},
      {'id': 'lizto_delivery', 'name': 'Delivery (LIZTO)', 'icon': Icons.two_wheeler_rounded, 'color': const Color(0xFF4F46E5)},
      {'id': 'courtesy', 'name': 'Cortesía', 'icon': Icons.card_giftcard_rounded, 'color': const Color(0xFF9333EA)},
      {'id': 'daz', 'name': 'DAZ DAZ', 'icon': Icons.local_shipping_rounded, 'color': const Color(0xFFE11D48)},
      {'id': 'llama', 'name': 'LLAMA FOOD', 'icon': Icons.fastfood_rounded, 'color': const Color(0xFFEA580C)},
    ];

    return Container(
      height: 46,
      margin: const EdgeInsets.only(top: 8, bottom: 4),
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: channels.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final item = channels[index];
          final id = item['id'] as String;
          final isSelected = _selectedOrderType == id;
          final color = item['color'] as Color;

          return InkWell(
            onTap: () {
              setState(() {
                _selectedOrderType = id;
                if (id == 'dine_in' && _selectedTable == null) {
                  _showTableSelectionDialog();
                }
              });
            },
            borderRadius: BorderRadius.circular(12),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: isSelected ? color : color.withOpacity(0.08),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: isSelected ? color : color.withOpacity(0.3),
                  width: isSelected ? 1.5 : 1,
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    item['icon'] as IconData,
                    size: 16,
                    color: isSelected ? Colors.white : color,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    item['name'] as String,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: isSelected ? FontWeight.bold : FontWeight.w600,
                      color: isSelected ? Colors.white : color,
                    ),
                  ),
                  if (id == 'dine_in' && _selectedTable != null) ...[
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: isSelected ? Colors.white.withOpacity(0.3) : color.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        _selectedTable!.name ?? '',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: isSelected ? Colors.white : color,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _showTableSelectionDialog() async {
    if (panelCtrl.areas.isEmpty) {
      await panelCtrl.loadTables();
    }
    final tables = panelCtrl.areas.expand((a) => a.tables ?? <PanelTableModel>[]).toList();

    if (!mounted) return;

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Seleccionar Mesa', style: boldLarge.copyWith(fontSize: 18)),
              const SizedBox(height: 12),
              Expanded(
                child: tables.isEmpty
                    ? Center(
                        child: Text(
                          'No hay mesas configuradas',
                          style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                        ),
                      )
                    : GridView.builder(
                        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                          crossAxisCount: 3,
                          crossAxisSpacing: 10,
                          mainAxisSpacing: 10,
                          childAspectRatio: 1.3,
                        ),
                        itemCount: tables.length,
                        itemBuilder: (context, index) {
                          final t = tables[index];
                          final isSel = _selectedTable?.id == t.id;
                          return InkWell(
                            onTap: () {
                              Navigator.pop(ctx);
                              setState(() {
                                _selectedTable = t;
                                _selectedOrderType = 'dine_in';
                              });
                              _loadActiveOrder();
                            },
                            child: Container(
                              decoration: BoxDecoration(
                                color: isSel
                                    ? MyColor.primaryColor
                                    : (t.isOccupied ? const Color(0xFFFEF3C7) : const Color(0xFFDCFCE7)),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: isSel ? MyColor.primaryColor : Colors.grey.shade300),
                              ),
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    Icons.table_restaurant_rounded,
                                    color: isSel
                                        ? Colors.white
                                        : (t.isOccupied ? const Color(0xFFD97706) : const Color(0xFF16A34A)),
                                    size: 20,
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    t.name ?? '',
                                    style: boldDefault.copyWith(
                                      color: isSel ? Colors.white : MyColor.primaryTextColor,
                                      fontSize: 12,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildNotesField() {
    final isDelivery = ['delivery', 'lizto_delivery', 'daz', 'llama'].contains(_selectedOrderType);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (isDelivery) ...[
          Text('Datos del Cliente y Delivery', style: boldDefault.copyWith(fontSize: 14)),
          const SizedBox(height: 8),
          TextField(
            controller: _customerNameCtrl,
            decoration: InputDecoration(
              hintText: 'Nombre del Cliente',
              prefixIcon: const Icon(Icons.person_outline_rounded, size: 20),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            ),
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _customerPhoneCtrl,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              hintText: 'Teléfono de contacto',
              prefixIcon: const Icon(Icons.phone_outlined, size: 20),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            ),
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _deliveryAddressCtrl,
            maxLines: 2,
            decoration: InputDecoration(
              hintText: 'Dirección de Entrega (Ej: Av. Principal 123)...',
              prefixIcon: const Icon(Icons.location_on_outlined, size: 20),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              contentPadding: const EdgeInsets.all(12),
            ),
          ),
          const SizedBox(height: 16),
        ],
        Text('Notas para cocina', style: boldDefault.copyWith(fontSize: 14)),
        const SizedBox(height: 8),
        TextField(
          controller: _kitchenNotesCtrl,
          maxLines: 2,
          decoration: InputDecoration(
            hintText: 'Ej: Mesa cumpleaños, sin picante...',
            hintStyle: regularDefault.copyWith(color: MyColor.neutral500),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: MyColor.neutral200),
            ),
            contentPadding: const EdgeInsets.all(12),
          ),
        ),
      ],
    );
  }

  int _currentStoreId() {
    try {
      if (sellerCtrl.stores.isNotEmpty) {
        final raw = sellerCtrl.stores.first['id'];
        return raw is int ? raw : int.tryParse(raw?.toString() ?? '') ?? 0;
      }
    } catch (_) {}
    return 0;
  }

  Future<void> _sendOrder() async {
    if (_cart.isEmpty) return;

    if (_selectedOrderType == 'dine_in' && _selectedTable == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Por favor selecciona una mesa para consumos en local'),
          backgroundColor: Color(0xFFF59E0B),
          behavior: SnackBarBehavior.floating,
        ),
      );
      _showTableSelectionDialog();
      return;
    }

    final isDelivery = ['delivery', 'lizto_delivery', 'daz', 'llama'].contains(_selectedOrderType);
    if (isDelivery && _deliveryAddressCtrl.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Por favor ingresa la dirección de entrega del cliente'),
          backgroundColor: Color(0xFFF59E0B),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    final ok = await SubscriptionGuard.canProceed(context, storeId: _currentStoreId());
    if (!ok) return;

    setState(() => _sendingOrder = true);

    try {
      final hasCourtesy = _cart.any((item) => item.isCourtesy) || _selectedOrderType == 'courtesy';

      final items = _cart.map((item) => {
        'product_id': item.productId,
        'name': item.name,
        'price': (item.isCourtesy || _selectedOrderType == 'courtesy') ? 0 : item.price,
        'quantity': item.quantity,
        'notes': item.notes,
        'is_takeaway': item.isTakeaway,
      }).toList();

      final data = {
        if (_selectedOrderType == 'dine_in' && _selectedTable != null) 'table_id': _selectedTable!.id,
        'order_type': _selectedOrderType,
        'items': items,
        'kitchen_notes': _kitchenNotesCtrl.text.isNotEmpty ? _kitchenNotesCtrl.text : null,
        'customer_name': _customerNameCtrl.text.isNotEmpty ? _customerNameCtrl.text : null,
        'customer_phone': _customerPhoneCtrl.text.isNotEmpty ? _customerPhoneCtrl.text : null,
        'delivery_address': _deliveryAddressCtrl.text.isNotEmpty ? _deliveryAddressCtrl.text : null,
        if (hasCourtesy) 'courtesy': true,
      };

      ResponseModel r = await panelCtrl.repo.createOrder(data);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == 'success') {
          _cart.clear();
          _kitchenNotesCtrl.clear();
          _customerNameCtrl.clear();
          _customerPhoneCtrl.clear();
          _deliveryAddressCtrl.clear();
          if (_selectedTable != null) {
            await _loadActiveOrder();
          }
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(json['message']?.toString() ?? 'Pedido enviado correctamente'),
                backgroundColor: const Color(0xFF10B981),
                behavior: SnackBarBehavior.floating,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            );
          }
        } else {
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(json['message']?.toString() ?? 'Error al enviar pedido'),
                backgroundColor: MyColor.redCancelTextColor,
                behavior: SnackBarBehavior.floating,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            );
          }
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: MyColor.redCancelTextColor,
            behavior: SnackBarBehavior.floating,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        );
      }
    }

    setState(() => _sendingOrder = false);
  }

  @override
  void dispose() {
    _kitchenNotesCtrl.dispose();
    _customerNameCtrl.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }
}

class _CategoryTab extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  const _CategoryTab({required this.label, required this.isSelected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(right: 8),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? MyColor.primaryColor : MyColor.colorWhite,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: isSelected ? MyColor.primaryColor : MyColor.neutral200),
        ),
        child: Text(
          label,
          style: boldDefault.copyWith(
            color: isSelected ? Colors.white : MyColor.primaryTextColor,
            fontSize: 12,
          ),
        ),
      ),
    );
  }
}

class _QuantityButton extends StatelessWidget {
  final int quantity;
  final VoidCallback? onDecrease;
  final VoidCallback onIncrease;

  const _QuantityButton({required this.quantity, this.onDecrease, required this.onIncrease});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: MyColor.neutral50,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          InkWell(
            onTap: onDecrease,
            child: Container(
              padding: const EdgeInsets.all(6),
              child: Icon(
                Icons.remove_rounded,
                size: 16,
                color: onDecrease != null ? MyColor.primaryColor : MyColor.neutral300,
              ),
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text('$quantity', style: boldDefault.copyWith(fontSize: 13)),
          ),
          InkWell(
            onTap: onIncrease,
            child: Container(
              padding: const EdgeInsets.all(6),
              child: Icon(Icons.add_rounded, size: 16, color: MyColor.primaryColor),
            ),
          ),
        ],
      ),
    );
  }
}

class _CartToggle extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool isActive;
  final Color activeColor;
  final VoidCallback onTap;

  const _CartToggle({
    required this.label,
    required this.icon,
    required this.isActive,
    required this.activeColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: isActive ? activeColor.withValues(alpha: 0.15) : MyColor.neutral50,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: isActive ? activeColor.withValues(alpha: 0.4) : MyColor.neutral200,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 14, color: isActive ? activeColor : MyColor.neutral500),
            const SizedBox(width: 4),
            Text(
              label,
              style: boldSmall.copyWith(
                color: isActive ? activeColor : MyColor.bodyMutedTextColor,
                fontSize: 11,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
