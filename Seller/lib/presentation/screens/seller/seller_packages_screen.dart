import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_package_controller.dart';
import 'package:lizto_store/data/repo/seller/seller_package_repo.dart';

class SellerPackagesScreen extends StatefulWidget {
  final int storeId;
  const SellerPackagesScreen({super.key, required this.storeId});

  @override
  State<SellerPackagesScreen> createState() => _SellerPackagesScreenState();
}

class _SellerPackagesScreenState extends State<SellerPackagesScreen> {
  late SellerPackageController _c;

  @override
  void initState() {
    super.initState();
    Get.put(SellerPackageRepo(apiClient: Get.find()));
    _c = Get.put(SellerPackageController(repo: Get.find()));
    WidgetsBinding.instance.addPostFrameCallback((_) => _c.loadPackages());
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPackageController>(
      builder: (c) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          title: Text('Planes Empresariales', style: boldLarge.copyWith(color: MyColor.colorWhite)),
          centerTitle: true,
        ),
        body: c.loadingPackages
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : c.packagesFailed
                ? Center(
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      Icon(Icons.cloud_off_rounded, size: 56, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.4)),
                      SizedBox(height: Dimensions.space12),
                      Text(c.packagesError.isEmpty ? 'No se pudieron cargar los paquetes' : c.packagesError, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor), textAlign: TextAlign.center),
                      SizedBox(height: Dimensions.space16),
                      ElevatedButton.icon(
                        onPressed: () => _c.loadPackages(),
                        icon: const Icon(Icons.refresh_rounded, size: 18),
                        label: const Text('Reintentar'),
                        style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, padding: EdgeInsets.symmetric(horizontal: Dimensions.space24, vertical: Dimensions.space12), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius))),
                      ),
                    ]),
                  )
                : c.packages.isEmpty
                    ? Center(
                        child: Column(mainAxisSize: MainAxisSize.min, children: [
                          Icon(Icons.card_membership_rounded, size: 56, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3)),
                          SizedBox(height: Dimensions.space12),
                          Text('Sin paquetes disponibles', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                          SizedBox(height: Dimensions.space4),
                          Text('Contacta al administrador para activarlos', style: regularSmall.copyWith(color: MyColor.neutral300)),
                        ]),
                      )
                    : ListView.builder(
                    padding: EdgeInsets.all(Dimensions.space16),
                    itemCount: c.packages.length,
                    itemBuilder: (_, i) => _buildPackageCard(c, c.packages[i]),
                  ),
      ),
    );
  }

  Widget _buildPackageCard(SellerPackageController c, package) {
    final icon = package.icon ?? '⭐';
    final features = package.features ?? [];
    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space14),
      padding: EdgeInsets.all(Dimensions.space20),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: MyColor.primaryColor.withValues(alpha: 0.15)),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Text(icon, style: const TextStyle(fontSize: 28)),
            SizedBox(width: Dimensions.space10),
            Expanded(child: Text(package.name ?? '', style: boldLarge.copyWith(fontSize: 18))),
            Container(padding: EdgeInsets.symmetric(horizontal: 12, vertical: 6), decoration: BoxDecoration(color: MyColor.primaryColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)), child: Text('S/ ${package.price?.toStringAsFixed(2) ?? "0.00"}', style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 15))),
          ]),
          if (package.description != null) ...[
            SizedBox(height: Dimensions.space8),
            Text(package.description ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
          ],
          if (package.durationDays != null) ...[
            SizedBox(height: Dimensions.space4),
            Text('Duración: ${package.durationDays} días', style: regularSmall.copyWith(color: MyColor.primaryColor)),
          ],
          if (features.isNotEmpty) ...[
            SizedBox(height: Dimensions.space12),
            ...features.map((f) => Padding(
              padding: EdgeInsets.only(bottom: 4),
              child: Row(children: [
                Icon(Icons.check_circle_rounded, size: 16, color: const Color(0xFF10B981)),
                SizedBox(width: 8),
                Expanded(child: Text(f, style: regularSmall)),
              ]),
            )),
          ],
          SizedBox(height: Dimensions.space16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: c.purchasing ? null : () => _purchase(c, package),
              icon: c.purchasing ? SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Icon(Icons.shopping_cart_rounded, size: 18),
              label: Text('Comprar', style: boldDefault.copyWith(color: MyColor.colorWhite)),
              style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, padding: EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
            ),
          ),
        ],
      ),
    );
  }

  void _purchase(SellerPackageController c, package) {
    Get.bottomSheet(
      Container(
        padding: const EdgeInsets.all(24),
        decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
        child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Confirmar compra', style: boldLarge.copyWith(fontSize: 18)),
          SizedBox(height: Dimensions.space8),
          Text('${package.name} — S/ ${package.price?.toStringAsFixed(2) ?? "0.00"}', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          SizedBox(height: Dimensions.space20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () async {
                Get.back();
                bool ok = await c.purchasePackage(packageId: package.id ?? 0, storeId: widget.storeId);
                if (!ok) {
                  Get.snackbar('No se pudo enviar la solicitud', c.purchaseError.isEmpty ? 'Inténtalo nuevamente' : c.purchaseError, backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
                  return;
                }
                if (mounted) {
                  Get.snackbar('Solicitud enviada', 'Tu compra está pendiente de aprobación', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
                }
              },
              style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, padding: EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
              child: Text('Confirmar pago', style: boldDefault.copyWith(color: MyColor.colorWhite)),            ),
          ),
          SizedBox(height: Dimensions.space12),
        ]),
      ),
      backgroundColor: Colors.white,
    );
  }
}
