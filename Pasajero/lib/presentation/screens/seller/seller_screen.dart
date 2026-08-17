import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/seller/seller_controller.dart';
import 'package:liztogo/data/controller/delivery/seller_notification_service.dart';
import 'package:liztogo/data/repo/seller/seller_repo.dart';
import 'package:liztogo/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';
import 'package:liztogo/presentation/screens/seller/seller_orders_screen.dart';
import 'package:liztogo/presentation/screens/seller/seller_favor_create_screen.dart';
import 'package:liztogo/presentation/screens/seller/seller_menu_categories_screen.dart';
import 'package:liztogo/presentation/screens/seller/seller_product_add_edit_screen.dart';
import 'package:liztogo/presentation/screens/seller/seller_store_add_edit_screen.dart';
import 'package:liztogo/presentation/screens/seller/seller_wallet_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SellerScreen extends StatefulWidget {
  const SellerScreen({super.key});

  @override
  State<SellerScreen> createState() => _SellerScreenState();
}

class _SellerScreenState extends State<SellerScreen> {
  late SellerController _controller;
  final _emailCtrl = TextEditingController();
  final _passCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _controller = Get.put(SellerController(sellerRepo: SellerRepo(prefs: Get.find<SharedPreferences>())));
    Get.put(SellerNotificationService());
    WidgetsBinding.instance.addPostFrameCallback((_) => _controller.restoreSession());
  }

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Mi Negocio', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            automaticallyImplyLeading: !c.isLoggedIn,
            actions: c.isLoggedIn
                ? [
                    IconButton(
                      icon: Icon(Icons.logout, color: MyColor.colorWhite),
                      onPressed: () => c.logout(),
                    ),
                  ]
                : null,
          ),
          body: c.isLoggedIn ? _buildDashboard(c) : _buildLogin(c),
        );
      },
    );
  }

  Widget _buildLogin(SellerController c) {
    return SingleChildScrollView(
      padding: EdgeInsets.all(Dimensions.space20),
      child: Column(
        children: [
          SizedBox(height: 60),
          Icon(Icons.storefront_rounded, size: 80, color: MyColor.primaryColor),
          SizedBox(height: Dimensions.space16),
          Text('Vende en LiztoGo', style: boldLarge),
          SizedBox(height: 8),
          Text('Gestiona tu tienda y productos', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          SizedBox(height: 40),
          TextField(
            controller: _emailCtrl,
            decoration: InputDecoration(
              labelText: 'Correo electrónico',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
            ),
            keyboardType: TextInputType.emailAddress,
          ),
          SizedBox(height: Dimensions.space16),
          TextField(
            controller: _passCtrl,
            decoration: InputDecoration(
              labelText: 'Contraseña',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
            ),
            obscureText: true,
          ),
          if (c.errorMessage != null) ...[
            SizedBox(height: Dimensions.space12),
            Text(c.errorMessage!, style: regularSmall.copyWith(color: MyColor.redCancelTextColor)),
          ],
          SizedBox(height: Dimensions.space20),
          RoundedButton(
            text: 'Iniciar sesión',
            isLoading: c.isLoading,
            press: () => c.login(_emailCtrl.text.trim(), _passCtrl.text),
            isOutlined: false,
          ),
        ],
      ),
    );
  }

  Widget _buildDashboard(SellerController c) {
    return RefreshIndicator(
      onRefresh: () => c.loadDashboard(),
      child: ListView(
        padding: EdgeInsets.all(Dimensions.space16),
        children: [
          if (c.sellerData != null) ...[
            Text('Bienvenido, ${c.sellerData!['seller']?['name'] ?? ''}', style: boldLarge),
            SizedBox(height: Dimensions.space4),
            Text('${c.sellerData!['total_stores'] ?? 0} tiendas | ${c.sellerData!['total_products'] ?? 0} productos',
                style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          ],
          SizedBox(height: Dimensions.space16),
          GestureDetector(
            onTap: () => Get.to(() => const SellerOrdersScreen()),
            child: Container(
              padding: EdgeInsets.all(Dimensions.space16),
              decoration: BoxDecoration(
                color: MyColor.primaryColor.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
              ),
              child: Row(children: [
                Icon(Icons.receipt_long_rounded, color: MyColor.primaryColor, size: 32),
                SizedBox(width: Dimensions.space12),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Pedidos recibidos', style: boldDefault),
                  Text('Gestiona los pedidos de tus tiendas', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                ])),
                Icon(Icons.chevron_right_rounded, color: MyColor.primaryColor),
              ]),
            ),
          ),
          SizedBox(height: Dimensions.space12),
          GestureDetector(
            onTap: () => Get.to(() => SellerFavorCreateScreen(sellerToken: '')),
            child: Container(
              padding: EdgeInsets.all(Dimensions.space16),
              decoration: BoxDecoration(
                color: const Color(0xFFF59E0B).withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
              ),
              child: Row(children: [
                Icon(Icons.motorcycle_rounded, color: const Color(0xFFF59E0B), size: 32),
                SizedBox(width: Dimensions.space12),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Solicitar Repartidor', style: boldDefault),
                  Text('Entrega tus productos con un repartidor cercano', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                ])),
                Icon(Icons.chevron_right_rounded, color: const Color(0xFFF59E0B)),
              ]),
            ),
          ),
          SizedBox(height: Dimensions.space12),
          GestureDetector(
            onTap: () => Get.to(() => const SellerWalletScreen()),
            child: Container(
              padding: EdgeInsets.all(Dimensions.space16),
              decoration: BoxDecoration(
                color: const Color(0xFF10B981).withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
              ),
              child: Row(children: [
                Icon(Icons.account_balance_wallet_rounded, color: const Color(0xFF10B981), size: 32),
                SizedBox(width: Dimensions.space12),
                Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Mi Billetera', style: boldDefault),
                  Text('Administra tu saldo y retiros', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                ])),
                Icon(Icons.chevron_right_rounded, color: const Color(0xFF10B981)),
              ]),
            ),
          ),
          SizedBox(height: Dimensions.space20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Mis Tiendas', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
              IconButton(
                icon: const Icon(Icons.add_business_rounded, color: MyColor.primaryColor),
                onPressed: () => Get.to(() => const SellerStoreAddEditScreen()),
              ),
            ],
          ),
          SizedBox(height: Dimensions.space8),
          if (c.stores.isEmpty)
            Center(child: Text('No tienes tiendas registradas', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
          else
            ...c.stores.map((store) => _buildStoreCard(c, store)),
        ],
      ),
    );
  }

  Widget _buildStoreCard(SellerController c, dynamic store) {
    final storeId = store['id'] is int ? store['id'] : int.parse(store['id'].toString());
    final storeName = store['name']?.toString() ?? 'Mi Tienda';

    return GestureDetector(
      onTap: () => _showStoreOptions(c, store, storeId, storeName),
      child: Container(
        margin: EdgeInsets.only(bottom: Dimensions.space12),
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 8, offset: Offset(0, 2))],
        ),
        padding: EdgeInsets.all(Dimensions.space12),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
              child: MyImageWidget(
                imageUrl: '${c.storeImagePath}/${store['image'] ?? ''}',
                height: 64,
                width: 64,
                boxFit: BoxFit.cover,
              ),
            ),
            SizedBox(width: Dimensions.space12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(store['name'] ?? '', style: boldDefault),
                  SizedBox(height: 4),
                  Text(
                    '${store['products_count'] ?? 0} productos',
                    style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                  ),
                ],
              ),
            ),
            Icon(Icons.chevron_right_rounded, color: MyColor.bodyMutedTextColor),
          ],
        ),
      ),
    );
  }

  void _showStoreOptions(SellerController c, dynamic store, int storeId, String storeName) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(color: MyColor.neutral200, borderRadius: BorderRadius.circular(2)),
              ),
              const SizedBox(height: 16),
              Text(storeName, style: boldLarge.copyWith(fontSize: 18)),
              Text('¿Qué quieres gestionar?', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
              const SizedBox(height: 20),
              _StoreOptionTile(
                icon: Icons.inventory_2_rounded,
                color: MyColor.primaryColor,
                title: 'Productos',
                subtitle: 'Ver, agregar y editar productos',
                onTap: () {
                  Get.back();
                  Get.to(() => _SellerProductsScreen(storeId: storeId));
                },
              ),
              const SizedBox(height: 10),
              _StoreOptionTile(
                icon: Icons.category_rounded,
                color: const Color(0xFF7C3AED),
                title: 'Categorías del menú',
                subtitle: 'Organiza tus productos en secciones',
                onTap: () {
                  Get.back();
                  Get.to(() => SellerMenuCategoriesScreen(storeId: storeId, storeName: storeName));
                },
              ),
              const SizedBox(height: 10),
              _StoreOptionTile(
                icon: Icons.edit_note_rounded,
                color: const Color(0xFF0D9488),
                title: 'Editar Negocio',
                subtitle: 'Modifica los datos y horarios de tu tienda',
                onTap: () {
                  Get.back();
                  Get.to(() => SellerStoreAddEditScreen(store: store));
                },
              ),
              const SizedBox(height: 10),
              _StoreOptionTile(
                icon: Icons.delete_forever_rounded,
                color: MyColor.redCancelTextColor,
                title: 'Eliminar Negocio',
                subtitle: 'Borra esta tienda definitivamente',
                onTap: () {
                  Get.back();
                  showDialog(
                    context: context,
                    builder: (alertCtx) => AlertDialog(
                      title: const Text('Eliminar Tienda'),
                      content: const Text('¿Estás seguro de que deseas eliminar este negocio y todos sus productos?'),
                      actions: [
                        TextButton(
                          onPressed: () => Navigator.pop(alertCtx),
                          child: const Text('Cancelar'),
                        ),
                        TextButton(
                          onPressed: () async {
                            Navigator.pop(alertCtx);
                            final success = await c.deleteStore(storeId);
                            if (success) {
                              Get.snackbar('Eliminado', 'Tienda eliminada con éxito',
                                  backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
                            }
                          },
                          child: Text('Eliminar', style: TextStyle(color: MyColor.redCancelTextColor)),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ],
          ),
        );
      },
    );
  }
}

class _StoreOptionTile extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _StoreOptionTile({
    required this.icon,
    required this.color,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withValues(alpha: 0.15)),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: boldDefault),
                  const SizedBox(height: 2),
                  Text(subtitle, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                ],
              ),
            ),
            Icon(Icons.arrow_forward_ios_rounded, size: 16, color: color),
          ],
        ),
      ),
    );
  }
}

