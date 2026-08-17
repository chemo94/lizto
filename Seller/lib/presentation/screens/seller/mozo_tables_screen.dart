import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/model/seller/panel_models.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';
import 'package:lizto_store/presentation/screens/seller/mozo_order_screen.dart';

class MozoTablesScreen extends StatefulWidget {
  const MozoTablesScreen({super.key});

  @override
  State<MozoTablesScreen> createState() => _MozoTablesScreenState();
}

class _MozoTablesScreenState extends State<MozoTablesScreen> {
  late SellerPanelController panelCtrl;
  late SellerController sellerCtrl;
  int? selectedAreaId;

  @override
  void initState() {
    super.initState();
    Get.put(SellerPanelRepo(apiClient: Get.find()));
    panelCtrl = Get.put(SellerPanelController(repo: Get.find()));
    sellerCtrl = Get.find<SellerController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadData());
  }

  Future<void> _loadData() async {
    await panelCtrl.loadTables();
    await panelCtrl.loadPanelDashboard();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPanelController>(
      builder: (_) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          title: Column(
            children: [
              Text(
                sellerCtrl.staffName ?? 'Mozo',
                style: boldLarge.copyWith(color: Colors.white, fontSize: 16),
              ),
              Text(
                _getGreeting(),
                style: regularSmall.copyWith(color: Colors.white.withValues(alpha: 0.8), fontSize: 11),
              ),
            ],
          ),
          centerTitle: true,
          actions: [
            IconButton(
              icon: const Icon(Icons.add_shopping_cart_rounded, color: Colors.white),
              onPressed: () => Get.to(() => const MozoOrderScreen()),
              tooltip: 'Nueva Comanda',
            ),
            IconButton(
              icon: const Icon(Icons.refresh_rounded, color: Colors.white),
              onPressed: _loadData,
            ),
            IconButton(
              icon: const Icon(Icons.logout_rounded, color: Colors.white),
              onPressed: () => _confirmLogout(),
            ),
          ],
        ),
        body: panelCtrl.loadingTables
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : RefreshIndicator(
                onRefresh: _loadData,
                color: MyColor.primaryColor,
                child: _buildBody(),
              ),
      ),
    );
  }

  String _getGreeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Buenos días';
    if (hour < 18) return 'Buenas tardes';
    return 'Buenas noches';
  }

  Widget _buildBody() {
    if (panelCtrl.areas.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.table_bar_rounded, size: 64, color: MyColor.neutral300),
            const SizedBox(height: Dimensions.space16),
            Text('Sin mesas configuradas', style: boldLarge.copyWith(color: MyColor.bodyMutedTextColor)),
            const SizedBox(height: Dimensions.space8),
            Text('Contacta al administrador', style: regularDefault.copyWith(color: MyColor.neutral300)),
          ],
        ),
      );
    }

    return Column(
      children: [
        _buildStatsBar(),
        _buildAreaTabs(),
        Expanded(child: _buildTablesGrid()),
      ],
    );
  }

  Widget _buildStatsBar() {
    final dashboard = panelCtrl.dashboard;
    return Container(
      margin: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space12, Dimensions.space16, 0),
      padding: EdgeInsets.all(Dimensions.space14),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Row(
        children: [
          _StatItem(
            icon: Icons.table_bar_rounded,
            label: 'Mesas libres',
            value: _getFreeTablesCount().toString(),
            color: const Color(0xFF10B981),
          ),
          Container(width: 1, height: 32, color: MyColor.neutral200),
          _StatItem(
            icon: Icons.table_restaurant_rounded,
            label: 'Ocupadas',
            value: _getOccupiedTablesCount().toString(),
            color: const Color(0xFFF59E0B),
          ),
          Container(width: 1, height: 32, color: MyColor.neutral200),
          _StatItem(
            icon: Icons.receipt_long_rounded,
            label: 'Pedidos hoy',
            value: '${dashboard?.posTodayCount ?? 0}',
            color: MyColor.primaryColor,
          ),
        ],
      ),
    );
  }

  int _getFreeTablesCount() {
    int count = 0;
    for (var area in panelCtrl.areas) {
      for (var table in area.tables) {
        if (table.isFree) count++;
      }
    }
    return count;
  }

  int _getOccupiedTablesCount() {
    int count = 0;
    for (var area in panelCtrl.areas) {
      for (var table in area.tables) {
        if (table.isOccupied) count++;
      }
    }
    return count;
  }

  Widget _buildAreaTabs() {
    return Container(
      height: 50,
      margin: EdgeInsets.only(top: Dimensions.space12),
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: Dimensions.space16),
        children: [
          _AreaTab(
            label: 'Todas',
            isSelected: selectedAreaId == null,
            onTap: () => setState(() => selectedAreaId = null),
          ),
          ...panelCtrl.areas.map((area) => _AreaTab(
                label: area.name ?? 'Área',
                isSelected: selectedAreaId == area.id,
                onTap: () => setState(() => selectedAreaId = area.id),
              )),
        ],
      ),
    );
  }

  Widget _buildTablesGrid() {
    final areas = selectedAreaId == null
        ? panelCtrl.areas
        : panelCtrl.areas.where((a) => a.id == selectedAreaId).toList();

    return ListView(
      padding: EdgeInsets.all(Dimensions.space16),
      children: areas.map((area) => _buildAreaSection(area)).toList(),
    );
  }

  Widget _buildAreaSection(PanelAreaModel area) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: EdgeInsets.only(bottom: Dimensions.space10),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: MyColor.primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(Icons.layers_rounded, size: 18, color: MyColor.primaryColor),
              ),
              const SizedBox(width: 10),
              Text(area.name ?? 'Área', style: boldLarge.copyWith(fontSize: 16)),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: MyColor.neutral100,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${area.tables.length} mesas',
                  style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                ),
              ),
            ],
          ),
        ),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 3,
            crossAxisSpacing: 10,
            mainAxisSpacing: 10,
            childAspectRatio: 0.85,
          ),
          itemCount: area.tables.length,
          itemBuilder: (context, index) => _buildTableCard(area.tables[index]),
        ),
        SizedBox(height: Dimensions.space20),
      ],
    );
  }

  Widget _buildTableCard(PanelTableModel table) {
    final isFree = table.isFree;
    final color = isFree ? const Color(0xFF10B981) : const Color(0xFFF59E0B);

    return GestureDetector(
      onTap: () => _onTableTap(table),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.05),
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
          border: Border.all(color: color.withValues(alpha: 0.3), width: 1.5),
          boxShadow: [
            BoxShadow(color: color.withValues(alpha: 0.08), blurRadius: 8, offset: const Offset(0, 2)),
          ],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.15),
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.table_bar_rounded,
                size: 24,
                color: color,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              table.name ?? '',
              style: boldDefault.copyWith(color: MyColor.primaryTextColor, fontSize: 13),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                isFree ? 'Libre' : 'Ocupada',
                style: boldSmall.copyWith(color: color, fontSize: 10),
              ),
            ),
            if (table.capacity != null) ...[
              const SizedBox(height: 4),
              Text(
                '${table.capacity} personas',
                style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10),
              ),
            ],
            if (table.isOccupied && table.activeOrderTotal != null) ...[
              const SizedBox(height: 4),
              Text(
                'S/ ${(table.activeOrderTotal ?? 0).toStringAsFixed(2)}',
                style: boldSmall.copyWith(color: MyColor.primaryColor, fontSize: 11),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _onTableTap(PanelTableModel table) {
    Get.to(() => MozoOrderScreen(table: table));
  }

  void _confirmLogout() {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Cerrar sesión'),
        content: const Text('¿Estás seguro que deseas salir?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('Cancelar', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              Get.find<SellerController>().logout();
            },
            child: Text('Salir', style: boldDefault.copyWith(color: MyColor.redCancelTextColor)),
          ),
        ],
      ),
    );
  }
}

class _AreaTab extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  const _AreaTab({required this.label, required this.isSelected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(right: 8),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? MyColor.primaryColor : MyColor.colorWhite,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? MyColor.primaryColor : MyColor.neutral200,
          ),
        ),
        child: Text(
          label,
          style: boldDefault.copyWith(
            color: isSelected ? Colors.white : MyColor.primaryTextColor,
            fontSize: 13,
          ),
        ),
      ),
    );
  }
}

class _StatItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  const _StatItem({required this.icon, required this.label, required this.value, required this.color});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, size: 20, color: color),
          const SizedBox(height: 4),
          Text(value, style: boldLarge.copyWith(color: color, fontSize: 18)),
          Text(label, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10)),
        ],
      ),
    );
  }
}
