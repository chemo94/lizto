import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/presentation/components/image/my_network_image_widget.dart';

class SellerMenuCategoriesScreen extends StatefulWidget {
  final int storeId;
  final String storeName;

  const SellerMenuCategoriesScreen({
    super.key,
    required this.storeId,
    required this.storeName,
  });

  @override
  State<SellerMenuCategoriesScreen> createState() => _SellerMenuCategoriesScreenState();
}

class _SellerMenuCategoriesScreenState extends State<SellerMenuCategoriesScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<SellerController>().loadMenuCategories(widget.storeId);
    });
  }

  void _showAddDialog() {
    _showCategoryDialog(null);
  }

  void _showEditDialog(Map<String, dynamic> category) {
    _showCategoryDialog(category);
  }

  void _showCategoryDialog(Map<String, dynamic>? existing) {
    final nameCtrl = TextEditingController(text: existing?['name'] ?? '');
    File? pickedImage;
    final picker = ImagePicker();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(builder: (ctx, setModalState) {
          return Container(
            padding: EdgeInsets.only(
              left: Dimensions.space20,
              right: Dimensions.space20,
              top: Dimensions.space20,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + Dimensions.space24,
            ),
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Handle bar
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: MyColor.neutral200,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: Dimensions.space16),
                Text(
                  existing == null ? 'Nueva categoría' : 'Editar categoría',
                  style: boldLarge.copyWith(fontSize: 20),
                ),
                const SizedBox(height: Dimensions.space20),

                // Image picker
                GestureDetector(
                  onTap: () async {
                    final xf = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
                    if (xf != null) setModalState(() => pickedImage = File(xf.path));
                  },
                  child: Container(
                    height: 110,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF7F8FA),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: MyColor.neutral200),
                    ),
                    child: pickedImage != null
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(16),
                            child: Image.file(pickedImage!, fit: BoxFit.cover),
                          )
                        : existing != null && (existing['image'] ?? '').toString().isNotEmpty
                            ? Stack(
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(16),
                                    child: MyImageWidget(
                                      imageUrl: '${Get.find<SellerController>().menuCategoryImagePath}/${existing['image']}',
                                      height: 110,
                                      width: double.infinity,
                                      boxFit: BoxFit.cover,
                                    ),
                                  ),
                                  Positioned(
                                    bottom: 8,
                                    right: 8,
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: Colors.black.withValues(alpha: 0.55),
                                        borderRadius: BorderRadius.circular(20),
                                      ),
                                      child: Row(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          Icon(Icons.edit_rounded, color: Colors.white, size: 13),
                                          const SizedBox(width: 4),
                                          Text('Cambiar', style: regularSmall.copyWith(color: Colors.white, fontSize: 11)),
                                        ],
                                      ),
                                    ),
                                  ),
                                ],
                              )
                            : Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.add_photo_alternate_rounded, size: 36, color: MyColor.primaryColor),
                                  const SizedBox(height: 8),
                                  Text('Agregar imagen', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                                ],
                              ),
                  ),
                ),
                const SizedBox(height: Dimensions.space16),

                // Name input
                TextField(
                  controller: nameCtrl,
                  decoration: InputDecoration(
                    labelText: 'Nombre de la categoría',
                    hintText: 'Ej: Pizzas, Bebidas, Postres…',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: BorderSide(color: MyColor.primaryColor, width: 1.5),
                    ),
                    prefixIcon: Icon(Icons.label_rounded, color: MyColor.primaryColor),
                  ),
                ),
                const SizedBox(height: Dimensions.space20),

                // Action buttons
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Get.back(),
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          side: BorderSide(color: MyColor.neutral200),
                        ),
                        child: Text('Cancelar', style: boldDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                      ),
                    ),
                    const SizedBox(width: Dimensions.space12),
                    Expanded(
                      flex: 2,
                      child: GetBuilder<SellerController>(
                        builder: (c) => ElevatedButton(
                          onPressed: c.isLoading
                              ? null
                              : () async {
                                  final name = nameCtrl.text.trim();
                                  if (name.isEmpty) {
                                    Get.snackbar('Error', 'El nombre es obligatorio',
                                        backgroundColor: MyColor.redCancelTextColor,
                                        colorText: Colors.white,
                                        snackPosition: SnackPosition.BOTTOM);
                                    return;
                                  }
                                  bool ok;
                                  if (existing == null) {
                                    ok = await c.createMenuCategory(widget.storeId, name, image: pickedImage);
                                  } else {
                                    ok = await c.updateMenuCategory(
                                      existing['id'] is int ? existing['id'] : int.parse(existing['id'].toString()),
                                      widget.storeId,
                                      name,
                                      image: pickedImage,
                                    );
                                  }
                                  if (ok) {
                                    Get.back();
                                    Get.snackbar(
                                      '¡Listo!',
                                      existing == null ? 'Categoría creada' : 'Categoría actualizada',
                                      backgroundColor: const Color(0xFF039855),
                                      colorText: Colors.white,
                                      snackPosition: SnackPosition.BOTTOM,
                                    );
                                  } else {
                                    Get.snackbar('Error', 'No se pudo guardar la categoría',
                                        backgroundColor: MyColor.redCancelTextColor,
                                        colorText: Colors.white,
                                        snackPosition: SnackPosition.BOTTOM);
                                  }
                                },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: MyColor.primaryColor,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          ),
                          child: c.isLoading
                              ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                              : Text(existing == null ? 'Crear' : 'Guardar', style: boldDefault.copyWith(color: Colors.white)),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          );
        });
      },
    );
  }

  void _confirmDelete(Map<String, dynamic> category) {
    Get.dialog(
      AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text('Eliminar categoría', style: boldLarge),
        content: Text(
          '¿Estás seguro de eliminar "${category['name']}"? Los productos asignados a esta categoría quedarán sin categoría.',
          style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
        ),
        actions: [
          TextButton(
            onPressed: () => Get.back(),
            child: Text('Cancelar', style: boldDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          ),
          ElevatedButton(
            onPressed: () async {
              Get.back();
              final c = Get.find<SellerController>();
              final id = category['id'] is int ? category['id'] : int.parse(category['id'].toString());
              final ok = await c.deleteMenuCategory(id, widget.storeId);
              if (ok) {
                Get.snackbar('Eliminado', 'Categoría eliminada correctamente',
                    backgroundColor: const Color(0xFF039855),
                    colorText: Colors.white,
                    snackPosition: SnackPosition.BOTTOM);
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: MyColor.redCancelTextColor,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: Text('Eliminar', style: boldDefault.copyWith(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: const Color(0xFFF7F8FA),
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Categorías del menú', style: boldDefault.copyWith(color: Colors.white, fontSize: 16)),
                Text(widget.storeName, style: regularSmall.copyWith(color: Colors.white.withValues(alpha: 0.75))),
              ],
            ),
            leading: IconButton(
              icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20),
              onPressed: () => Get.back(),
            ),
            actions: [
              IconButton(
                icon: const Icon(Icons.refresh_rounded, color: Colors.white),
                onPressed: () => c.loadMenuCategories(widget.storeId),
              ),
            ],
          ),
          floatingActionButton: FloatingActionButton.extended(
            onPressed: _showAddDialog,
            backgroundColor: MyColor.primaryColor,
            icon: const Icon(Icons.add_rounded, color: Colors.white),
            label: Text('Nueva categoría', style: boldDefault.copyWith(color: Colors.white, fontSize: 14)),
          ),
          body: c.isLoading && c.menuCategories.isEmpty
              ? const Center(child: CircularProgressIndicator())
              : c.menuCategories.isEmpty
                  ? _EmptyState(onAdd: _showAddDialog)
                  : ListView.separated(
                      padding: const EdgeInsets.fromLTRB(
                        Dimensions.space16,
                        Dimensions.space16,
                        Dimensions.space16,
                        100,
                      ),
                      itemCount: c.menuCategories.length,
                      separatorBuilder: (_, __) => const SizedBox(height: Dimensions.space10),
                      itemBuilder: (_, i) {
                        final cat = c.menuCategories[i] as Map<String, dynamic>;
                        final imageUrl = cat['image'] != null && (cat['image'] as String).isNotEmpty
                            ? '${c.menuCategoryImagePath}/${cat['image']}'
                            : null;
                        return _CategoryCard(
                          name: cat['name'] ?? '',
                          imageUrl: imageUrl,
                          onEdit: () => _showEditDialog(cat),
                          onDelete: () => _confirmDelete(cat),
                        );
                      },
                    ),
        );
      },
    );
  }
}

