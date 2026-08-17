import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_package_controller.dart';
import 'package:lizto_store/data/repo/seller/seller_package_repo.dart';
import 'package:lizto_store/presentation/screens/seller/subscription_guard.dart';

class SellerNotifyScreen extends StatefulWidget {
  final int storeId;
  const SellerNotifyScreen({super.key, required this.storeId});

  @override
  State<SellerNotifyScreen> createState() => _SellerNotifyScreenState();
}

class _SellerNotifyScreenState extends State<SellerNotifyScreen> {
  final _titleCtrl = TextEditingController();
  final _bodyCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<SellerPackageController>()) {
      Get.put(SellerPackageRepo(apiClient: Get.find()));
      Get.put(SellerPackageController(repo: Get.find()));
    }
    Get.find<SellerPackageController>();
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _bodyCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerPackageController>(
      builder: (c) => Scaffold(
        backgroundColor: MyColor.screenBgColor,
        appBar: AppBar(
          backgroundColor: MyColor.primaryColor,
          title: Text('Notificar Clientes', style: boldLarge.copyWith(color: MyColor.colorWhite)),
          centerTitle: true,
        ),
        body: ListView(padding: EdgeInsets.all(Dimensions.space16), children: [
          Container(
            padding: EdgeInsets.all(Dimensions.space16),
            decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(16)),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('Envía una notificación push a tus clientes', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
              SizedBox(height: Dimensions.space16),
              TextField(
                controller: _titleCtrl,
                decoration: InputDecoration(labelText: 'Título', hintText: '🍕 2x1 en Pizzas', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
              ),
              SizedBox(height: Dimensions.space12),
              TextField(
                controller: _bodyCtrl,
                maxLines: 3,
                decoration: InputDecoration(labelText: 'Mensaje', hintText: 'Solo por hoy. Pide ya.', border: OutlineInputBorder(borderRadius: BorderRadius.circular(12))),
              ),
              SizedBox(height: Dimensions.space20),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: c.loadingNotify ? null : () => _send(c),
                  icon: c.loadingNotify ? SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Icon(Icons.notifications_active_rounded, size: 18),
                  label: Text('Enviar notificación', style: boldDefault.copyWith(color: MyColor.colorWhite)),
                  style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, padding: EdgeInsets.symmetric(vertical: 14), shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14))),
                ),
              ),
              if (c.notifyResult != null) ...[
                SizedBox(height: Dimensions.space16),
                Container(
                  padding: EdgeInsets.all(Dimensions.space12),
                  decoration: BoxDecoration(color: const Color(0xFF10B981).withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
                  child: Row(children: [
                    Icon(Icons.check_circle_rounded, color: const Color(0xFF10B981)),
                    SizedBox(width: 8),
                    Text('Enviado a ${c.notifyResult!.recipients ?? 0} clientes', style: boldDefault.copyWith(color: const Color(0xFF10B981))),
                  ]),
                ),
              ],
            ]),
          ),
        ]),
      ),
    );
  }

  void _send(SellerPackageController c) async {
    final title = _titleCtrl.text.trim();
    final body = _bodyCtrl.text.trim();
    if (title.isEmpty || body.isEmpty) {
      Get.snackbar('Error', 'Completa todos los campos', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
      return;
    }
    final ok = await SubscriptionGuard.canProceed(context, storeId: widget.storeId);
    if (!ok) return;
    bool ok2 = await c.sendNotification(widget.storeId, title, body);
    if (ok2) {
      _titleCtrl.clear();
      _bodyCtrl.clear();
    }
  }
}
