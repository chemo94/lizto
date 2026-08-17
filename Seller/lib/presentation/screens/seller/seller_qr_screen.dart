import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_package_controller.dart';
import 'package:lizto_store/data/repo/seller/seller_package_repo.dart';
import 'package:lizto_store/presentation/screens/seller/subscription_guard.dart';

class SellerQrScreen extends StatefulWidget {
  final int storeId;
  const SellerQrScreen({super.key, required this.storeId});

  @override
  State<SellerQrScreen> createState() => _SellerQrScreenState();
}

class _SellerQrScreenState extends State<SellerQrScreen> {
  late SellerPackageController _c;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPackageController>()) {
      Get.put(SellerPackageRepo(apiClient: Get.find()));
      Get.put(SellerPackageController(repo: Get.find()));
    }
    _c = Get.find<SellerPackageController>();
    WidgetsBinding.instance.addPostFrameCallback((_) => _init());
  }

  Future<void> _init() async {
    final ok = await SubscriptionGuard.canProceed(context, storeId: widget.storeId);
    if (ok) {
      _c.loadQr(widget.storeId);
    }
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPackageController>(
      builder: (c) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          title: Text('QR de Tienda', style: boldLarge.copyWith(color: MyColor.colorWhite)),
          centerTitle: true,
        ),
        body: Center(
          child: c.loadingQr
              ? const CircularProgressIndicator(color: MyColor.primaryColor)
              : c.storeQr?.qrUrl != null
                  ? Padding(
                      padding: EdgeInsets.all(Dimensions.space24),
                      child: Column(mainAxisSize: MainAxisSize.min, children: [
                        Text('Escanea para ver el menú', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                        SizedBox(height: Dimensions.space20),
                        Container(
                          padding: EdgeInsets.all(Dimensions.space16),
                          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.1), blurRadius: 20)]),
                          child: Image.network(c.storeQr!.qrUrl!, height: 260, width: 260, fit: BoxFit.contain, loadingBuilder: (_, child, progress) => progress == null ? child : SizedBox(height: 260, width: 260, child: Center(child: CircularProgressIndicator(color: MyColor.primaryColor)))),
                        ),
                        SizedBox(height: Dimensions.space20),
                        Text('Comparte este QR con tus clientes\npara que accedan a tu menú', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), textAlign: TextAlign.center),
                      ]),
                    )
                  : Column(mainAxisSize: MainAxisSize.min, children: [
                      Icon(Icons.qr_code_2_rounded, size: 64, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3)),
                      SizedBox(height: 12),
                      Text('No se pudo generar el QR', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                    ]),
        ),
      ),
    );
  }
}
