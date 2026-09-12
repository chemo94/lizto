import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_inventory_controller.dart';
import 'package:lizto_store/data/model/seller/inventory_models.dart';
import 'package:lizto_store/data/repo/seller/seller_inventory_repo.dart';

class SellerInventoryRecipesScreen extends StatefulWidget {
  final bool isTab;
  const SellerInventoryRecipesScreen({super.key, this.isTab = false});

  @override
  State<SellerInventoryRecipesScreen> createState() => _SellerInventoryRecipesScreenState();
}

class _SellerInventoryRecipesScreenState extends State<SellerInventoryRecipesScreen> {
  late SellerInventoryController c;
  String _recipeFilter = 'all'; // all, kitchen, bar

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
      c.loadRecipes();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerInventoryController>(
      builder: (_) {
        final filteredRecipes = c.recipes.where((r) {
          if (_recipeFilter == 'kitchen') return r.isKitchen;
          if (_recipeFilter == 'bar') return r.isBar;
          return true;
        }).toList();

        Widget content = Column(
          children: [
            _buildTypeFilter(),
            Expanded(
              child: c.loadingRecipes
                  ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
                  : filteredRecipes.isEmpty
                      ? _buildEmptyState()
                      : RefreshIndicator(
                          onRefresh: () => c.loadRecipes(),
                          child: ListView.builder(
                            padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: 8),
                            itemCount: filteredRecipes.length,
                            itemBuilder: (_, i) => _buildRecipeCard(filteredRecipes[i]),
                          ),
                        ),
            ),
          ],
        );

        if (widget.isTab) {
          return Scaffold(
            backgroundColor: MyColor.screenBgColor,
            body: content,
            floatingActionButton: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                FloatingActionButton.small(
                  heroTag: 'prod_history',
                  onPressed: _showProductionsSheet,
                  backgroundColor: const Color(0xFF475569),
                  child: const Icon(Icons.history_rounded, color: Colors.white),
                ),
                const SizedBox(width: 8),
                FloatingActionButton.extended(
                  heroTag: 'new_recipe',
                  onPressed: () => _showRecipeForm(),
                  backgroundColor: MyColor.primaryColor,
                  icon: const Icon(Icons.add_rounded, color: Colors.white),
                  label: Text('Nueva Receta', style: boldDefault.copyWith(color: Colors.white)),
                ),
              ],
            ),
          );
        }

        return Scaffold(
          backgroundColor: MyColor.screenBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            elevation: 0,
            title: Text('Recetas & Producción', style: boldLarge.copyWith(color: Colors.white)),
            centerTitle: true,
            actions: [
              IconButton(
                icon: const Icon(Icons.history_rounded, color: Colors.white),
                tooltip: 'Historial Producciones',
                onPressed: _showProductionsSheet,
              ),
            ],
          ),
          body: content,
          floatingActionButton: FloatingActionButton.extended(
            onPressed: () => _showRecipeForm(),
            backgroundColor: MyColor.primaryColor,
            icon: const Icon(Icons.add_rounded, color: Colors.white),
            label: Text('Nueva Receta', style: boldDefault.copyWith(color: Colors.white)),
          ),
        );
      },
    );
  }

  Widget _buildTypeFilter() {
    return Container(
      margin: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space12, Dimensions.space16, 4),
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          _buildFilterButton('all', 'Todas', Icons.restaurant_menu_rounded),
          _buildFilterButton('kitchen', 'Cocina', Icons.kitchen_rounded),
          _buildFilterButton('bar', 'Bar / Barra', Icons.local_bar_rounded),
        ],
      ),
    );
  }

  Widget _buildFilterButton(String key, String label, IconData icon) {
    final isSel = _recipeFilter == key;
    return Expanded(
      child: InkWell(
        onTap: () => setState(() => _recipeFilter = key),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: isSel ? MyColor.primaryColor : Colors.transparent,
            borderRadius: BorderRadius.circular(8),
          ),
          alignment: Alignment.center,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 15, color: isSel ? Colors.white : Colors.black87),
              const SizedBox(width: 4),
              Text(
                label,
                style: boldSmall.copyWith(color: isSel ? Colors.white : Colors.black87),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildRecipeCard(InvRecipeModel recipe) {
    final c = Get.find<SellerInventoryController>();
    Color marginColor = const Color(0xFF10B981);
    if ((recipe.margin ?? 0) < 30) {
      marginColor = Colors.redAccent;
    } else if ((recipe.margin ?? 0) < 50) {
      marginColor = const Color(0xFFF59E0B);
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
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
      child: Theme(
        data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
        child: ExpansionTile(
          tilePadding: const EdgeInsets.all(12),
          leading: Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: recipe.isBar
                  ? const Color(0xFF8B5CF6).withOpacity(0.12)
                  : const Color(0xFFF97316).withOpacity(0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              recipe.isBar ? Icons.local_bar_rounded : Icons.soup_kitchen_rounded,
              color: recipe.isBar ? const Color(0xFF8B5CF6) : const Color(0xFFF97316),
              size: 24,
            ),
          ),
          title: Row(
            children: [
              Expanded(
                child: Text(
                  recipe.name ?? '',
                  style: boldDefault.copyWith(fontSize: 15),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: recipe.isBar
                      ? const Color(0xFF8B5CF6).withOpacity(0.1)
                      : const Color(0xFFF97316).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  recipe.isBar ? 'BAR' : 'COCINA',
                  style: boldSmall.copyWith(
                    color: recipe.isBar ? const Color(0xFF8B5CF6) : const Color(0xFFF97316),
                    fontSize: 9,
                  ),
                ),
              ),
            ],
          ),
          subtitle: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 4),
              Row(
                children: [
                  Text(
                    '${recipe.portions?.toStringAsFixed(0) ?? '1'} ${recipe.unitProduced ?? 'porción'}',
                    style: regularSmall.copyWith(color: Colors.grey.shade600),
                  ),
                  if (recipe.productName != null) ...[
                    const SizedBox(width: 6),
                    Text('• Carta: ${recipe.productName}',
                        style: regularSmall.copyWith(color: const Color(0xFF0284C7)),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis),
                  ],
                ],
              ),
              const SizedBox(height: 6),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Costo Porción', style: regularSmall.copyWith(color: Colors.grey, fontSize: 10)),
                      Text(c.formatCurrency(recipe.costPerPortion),
                          style: boldDefault.copyWith(color: Colors.black87, fontSize: 13)),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Precio Carta', style: regularSmall.copyWith(color: Colors.grey, fontSize: 10)),
                      Text(c.formatCurrency(recipe.productPrice),
                          style: boldDefault.copyWith(color: Colors.black87, fontSize: 13)),
                    ],
                  ),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text('Margen', style: regularSmall.copyWith(color: Colors.grey, fontSize: 10)),
                      Text('${(recipe.margin ?? 0).toStringAsFixed(1)}%',
                          style: boldDefault.copyWith(color: marginColor, fontSize: 13)),
                    ],
                  ),
                ],
              ),
            ],
          ),
          children: [
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Ingredientes / Insumos (${recipe.items.length})',
                        style: boldSmall.copyWith(color: Colors.grey.shade800),
                      ),
                      Text(
                        'Costo Total: ${c.formatCurrency(recipe.totalCost)}',
                        style: boldSmall.copyWith(color: const Color(0xFF10B981)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  ...recipe.items.map((ing) => Padding(
                        padding: const EdgeInsets.only(bottom: 6),
                        child: Row(
                          children: [
                            const Icon(Icons.circle, size: 6, color: Colors.grey),
                            const SizedBox(width: 6),
                            Expanded(
                              child: Text(
                                ing.item?.name ?? 'Insumo #${ing.itemId}',
                                style: regularSmall.copyWith(color: Colors.black87),
                              ),
                            ),
                            Text(
                              '${(ing.quantityNet ?? ing.quantityGross ?? 0).toStringAsFixed(2)} ${ing.unit}',
                              style: regularSmall.copyWith(color: Colors.grey.shade700),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              c.formatCurrency(ing.ingredientCost),
                              style: boldSmall.copyWith(color: Colors.grey.shade800),
                            ),
                          ],
                        ),
                      )),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF10B981),
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                        onPressed: () => _showProduceDialog(recipe),
                        icon: const Icon(Icons.precision_manufacturing_rounded, size: 16, color: Colors.white),
                        label: Text('Producir Lote', style: boldSmall.copyWith(color: Colors.white)),
                      ),
                      const SizedBox(width: 8),
                      IconButton(
                        icon: const Icon(Icons.delete_outline, size: 18, color: Colors.redAccent),
                        onPressed: () => _confirmDeleteRecipe(recipe),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.menu_book_rounded, size: 64, color: Colors.grey.shade300),
          const SizedBox(height: 12),
          Text('No hay recetas registradas', style: regularDefault.copyWith(color: Colors.grey)),
        ],
      ),
    );
  }

  void _showProduceDialog(InvRecipeModel recipe) {
    final portionsCtrl = TextEditingController(text: '1');
    final notesCtrl = TextEditingController();

    Get.dialog(
      StatefulBuilder(
        builder: (ctx, setDialogState) {
          final portions = double.tryParse(portionsCtrl.text.trim()) ?? 1.0;

          return AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: Row(
              children: [
                const Icon(Icons.precision_manufacturing_rounded, color: Color(0xFF10B981)),
                const SizedBox(width: 8),
                Expanded(
                  child: Text('Producir Lote: ${recipe.name}', style: boldDefault.copyWith(fontSize: 15)),
                ),
              ],
            ),
            content: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Se descontarán los siguientes insumos según las porciones producidas:',
                    style: regularSmall.copyWith(color: Colors.grey.shade700),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: portionsCtrl,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    onChanged: (_) => setDialogState(() {}),
                    decoration: InputDecoration(
                      labelText: 'Cantidad de Porciones a Producir',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                  ),
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.grey.shade200),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Insumos requeridos:', style: boldSmall.copyWith(fontSize: 11)),
                        const SizedBox(height: 6),
                        ...recipe.items.map((ing) {
                          final needed = (ing.quantityNet ?? ing.quantityGross ?? 0) * portions;
                          final currentStock = ing.item?.stock ?? 0.0;
                          final hasEnough = currentStock >= needed;

                          return Padding(
                            padding: const EdgeInsets.only(bottom: 4),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Text(
                                    ing.item?.name ?? 'Insumo',
                                    style: regularSmall.copyWith(
                                      color: hasEnough ? Colors.black87 : Colors.red,
                                      fontWeight: hasEnough ? FontWeight.normal : FontWeight.bold,
                                      fontSize: 11,
                                    ),
                                  ),
                                ),
                                Text(
                                  '${needed.toStringAsFixed(2)} ${ing.unit} (disp: ${currentStock.toStringAsFixed(1)})',
                                  style: regularSmall.copyWith(
                                    color: hasEnough ? Colors.grey.shade700 : Colors.red,
                                    fontSize: 10,
                                  ),
                                ),
                              ],
                            ),
                          );
                        }),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: notesCtrl,
                    decoration: InputDecoration(
                      labelText: 'Notas de producción (opcional)',
                      hintText: 'Ej: Lote matutino, evento...',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                  ),
                ],
              ),
            ),
            actions: [
              TextButton(onPressed: () => Get.back(), child: const Text('Cancelar')),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                ),
                onPressed: () async {
                  final p = double.tryParse(portionsCtrl.text.trim()) ?? 0;
                  if (p <= 0) return;
                  Get.back();
                  await c.produceRecipe(
                    recipe.id!,
                    p,
                    notes: notesCtrl.text.trim().isNotEmpty ? notesCtrl.text.trim() : null,
                  );
                },
                child: const Text('Confirmar Producción', style: TextStyle(color: Colors.white)),
              ),
            ],
          );
        },
      ),
    );
  }

  void _showProductionsSheet() {
    Get.bottomSheet(
      Container(
        height: Get.height * 0.7,
        padding: const EdgeInsets.all(16),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Historial de Producciones', style: boldLarge.copyWith(fontSize: 16)),
                IconButton(icon: const Icon(Icons.close), onPressed: () => Get.back()),
              ],
            ),
            const Divider(),
            Expanded(
              child: c.recentProductions.isEmpty
                  ? Center(
                      child: Text('No hay producciones registradas',
                          style: regularDefault.copyWith(color: Colors.grey)),
                    )
                  : ListView.builder(
                      itemCount: c.recentProductions.length,
                      itemBuilder: (_, i) {
                        final prod = c.recentProductions[i];
                        return Container(
                          margin: const EdgeInsets.only(bottom: 8),
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: Colors.grey.shade50,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    prod.recipeName ?? 'Receta',
                                    style: boldSmall.copyWith(fontSize: 13),
                                  ),
                                  Text(
                                    '${prod.portionsProduced?.toStringAsFixed(1) ?? '1'} ${prod.unitProduced ?? 'porciones'} • ${prod.producedAt ?? ''}',
                                    style: regularSmall.copyWith(color: Colors.grey.shade600, fontSize: 10),
                                  ),
                                ],
                              ),
                              if (prod.isCompleted)
                                TextButton(
                                  onPressed: () async {
                                    Get.back();
                                    await c.voidProduction(prod.id!);
                                  },
                                  child: const Text('Anular', style: TextStyle(color: Colors.redAccent, fontSize: 12)),
                                )
                              else
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: Colors.grey.shade200,
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: const Text('Anulada', style: TextStyle(color: Colors.grey, fontSize: 10)),
                                ),
                            ],
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
      isScrollControlled: true,
    );
  }

  void _showRecipeForm() {
    final nameCtrl = TextEditingController();
    final portionsCtrl = TextEditingController(text: '1');
    final unitCtrl = TextEditingController(text: 'porcion');
    final notesCtrl = TextEditingController();
    String recipeType = 'kitchen';
    int? selectedProductId;

    List<Map<String, dynamic>> ingredients = [];

    Get.bottomSheet(
      StatefulBuilder(
        builder: (ctx, setSheetState) {
          return Container(
            height: Get.height * 0.85,
            padding: const EdgeInsets.all(20),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text('Nueva Receta / Escandallo', style: boldLarge.copyWith(fontSize: 18)),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Get.back()),
                  ],
                ),
                const SizedBox(height: 10),
                Expanded(
                  child: SingleChildScrollView(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        TextField(
                          controller: nameCtrl,
                          decoration: InputDecoration(
                            labelText: 'Nombre de la Receta *',
                            hintText: 'Ej: Ceviche Clásico, Pisco Sour...',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            Expanded(
                              child: DropdownButtonFormField<String>(
                                value: recipeType,
                                items: const [
                                  DropdownMenuItem(value: 'kitchen', child: Text('Cocina')),
                                  DropdownMenuItem(value: 'bar', child: Text('Bar / Barra')),
                                ],
                                onChanged: (v) => setSheetState(() => recipeType = v ?? 'kitchen'),
                                decoration: InputDecoration(
                                  labelText: 'Tipo',
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: TextField(
                                controller: portionsCtrl,
                                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                decoration: InputDecoration(
                                  labelText: 'Porciones *',
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        if (c.availableProducts.isNotEmpty)
                          DropdownButtonFormField<int>(
                            value: selectedProductId,
                            items: [
                              const DropdownMenuItem<int>(value: null, child: Text('Sin vincular a producto')),
                              ...c.availableProducts.map((p) => DropdownMenuItem<int>(
                                    value: p['id'] as int?,
                                    child: Text('${p['name']} (${c.formatCurrency(double.tryParse(p['price'].toString()))})', style: regularSmall),
                                  )),
                            ],
                            onChanged: (v) => setSheetState(() => selectedProductId = v),
                            decoration: InputDecoration(
                              labelText: 'Vincular a Plato / Producto de Carta',
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            ),
                          ),
                        const SizedBox(height: 10),
                        TextField(
                          controller: notesCtrl,
                          decoration: InputDecoration(
                            labelText: 'Notas / Observaciones de la receta (opcional)',
                            hintText: 'Instrucciones o detalles de preparación...',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Ingredientes (${ingredients.length})', style: boldDefault),
                            TextButton.icon(
                              onPressed: () {
                                if (c.recipeInsumos.isEmpty) return;
                                final firstItem = c.recipeInsumos.first;
                                setSheetState(() {
                                  ingredients.add({
                                    'item_id': firstItem.id,
                                    'quantity_gross': 0.5,
                                    'waste_pct': 0.0,
                                    'unit': firstItem.unit ?? 'KG',
                                  });
                                });
                              },
                              icon: const Icon(Icons.add, size: 16),
                              label: const Text('Agregar Insumo'),
                            ),
                          ],
                        ),
                        ...ingredients.asMap().entries.map((entry) {
                          final idx = entry.key;
                          final ing = entry.value;

                          return Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: Colors.grey.shade50,
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(color: Colors.grey.shade200),
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  flex: 3,
                                  child: DropdownButton<int>(
                                    value: ing['item_id'],
                                    isExpanded: true,
                                    underline: const SizedBox(),
                                    items: c.recipeInsumos
                                        .map((it) => DropdownMenuItem(
                                              value: it.id,
                                              child: Text(it.name ?? '',
                                                  style: regularSmall, overflow: TextOverflow.ellipsis),
                                            ))
                                        .toList(),
                                    onChanged: (v) {
                                      final selected = c.recipeInsumos.firstWhereOrNull((it) => it.id == v);
                                      setSheetState(() {
                                        ing['item_id'] = v;
                                        if (selected != null) ing['unit'] = selected.unit ?? 'KG';
                                      });
                                    },
                                  ),
                                ),
                                const SizedBox(width: 6),
                                Expanded(
                                  flex: 2,
                                  child: TextFormField(
                                    initialValue: ing['quantity_gross'].toString(),
                                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                    decoration: InputDecoration(
                                      labelText: 'Cant (${ing['unit']})',
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(6)),
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                                    ),
                                    onChanged: (v) => ing['quantity_gross'] = double.tryParse(v) ?? 0,
                                  ),
                                ),
                                const SizedBox(width: 6),
                                Expanded(
                                  flex: 2,
                                  child: TextFormField(
                                    initialValue: ing['waste_pct'].toString(),
                                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                    decoration: InputDecoration(
                                      labelText: '% Merma',
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(6)),
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                                    ),
                                    onChanged: (v) => ing['waste_pct'] = double.tryParse(v) ?? 0,
                                  ),
                                ),
                                IconButton(
                                  icon: const Icon(Icons.remove_circle_outline, color: Colors.redAccent, size: 20),
                                  onPressed: () => setSheetState(() => ingredients.removeAt(idx)),
                                ),
                              ],
                            ),
                          );
                        }),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 10),
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
                      if (name.isEmpty || ingredients.isEmpty) return;

                      final data = {
                        'name': name,
                        'recipe_type': recipeType,
                        'portions': double.tryParse(portionsCtrl.text.trim()) ?? 1.0,
                        'unit_produced': unitCtrl.text.trim(),
                        'product_id': selectedProductId,
                        'notes': notesCtrl.text.trim(),
                        'ingredients': ingredients,
                      };

                      Get.back();
                      await c.saveRecipe(data);
                    },
                    child: Text('Guardar Receta', style: boldDefault.copyWith(color: Colors.white)),
                  ),
                ),
              ],
            ),
          );
        },
      ),
      isScrollControlled: true,
    );
  }

  void _confirmDeleteRecipe(InvRecipeModel recipe) {
    Get.dialog(
      AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Eliminar Receta', style: boldDefault),
        content: Text('¿Estás seguro de eliminar la receta "${recipe.name}"?'),
        actions: [
          TextButton(onPressed: () => Get.back(), child: const Text('Cancelar')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Get.back();
              await c.deleteRecipe(recipe.id!);
            },
            child: const Text('Eliminar', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }
}
