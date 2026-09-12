import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/model/seller/panel_models.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';
import 'package:lizto_store/presentation/screens/seller/seller_cash_screen.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/data/services/download_service.dart';
import 'package:lizto_store/presentation/components/snack_bar/show_custom_snackbar.dart';

class SellerBillingScreen extends StatefulWidget {
  const SellerBillingScreen({super.key});
  @override
  State<SellerBillingScreen> createState() => _SellerBillingScreenState();
}

class _SellerBillingScreenState extends State<SellerBillingScreen> with SingleTickerProviderStateMixin {
  late SellerPanelController c;
  late TabController _tabCtrl;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPanelController>()) {
      Get.put(SellerPanelRepo(apiClient: Get.find()));
      Get.put(SellerPanelController(repo: Get.find()));
    }
    c = Get.find<SellerPanelController>();
    _tabCtrl = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) => c.loadBilling());
  }

  @override
  void dispose() {
    _tabCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPanelController>(
      builder: (_) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          elevation: 0,
          title: Text('Facturación y Cobros', style: boldLarge.copyWith(color: Colors.white)),
          centerTitle: true,
          bottom: TabBar(
            controller: _tabCtrl,
            indicatorColor: Colors.white,
            labelColor: Colors.white,
            unselectedLabelColor: Colors.white.withOpacity(0.6),
            tabs: const [
              Tab(text: 'Pendientes', icon: Icon(Icons.hourglass_empty_rounded)),
              Tab(text: 'Cobrados', icon: Icon(Icons.check_circle_outline_rounded)),
            ],
          ),
        ),
        body: c.loadingBilling
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : Column(
                children: [
                  _buildCashStatusBanner(),
                  Expanded(
                    child: TabBarView(
                      controller: _tabCtrl,
                      children: [
                        _buildPendingTab(),
                        _buildPaidTab(),
                      ],
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  Widget _buildCashStatusBanner() {
    final isCashOpen = c.billingData?.isCashOpen == true;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      color: isCashOpen ? const Color(0xFFDCFCE7) : const Color(0xFFFEE2E2),
      child: Row(
        children: [
          Icon(
            isCashOpen ? Icons.check_circle_rounded : Icons.warning_amber_rounded,
            color: isCashOpen ? const Color(0xFF15803D) : const Color(0xFF991B1B),
            size: 20,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              isCashOpen ? 'Caja Abierta — Lista para procesar pagos' : 'Caja Cerrada — Debe abrir una caja para cobrar pedidos',
              style: boldDefault.copyWith(
                color: isCashOpen ? const Color(0xFF15803D) : const Color(0xFF991B1B),
                fontSize: 13,
              ),
            ),
          ),
          if (!isCashOpen) ...[
            const SizedBox(width: 8),
            ElevatedButton(
              onPressed: () => Get.to(() => const SellerCashScreen()),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF991B1B),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: const Text('Abrir Caja', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
            ),
          ],
        ],
      ),
    );
  }

  void _showCashClosedDialog() {
    Get.defaultDialog(
      title: 'Caja Cerrada',
      titleStyle: boldLarge.copyWith(color: const Color(0xFF991B1B)),
      content: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        child: Text(
          'Debe abrir una caja chica antes de registrar pagos o transacciones.',
          textAlign: TextAlign.center,
          style: regularDefault.copyWith(color: MyColor.primaryTextColor),
        ),
      ),
      textConfirm: 'Ir a Caja',
      confirmTextColor: Colors.white,
      buttonColor: MyColor.primaryColor,
      textCancel: 'Cancelar',
      cancelTextColor: MyColor.bodyMutedTextColor,
      onConfirm: () {
        Get.back();
        Get.to(() => const SellerCashScreen());
      },
    );
  }

  Widget _buildPendingTab() {
    final pending = c.billingData?.pending ?? [];
    if (pending.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.receipt_long_outlined, size: 64, color: MyColor.bodyMutedTextColor.withOpacity(0.3)),
            const SizedBox(height: 12),
            Text('No hay cobros pendientes', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => c.loadBilling(),
      child: ListView.builder(
        padding: EdgeInsets.all(Dimensions.space16),
        itemCount: pending.length,
        itemBuilder: (_, i) => _buildOrderCard(pending[i], isPending: true),
      ),
    );
  }

  Widget _buildPaidTab() {
    final paid = c.billingData?.paid ?? [];
    if (paid.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.check_circle_outline_rounded, size: 64, color: MyColor.bodyMutedTextColor.withOpacity(0.3)),
            const SizedBox(height: 12),
            Text('No hay cobros registrados hoy', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => c.loadBilling(),
      child: ListView.builder(
        padding: EdgeInsets.all(Dimensions.space16),
        itemCount: paid.length,
        itemBuilder: (_, i) => _buildOrderCard(paid[i], isPending: false),
      ),
    );
  }

  Widget _buildOrderCard(BillingOrderModel order, {required bool isPending}) {
    final isCashOpen = c.billingData?.isCashOpen == true;
    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space12),
      padding: EdgeInsets.all(Dimensions.space14),
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
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Orden ${order.orderNo ?? ''}',
                style: boldDefault.copyWith(fontSize: 16),
              ),
              Text(
                'S/ ${order.total?.toStringAsFixed(2) ?? '0.00'}',
                style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 16),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Icon(Icons.person_outline_rounded, size: 16, color: MyColor.bodyMutedTextColor),
              const SizedBox(width: 6),
              Expanded(
                child: Text(
                  order.customerName ?? 'Cliente Varios',
                  style: regularDefault.copyWith(color: MyColor.primaryTextColor),
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Row(
            children: [
              Icon(Icons.table_bar_outlined, size: 16, color: MyColor.bodyMutedTextColor),
              const SizedBox(width: 6),
              Text(
                order.table?.isNotEmpty == true ? 'Mesa: ${order.table}' : 'Para llevar / Delivery',
                style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
              ),
            ],
          ),
          if (order.invoice != null) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                const Icon(Icons.receipt_rounded, size: 16, color: Color(0xFF10B981)),
                const SizedBox(width: 6),
                Text(
                  'Comprobante: ${order.invoice}',
                  style: boldDefault.copyWith(color: const Color(0xFF10B981)),
                ),
              ],
            ),
          ],
          if (isPending) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () => _downloadPreCuenta(order),
                    icon: const Icon(Icons.print_rounded, size: 18, color: Color(0xFF0284C7)),
                    label: Text('Pre-cuenta', style: boldDefault.copyWith(color: const Color(0xFF0284C7), fontSize: 13)),
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: Color(0xFF0284C7)),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: () {
                      if (!isCashOpen) {
                        _showCashClosedDialog();
                      } else {
                        _showPaymentDialog(order);
                      }
                    },
                    icon: const Icon(Icons.payment_rounded, color: Colors.white, size: 18),
                    label: Text('Cobrar Pedido', style: boldDefault.copyWith(color: Colors.white, fontSize: 13)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: isCashOpen ? MyColor.primaryColor : Colors.grey,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    ),
                  ),
                ),
              ],
            ),
          ] else ...[
            const SizedBox(height: 8),
            Align(
              alignment: Alignment.centerRight,
              child: Text(
                'Cobrado: ${order.paidAt ?? ''}',
                style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
              ),
            ),
            if (order.sunatInvoiceId != null) ...[
              const SizedBox(height: 10),
              const Divider(height: 1),
              const SizedBox(height: 10),
              Text('Descargar comprobante:', style: boldSmall.copyWith(color: MyColor.primaryTextColor)),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _buildDownloadChip(
                    label: 'Ticket',
                    icon: Icons.receipt_long_rounded,
                    color: const Color(0xFF0284C7),
                    onTap: () => _downloadDocument(order, 'ticket'),
                  ),
                  _buildDownloadChip(
                    label: 'PDF A4',
                    icon: Icons.picture_as_pdf_rounded,
                    color: const Color(0xFFDC2626),
                    onTap: () => _downloadDocument(order, 'a4'),
                  ),
                  _buildDownloadChip(
                    label: 'XML',
                    icon: Icons.code_rounded,
                    color: const Color(0xFF7C3AED),
                    onTap: () => _downloadDocument(order, 'xml'),
                  ),
                  _buildDownloadChip(
                    label: 'CDR',
                    icon: Icons.verified_user_rounded,
                    color: const Color(0xFF059669),
                    onTap: () => _downloadDocument(order, 'cdr'),
                  ),
                ],
              ),
            ],
          ],
        ],
      ),
    );
  }

  void _downloadPreCuenta(BillingOrderModel order) async {
    final url = '${UrlContainer.baseUrl}seller/panel/billing/${order.id}/ticket';
    final filename = 'PreCuenta_${(order.orderNo ?? '${order.id}').replaceAll(RegExp(r'[^a-zA-Z0-9_-]'), '_')}.pdf';

    CustomSnackBar.success(
      successList: ['Descargando Pre-cuenta...'],
    );

    bool success = await DownloadService.downloadPDF(
      url: url,
      fileName: filename,
    );

    if (success) {
      CustomSnackBar.success(
        successList: ['Pre-cuenta descargada correctamente'],
      );
    } else {
      CustomSnackBar.error(
        errorList: ['No se pudo descargar la pre-cuenta'],
      );
    }
  }

  void _downloadDocument(BillingOrderModel order, String type) async {
    if (order.sunatInvoiceId == null) return;

    String url = '';
    String filename = '';
    final invName = (order.invoice ?? 'INV_${order.sunatInvoiceId}').replaceAll(RegExp(r'[^a-zA-Z0-9_-]'), '_');

    if (type == 'ticket') {
      url = '${UrlContainer.baseUrl}seller/panel/invoicing/invoice/${order.sunatInvoiceId}/pdf/ticket';
      filename = 'Ticket_$invName.pdf';
    } else if (type == 'a4') {
      url = '${UrlContainer.baseUrl}seller/panel/invoicing/invoice/${order.sunatInvoiceId}/pdf/a4';
      filename = 'Comprobante_$invName.pdf';
    } else if (type == 'xml') {
      url = '${UrlContainer.baseUrl}seller/panel/invoicing/invoice/${order.sunatInvoiceId}/xml';
      filename = 'Comprobante_$invName.xml';
    } else if (type == 'cdr') {
      url = '${UrlContainer.baseUrl}seller/panel/invoicing/invoice/${order.sunatInvoiceId}/cdr';
      filename = 'CDR_$invName.zip';
    }

    CustomSnackBar.success(
      successList: ['Descargando $type...'],
    );

    bool success = await DownloadService.downloadPDF(
      url: url,
      fileName: filename,
    );

    if (success) {
      CustomSnackBar.success(
        successList: ['Archivo $filename descargado correctamente'],
      );
    } else {
      CustomSnackBar.error(
        errorList: ['No se pudo descargar el archivo $type'],
      );
    }
  }

  Widget _buildDownloadChip({
    required String label,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(8),
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 14, color: color),
            const SizedBox(width: 4),
            Text(
              label,
              style: boldSmall.copyWith(color: color, fontSize: 12),
            ),
          ],
        ),
      ),
    );
  }

  void _showPaymentDialog(BillingOrderModel order) {
    String selectedMethod = 'cash';
    bool emitInvoice = false;
    InvoiceTypeModel? selectedType;
    InvoiceSeriesModel? selectedSeries;
    String docType = '1'; // 1 = DNI, 6 = RUC
    String detailMode = 'detailed';
    final docNumCtrl = TextEditingController();
    final nameCtrl = TextEditingController(text: order.customerName);
    final addressCtrl = TextEditingController();
    final consumptionDescriptionCtrl = TextEditingController(text: 'Consumo');

    final invoiceTypes = c.billingData?.invoiceTypes ?? [];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            void triggerLookup(String val) {
              final cleanVal = val.trim();
              if ((docType == '1' && cleanVal.length == 8) || (docType == '6' && cleanVal.length == 11)) {
                c.lookupSunat(cleanVal, docType).then((res) {
                  if (res != null && res['nombre'] != null) {
                    setModalState(() {
                      nameCtrl.text = res['nombre'].toString();
                      addressCtrl.text = res['direccion']?.toString() ?? '';
                    });
                  }
                });
              }
            }

            return GetBuilder<SellerPanelController>(
              builder: (controller) {
                return Container(
                  padding: EdgeInsets.fromLTRB(20, 16, 20, MediaQuery.of(context).viewInsets.bottom + 32),
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Center(
                          child: Container(
                            width: 40,
                            height: 4,
                            decoration: BoxDecoration(color: MyColor.neutral200, borderRadius: BorderRadius.circular(2)),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text('Cobrar Orden ${order.orderNo}', style: boldLarge.copyWith(fontSize: 18)),
                        Text('Total a pagar: S/ ${order.total?.toStringAsFixed(2)}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
                        const Divider(height: 24),
                        Text('Método de Pago', style: boldDefault),
                        const SizedBox(height: 10),
                        DropdownButtonFormField<String>(
                          value: selectedMethod,
                          decoration: InputDecoration(
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                          ),
                          items: const [
                            DropdownMenuItem(value: 'cash', child: Text('Efectivo')),
                            DropdownMenuItem(value: 'card', child: Text('Tarjeta de Crédito/Débito')),
                            DropdownMenuItem(value: 'bank', child: Text('Transferencia Bancaria')),
                            DropdownMenuItem(value: 'yape', child: Text('Yape')),
                            DropdownMenuItem(value: 'plin', child: Text('Plin')),
                          ],
                          onChanged: (val) => setModalState(() => selectedMethod = val ?? 'cash'),
                        ),
                        const SizedBox(height: 16),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('¿Emitir Comprobante Electrónico?', style: boldDefault),
                            Switch(
                              value: emitInvoice,
                              activeColor: MyColor.primaryColor,
                              onChanged: (val) {
                                setModalState(() {
                                  emitInvoice = val;
                                  if (val && invoiceTypes.isNotEmpty) {
                                    selectedType = invoiceTypes.first;
                                    if (selectedType!.series.isNotEmpty) {
                                      selectedSeries = selectedType!.series.first;
                                    }
                                  }
                                });
                              },
                            ),
                          ],
                        ),
                        if (emitInvoice) ...[
                          const SizedBox(height: 12),
                          Text('Detalle que verá el cliente', style: boldDefault),
                          const SizedBox(height: 8),
                          SegmentedButton<String>(
                            segments: const [
                              ButtonSegment(value: 'detailed', icon: Icon(Icons.format_list_bulleted_rounded), label: Text('Por ítems')),
                              ButtonSegment(value: 'consumption', icon: Icon(Icons.restaurant_rounded), label: Text('Por consumo')),
                            ],
                            selected: {detailMode},
                            onSelectionChanged: (value) => setModalState(() => detailMode = value.first),
                          ),
                          if (detailMode == 'consumption') ...[
                            const SizedBox(height: 10),
                            TextField(
                              controller: consumptionDescriptionCtrl,
                              maxLength: 250,
                              decoration: InputDecoration(
                                labelText: 'Descripción del consumo',
                                hintText: 'Ej.: Consumo en restaurante',
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              ),
                            ),
                          ],
                          const SizedBox(height: 12),
                          Text('Tipo de Comprobante', style: boldDefault),
                          const SizedBox(height: 8),
                          DropdownButtonFormField<InvoiceTypeModel>(
                            value: selectedType,
                            decoration: InputDecoration(
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                            ),
                            items: invoiceTypes.map((type) {
                              return DropdownMenuItem(value: type, child: Text(type.name ?? ''));
                            }).toList(),
                            onChanged: (type) {
                              setModalState(() {
                                selectedType = type;
                                selectedSeries = type?.series.isNotEmpty == true ? type?.series.first : null;
                                if (type?.code == '01') {
                                  docType = '6'; // RUC for Factura
                                } else {
                                  docType = '1'; // DNI for Boleta
                                }
                              });
                            },
                          ),
                          if (selectedType != null && selectedType!.series.isNotEmpty) ...[
                            const SizedBox(height: 12),
                            Text('Serie', style: boldDefault),
                            const SizedBox(height: 8),
                            DropdownButtonFormField<InvoiceSeriesModel>(
                              value: selectedSeries,
                              decoration: InputDecoration(
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                                contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                              ),
                              items: selectedType!.series.map((s) {
                                return DropdownMenuItem(value: s, child: Text('${s.series} (Correlativo: ${s.currentNumber})'));
                              }).toList(),
                              onChanged: (s) => setModalState(() => selectedSeries = s),
                            ),
                          ],
                          const SizedBox(height: 12),
                          Text('Tipo de Documento del Cliente', style: boldDefault),
                          const SizedBox(height: 8),
                          DropdownButtonFormField<String>(
                            value: docType,
                            decoration: InputDecoration(
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                            ),
                            items: const [
                              DropdownMenuItem(value: '1', child: Text('DNI')),
                              DropdownMenuItem(value: '6', child: Text('RUC')),
                            ],
                            onChanged: (val) {
                              setModalState(() {
                                docType = val ?? '1';
                              });
                              triggerLookup(docNumCtrl.text);
                            },
                          ),
                          const SizedBox(height: 12),
                          Text('Número de Documento', style: boldDefault),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: TextField(
                                  controller: docNumCtrl,
                                  keyboardType: TextInputType.number,
                                  decoration: InputDecoration(
                                    hintText: docType == '1' ? 'DNI (8 dígitos)' : 'RUC (11 dígitos)',
                                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                                  ),
                                  onChanged: (val) => triggerLookup(val),
                                ),
                              ),
                              const SizedBox(width: 8),
                              ElevatedButton(
                                onPressed: controller.isLookupLoading ? null : () => triggerLookup(docNumCtrl.text),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: MyColor.primaryColor,
                                  padding: const EdgeInsets.all(14),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                                ),
                                child: controller.isLookupLoading ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.search_rounded, color: Colors.white, size: 20),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text('Nombre / Razón Social del Cliente', style: boldDefault),
                          const SizedBox(height: 8),
                          TextField(
                            controller: nameCtrl,
                            decoration: InputDecoration(
                              hintText: 'Ingrese nombre o razón social',
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                          ),
                          const SizedBox(height: 12),
                          Text('Dirección del Cliente', style: boldDefault),
                          const SizedBox(height: 8),
                          TextField(
                            controller: addressCtrl,
                            maxLines: 2,
                            decoration: InputDecoration(
                              hintText: 'Dirección fiscal o domicilio',
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                          ),
                        ],
                        const SizedBox(height: 24),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: () async {
                              final docNum = docNumCtrl.text.trim();
                              final name = nameCtrl.text.trim();
                              final address = addressCtrl.text.trim();
                              final consumptionDescription = consumptionDescriptionCtrl.text.trim();

                              // Strict validation if a document number was entered
                              if (docNum.isNotEmpty) {
                                if (docType == '1' && docNum.length != 8) {
                                  Get.snackbar('Documento Inválido', 'El DNI debe tener exactamente 8 dígitos', backgroundColor: Colors.redAccent, colorText: Colors.white);
                                  return;
                                }
                                if (docType == '6' && docNum.length != 11) {
                                  Get.snackbar('Documento Inválido', 'El RUC debe tener exactamente 11 dígitos', backgroundColor: Colors.redAccent, colorText: Colors.white);
                                  return;
                                }
                                if (name.isEmpty || name == 'Cliente Varios' || name == 'Sin Nombre' || name == 'Cliente General') {
                                  Get.snackbar('Cliente Requerido', 'Al ingresar un DNI o RUC, debe colocar el nombre completo o razón social del cliente', backgroundColor: Colors.redAccent, colorText: Colors.white);
                                  return;
                                }
                              }

                              if (emitInvoice) {
                                if (detailMode == 'consumption' && consumptionDescription.isEmpty) {
                                  Get.snackbar('Descripción requerida', 'Indique el texto que debe aparecer como consumo', backgroundColor: Colors.redAccent, colorText: Colors.white);
                                  return;
                                }
                                if (selectedSeries == null) {
                                  Get.snackbar('Error', 'Seleccione una serie válida para facturar', backgroundColor: Colors.redAccent, colorText: Colors.white);
                                  return;
                                }
                                if (docNum.isEmpty) {
                                  Get.snackbar('Documento Requerido', 'Ingrese el DNI o RUC del cliente para emitir el comprobante', backgroundColor: Colors.redAccent, colorText: Colors.white);
                                  return;
                                }
                                if (docType == '1' && docNum.length != 8) {
                                  Get.snackbar('DNI Inválido', 'El DNI para la boleta debe tener 8 dígitos', backgroundColor: Colors.redAccent, colorText: Colors.white);
                                  return;
                                }
                                if (docType == '6' && docNum.length != 11) {
                                  Get.snackbar('RUC Inválido', 'El RUC para la factura debe tener 11 dígitos', backgroundColor: Colors.redAccent, colorText: Colors.white);
                                  return;
                                }
                                if (name.isEmpty || name == 'Cliente Varios' || name == 'Sin Nombre' || name == 'Cliente General') {
                                  Get.snackbar('Nombre Requerido', 'Ingrese el nombre o razón social del cliente para el comprobante', backgroundColor: Colors.redAccent, colorText: Colors.white);
                                  return;
                                }
                              }

                              Navigator.pop(ctx);
                              Get.dialog(const Center(child: CircularProgressIndicator(color: MyColor.primaryColor)), barrierDismissible: false);

                              bool success = false;
                              if (emitInvoice) {
                                success = await controller.generateInvoice(
                                  order.id!,
                                  selectedSeries!.id!,
                                  selectedMethod,
                                  docType: docType,
                                  docNum: docNum,
                                  name: name,
                                  address: address,
                                  detailMode: detailMode,
                                  consumptionDescription: detailMode == 'consumption' ? consumptionDescription : null,
                                );
                              } else {
                                success = await controller.payBillingOrder(
                                  order.id!,
                                  selectedMethod,
                                  docType: docType,
                                  docNum: docNum,
                                  name: name,
                                  address: address,
                                  detailMode: detailMode,
                                  consumptionDescription: detailMode == 'consumption' ? consumptionDescription : null,
                                );
                              }

                              Get.back(); // close loading dialog
                              if (success) {
                                Get.snackbar('Éxito', 'Pago registrado correctamente', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
                                controller.loadBilling();
                              }
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: MyColor.primaryColor,
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                            child: Text('Confirmar Pago', style: boldDefault.copyWith(color: Colors.white, fontSize: 16)),
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            );
          },
        );
      },
    );
  }
}
