import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_inventory_controller.dart';
import 'package:lizto_store/data/repo/seller/seller_inventory_repo.dart';
import 'package:lizto_store/presentation/screens/seller/inventory/seller_inventory_items_screen.dart';
import 'package:lizto_store/presentation/screens/seller/inventory/seller_inventory_recipes_screen.dart';
import 'package:lizto_store/presentation/screens/seller/inventory/seller_inventory_purchases_screen.dart';
import 'package:lizto_store/presentation/screens/seller/inventory/seller_inventory_wastes_screen.dart';
import 'package:lizto_store/presentation/screens/seller/inventory/seller_inventory_kardex_screen.dart';
import 'package:lizto_store/presentation/screens/seller/inventory/seller_inventory_suppliers_screen.dart';

class SellerInventoryHubScreen extends StatefulWidget {
  final int initialIndex;
  const SellerInventoryHubScreen({super.key, this.initialIndex = 0});

  @override
  State<SellerInventoryHubScreen> createState() => _SellerInventoryHubScreenState();
}

class _SellerInventoryHubScreenState extends State<SellerInventoryHubScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerInventoryRepo>()) {
      Get.put(SellerInventoryRepo(apiClient: Get.find()));
    }
    if (!Get.isRegistered<SellerInventoryController>()) {
      Get.put(SellerInventoryController(repo: Get.find()));
    }

    _tabController = TabController(
      length: 6,
      vsync: this,
      initialIndex: widget.initialIndex.clamp(0, 5),
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.screenBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 20),
          onPressed: () => Get.back(),
        ),
        title: Text(
          'Inventario & Gastronomía',
          style: boldLarge.copyWith(color: Colors.white, fontSize: 18),
        ),
        centerTitle: true,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(48),
          child: Container(
            color: Colors.white,
            child: TabBar(
              controller: _tabController,
              isScrollable: true,
              labelColor: MyColor.primaryColor,
              unselectedLabelColor: Colors.grey.shade600,
              indicatorColor: MyColor.primaryColor,
              indicatorWeight: 3,
              labelStyle: boldSmall.copyWith(fontSize: 13),
              unselectedLabelStyle: regularSmall.copyWith(fontSize: 13),
              tabs: const [
                Tab(
                  icon: Icon(Icons.inventory_2_rounded, size: 18),
                  text: 'Insumos',
                ),
                Tab(
                  icon: Icon(Icons.menu_book_rounded, size: 18),
                  text: 'Recetas',
                ),
                Tab(
                  icon: Icon(Icons.shopping_cart_checkout_rounded, size: 18),
                  text: 'Compras',
                ),
                Tab(
                  icon: Icon(Icons.delete_sweep_rounded, size: 18),
                  text: 'Mermas',
                ),
                Tab(
                  icon: Icon(Icons.history_toggle_off_rounded, size: 18),
                  text: 'Kardex',
                ),
                Tab(
                  icon: Icon(Icons.local_shipping_rounded, size: 18),
                  text: 'Proveedores',
                ),
              ],
            ),
          ),
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: const [
          SellerInventoryItemsScreen(isTab: true),
          SellerInventoryRecipesScreen(isTab: true),
          SellerInventoryPurchasesScreen(isTab: true),
          SellerInventoryWastesScreen(isTab: true),
          SellerInventoryKardexScreen(isTab: true),
          SellerInventorySuppliersScreen(isTab: true),
        ],
      ),
    );
  }
}