// ─── Category card ──────────────────────────────────────────────────────────

class _CategoryCard extends StatelessWidget {
  final String name;
  final String? imageUrl;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _CategoryCard({
    required this.name,
    this.imageUrl,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Thumbnail
          Container(
            height: 56,
            width: 56,
            decoration: BoxDecoration(
              color: MyColor.primaryColor.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(12),
            ),
            clipBehavior: Clip.antiAlias,
            child: imageUrl != null
                ? MyImageWidget(
                    imageUrl: imageUrl!,
                    height: 56,
                    width: 56,
                    boxFit: BoxFit.cover,
                  )
                : Icon(Icons.category_rounded, color: MyColor.primaryColor, size: 26),
          ),
          const SizedBox(width: Dimensions.space12),

          // Name
          Expanded(
            child: Text(
              name,
              style: boldDefault.copyWith(fontSize: 15),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ),

          // Actions
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              _ActionButton(
                icon: Icons.edit_rounded,
                color: MyColor.primaryColor,
                onTap: onEdit,
              ),
              const SizedBox(width: Dimensions.space8),
              _ActionButton(
                icon: Icons.delete_outline_rounded,
                color: MyColor.redCancelTextColor,
                onTap: onDelete,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _ActionButton({required this.icon, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Icon(icon, color: color, size: 18),
      ),
    );
  }
}

// ─── Empty state ─────────────────────────────────────────────────────────────

class _EmptyState extends StatelessWidget {
  final VoidCallback onAdd;
  const _EmptyState({required this.onAdd});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(Dimensions.space24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              height: 100,
              width: 100,
              decoration: BoxDecoration(
                color: MyColor.primaryColor.withValues(alpha: 0.08),
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.category_rounded, size: 46, color: MyColor.primaryColor),
            ),
            const SizedBox(height: Dimensions.space20),
            Text(
              'Sin categorías de menú',
              style: boldLarge.copyWith(fontSize: 20),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: Dimensions.space8),
            Text(
              'Crea categorías para organizar tus productos,\ncomo Pizzas, Bebidas, Postres, etc.',
              style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor, height: 1.4),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: Dimensions.space24),
            ElevatedButton.icon(
              onPressed: onAdd,
              icon: const Icon(Icons.add_rounded, color: Colors.white),
              label: Text('Crear primera categoría', style: boldDefault.copyWith(color: Colors.white)),
              style: ElevatedButton.styleFrom(
                backgroundColor: MyColor.primaryColor,
                padding: const EdgeInsets.symmetric(horizontal: Dimensions.space20, vertical: Dimensions.space14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
