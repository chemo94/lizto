import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_package_controller.dart';
import 'package:lizto_store/data/repo/seller/seller_package_repo.dart';

class SellerSubscriptionsScreen extends StatefulWidget {
  final int storeId;
  const SellerSubscriptionsScreen({super.key, required this.storeId});

  @override
  State<SellerSubscriptionsScreen> createState() => _SellerSubscriptionsScreenState();
}

class _SellerSubscriptionsScreenState extends State<SellerSubscriptionsScreen> {
  late SellerPackageController _c;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPackageController>()) {
      Get.put(SellerPackageRepo(apiClient: Get.find()));
      Get.put(SellerPackageController(repo: Get.find()));
    }
    _c = Get.find<SellerPackageController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => _c.loadMySubscriptions(widget.storeId));
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPackageController>(
      builder: (c) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          title: Text('Mis Suscripciones', style: boldLarge.copyWith(color: MyColor.colorWhite)),
          centerTitle: true,
        ),
        body: c.loadingSubs
            ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
            : c.subscriptions.isEmpty
                ? Center(
                    child: Column(mainAxisSize: MainAxisSize.min, children: [
                      Icon(Icons.card_membership_rounded, size: 64, color: MyColor.bodyMutedTextColor.withOpacity(0.3)),
                      SizedBox(height: 12),
                      Text('Sin suscripciones activas', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                      SizedBox(height: 4),
                      Text('Compra un plan para empezar', style: regularSmall.copyWith(color: MyColor.neutral300)),
                    ]),
                  )
                : ListView.builder(
                    padding: EdgeInsets.all(Dimensions.space16),
                    itemCount: c.subscriptions.length,
                    itemBuilder: (_, i) {
                      final s = c.subscriptions[i];
                      final active = s.status == 'active';
                      return Container(
                        margin: EdgeInsets.only(bottom: Dimensions.space10),
                        padding: EdgeInsets.all(Dimensions.space16),
                        decoration: BoxDecoration(
                          color: MyColor.colorWhite,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: active ? const Color(0xFF10B981).withOpacity(0.3) : MyColor.neutral200),
                          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 6, offset: const Offset(0, 2))],
                        ),
                        child: Row(children: [
                          Container(width: 44, height: 44, decoration: BoxDecoration(color: active ? const Color(0xFF10B981).withOpacity(0.1) : MyColor.neutral200, borderRadius: BorderRadius.circular(12)), child: Icon(active ? Icons.check_circle_rounded : Icons.hourglass_empty_rounded, color: active ? const Color(0xFF10B981) : MyColor.bodyMutedTextColor, size: 24)),
                          SizedBox(width: Dimensions.space12),
                          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(s.packageName ?? s.package?.name ?? 'Plan', style: boldDefault),
                            SizedBox(height: 2),
                            Text(active ? 'Activo' : s.status?.toUpperCase() ?? '', style: regularSmall.copyWith(color: active ? const Color(0xFF10B981) : const Color(0xFFF59E0B), fontWeight: FontWeight.w600)),
                            if (s.expiresAt != null) Text('Expira: ${_formatDate(s.expiresAt)}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                          ])),
                        ]),
                      );
                    },
                  ),
      ),
    );
  }

  String _formatDate(String? raw) {
    if (raw == null) return '';
    try { return DateTime.parse(raw).toLocal().toString().substring(0, 10); } catch (_) { return raw; }
  }
}
