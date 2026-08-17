import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/model/seller/panel_models.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';

class SellerCustomersScreen extends StatefulWidget {
  const SellerCustomersScreen({super.key});
  @override
  State<SellerCustomersScreen> createState() => _SellerCustomersScreenState();
}

class _SellerCustomersScreenState extends State<SellerCustomersScreen> {
  late SellerPanelController c;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPanelController>()) {
      Get.put(SellerPanelRepo(apiClient: Get.find()));
      Get.put(SellerPanelController(repo: Get.find()));
    }
    c = Get.find<SellerPanelController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => c.loadCustomers());
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPanelController>(
      builder: (_) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(backgroundColor: MyColor.primaryColor, title: Text('Clientes', style: boldLarge.copyWith(color: Colors.white)), centerTitle: true),
        body: c.customers.isEmpty
            ? Center(child: Column(mainAxisSize: MainAxisSize.min, children: [Icon(Icons.people_outline_rounded, size: 64, color: MyColor.bodyMutedTextColor.withOpacity(0.3)), SizedBox(height: 12), Text('Sin clientes', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))]))
            : ListView.builder(
                padding: EdgeInsets.all(Dimensions.space16),
                itemCount: c.customers.length,
                itemBuilder: (_, i) => _card(c.customers[i]),
              ),
      ),
    );
  }

  Widget _card(PanelCustomerModel cust) => Container(
    margin: EdgeInsets.only(bottom: Dimensions.space10),
    padding: EdgeInsets.all(Dimensions.space14),
    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 6)]),
    child: Row(children: [
      CircleAvatar(radius: 24, backgroundColor: MyColor.primaryColor.withOpacity(0.1), child: Icon(Icons.person_rounded, color: MyColor.primaryColor)),
      SizedBox(width: Dimensions.space12),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text(cust.customerName ?? '', style: boldDefault),
        if (cust.customerPhone != null) Text(cust.customerPhone!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
      ])),
      Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
        Text('${cust.totalOrders ?? 0} pedidos', style: boldDefault.copyWith(color: MyColor.primaryColor)),
        Text('S/ ${cust.totalSpent?.toStringAsFixed(2) ?? "0"}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
      ]),
    ]),
  );
}
