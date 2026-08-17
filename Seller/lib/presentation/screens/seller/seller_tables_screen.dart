import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/model/seller/panel_models.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';
import 'package:lizto_store/presentation/screens/seller/mozo_order_screen.dart';

class SellerTablesScreen extends StatefulWidget {
  const SellerTablesScreen({super.key});
  @override
  State<SellerTablesScreen> createState() => _SellerTablesScreenState();
}

class _SellerTablesScreenState extends State<SellerTablesScreen> {
  late SellerPanelController c;
  int? selectedAreaId;

  @override
  void initState() {
    super.initState();
    Get.put(SellerPanelRepo(apiClient: Get.find()));
    c = Get.put(SellerPanelController(repo: Get.find()));
    WidgetsBinding.instance.addPostFrameCallback((_) => c.loadTables());
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPanelController>(
      builder: (_) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          title: Text('Mesas', style: boldLarge.copyWith(color: Colors.white)),
          centerTitle: true,
          actions: [
            IconButton(
              icon: const Icon(Icons.add_shopping_cart_rounded, color: Colors.white),
              onPressed: () => Get.to(() => const MozoOrderScreen()),
              tooltip: 'Nueva Comanda',
            ),
            IconButton(
              icon: const Icon(Icons.layers_rounded, color: Colors.white),
              onPressed: () => _manageAreas(context),
              tooltip: 'Gestionar Zonas',
            ),
          ],
        ),
        floatingActionButton: FloatingActionButton(
          backgroundColor: MyColor.primaryColor,
          onPressed: () => _addTable(context),
          child: const Icon(Icons.add, color: Colors.white),
        ),
        body: c.loadingTables
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : c.areas.isEmpty
                ? _buildEmptyState()
                : Column(
                    children: [
                      _buildAreaTabs(),
                      _buildAreaStats(),
                      Expanded(child: _buildTablesContent()),
                    ],
                  ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.table_bar_rounded, size: 64, color: MyColor.neutral300),
            const SizedBox(height: Dimensions.space16),
            Text('Sin mesas configuradas', style: boldLarge.copyWith(color: MyColor.bodyMutedTextColor)),
            const SizedBox(height: Dimensions.space8),
            Text('Crea zonas y agrega mesas para comenzar', style: regularDefault.copyWith(color: MyColor.neutral300), textAlign: TextAlign.center),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () => _addArea(context),
              icon: const Icon(Icons.add, color: Colors.white),
              label: Text('Crear primera zona', style: boldDefault.copyWith(color: Colors.white)),
              style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildAreaTabs() {
    return Container(
      height: 50,
      color: MyColor.colorWhite,
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: 6),
        children: [
          _AreaTab(
            label: 'Todas',
            count: _totalTablesCount(),
            isSelected: selectedAreaId == null,
            onTap: () => setState(() => selectedAreaId = null),
          ),
          ...c.areas.map((area) => _AreaTab(
            label: area.name ?? 'Zona',
            count: area.tables.length,
            isSelected: selectedAreaId == area.id,
            onTap: () => setState(() => selectedAreaId = area.id),
            onLongPress: () => _showAreaOptions(area),
          )),
          _AddAreaTab(onTap: () => _addArea(context)),
        ],
      ),
    );
  }

  Widget _buildAreaStats() {
    final filteredAreas = selectedAreaId == null
        ? c.areas
        : c.areas.where((a) => a.id == selectedAreaId).toList();
    int free = 0, occupied = 0;
    for (var area in filteredAreas) {
      for (var t in area.tables) {
        if (t.isFree) free++;
        if (t.isOccupied) occupied++;
      }
    }
    return Container(
      margin: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space10, Dimensions.space16, 0),
      padding: EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Row(
        children: [
          _StatChip(icon: Icons.check_circle_outline, label: 'Libres', value: free, color: const Color(0xFF10B981)),
          Container(width: 1, height: 20, color: MyColor.neutral200),
          _StatChip(icon: Icons.hourglass_top_rounded, label: 'Ocupadas', value: occupied, color: const Color(0xFFF59E0B)),
          Container(width: 1, height: 20, color: MyColor.neutral200),
          _StatChip(icon: Icons.table_bar_rounded, label: 'Total', value: free + occupied, color: MyColor.primaryColor),
        ],
      ),
    );
  }

  Widget _buildTablesContent() {
    final areas = selectedAreaId == null
        ? c.areas
        : c.areas.where((a) => a.id == selectedAreaId).toList();

    if (areas.isEmpty || areas.every((a) => a.tables.isEmpty)) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.table_bar_rounded, size: 48, color: MyColor.neutral300),
            const SizedBox(height: 12),
            Text('Sin mesas en esta zona', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
            const SizedBox(height: 8),
            Text('Toca + para agregar una mesa', style: regularSmall.copyWith(color: MyColor.neutral300)),
          ],
        ),
      );
    }

    return ListView(
      padding: EdgeInsets.all(Dimensions.space16),
      children: areas.where((a) => a.tables.isNotEmpty).map((area) => _buildAreaSection(area)).toList(),
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
              Text(area.name ?? 'Zona', style: boldLarge.copyWith(fontSize: 16)),
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

  Widget _buildTableCard(PanelTableModel t) {
    final isFree = t.isFree;
    final color = isFree ? const Color(0xFF10B981) : const Color(0xFFF59E0B);

    return GestureDetector(
      onTap: () => _showTableOptions(t),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.05),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: color.withValues(alpha: 0.3), width: 1.5),
          boxShadow: [BoxShadow(color: color.withValues(alpha: 0.08), blurRadius: 8, offset: const Offset(0, 2))],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(color: color.withValues(alpha: 0.15), shape: BoxShape.circle),
              child: Icon(Icons.table_bar_rounded, size: 22, color: color),
            ),
            const SizedBox(height: 6),
            Text(
              t.name ?? '',
              style: boldDefault.copyWith(color: MyColor.primaryTextColor, fontSize: 12),
              textAlign: TextAlign.center,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
              decoration: BoxDecoration(color: color.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(8)),
              child: Text(isFree ? 'Libre' : 'Ocupada', style: boldSmall.copyWith(color: color, fontSize: 10)),
            ),
            if (t.capacity != null) ...[
              const SizedBox(height: 3),
              Text('${t.capacity} personas', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10)),
            ],
          ],
        ),
      ),
    );
  }

  int _totalTablesCount() {
    int count = 0;
    for (var area in c.areas) {
      count += area.tables.length;
    }
    return count;
  }

  // ── Table Management ──

  void _showTableOptions(PanelTableModel t) {
    final isFree = t.isFree;
    Get.bottomSheet(
      Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          // Handle pill
          Container(
            width: 40, height: 4,
            decoration: BoxDecoration(color: MyColor.neutral300, borderRadius: BorderRadius.circular(2)),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: (isFree ? const Color(0xFF10B981) : const Color(0xFFF59E0B)).withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  Icons.table_bar_rounded,
                  color: isFree ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(t.name ?? '', style: boldLarge.copyWith(fontSize: 17)),
                    if (t.areaName != null)
                      Text('Zona: ${t.areaName}', style: regularSmall.copyWith(color: MyColor.primaryColor)),
                    Text(
                      isFree ? 'Mesa libre' : 'Mesa ocupada · Tap para ver pedido',
                      style: regularSmall.copyWith(
                        color: isFree ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          // Primary CTA: Take / add order
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () {
                Get.back();
                Get.to(() => MozoOrderScreen(table: t));
              },
              icon: const Icon(Icons.restaurant_menu_rounded, color: Colors.white, size: 20),
              label: Text(
                isFree ? 'Tomar comanda' : 'Agregar a comanda',
                style: boldDefault.copyWith(color: Colors.white),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: MyColor.primaryColor,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
          const SizedBox(height: 10),
          // Secondary: Delete table
          SizedBox(
            width: double.infinity,
            child: OutlinedButton.icon(
              onPressed: () {
                Get.back();
                _deleteTable(t);
              },
              icon: Icon(Icons.delete_outline_rounded, color: MyColor.redCancelTextColor, size: 20),
              label: Text('Eliminar mesa', style: boldDefault.copyWith(color: MyColor.redCancelTextColor)),
              style: OutlinedButton.styleFrom(
                side: BorderSide(color: MyColor.redCancelTextColor),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
          SizedBox(height: MediaQuery.of(Get.context!).padding.bottom + 8),
        ]),
      ),
      backgroundColor: Colors.white,
      isScrollControlled: true,
    );
  }

  void _addTable(BuildContext ctx) {
    final nameCtrl = TextEditingController();
    final capCtrl = TextEditingController(text: '4');
    int? selectedArea = selectedAreaId;

    Get.bottomSheet(
      StatefulBuilder(
        builder: (ctx, setModalState) => Container(
          padding: const EdgeInsets.all(24),
          decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Nueva Mesa', style: boldLarge.copyWith(fontSize: 18)),
            const SizedBox(height: 16),
            TextField(controller: nameCtrl, decoration: InputDecoration(labelText: 'Nombre', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)))),
            const SizedBox(height: 12),
            TextField(controller: capCtrl, keyboardType: TextInputType.number, decoration: InputDecoration(labelText: 'Capacidad', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)))),
            const SizedBox(height: 12),
            DropdownButtonFormField<int>(
              value: selectedArea,
              decoration: InputDecoration(labelText: 'Zona', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
              items: [
                const DropdownMenuItem<int>(value: null, child: Text('Sin zona')),
                ...c.areas.map((a) => DropdownMenuItem<int>(value: a.id, child: Text(a.name ?? ''))),
              ],
              onChanged: (v) => setModalState(() => selectedArea = v),
            ),
            const SizedBox(height: 20),
            SizedBox(width: double.infinity, child: ElevatedButton(
              onPressed: () async {
                Get.back();
                final data = {
                  'name': nameCtrl.text,
                  'capacity': int.tryParse(capCtrl.text) ?? 4,
                  if (selectedArea != null) 'pos_area_id': selectedArea,
                };
                bool ok = await c.createTable(data);
                if (ok) {
                  c.loadTables();
                  Get.snackbar('Creado', 'Mesa creada', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
                }
              },
              child: Text('Guardar', style: boldDefault.copyWith(color: Colors.white)),
              style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor),
            )),
          ]),
        ),
      ),
      backgroundColor: Colors.white,
    );
  }

  void _deleteTable(PanelTableModel t) async {
    bool ok = await c.deleteTable(t.id ?? 0);
    if (ok) {
      c.loadTables();
      Get.snackbar('Eliminado', 'Mesa eliminada', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
    }
  }

  // ── Area Management ──

  void _addArea(BuildContext ctx) {
    final nameCtrl = TextEditingController();
    Get.bottomSheet(
      Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Nueva Zona', style: boldLarge.copyWith(fontSize: 18)),
          const SizedBox(height: 4),
          Text('Ej: Terraza, Interior, VIP, Bar...', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          const SizedBox(height: 16),
          TextField(
            controller: nameCtrl,
            autofocus: true,
            textCapitalization: TextCapitalization.words,
            decoration: InputDecoration(labelText: 'Nombre de la zona', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
          ),
          const SizedBox(height: 20),
          SizedBox(width: double.infinity, child: ElevatedButton(
            onPressed: () async {
              if (nameCtrl.text.trim().isEmpty) return;
              Get.back();
              bool ok = await c.createArea(nameCtrl.text.trim());
              if (ok) {
                Get.snackbar('Creada', 'Zona "${nameCtrl.text.trim()}" creada', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
              }
            },
            child: Text('Crear Zona', style: boldDefault.copyWith(color: Colors.white)),
            style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor),
          )),
        ]),
      ),
      backgroundColor: Colors.white,
    );
  }

  void _showAreaOptions(PanelAreaModel area) {
    Get.bottomSheet(
      Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Text(area.name ?? '', style: boldLarge.copyWith(fontSize: 18)),
          Text('${area.tables.length} mesas en esta zona', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          const SizedBox(height: Dimensions.space16),
          if (area.tables.isEmpty)
            SizedBox(width: double.infinity, child: ElevatedButton(
              onPressed: () {
                Get.back();
                _deleteArea(area);
              },
              style: ElevatedButton.styleFrom(backgroundColor: MyColor.redCancelTextColor),
              child: Text('Eliminar zona', style: boldDefault.copyWith(color: Colors.white)),
            ))
          else
            Text('No se puede eliminar: tiene mesas asignadas.\nPrimero mueve o elimina las mesas.', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor), textAlign: TextAlign.center),
        ]),
      ),
      backgroundColor: Colors.white,
    );
  }

  void _deleteArea(PanelAreaModel area) async {
    bool ok = await c.deleteArea(area.id ?? 0);
    if (ok) {
      setState(() { if (selectedAreaId == area.id) selectedAreaId = null; });
      Get.snackbar('Eliminada', 'Zona "${area.name}" eliminada', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
    }
  }

  void _manageAreas(BuildContext ctx) {
    Get.bottomSheet(
      Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(
            children: [
              Text('Zonas', style: boldLarge.copyWith(fontSize: 18)),
              const Spacer(),
              IconButton(
                onPressed: () { Get.back(); _addArea(ctx); },
                icon: Icon(Icons.add_circle_rounded, color: MyColor.primaryColor, size: 28),
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (c.areas.isEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Center(child: Text('Sin zonas creadas', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))),
            )
          else
            ...c.areas.map((area) => ListTile(
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: MyColor.primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(Icons.layers_rounded, size: 20, color: MyColor.primaryColor),
              ),
              title: Text(area.name ?? '', style: boldDefault),
              subtitle: Text('${area.tables.length} mesas', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
              trailing: IconButton(
                onPressed: () { Get.back(); _showAreaOptions(area); },
                icon: Icon(Icons.more_vert, color: MyColor.neutral500),
              ),
            )),
        ]),
      ),
      backgroundColor: Colors.white,
    );
  }
}

