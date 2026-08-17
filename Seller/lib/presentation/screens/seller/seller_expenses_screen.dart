import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/model/seller/panel_models.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';

class SellerExpensesScreen extends StatefulWidget {
  const SellerExpensesScreen({super.key});
  @override
  State<SellerExpensesScreen> createState() => _SellerExpensesScreenState();
}

class _SellerExpensesScreenState extends State<SellerExpensesScreen> {
  late SellerPanelController c;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPanelController>()) {
      Get.put(SellerPanelRepo(apiClient: Get.find()));
      Get.put(SellerPanelController(repo: Get.find()));
    }
    c = Get.find<SellerPanelController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => c.loadExpenses());
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPanelController>(
      builder: (_) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          elevation: 0,
          title: Text('Control de Gastos', style: boldLarge.copyWith(color: Colors.white)),
          centerTitle: true,
        ),
        body: c.loadingExpenses
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : c.expensesList.isEmpty
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.money_off_rounded, size: 64, color: MyColor.bodyMutedTextColor.withOpacity(0.3)),
                        const SizedBox(height: 12),
                        Text('Sin gastos registrados', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                      ],
                    ),
                  )
                : RefreshIndicator(
                    onRefresh: () => c.loadExpenses(),
                    child: ListView.builder(
                      padding: EdgeInsets.all(Dimensions.space16),
                      itemCount: c.expensesList.length,
                      itemBuilder: (_, i) => _buildExpenseCard(c.expensesList[i]),
                    ),
                  ),
        floatingActionButton: FloatingActionButton.extended(
          onPressed: _showAddExpenseSheet,
          backgroundColor: MyColor.primaryColor,
          icon: const Icon(Icons.add_rounded, color: Colors.white),
          label: Text('Registrar Gasto', style: boldDefault.copyWith(color: Colors.white)),
        ),
      ),
    );
  }

  Widget _buildExpenseCard(ExpenseModel expense) {
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
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.redAccent.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.trending_down_rounded, color: Colors.redAccent, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: MyColor.screenBgColor,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        expense.category?.toUpperCase() ?? 'OTROS',
                        style: boldExtraSmall.copyWith(color: MyColor.primaryTextColor, fontSize: 10),
                      ),
                    ),
                    Text(
                      '- S/ ${expense.amount?.toStringAsFixed(2) ?? '0.00'}',
                      style: boldDefault.copyWith(color: Colors.redAccent, fontSize: 16),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  expense.description ?? '',
                  style: semiBoldDefault.copyWith(fontSize: 14),
                ),
                if (expense.provider?.isNotEmpty == true || expense.invoiceNumber?.isNotEmpty == true) ...[
                  const SizedBox(height: 4),
                  Text(
                    '${expense.provider ?? ''} ${expense.invoiceNumber?.isNotEmpty == true ? "#${expense.invoiceNumber}" : ""}'.trim(),
                    style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                  ),
                ],
                const SizedBox(height: 6),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      expense.expenseDate != null
                          ? expense.expenseDate!.split('T').first
                          : '',
                      style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                    ),
                    Text(
                      expense.paymentMethod == 'cash'
                          ? 'Efectivo'
                          : expense.paymentMethod == 'card'
                              ? 'Tarjeta'
                              : expense.paymentMethod == 'bank'
                                  ? 'Transferencia'
                                  : expense.paymentMethod?.toUpperCase() ?? 'EFECTIVO',
                      style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          IconButton(
            icon: const Icon(Icons.delete_outline_rounded, color: Colors.redAccent, size: 20),
            onPressed: () => _confirmDeleteExpense(expense),
          ),
        ],
      ),
    );
  }

  void _confirmDeleteExpense(ExpenseModel expense) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Eliminar Gasto'),
        content: Text('¿Está seguro de eliminar el gasto por S/ ${expense.amount?.toStringAsFixed(2)}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancelar')),
          TextButton(
            onPressed: () async {
              Navigator.pop(ctx);
              Get.dialog(const Center(child: CircularProgressIndicator(color: MyColor.primaryColor)), barrierDismissible: false);
              
              final success = await c.removeExpense(expense.id!);
              
              Get.back(); // close loader
              if (success) {
                Get.snackbar('Eliminado', 'Gasto eliminado con éxito', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
                c.loadExpenses();
              } else {
                Get.snackbar('Error', 'No se pudo eliminar el gasto', backgroundColor: Colors.redAccent, colorText: Colors.white);
              }
            },
            child: const Text('Eliminar', style: TextStyle(color: Colors.redAccent)),
          ),
        ],
      ),
    );
  }

  void _showAddExpenseSheet() {
    String category = 'Servicios';
    final amountCtrl = TextEditingController();
    final descCtrl = TextEditingController();
    final providerCtrl = TextEditingController();
    final invoiceCtrl = TextEditingController();
    final notesCtrl = TextEditingController();
    String paymentMethod = 'cash';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
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
                        width: 40, height: 4,
                        decoration: BoxDecoration(color: MyColor.neutral200, borderRadius: BorderRadius.circular(2)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text('Registrar Gasto', style: boldLarge.copyWith(fontSize: 18)),
                    const Divider(height: 24),
                    Text('Categoría', style: boldDefault),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      value: category,
                      decoration: InputDecoration(
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'Servicios', child: Text('Servicios Públicos')),
                        DropdownMenuItem(value: 'Insumos', child: Text('Insumos / Mercadería')),
                        DropdownMenuItem(value: 'Alquiler', child: Text('Alquiler de Local')),
                        DropdownMenuItem(value: 'Personal', child: Text('Sueldos / Personal')),
                        DropdownMenuItem(value: 'Mantenimiento', child: Text('Mantenimiento')),
                        DropdownMenuItem(value: 'Otros', child: Text('Otros Gastos')),
                      ],
                      onChanged: (val) => setModalState(() => category = val ?? 'Otros'),
                    ),
                    const SizedBox(height: 16),
                    Text('Monto (S/)', style: boldDefault),
                    const SizedBox(height: 8),
                    TextField(
                      controller: amountCtrl,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                      decoration: InputDecoration(
                        hintText: '0.00',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text('Descripción', style: boldDefault),
                    const SizedBox(height: 8),
                    TextField(
                      controller: descCtrl,
                      decoration: InputDecoration(
                        hintText: 'Ej. Compra de verduras / Pago de luz',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text('Proveedor (Opcional)', style: boldDefault),
                    const SizedBox(height: 8),
                    TextField(
                      controller: providerCtrl,
                      decoration: InputDecoration(
                        hintText: 'Nombre de empresa o persona',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text('N° Comprobante (Opcional)', style: boldDefault),
                    const SizedBox(height: 8),
                    TextField(
                      controller: invoiceCtrl,
                      decoration: InputDecoration(
                        hintText: 'Ej. F001-000123',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text('Método de Pago', style: boldDefault),
                    const SizedBox(height: 8),
                    DropdownButtonFormField<String>(
                      value: paymentMethod,
                      decoration: InputDecoration(
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'cash', child: Text('Efectivo')),
                        DropdownMenuItem(value: 'card', child: Text('Tarjeta')),
                        DropdownMenuItem(value: 'bank', child: Text('Transferencia')),
                      ],
                      onChanged: (val) => setModalState(() => paymentMethod = val ?? 'cash'),
                    ),
                    const SizedBox(height: 16),
                    Text('Notas (Opcional)', style: boldDefault),
                    const SizedBox(height: 8),
                    TextField(
                      controller: notesCtrl,
                      maxLines: 2,
                      decoration: InputDecoration(
                        hintText: 'Detalles adicionales',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () async {
                          final amt = double.tryParse(amountCtrl.text);
                          if (amt == null || amt <= 0 || descCtrl.text.trim().isEmpty) {
                            Get.snackbar('Error', 'Ingrese un monto y descripción válidos', backgroundColor: Colors.redAccent, colorText: Colors.white);
                            return;
                          }
                          Navigator.pop(ctx);
                          Get.dialog(const Center(child: CircularProgressIndicator(color: MyColor.primaryColor)), barrierDismissible: false);
                          
                          final success = await c.registerExpense({
                            'category': category,
                            'amount': amt,
                            'description': descCtrl.text.trim(),
                            'provider': providerCtrl.text.trim(),
                            'invoice_number': invoiceCtrl.text.trim(),
                            'payment_method': paymentMethod,
                            'notes': notesCtrl.text.trim(),
                          });
                          
                          Get.back(); // close loader
                          if (success) {
                            Get.snackbar('Éxito', 'Gasto registrado correctamente', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
                            c.loadExpenses();
                          } else {
                            Get.snackbar('Error', 'No se pudo registrar el gasto', backgroundColor: Colors.redAccent, colorText: Colors.white);
                          }
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: MyColor.primaryColor,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: Text('Registrar Gasto', style: boldDefault.copyWith(color: Colors.white, fontSize: 16)),
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
  }
}
