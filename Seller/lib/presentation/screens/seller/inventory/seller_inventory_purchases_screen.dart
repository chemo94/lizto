import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_inventory_controller.dart';
import 'package:lizto_store/data/model/seller/inventory_models.dart';
import 'package:lizto_store/data/repo/seller/seller_inventory_repo.dart';

class SellerInventoryPurchasesScreen extends StatefulWidget {
  final bool isTab;
  const SellerInventoryPurchasesScreen({super.key, this.isTab = false});

  @override
  State<SellerInventoryPurchasesScreen> createState() => _SellerInventoryPurchasesScreenState();
}

class _SellerInventoryPurchasesScreenState extends State<SellerInventoryPurchasesScreen> {
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
      c.loadPurchases();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerInventoryController>(
      builder: (_) {
        Widget content = c.loadingPurchases
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : c.purchasesList.isEmpty
                ? _buildEmptyState()
                : RefreshIndicator(
                    onRefresh: () => c.loadPurchases(),
                    child: ListView.builder(
                      padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: 12),
                      itemCount: c.purchasesList.length,
                      itemBuilder: (_, i) => _buildPurchaseCard(c.purchasesList[i]),
                    ),
                  );

        if (widget.isTab) {
          return Scaffold(
            backgroundColor: MyColor.screenBgColor,
            body: content,
            floatingActionButton: FloatingActionButton.extended(
              onPressed: () => _showPurchaseForm(),
              backgroundColor: const Color(0xFF059669),
              icon: const Icon(Icons.add_shopping_cart_rounded, color: Colors.white),
              label: Text('Registrar Compra', style: boldDefault.copyWith(color: Colors.white)),
            ),
          );
        }

        return Scaffold(
          backgroundColor: MyColor.screenBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            elevation: 0,
            title: Text('Compras de Insumos', style: boldLarge.copyWith(color: Colors.white)),
            centerTitle: true,
          ),
          body: content,
          floatingActionButton: FloatingActionButton.extended(
            onPressed: () => _showPurchaseForm(),
            backgroundColor: const Color(0xFF059669),
            icon: const Icon(Icons.add_shopping_cart_rounded, color: Colors.white),
            label: Text('Registrar Compra', style: boldDefault.copyWith(color: Colors.white)),
          ),
        );
      },
    );
  }

  Widget _buildPurchaseCard(InvPurchaseModel purchase) {
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
          tilePadding: const EdgeInsets.all(14),
          leading: Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: const Color(0xFF059669).withOpacity(0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Icons.shopping_cart_checkout_rounded,
              color: Color(0xFF059669),
              size: 22,
            ),
          ),
          title: Row(
            children: [
              Expanded(
                child: Text(
                  purchase.supplier?.name ?? 'Proveedor #${purchase.supplierId}',
                  style: boldDefault.copyWith(fontSize: 14),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              Text(
                c.formatCurrency(purchase.total),
                style: boldDefault.copyWith(color: const Color(0xFF059669), fontSize: 14),
              ),
            ],
          ),
          subtitle: Padding(
            padding: const EdgeInsets.only(top: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  '${purchase.documentName} ${purchase.fullDocNumber}',
                  style: regularSmall.copyWith(color: Colors.grey.shade600),
                ),
                Text(
                  purchase.documentDate ?? '',
                  style: regularSmall.copyWith(color: Colors.grey.shade500, fontSize: 11),
                ),
              ],
            ),
          ),
          children: [
            const Divider(height: 1),
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Insumos comprados:', style: boldSmall.copyWith(fontSize: 12)),
                  const SizedBox(height: 6),
                  ...purchase.items.map((pi) => Padding(
                        padding: const EdgeInsets.only(bottom: 4),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              '${pi.item?.name ?? 'Insumo'} × ${(pi.quantity ?? 0).toStringAsFixed(2)} ${pi.item?.unit ?? ''}',
                              style: regularSmall.copyWith(color: Colors.black87),
                            ),
                            Text(
                              '${c.formatCurrency(pi.total)} (c/u ${c.formatCurrency(pi.unitCost)})',
                              style: regularSmall.copyWith(color: Colors.grey.shade700, fontSize: 11),
                            ),
                          ],
                        ),
                      )),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Subtotal: ${c.formatCurrency(purchase.subtotal)}',
                          style: regularSmall.copyWith(color: Colors.grey.shade600)),
                      Text('Impuesto (${c.taxRatePercent.toStringAsFixed(0)}%): ${c.formatCurrency(purchase.igv)}',
                          style: regularSmall.copyWith(color: Colors.grey.shade600)),
                    ],
                  ),
                  if (purchase.notes != null && purchase.notes!.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Text('Nota: ${purchase.notes}',
                        style: regularSmall.copyWith(color: Colors.grey.shade600, fontStyle: FontStyle.italic)),
                  ],
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
          Icon(Icons.shopping_cart_outlined, size: 64, color: Colors.grey.shade300),
          const SizedBox(height: 12),
          Text('No hay compras de insumos registradas',
              style: regularDefault.copyWith(color: Colors.grey)),
        ],
      ),
    );
  }

  void _showPurchaseForm() {
    final availableDocTypes = c.documentTypes.isNotEmpty
        ? c.documentTypes
        : [
            InventoryOptionModel(code: '01', name: 'Factura (01)'),
            InventoryOptionModel(code: '03', name: 'Boleta (03)'),
            InventoryOptionModel(code: '00', name: 'Nota / Recibo'),
          ];
    final availablePaymentMethods = c.paymentMethods.isNotEmpty
        ? c.paymentMethods
        : [
            InventoryOptionModel(code: 'cash', name: 'Efectivo'),
            InventoryOptionModel(code: 'transfer', name: 'Transferencia'),
            InventoryOptionModel(code: 'yape', name: 'Yape / Plin'),
            InventoryOptionModel(code: 'card', name: 'Tarjeta'),
            InventoryOptionModel(code: 'credit', name: 'Crédito'),
          ];
    final seriesCtrl = TextEditingController();
    final numCtrl = TextEditingController();
    final notesCtrl = TextEditingController();
    int? selectedSupplierId = c.purchaseSuppliers.isNotEmpty ? c.purchaseSuppliers.first.id : null;
    String docType = availableDocTypes.first.code;
    String paymentMethod = availablePaymentMethods.first.code;
    DateTime selectedDate = DateTime.now();

    List<Map<String, dynamic>> purchaseItems = [];
    if (c.recipeInsumos.isNotEmpty) {
      final it = c.recipeInsumos.first;
      purchaseItems.add({
        'item_id': it.id,
        'quantity': 1.0,
        'unit_cost': it.cost ?? 0.0,
      });
    }

    Get.bottomSheet(
      StatefulBuilder(
        builder: (ctx, setSheetState) {
          double subtotal = 0;
          for (var item in purchaseItems) {
            subtotal += (item['quantity'] as double) * (item['unit_cost'] as double);
          }
          final taxPct = c.taxRatePercent;
          final igv = subtotal * (taxPct / 100);
          final total = subtotal + igv;

          return Container(
            height: Get.height * 0.88,
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
                    Text('Registrar Compra de Insumos', style: boldLarge.copyWith(fontSize: 18)),
                    IconButton(icon: const Icon(Icons.close), onPressed: () => Get.back()),
                  ],
                ),
                const SizedBox(height: 10),
                Expanded(
                  child: SingleChildScrollView(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (c.purchaseSuppliers.isNotEmpty)
                          DropdownButtonFormField<int>(
                            value: selectedSupplierId,
                            items: c.purchaseSuppliers
                                .map((s) => DropdownMenuItem<int>(
                                      value: s.id,
                                      child: Text('${s.name} (${s.documentNumber ?? ''})', style: regularSmall),
                                    ))
                                .toList(),
                            onChanged: (v) => setSheetState(() => selectedSupplierId = v),
                            decoration: InputDecoration(
                              labelText: 'Proveedor *',
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            ),
                          )
                        else
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: Colors.amber.shade50,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Text(
                              'Primero registra un proveedor en la pestaña "Proveedores"',
                              style: TextStyle(color: Colors.brown, fontSize: 12),
                            ),
                          ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            Expanded(
                              flex: 2,
                              child: DropdownButtonFormField<String>(
                                value: availableDocTypes.any((d) => d.code == docType) ? docType : availableDocTypes.first.code,
                                items: availableDocTypes
                                    .map((d) => DropdownMenuItem(value: d.code, child: Text(d.name, style: regularSmall)))
                                    .toList(),
                                onChanged: (v) => setSheetState(() => docType = v ?? availableDocTypes.first.code),
                                decoration: InputDecoration(
                                  labelText: 'Tipo Comprobante',
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              flex: 1,
                              child: TextField(
                                controller: seriesCtrl,
                                decoration: InputDecoration(
                                  labelText: 'Serie',
                                  hintText: 'Ej: F001',
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              flex: 2,
                              child: TextField(
                                controller: numCtrl,
                                keyboardType: TextInputType.number,
                                decoration: InputDecoration(
                                  labelText: 'Número',
                                  hintText: '000123',
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Row(
                          children: [
                            Expanded(
                              child: DropdownButtonFormField<String>(
                                value: availablePaymentMethods.any((p) => p.code == paymentMethod) ? paymentMethod : availablePaymentMethods.first.code,
                                items: availablePaymentMethods
                                    .map((p) => DropdownMenuItem(value: p.code, child: Text(p.name, style: regularSmall)))
                                    .toList(),
                                onChanged: (v) => setSheetState(() => paymentMethod = v ?? availablePaymentMethods.first.code),
                                decoration: InputDecoration(
                                  labelText: 'Forma de Pago',
                                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                                  contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
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
                        const SizedBox(height: 16),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Insumos a comprar (${purchaseItems.length})', style: boldDefault),
                            TextButton.icon(
                              onPressed: () {
                                if (c.recipeInsumos.isEmpty) return;
                                final it = c.recipeInsumos.first;
                                setSheetState(() {
                                  purchaseItems.add({
                                    'item_id': it.id,
                                    'quantity': 1.0,
                                    'unit_cost': it.cost ?? 0.0,
                                  });
                                });
                              },
                              icon: const Icon(Icons.add, size: 16),
                              label: const Text('Agregar Insumo'),
                            ),
                          ],
                        ),
                        ...purchaseItems.asMap().entries.map((entry) {
                          final idx = entry.key;
                          final pi = entry.value;

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
                                    value: pi['item_id'],
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
                                        pi['item_id'] = v;
                                        if (selected != null && (pi['unit_cost'] == 0 || pi['unit_cost'] == null)) {
                                          pi['unit_cost'] = selected.cost ?? 0.0;
                                        }
                                      });
                                    },
                                  ),
                                ),
                                const SizedBox(width: 6),
                                Expanded(
                                  flex: 2,
                                  child: TextFormField(
                                    initialValue: pi['quantity'].toString(),
                                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                    decoration: InputDecoration(
                                      labelText: 'Cantidad',
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(6)),
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                                    ),
                                    onChanged: (v) => setSheetState(() {
                                      pi['quantity'] = double.tryParse(v) ?? 0.0;
                                    }),
                                  ),
                                ),
                                const SizedBox(width: 6),
                                Expanded(
                                  flex: 2,
                                  child: TextFormField(
                                    initialValue: pi['unit_cost'].toString(),
                                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                    decoration: InputDecoration(
                                      labelText: 'Costo Unit.',
                                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(6)),
                                      contentPadding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                                    ),
                                    onChanged: (v) => setSheetState(() {
                                      pi['unit_cost'] = double.tryParse(v) ?? 0.0;
                                    }),
                                  ),
                                ),
                                IconButton(
                                  icon: const Icon(Icons.remove_circle_outline, color: Colors.redAccent, size: 20),
                                  onPressed: () => setSheetState(() => purchaseItems.removeAt(idx)),
                                ),
                              ],
                            ),
                          );
                        }),
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.grey.shade100,
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Column(
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text('Subtotal:', style: regularSmall),
                                  Text(c.formatCurrency(subtotal), style: regularSmall),
                                ],
                              ),
                              const SizedBox(height: 4),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text('Impuesto (${taxPct.toStringAsFixed(0)}%):', style: regularSmall),
                                  Text(c.formatCurrency(igv), style: regularSmall),
                                ],
                              ),
                              const Divider(height: 8),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text('Total Compra:', style: boldDefault),
                                  Text(c.formatCurrency(total),
                                      style: boldDefault.copyWith(color: const Color(0xFF059669))),
                                ],
                              ),
                            ],
                          ),
                        ),
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
                      backgroundColor: const Color(0xFF059669),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                    onPressed: () async {
                      if (selectedSupplierId == null || purchaseItems.isEmpty) return;

                      final data = {
                        'supplier_id': selectedSupplierId,
                        'document_type': docType,
                        'document_series': seriesCtrl.text.trim(),
                        'document_number': numCtrl.text.trim(),
                        'document_date':
                            '${selectedDate.year}-${selectedDate.month.toString().padLeft(2, '0')}-${selectedDate.day.toString().padLeft(2, '0')}',
                        'payment_method': paymentMethod,
                        'notes': notesCtrl.text.trim(),
                        'items': purchaseItems,
                      };

                      Get.back();
                      await c.registerPurchase(data);
                    },
                    child: Text('Registrar Compra', style: boldDefault.copyWith(color: Colors.white)),
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
}
