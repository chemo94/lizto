import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_inventory_controller.dart';
import 'package:lizto_store/data/model/seller/inventory_models.dart';
import 'package:lizto_store/data/repo/seller/seller_inventory_repo.dart';
import 'package:url_launcher/url_launcher.dart';

class SellerInventorySuppliersScreen extends StatefulWidget {
  final bool isTab;
  const SellerInventorySuppliersScreen({super.key, this.isTab = false});

  @override
  State<SellerInventorySuppliersScreen> createState() => _SellerInventorySuppliersScreenState();
}

class _SellerInventorySuppliersScreenState extends State<SellerInventorySuppliersScreen> {
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
      c.loadSuppliers();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerInventoryController>(
      builder: (_) {
        Widget content = Column(
          children: [
            Padding(
              padding: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space12, Dimensions.space16, 8),
              child: Container(
                height: 44,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade200),
                ),
                child: TextField(
                  controller: _searchCtrl,
                  onChanged: (v) => c.loadSuppliers(search: v.isNotEmpty ? v : null),
                  decoration: InputDecoration(
                    hintText: 'Buscar proveedor por nombre, RUC o contacto...',
                    hintStyle: regularSmall.copyWith(color: Colors.grey),
                    prefixIcon: const Icon(Icons.search, size: 20, color: Colors.grey),
                    suffixIcon: _searchCtrl.text.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear, size: 18, color: Colors.grey),
                            onPressed: () {
                              _searchCtrl.clear();
                              c.loadSuppliers();
                            },
                          )
                        : null,
                    border: InputBorder.none,
                    contentPadding: const EdgeInsets.symmetric(vertical: 10),
                  ),
                ),
              ),
            ),
            Expanded(
              child: c.loadingSuppliers
                  ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
                  : c.suppliersList.isEmpty
                      ? _buildEmptyState()
                      : RefreshIndicator(
                          onRefresh: () => c.loadSuppliers(),
                          child: ListView.builder(
                            padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: 6),
                            itemCount: c.suppliersList.length,
                            itemBuilder: (_, i) => _buildSupplierCard(c.suppliersList[i]),
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
              onPressed: () => _showSupplierForm(),
              backgroundColor: const Color(0xFF0D9488),
              icon: const Icon(Icons.person_add_rounded, color: Colors.white),
              label: Text('Nuevo Proveedor', style: boldDefault.copyWith(color: Colors.white)),
            ),
          );
        }

        return Scaffold(
          backgroundColor: MyColor.screenBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            elevation: 0,
            title: Text('Proveedores', style: boldLarge.copyWith(color: Colors.white)),
            centerTitle: true,
          ),
          body: content,
          floatingActionButton: FloatingActionButton.extended(
            onPressed: () => _showSupplierForm(),
            backgroundColor: const Color(0xFF0D9488),
            icon: const Icon(Icons.person_add_rounded, color: Colors.white),
            label: Text('Nuevo Proveedor', style: boldDefault.copyWith(color: Colors.white)),
          ),
        );
      },
    );
  }

  Widget _buildSupplierCard(InvSupplierModel s) {
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
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: const Color(0xFF0D9488).withOpacity(0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.local_shipping_rounded,
                  color: Color(0xFF0D9488),
                  size: 22,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      s.name ?? '',
                      style: boldDefault.copyWith(fontSize: 14),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        if (s.documentNumber != null && s.documentNumber!.isNotEmpty)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                            decoration: BoxDecoration(
                              color: Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Text(
                              'RUC: ${s.documentNumber}',
                              style: regularSmall.copyWith(color: Colors.grey.shade700, fontSize: 10),
                            ),
                          ),
                        if (s.contactName != null && s.contactName!.isNotEmpty) ...[
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              'Contacto: ${s.contactName}',
                              style: regularSmall.copyWith(color: Colors.grey.shade600, fontSize: 11),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (s.address != null && s.address!.isNotEmpty) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                const Icon(Icons.location_on_outlined, size: 13, color: Colors.grey),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    s.address!,
                    style: regularSmall.copyWith(color: Colors.grey.shade600, fontSize: 10),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: 8),
          const Divider(height: 1),
          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  if (s.phone != null && s.phone!.isNotEmpty) ...[
                    InkWell(
                      onTap: () => _callPhone(s.phone!),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0284C7).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.call_rounded, size: 12, color: Color(0xFF0284C7)),
                            const SizedBox(width: 4),
                            Text(s.phone!, style: boldSmall.copyWith(color: const Color(0xFF0284C7), fontSize: 11)),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(width: 6),
                    InkWell(
                      onTap: () => _openWhatsApp(s.phone!),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF10B981).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.chat_bubble_outline_rounded, size: 12, color: Color(0xFF10B981)),
                            const SizedBox(width: 4),
                            Text('WhatsApp', style: boldSmall.copyWith(color: const Color(0xFF10B981), fontSize: 11)),
                          ],
                        ),
                      ),
                    ),
                  ],
                ],
              ),
              Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.edit_outlined, size: 18, color: Colors.grey),
                    onPressed: () => _showSupplierForm(supplier: s),
                    constraints: const BoxConstraints(),
                    padding: const EdgeInsets.all(6),
                  ),
                  IconButton(
                    icon: const Icon(Icons.delete_outline, size: 18, color: Colors.redAccent),
                    onPressed: () => _confirmDeleteSupplier(s),
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
          Icon(Icons.local_shipping_outlined, size: 64, color: Colors.grey.shade300),
          const SizedBox(height: 12),
          Text('No hay proveedores registrados', style: regularDefault.copyWith(color: Colors.grey)),
        ],
      ),
    );
  }

  Future<void> _callPhone(String phone) async {
    final clean = phone.replaceAll(RegExp(r'\D'), '');
    final uri = Uri.parse('tel:$clean');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _openWhatsApp(String phone) async {
    final clean = phone.replaceAll(RegExp(r'\D'), '');
    final number = clean.startsWith('51') ? clean : '51$clean';
    final uri = Uri.parse('https://wa.me/$number');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  void _showSupplierForm({InvSupplierModel? supplier}) {
    final isEdit = supplier != null;
    final nameCtrl = TextEditingController(text: supplier?.name ?? '');
    final rucCtrl = TextEditingController(text: supplier?.documentNumber ?? '');
    final phoneCtrl = TextEditingController(text: supplier?.phone ?? '');
    final emailCtrl = TextEditingController(text: supplier?.email ?? '');
    final contactCtrl = TextEditingController(text: supplier?.contactName ?? '');
    final addressCtrl = TextEditingController(text: supplier?.address ?? '');
    final notesCtrl = TextEditingController(text: supplier?.notes ?? '');

    Get.bottomSheet(
      Container(
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
                  Text(isEdit ? 'Editar Proveedor' : 'Nuevo Proveedor', style: boldLarge.copyWith(fontSize: 18)),
                  IconButton(icon: const Icon(Icons.close), onPressed: () => Get.back()),
                ],
              ),
              const SizedBox(height: 12),
              TextField(
                controller: nameCtrl,
                decoration: InputDecoration(
                  labelText: 'Razón Social / Nombre Comercial *',
                  hintText: 'Ej: Distribuidora San Fernando S.A.C.',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                ),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: rucCtrl,
                      keyboardType: TextInputType.number,
                      decoration: InputDecoration(
                        labelText: 'RUC / DNI',
                        hintText: '20123456789',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: phoneCtrl,
                      keyboardType: TextInputType.phone,
                      decoration: InputDecoration(
                        labelText: 'Teléfono / Celular',
                        hintText: '987654321',
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
                      controller: contactCtrl,
                      decoration: InputDecoration(
                        labelText: 'Persona de Contacto',
                        hintText: 'Ej: Juan Pérez',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: TextField(
                      controller: emailCtrl,
                      keyboardType: TextInputType.emailAddress,
                      decoration: InputDecoration(
                        labelText: 'Correo Electrónico',
                        hintText: 'ventas@proveedor.com',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              TextField(
                controller: addressCtrl,
                decoration: InputDecoration(
                  labelText: 'Dirección Comercial',
                  hintText: 'Av. Los Próceres 123, Lima',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: notesCtrl,
                decoration: InputDecoration(
                  labelText: 'Notas / Condiciones de crédito / Días de entrega',
                  hintText: 'Entrega los martes y jueves...',
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
                    backgroundColor: const Color(0xFF0D9488),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  onPressed: () async {
                    final name = nameCtrl.text.trim();
                    if (name.isEmpty) return;

                    final data = {
                      if (isEdit) 'id': supplier.id,
                      'name': name,
                      'document_number': rucCtrl.text.trim(),
                      'phone': phoneCtrl.text.trim(),
                      'email': emailCtrl.text.trim(),
                      'contact_name': contactCtrl.text.trim(),
                      'address': addressCtrl.text.trim(),
                      'notes': notesCtrl.text.trim(),
                    };

                    Get.back();
                    await c.saveSupplier(data);
                  },
                  child: Text(
                    isEdit ? 'Actualizar Proveedor' : 'Guardar Proveedor',
                    style: boldDefault.copyWith(color: Colors.white),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      isScrollControlled: true,
    );
  }

  void _confirmDeleteSupplier(InvSupplierModel s) {
    Get.dialog(
      AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Eliminar Proveedor', style: boldDefault),
        content: Text('¿Estás seguro de eliminar "${s.name}"? Solo se puede eliminar si no registra compras activas.'),
        actions: [
          TextButton(onPressed: () => Get.back(), child: const Text('Cancelar')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () async {
              Get.back();
              await c.deleteSupplier(s.id!);
            },
            child: const Text('Eliminar', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }
}
