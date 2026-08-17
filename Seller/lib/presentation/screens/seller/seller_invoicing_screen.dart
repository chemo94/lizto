import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/model/seller/panel_models.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';

class SellerInvoicingScreen extends StatefulWidget {
  const SellerInvoicingScreen({super.key});
  @override
  State<SellerInvoicingScreen> createState() => _SellerInvoicingScreenState();
}

class _SellerInvoicingScreenState extends State<SellerInvoicingScreen> {
  late SellerPanelController c;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPanelController>()) {
      Get.put(SellerPanelRepo(apiClient: Get.find()));
      Get.put(SellerPanelController(repo: Get.find()));
    }
    c = Get.find<SellerPanelController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => c.loadInvoicing());
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPanelController>(
      builder: (_) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          elevation: 0,
          title: Text('Series de Facturación', style: boldLarge.copyWith(color: Colors.white)),
          centerTitle: true,
        ),
        body: c.loadingInvoicing
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : c.invoiceTypes.isEmpty
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.settings_applications_outlined, size: 64, color: MyColor.bodyMutedTextColor.withOpacity(0.3)),
                        const SizedBox(height: 12),
                        Text('Sin configuración de comprobantes', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                      ],
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: () => c.loadInvoicing(),
                    child: ListView.builder(
                      padding: EdgeInsets.all(Dimensions.space16),
                      itemCount: c.invoiceTypes.length,
                      itemBuilder: (_, i) => _buildTypeCard(c.invoiceTypes[i]),
                    ),
                  ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _showAddSeriesDialog,
          backgroundColor: MyColor.primaryColor,
          icon: const Icon(Icons.add_rounded, color: Colors.white),
          label: Text('Nueva Serie', style: boldDefault.copyWith(color: Colors.white)),
        ),
      ),
    );
  }

  Widget _buildTypeCard(InvoiceTypeModel type) {
    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space16),
      padding: EdgeInsets.all(Dimensions.space16),
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: MyColor.primaryColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.receipt_long_rounded, color: MyColor.primaryColor, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(type.name ?? '', style: boldDefault.copyWith(fontSize: 16)),
                    Text(
                      'Código SUNAT: ${type.code ?? type.sunatCode ?? "N/A"}',
                      style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const Divider(height: 24),
          if (type.series.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Text(
                'No hay series registradas para este comprobante',
                style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontStyle: FontStyle.italic),
              ),
            )
          else
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: type.series.length,
              itemBuilder: (_, idx) {
                final s = type.series[idx];
                return Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                  decoration: BoxDecoration(
                    color: MyColor.screenBgColor,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: MyColor.neutral200.withOpacity(0.5)),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.tag_rounded, size: 16, color: MyColor.primaryColor),
                          const SizedBox(width: 6),
                          Text(s.series ?? '', style: boldDefault),
                        ],
                      ),
                      Text(
                        'Correlativo Actual: ${s.currentNumber ?? 1}',
                        style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                      ),
                    ],
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  void _showAddSeriesDialog() {
    InvoiceTypeModel? selectedType = c.invoiceTypes.isNotEmpty ? c.invoiceTypes.first : null;
    final seriesCtrl = TextEditingController();
    final numberCtrl = TextEditingController(text: '1');

    showDialog(
      context: context,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              title: const Text('Agregar Nueva Serie'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Tipo de Comprobante', style: boldDefault),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<InvoiceTypeModel>(
                    value: selectedType,
                    decoration: InputDecoration(
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                    ),
                    items: c.invoiceTypes.map((t) {
                      return DropdownMenuItem(value: t, child: Text(t.name ?? ''));
                    }).toList(),
                    onChanged: (t) => setDialogState(() => selectedType = t),
                  ),
                  const SizedBox(height: 16),
                  Text('Serie (Ej. F001, B001)', style: boldDefault),
                  const SizedBox(height: 8),
                  TextField(
                    controller: seriesCtrl,
                    textCapitalization: TextCapitalization.characters,
                    maxLength: 4,
                    decoration: InputDecoration(
                      hintText: '4 caracteres',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      counterText: '',
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text('Correlativo Inicial', style: boldDefault),
                  const SizedBox(height: 8),
                  TextField(
                    controller: numberCtrl,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      hintText: '1',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('Cancelar'),
                ),
                TextButton(
                  onPressed: () async {
                    if (selectedType == null || seriesCtrl.text.trim().length < 4) {
                      Get.snackbar('Error', 'Ingrese una serie válida de 4 caracteres', backgroundColor: Colors.redAccent, colorText: Colors.white);
                      return;
                    }
                    Navigator.pop(ctx);
                    Get.dialog(const Center(child: CircularProgressIndicator(color: MyColor.primaryColor)), barrierDismissible: false);
                    
                    final success = await c.createInvoiceSeries(
                      selectedType!.id!,
                      seriesCtrl.text.trim().toUpperCase(),
                      int.tryParse(numberCtrl.text) ?? 1,
                    );
                    
                    Get.back(); // close loader
                    if (success) {
                      Get.snackbar('Éxito', 'Serie agregada correctamente', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
                      c.loadInvoicing();
                    } else {
                      Get.snackbar('Error', 'No se pudo agregar la serie', backgroundColor: Colors.redAccent, colorText: Colors.white);
                    }
                  },
                  child: Text('Guardar', style: TextStyle(color: MyColor.primaryColor, fontWeight: FontWeight.bold)),
                ),
              ],
            );
          },
        );
      },
    );
  }
}
