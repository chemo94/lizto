import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_inventory_controller.dart';
import 'package:lizto_store/data/model/seller/inventory_models.dart';
import 'package:lizto_store/data/repo/seller/seller_inventory_repo.dart';

class SellerInventoryKardexScreen extends StatefulWidget {
  final bool isTab;
  const SellerInventoryKardexScreen({super.key, this.isTab = false});

  @override
  State<SellerInventoryKardexScreen> createState() => _SellerInventoryKardexScreenState();
}

class _SellerInventoryKardexScreenState extends State<SellerInventoryKardexScreen> {
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
      c.loadKardex();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerInventoryController>(
      builder: (_) {
        Widget content = Column(
          children: [
            _buildFiltersBar(),
            Expanded(
              child: c.loadingKardex
                  ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
                  : c.kardexList.isEmpty
                      ? _buildEmptyState()
                      : RefreshIndicator(
                          onRefresh: () => c.loadKardex(),
                          child: ListView.builder(
                            padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: 8),
                            itemCount: c.kardexList.length,
                            itemBuilder: (_, i) => _buildMovementCard(c.kardexList[i]),
                          ),
                        ),
            ),
          ],
        );

        if (widget.isTab) {
          return Scaffold(
            backgroundColor: MyColor.screenBgColor,
            body: content,
          );
        }

        return Scaffold(
          backgroundColor: MyColor.screenBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            elevation: 0,
            title: Text('Kardex de Movimientos', style: boldLarge.copyWith(color: Colors.white)),
            centerTitle: true,
          ),
          body: content,
        );
      },
    );
  }

  Widget _buildFiltersBar() {
    return Container(
      margin: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space12, Dimensions.space16, 8),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          if (c.recipeInsumos.isNotEmpty)
            DropdownButtonFormField<int?>(
              value: c.kardexItemId,
              isExpanded: true,
              items: [
                const DropdownMenuItem<int?>(value: null, child: Text('Todos los Insumos', style: regularSmall)),
                ...c.recipeInsumos.map((it) => DropdownMenuItem<int?>(
                      value: it.id,
                      child: Text(it.name ?? '', style: regularSmall, overflow: TextOverflow.ellipsis),
                    )),
              ],
              onChanged: (v) => c.filterKardexItem(v),
              decoration: InputDecoration(
                isDense: true,
                labelText: 'Filtrar por Insumo',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
              ),
            ),
          const SizedBox(height: 8),
          Row(
            children: [
              _buildTypeChip(null, 'Todos'),
              const SizedBox(width: 8),
              _buildTypeChip('entrada', 'Entradas (+)'),
              const SizedBox(width: 8),
              _buildTypeChip('salida', 'Salidas (-)'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildTypeChip(String? type, String label) {
    final isSel = c.kardexType == type;
    return Expanded(
      child: InkWell(
        onTap: () => c.filterKardexType(type),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 6),
          decoration: BoxDecoration(
            color: isSel ? const Color(0xFF475569) : Colors.grey.shade100,
            borderRadius: BorderRadius.circular(8),
          ),
          alignment: Alignment.center,
          child: Text(
            label,
            style: boldSmall.copyWith(
              color: isSel ? Colors.white : Colors.black87,
              fontSize: 11,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildMovementCard(InvKardexModel m) {
    final isEntrada = m.isEntrada;
    final color = isEntrada ? const Color(0xFF10B981) : Colors.redAccent;

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
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: color.withOpacity(0.12),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      isEntrada ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
                      color: color,
                      size: 14,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    isEntrada ? 'ENTRADA' : 'SALIDA',
                    style: boldSmall.copyWith(color: color, fontSize: 11),
                  ),
                ],
              ),
              Text(
                '${isEntrada ? '+' : ''}${(m.quantity ?? 0).toStringAsFixed(2)} ${m.item?.unit ?? ''}',
                style: boldDefault.copyWith(color: color, fontSize: 14),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            m.item?.name ?? 'Insumo #${m.itemId}',
            style: boldDefault.copyWith(fontSize: 14),
          ),
          const SizedBox(height: 2),
          Text(
            m.description ?? 'Movimiento',
            style: regularSmall.copyWith(color: Colors.grey.shade600, fontSize: 11),
          ),
          const SizedBox(height: 8),
          const Divider(height: 1),
          const SizedBox(height: 6),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Costo: ${c.formatCurrency(m.unitCost)} | Total: ${c.formatCurrency(m.totalCost)}',
                style: regularSmall.copyWith(color: Colors.grey.shade600, fontSize: 10),
              ),
              Text(
                'Saldo: ${(m.balanceStock ?? 0).toStringAsFixed(2)} ${m.item?.unit ?? ''}',
                style: boldSmall.copyWith(color: const Color(0xFF0284C7), fontSize: 11),
              ),
            ],
          ),
          if (m.createdAt != null) ...[
            const SizedBox(height: 2),
            Text(
              m.createdAt!,
              style: regularSmall.copyWith(color: Colors.grey.shade400, fontSize: 9),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.history_toggle_off_rounded, size: 64, color: Colors.grey.shade300),
          const SizedBox(height: 12),
          Text('No hay movimientos de Kardex registrados',
              style: regularDefault.copyWith(color: Colors.grey)),
        ],
      ),
    );
  }
}