class _SellerProductsScreen extends StatefulWidget {
  final int storeId;
  const _SellerProductsScreen({required this.storeId});

  @override
  State<_SellerProductsScreen> createState() => _SellerProductsScreenState();
}

class _SellerProductsScreenState extends State<_SellerProductsScreen> {
  final Set<int> _togglingIds = {};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<SellerController>().loadProducts(widget.storeId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Productos', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
          ),
          floatingActionButton: FloatingActionButton.extended(
            backgroundColor: MyColor.primaryColor,
            onPressed: () => Get.to(() => SellerProductAddEditScreen(storeId: widget.storeId)),
            icon: const Icon(Icons.add, color: MyColor.colorWhite),
            label: Text('Agregar', style: boldDefault.copyWith(color: MyColor.colorWhite)),
          ),
          body: c.isLoading
              ? const Center(child: CircularProgressIndicator())
              : c.products.isEmpty
                  ? Center(child: Text('Sin productos', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
                  : RefreshIndicator(
                      onRefresh: () => c.loadProducts(widget.storeId),
                      child: ListView.builder(
                        padding: EdgeInsets.all(Dimensions.space16),
                        itemCount: c.products.length,
                        itemBuilder: (_, i) {
                          final p = c.products[i];
                          return GestureDetector(
                            onTap: () => Get.to(() => SellerProductAddEditScreen(storeId: widget.storeId, product: p)),
                            child: Container(
                              margin: EdgeInsets.only(bottom: Dimensions.space10),
                              padding: EdgeInsets.all(Dimensions.space12),
                              decoration: BoxDecoration(
                                color: MyColor.colorWhite,
                                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.04),
                                    blurRadius: 6,
                                    offset: const Offset(0, 2),
                                  )
                                ],
                              ),
                              child: Row(
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                                    child: MyImageWidget(
                                      imageUrl: '${c.productImagePath}/${p.image}',
                                      height: 56,
                                      width: 56,
                                      boxFit: BoxFit.cover,
                                    ),
                                  ),
                                  SizedBox(width: Dimensions.space12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(p.name ?? '', style: boldDefault),
                                        SizedBox(height: 4),
                                        Text(
                                          'S/ ${p.finalPrice.toStringAsFixed(2)}',
                                          style: boldDefault.copyWith(color: MyColor.primaryColor),
                                        ),
                                      ],
                                    ),
                                  ),
                                  if (_togglingIds.contains(p.id))
                                    Padding(
                                      padding: EdgeInsets.all(Dimensions.space10),
                                      child: SizedBox(
                                        height: 20,
                                        width: 20,
                                        child: CircularProgressIndicator(strokeWidth: 2, color: MyColor.primaryColor),
                                      ),
                                    )
                                  else
                                    Switch(
                                      value: p.status == 1,
                                      activeTrackColor: MyColor.primaryColor,
                                      onChanged: (val) async {
                                        setState(() => _togglingIds.add(p.id!));
                                        final repo = c.sellerRepo;
                                        final response = await repo.toggleProductStatus(p.id!);
                                        setState(() => _togglingIds.remove(p.id!));
                                        if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
                                          setState(() => p.status = val ? 1 : 0);
                                          Get.snackbar('Actualizado', val ? 'Producto habilitado' : 'Producto deshabilitado',
                                              backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite, snackPosition: SnackPosition.BOTTOM);
                                        } else {
                                          Get.snackbar('Error', 'No se pudo actualizar el producto',
                                              backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite, snackPosition: SnackPosition.BOTTOM);
                                        }
                                      },
                                    ),
                                  IconButton(
                                    icon: Icon(Icons.delete_outline, color: MyColor.redCancelTextColor, size: 22),
                                    onPressed: () {
                                      showDialog(
                                        context: context,
                                        builder: (ctx) => AlertDialog(
                                          title: const Text('Eliminar Producto'),
                                          content: const Text('¿Estás seguro de que deseas eliminar este producto?'),
                                          actions: [
                                            TextButton(
                                              onPressed: () => Navigator.pop(ctx),
                                              child: const Text('Cancelar'),
                                            ),
                                            TextButton(
                                              onPressed: () async {
                                                Navigator.pop(ctx);
                                                final success = await c.deleteProduct(p.id!, widget.storeId);
                                                if (success) {
                                                  Get.snackbar('Eliminado', 'Producto eliminado con éxito',
                                                      backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
                                                }
                                              },
                                              child: Text('Eliminar', style: TextStyle(color: MyColor.redCancelTextColor)),
                                            ),
                                          ],
                                        ),
                                      );
                                    },
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
        );
      },
    );
  }
}