// ── Private Widgets ──

class _AreaTab extends StatelessWidget {
  final String label;
  final int count;
  final bool isSelected;
  final VoidCallback onTap;
  final VoidCallback? onLongPress;

  const _AreaTab({required this.label, required this.count, required this.isSelected, required this.onTap, this.onLongPress});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      onLongPress: onLongPress,
      child: Container(
        margin: const EdgeInsets.only(right: 8),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? MyColor.primaryColor : MyColor.colorWhite,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? MyColor.primaryColor : MyColor.neutral200),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(label, style: boldDefault.copyWith(
              color: isSelected ? Colors.white : MyColor.primaryTextColor,
              fontSize: 13,
            )),
            const SizedBox(width: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
              decoration: BoxDecoration(
                color: isSelected ? Colors.white.withValues(alpha: 0.25) : MyColor.neutral100,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Text(
                '$count',
                style: boldSmall.copyWith(
                  color: isSelected ? Colors.white : MyColor.bodyMutedTextColor,
                  fontSize: 11,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _AddAreaTab extends StatelessWidget {
  final VoidCallback onTap;
  const _AddAreaTab({required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(right: 8),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: MyColor.primaryColor.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: MyColor.primaryColor.withValues(alpha: 0.3), width: 1.5),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.add, size: 16, color: MyColor.primaryColor),
            const SizedBox(width: 4),
            Text('Zona', style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 13)),
          ],
        ),
      ),
    );
  }
}

class _StatChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final int value;
  final Color color;

  const _StatChip({required this.icon, required this.label, required this.value, required this.color});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 6),
          Text('$value', style: boldLarge.copyWith(color: color, fontSize: 16)),
          const SizedBox(width: 4),
          Text(label, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11)),
        ],
      ),
    );
  }
}
