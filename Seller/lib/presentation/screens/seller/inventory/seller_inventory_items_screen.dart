import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_inventory_controller.dart';
import 'package:lizto_store/data/model/seller/inventory_models.dart';
import 'package:lizto_store/data/repo/seller/seller_inventory_repo.dart';

class SellerInventoryItemsScreen extends StatefulWidget {
  final bool isTab;
  const SellerInventoryItemsScreen({super.key, this.isTab = false});

  @override
  State<SellerInventoryItemsScreen> createState() => _SellerInventoryItemsScreenState();
}

class _SellerInventoryItemsScreenState extends State<SellerInventoryItemsScreen> {
  late SellerInventoryController c;
  final TextEditingController _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerInventoryRepo>()) {
      Get.put(SellerInventoryRepo(apiClient: Get.find()));
    }
    if (!Get.isRegistered<SellerInventoryController>()) {
      Get.put(SellerInventoryController(repo: Get.find()));
    }
    c = Get.find<SellerInventoryController>();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      c.loadItems();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerInventoryController>(
      builder: (_) {
        Widget content = Column(
          children: [
            _buildStatsHeader(),
            _buildSearchAndFilters(),
            Expanded(
              child: c.loadingItems
                  ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
                  : c.items.isEmpty
                      ? _buildEmptyState()
                      : RefreshIndicator(
                          onRefresh: () => c.loadItems(),
                          child: ListView.builder(
                            padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: 8),
                            itemCount: c.items.length,
                            itemBuilder: (_, i) => _buildItemCard(c.items[i]),
                          ),
                        ),
            ),
          ],
        );

        if (widget.isTab) {
          return Scaffold(
            backgroundColor: MyColor.screenBgColor,
            body: content,
            floatingActionButton: FloatingActionButton.extended(
              onPressed: () => _showItemForm(),
              backgroundColor: MyColor.primaryColor,
              icon: const Icon(Icons.add_rounded, color: Colors.white),
              label: Text('Nuevo Insumo', style: boldDefault.copyWith(color: Colors.white)),
            ),
          );
        }

        return Scaffold(
          backgroundColor: MyColor.screenBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            elevation: 0,
            title: Text('Insumos & Stock', style: boldLarge.copyWith(color: Colors.white)),
            centerTitle: true,
          ),
          body: content,
          floatingActionButton: FloatingActionButton.extended(
            onPressed: () => _showItemForm(),
            backgroundColor: MyColor.primaryColor,
            icon: const Icon(Icons.add_rounded, color: Colors.white),
            label: Text('Nuevo Insumo', style: boldDefault.copyWith(color: Colors.white)),
          ),
        );
      },
    );
  }

  Widget _buildStatsHeader() {
    return Container(
      margin: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space12, Dimensions.space16, 8),
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: _buildStatItem(
              title: 'Insumos',
              value: '${c.totalItems}',
              icon: Icons.inventory_2_outlined,
              color: const Color(0xFF0284C7),
            ),
          ),
          Container(height: 32, width: 1, color: Colors.grey.shade200),
          Expanded(
            child: _buildStatItem(
              title: 'Valor Total',
              value: c.formatCurrency(c.totalValuation),
              icon: Icons.monetization_on_outlined,
              color: const Color(0xFF10B981),
            ),
          ),
          Container(height: 32, width: 1, color: Colors.grey.shade200),
          Expanded(
            child: InkWell(
              onTap: () => c.toggleLowStockFilter(),
              child: _buildStatItem(
                title: 'Bajo Stock',
                value: '${c.lowStockCount}',
                icon: Icons.warning_amber_rounded,
                color: c.showOnlyLowStock ? Colors.red : const Color(0xFFF59E0B),
                isSelected: c.showOnlyLowStock,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatItem({
    required String title,
    required String value,
    required IconData icon,
    required Color color,
    bool isSelected = false,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
      decoration: isSelected
          ? BoxDecoration(
              color: color.withOpacity(0.12),
              borderRadius: BorderRadius.circular(10),
            )
          : null,
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 14, color: color),
              const SizedBox(width: 4),
              Text(
                title,
                style: regularSmall.copyWith(
                  color: isSelected ? color : MyColor.bodyMutedTextColor,
                  fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                ),
              ),
            ],
          ),
          const SizedBox(height: 3),
          Text(
            value,
            style: boldDefault.copyWith(color: color, fontSize: 13),
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildSearchAndFilters() {
    return Column(
      children: [
        Padding(
          padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: 4),
          child: Container(
            height: 44,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.grey.shade200),
            ),
            child: TextField(
              controller: _searchCtrl,
              onChanged: (v) => c.searchItems(v),
              decoration: InputDecoration(
                hintText: 'Buscar insumo por nombre o código...',
                hintStyle: regularSmall.copyWith(color: Colors.grey),
                prefixIcon: const Icon(Icons.search, size: 20, color: Colors.grey),
                suffixIcon: _searchCtrl.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear, size: 18, color: Colors.grey),
                        onPressed: () {
                          _searchCtrl.clear();
                          c.searchItems('');
                        },
                      )
                    : null,
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(vertical: 10),
              ),
            ),
          ),
        ),
        if (c.categories.length > 1)
          SizedBox(
            height: 38,
            child: ListView.builder(
              padding: EdgeInsets.symmetric(horizontal: Dimensions.space16),
              scrollDirection: Axis.horizontal,
              itemCount: c.categories.length,
              itemBuilder: (_, i) {
                final cat = c.categories[i];
                final isSel = c.selectedCategory == cat;
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: Text(cat, style: regularSmall.copyWith(color: isSel ? Colors.white : Colors.black87)),
                    selected: isSel,
                    selectedColor: MyColor.primaryColor,
                    backgroundColor: Colors.white,
                    side: BorderSide(color: isSel ? MyColor.primaryColor : Colors.grey.shade200),
                    onSelected: (_) => c.filterByCategory(cat),
                  ),
                );
              },
            ),
          ),
        const SizedBox(height: 6),
      ],
    );
  }

  Widget _buildItemCard(InvItemModel item) {
    final c = Get.find<SellerInventoryController>();
    Color badgeColor = const Color(0xFF10B981);
    String statusText = 'Normal';
    if (item.isOutOfStock) {
      badgeColor = Colors.red;
      statusText = 'Agotado';
    } else if (item.isLowStock) {
      badgeColor = const Color(0xFFF59E0B);
      statusText = 'Bajo Stock';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: item.isBarItem
                      ? const Color(0xFF8B5CF6).withOpacity(0.12)
                      : const Color(0xFF0284C7).withOpacity(0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  item.isBarItem ? Icons.local_bar_rounded : Icons.kitchen_rounded,
                  color: item.isBarItem ? const Color(0xFF8B5CF6) : const Color(0xFF0284C7),
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.name ?? '',
                      style: boldDefault.copyWith(fontSize: 14),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        if (item.category != null && item.category!.isNotEmpty)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                            decoration: BoxDecoration(
                              color: Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              item.category!,
                              style: regularSmall.copyWith(color: Colors.grey.shade700, fontSize: 10),
                            ),
                          ),
                        if (item.category != null && item.category!.isNotEmpty)
                          const SizedBox(width: 6),
                        Text(
                          'Costo: ${c.formatCurrency(item.cost)} / ${item.unit}',
                          style: regularSmall.copyWith(color: Colors.grey.shade600, fontSize: 11),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: badgeColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  statusText,
                  style: semiBoldDefault.copyWith(color: badgeColor, fontSize: 10),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          const Divider(height: 1),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Stock Actual', style: regularSmall.copyWith(color: Colors.grey, fontSize: 10)),
                  Text(
                    '${(item.stock ?? 0).toStringAsFixed(2)} ${item.unit}',
                    style: boldDefault.copyWith(
                      color: item.isOutOfStock ? Colors.red : (item.isLowStock ? const Color(0xFFF59E0B) : Colors.black87),
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
              Row(
                children: [
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      minimumSize: Size.zero,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      side: BorderSide(color: Colors.grey.shade300),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                    ),
                    onPressed: () => _showQuickAdjustDialog(item),
                    icon: const Icon(Icons.sync_alt_rounded, size: 14, color: Color(0xFF0284C7)),
                    label: Text('Ajustar', style: regularSmall.copyWith(color: const Color(0xFF0284C7), fontSize: 11)),
                  ),
                  const SizedBox(width: 6),
                  IconButton(
                    icon: const Icon(Icons.edit_outlined, size: 18, color: Colors.grey),
                    onPressed: () => _showItemForm(item: item),
                    constraints: const BoxConstraints(),
                    padding: const EdgeInsets.all(6),
                  ),
                  IconButton(
                    icon: const Icon(Icons.delete_outline, size: 18, color: Colors.redAccent),
                    onPressed: () => _confirmDeleteItem(item),
                    constraints: const BoxConstraints(),
                    padding: const EdgeInsets.all(6),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.inventory_2_outlined, size: 64, color: Colors.grey.shade300),
          const SizedBox(height: 12),
          Text(
            c.showOnlyLowStock ? 'No hay insumos con stock bajo' : 'No se encontraron insumos',
            style: regularDefault.copyWith(color: Colors.grey),
          ),
        ],
      ),
    );
  }

  void _showQuickAdjustDialog(InvItemModel item) {
    String type = 'entrada';
    final qtyCtrl = TextEditingController();
    final descCtrl = TextEditingController();

    Get.dialog(
      StatefulBuilder(
        builder: (ctx, setDialogState) {
          return AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: Row(
              children: [
                const Icon(Icons.tune_rounded, color: Color(0xFF0284C7)),
                const SizedBox(width: 8),
                Expanded(
                  child: Text('Ajustar Stock: ${item.name}', style: boldDefault.copyWith(fontSize: 15)),
                ),
              ],
            ),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Stock actual: ${(item.stock ?? 0).toStringAsFixed(2)} ${item.unit}',
                    style: regularSmall.copyWith(color: Colors.grey.shade700),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Expanded(
                        child: InkWell(
                          onTap: () => setDialogState(() => type = 'entrada'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            decoration: BoxDecoration(
                              color: type == 'entrada' ? const Color(0xFF10B981) : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            alignment: Alignment.center,
                            child: Text(
                              '+ Entrada',
                              style: boldSmall.copyWith(color: type == 'entrada' ? Colors.white : Colors.black87),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: InkWell(
                          onTap: () => setDialogState(() => type = 'salida'),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            decoration: BoxDecoration(
                              color: type == 'salida' ? Colors.redAccent : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            alignment: Alignment.center,
                            child: Text(
                              '- Salida',
                              style: boldSmall.copyWith(color: type == 'salida' ? Colors.white : Colors.black87),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: qtyCtrl,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: InputDecoration(
                      labelText: 'Cantidad (${item.unit})',
                      hintText: '0.00',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: descCtrl,
                    decoration: InputDecoration(
                      labelText: 'Motivo del ajuste',
                      hintText: 'Ej: Ajuste por inventario físico...',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Get.back(),
                child: Text('Cancelar', style: regularDefault.copyWith(color: Colors.grey)),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: type == 'entrada' ? const Color(0xFF10B981) : Colors.redAccent,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                onPressed: () async {
                  final q = double.tryParse(qtyCtrl.text.trim()) ?? 0;
                  if (q <= 0) return;
                  Get.back();
                  await c.adjustStock(
                    item.id!,
                    type,
                    q,
                    description: descCtrl.text.trim().isNotEmpty ? descCtrl.text.trim() : null,
                  );
                },
                child: const Text('Confirmar', style: TextStyle(color: Colors.white)),
              ),
            ],
          );
        },
      ),
    );
  }

  void _showItemForm({InvItemModel? item}) {
    final c = Get.find<SellerInventoryController>();
    final isEdit = item != null;
    final nameCtrl = TextEditingController(text: item?.name ?? '');
    final catCtrl = TextEditingController(text: item?.category ?? '');
    final costCtrl = TextEditingController(text: item?.cost != null ? item!.cost.toString() : '');
    final minStockCtrl = TextEditingController(text: item?.minStock != null ? item!.minStock.toString() : '5');
    final initialStockCtrl = TextEditingController(text: '0');
    final sunatCtrl = TextEditingController(text: item?.sunatCode ?? '');
    final units = c.units.isNotEmpty
        ? c.units.map((u) => u.code).toList()
        : ['KG', 'G', 'L', 'ML', 'UNIDAD', 'PORCION', 'LATA', 'BOTELLA', 'PAQUETE'];
    final availableTaxTypes = c.taxTypes.isNotEmpty
        ? c.taxTypes
        : [
            InventoryOptionModel(code: 'gravado', name: 'Gravado (IGV)'),
            InventoryOptionModel(code: 'exonerado', name: 'Exonerado'),
            InventoryOptionModel(code: 'inafecto', name: 'Inafecto'),
          ];
    String unit = item?.unit ?? units.first;
    String taxType = item?.taxType ?? (c.metadata?.defaultTaxType ?? availableTaxTypes.first.code);
    bool isBar = item?.isBarItem ?? false;

    Get.bottomSheet(
      StatefulBuilder(
        builder: (ctx, setSheetState) {
          return Container(
            padding: const EdgeInsets.all(20),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
            ),
            child: SingleChildScrollView(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(isEdit ? 'Editar Insumo' : 'Nuevo Insumo', style: boldLarge.copyWith(fontSize: 18)),
                      IconButton(icon: const Icon(Icons.close), onPressed: () => Get.back()),
                    ],
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: nameCtrl,
                    decoration: InputDecoration(
                      labelText: 'Nombre del Insumo *',
                      hintText: 'Ej: Pechuga de Pollo, Tomate, Ron...',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          value: units.contains(unit) ? unit : units.first,
                          items: units
                              .map((u) => DropdownMenuItem(value: u, child: Text(u, style: regularDefault)))
                              .toList(),
                          onChanged: (v) => setSheetState(() => unit = v ?? 'KG'),
                          decoration: InputDecoration(
                            labelText: 'Unidad de Medida *',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: TextField(
                          controller: catCtrl,
                          decoration: InputDecoration(
                            labelText: 'Categoría',
                            hintText: 'Carnes, Lácteos, Bar...',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: costCtrl,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          decoration: InputDecoration(
                            labelText: 'Costo Unitario (${c.currencySymbol})',
                            hintText: '0.00',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: TextField(
                          controller: minStockCtrl,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          decoration: InputDecoration(
                            labelText: 'Stock Mínimo (Alerta)',
                            hintText: '5.00',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                        ),
                      ),
                    ],
                  ),
                  if (!isEdit) ...[
                    const SizedBox(height: 10),
                    TextField(
                      controller: initialStockCtrl,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        labelText: 'Stock Inicial en almacén',
                        hintText: '0.00',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      ),
                    ),
                  ],
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: DropdownButtonFormField<String>(
                          value: availableTaxTypes.any((t) => t.code == taxType) ? taxType : availableTaxTypes.first.code,
                          items: availableTaxTypes
                              .map((t) => DropdownMenuItem(value: t.code, child: Text(t.name, style: regularDefault)))
                              .toList(),
                          onChanged: (v) => setSheetState(() => taxType = v ?? availableTaxTypes.first.code),
                          decoration: InputDecoration(
                            labelText: 'Tipo Afectación',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: TextField(
                          controller: sunatCtrl,
                          decoration: InputDecoration(
                            labelText: 'Código SUNAT',
                            hintText: 'Opcional',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  SwitchListTile(
                    title: const Text('Insumo de Barra / Bar', style: boldSmall),
                    subtitle: const Text('Para coctelería y licores', style: regularSmall),
                    value: isBar,
                    activeColor: const Color(0xFF8B5CF6),
                    onChanged: (v) => setSheetState(() => isBar = v),
                    contentPadding: EdgeInsets.zero,
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    height: 46,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: MyColor.primaryColor,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onPressed: () async {
                        final name = nameCtrl.text.trim();
                        if (name.isEmpty) return;

                        final data = <String, dynamic>{
                          'name': name,
                          'unit': unit,
                          'category': catCtrl.text.trim(),
                          'cost': double.tryParse(costCtrl.text.trim()) ?? 0.0,
                          'min_stock': double.tryParse(minStockCtrl.text.trim()) ?? 0.0,
                          'tax_type': taxType,
                          'is_bar_item': isBar ? 1 : 0,
                          'sunat_code': sunatCtrl.text.trim(),
                        };

                        if (!isEdit) {
                          data['initial_stock'] = double.tryParse(initialStockCtrl.text.trim()) ?? 0.0;
                        }

                        Get.back();
                        await c.saveItem(data, id: item?.id);
                      },
                      child: Text(
                        isEdit ? 'Actualizar Insumo' : 'Guardar Insumo',
                        style: boldDefault.copyWith(color: Colors.white),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
      isScrollControlled: true,
    );
  }

  void _confirmDeleteItem(InvItemModel item) {
    Get.dialog(
      AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Eliminar Insumo', style: boldDefault),
        content: Text('¿Estás seguro de eliminar "${item.name}"? Esta acción no se puede deshacer si no está en recetas activas.'),
        actions: [
          TextButton(onPressed: () => Get.back(), child: const Text('Cancelar')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Get.back();
              await c.deleteItem(item.id!);
            },
            child: const Text('Eliminar', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }
}
