import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_inventory_controller.dart';
import 'package:lizto_store/data/model/seller/inventory_models.dart';
import 'package:lizto_store/data/repo/seller/seller_inventory_repo.dart';

class SellerInventoryWastesScreen extends StatefulWidget {
  final bool isTab;
  const SellerInventoryWastesScreen({super.key, this.isTab = false});

  @override
  State<SellerInventoryWastesScreen> createState() => _SellerInventoryWastesScreenState();
}

class _SellerInventoryWastesScreenState extends State<SellerInventoryWastesScreen> {
  late SellerInventoryController c;

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
      c.loadWastes();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerInventoryController>(
      builder: (_) {
        Widget content = c.loadingWastes
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : c.wastesList.isEmpty
                ? _buildEmptyState()
                : RefreshIndicator(
                    onRefresh: () => c.loadWastes(),
                    child: ListView.builder(
                      padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: 12),
                      itemCount: c.wastesList.length,
                      itemBuilder: (_, i) => _buildWasteCard(c.wastesList[i]),
                    ),
                  );

        if (widget.isTab) {
          return Scaffold(
            backgroundColor: MyColor.screenBgColor,
            body: content,
            floatingActionButton: FloatingActionButton.extended(
              onPressed: () => _showWasteForm(),
              backgroundColor: const Color(0xFFDC2626),
              icon: const Icon(Icons.delete_sweep_rounded, color: Colors.white),
              label: Text('Registrar Merma', style: boldDefault.copyWith(color: Colors.white)),
            ),
          );
        }

        return Scaffold(
          backgroundColor: MyColor.screenBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            elevation: 0,
            title: Text('Control de Mermas', style: boldLarge.copyWith(color: Colors.white)),
            centerTitle: true,
          ),
          body: content,
          floatingActionButton: FloatingActionButton.extended(
            onPressed: () => _showWasteForm(),
            backgroundColor: const Color(0xFFDC2626),
            icon: const Icon(Icons.delete_sweep_rounded, color: Colors.white),
            label: Text('Registrar Merma', style: boldDefault.copyWith(color: Colors.white)),
          ),
        );
      },
    );
  }

  Widget _buildWasteCard(InvWasteModel waste) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.redAccent.withOpacity(0.12),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.delete_forever_rounded, color: Colors.redAccent, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        waste.item?.name ?? 'Insumo #${waste.itemId}',
                        style: boldDefault.copyWith(fontSize: 14),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    Text(
                      '-${(waste.quantity ?? 0).toStringAsFixed(2)} ${waste.unit ?? ''}',
                      style: boldDefault.copyWith(color: Colors.redAccent, fontSize: 14),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: Colors.red.shade50,
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        waste.reason ?? 'Merma',
                        style: regularSmall.copyWith(color: Colors.red.shade800, fontSize: 10),
                      ),
                    ),
                    Text(
                      'Pérdida est: ${c.formatCurrency(waste.estimatedCostLoss)}',
                      style: regularSmall.copyWith(color: Colors.grey.shade600, fontSize: 11),
                    ),
                  ],
                ),
                if (waste.wasteDate != null || waste.notes != null) ...[
                  const SizedBox(height: 6),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        waste.notes != null && waste.notes!.isNotEmpty ? waste.notes! : '',
                        style: regularSmall.copyWith(color: Colors.grey.shade500, fontSize: 10),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        waste.wasteDate ?? '',
                        style: regularSmall.copyWith(color: Colors.grey.shade500, fontSize: 10),
                      ),
                    ],
                  ),
                ],
              ],
            ),
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
          Icon(Icons.delete_sweep_outlined, size: 64, color: Colors.grey.shade300),
          const SizedBox(height: 12),
          Text('No hay mermas registradas', style: regularDefault.copyWith(color: Colors.grey)),
        ],
      ),
    );
  }

  void _showWasteForm() {
    final qtyCtrl = TextEditingController();
    final notesCtrl = TextEditingController();
    final reasons = c.wasteReasons.isNotEmpty
        ? c.wasteReasons
        : [
            'Caducado / Vencido',
            'Quemado / Malogrado en cocción',
            'Derrame / Rotura accidental',
            'Plato cancelado por comensal',
            'Calidad deficiente del insumo',
            'Muestra / Degustación',
            'Otro motivo',
          ];
    int? selectedItemId = c.recipeInsumos.isNotEmpty ? c.recipeInsumos.first.id : null;
    String reason = reasons.first;
    DateTime selectedDate = DateTime.now();

    Get.bottomSheet(
      StatefulBuilder(
        builder: (ctx, setSheetState) {
          final selectedItem = c.recipeInsumos.firstWhereOrNull((it) => it.id == selectedItemId);

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
                      Text('Registrar Merma', style: boldLarge.copyWith(fontSize: 18)),
                      IconButton(icon: const Icon(Icons.close), onPressed: () => Get.back()),
                    ],
                  ),
                  const SizedBox(height: 12),
                  if (c.recipeInsumos.isNotEmpty)
                    DropdownButtonFormField<int>(
                      value: selectedItemId,
                      items: c.recipeInsumos
                          .map((it) => DropdownMenuItem<int>(
                                value: it.id,
                                child: Text(
                                  '${it.name} (Stock: ${(it.stock ?? 0).toStringAsFixed(1)} ${it.unit})',
                                  style: regularSmall,
                                ),
                              ))
                          .toList(),
                      onChanged: (v) => setSheetState(() => selectedItemId = v),
                      decoration: InputDecoration(
                        labelText: 'Insumo a mermar *',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      ),
                    ),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: qtyCtrl,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          decoration: InputDecoration(
                            labelText: 'Cantidad (${selectedItem?.unit ?? 'UNIDAD'}) *',
                            hintText: '0.00',
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                          ),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: InkWell(
                          onTap: () async {
                            final picked = await showDatePicker(
                              context: context,
                              initialDate: selectedDate,
                              firstDate: DateTime(2020),
                              lastDate: DateTime.now(),
                            );
                            if (picked != null) {
                              setSheetState(() => selectedDate = picked);
                            }
                          },
                          child: Container(
                            height: 48,
                            padding: const EdgeInsets.symmetric(horizontal: 12),
                            decoration: BoxDecoration(
                              border: Border.all(color: Colors.grey.shade400),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            alignment: Alignment.centerLeft,
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  '${selectedDate.year}-${selectedDate.month.toString().padLeft(2, '0')}-${selectedDate.day.toString().padLeft(2, '0')}',
                                  style: regularSmall,
                                ),
                                const Icon(Icons.calendar_today, size: 16, color: Colors.grey),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  DropdownButtonFormField<String>(
                    value: reason,
                    items: reasons
                        .map((r) => DropdownMenuItem(value: r, child: Text(r, style: regularDefault)))
                        .toList(),
                    onChanged: (v) => setSheetState(() => reason = v ?? reasons.first),
                    decoration: InputDecoration(
                      labelText: 'Motivo de la merma *',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    ),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    controller: notesCtrl,
                    decoration: InputDecoration(
                      labelText: 'Detalles adicionales / Observaciones',
                      hintText: 'Ej: Se cayó de la repisa al cocinar...',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    height: 46,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFFDC2626),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      onPressed: () async {
                        final q = double.tryParse(qtyCtrl.text.trim()) ?? 0;
                        if (selectedItemId == null || q <= 0) return;

                        final data = {
                          'item_id': selectedItemId,
                          'quantity': q,
                          'reason': reason,
                          'waste_date':
                              '${selectedDate.year}-${selectedDate.month.toString().padLeft(2, '0')}-${selectedDate.day.toString().padLeft(2, '0')}',
                          'notes': notesCtrl.text.trim(),
                        };

                        Get.back();
                        await c.registerWaste(data);
                      },
                      child: Text('Registrar Merma', style: boldDefault.copyWith(color: Colors.white)),
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
}
