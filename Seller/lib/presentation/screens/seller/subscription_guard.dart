import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:intl/intl.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_package_controller.dart';
import 'package:lizto_store/data/model/seller/package_models.dart';
import 'package:lizto_store/presentation/screens/seller/seller_packages_screen.dart';

class SubscriptionGuard {
  /// Devuelve true si puede continuar; si no, muestra el modal y devuelve false.
  static Future<bool> canProceed(BuildContext context, {required int storeId}) async {
    if (storeId <= 0) return true;
    final SellerPackageController c = Get.find<SellerPackageController>();
    if (c.subscriptionsFor(storeId) == null) {
      await c.loadMySubscriptions(storeId);
    }
    if (c.hasActiveSubscriptionFor(storeId)) return true;
    await _showBlockDialog(context, c, storeId);
    return false;
  }

  static Future<void> _showBlockDialog(BuildContext context, SellerPackageController c, int storeId) async {
    final subs = c.subscriptionsFor(storeId);
    final SellerSubscriptionModel? expired = c.expiredSubscriptionFor(storeId);
    final bool loadFailed = subs == null;

    String title;
    String message;
    if (loadFailed) {
      title = 'No se pudo verificar tu suscripción';
      message = 'No pudimos confirmar el estado de tu plan.\n\nRevisa tu conexión a internet e inténtalo de nuevo.';
    } else if (expired != null) {
      final planName = expired.packageName ?? expired.package?.name ?? 'tu plan';
      final String dateLabel = expired.expiresAtDate != null
          ? DateFormat('dd/MM/yyyy').format(expired.expiresAtDate!)
          : 'una fecha anterior';
      title = 'Suscripción vencida';
      message = 'Tu suscripción al plan $planName venció el $dateLabel.\n\nRenueva tu plan para seguir enviando pedidos y usando todas las funciones del sistema.';
    } else {
      title = 'Plan inactivo';
      message = 'No tienes un plan activo para esta tienda.\n\nActiva o renueva tu suscripción para seguir enviando pedidos y usando todas las funciones del sistema.';
    }

    await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.mediumRadius)),
        title: Row(children: [
          Icon(Icons.error_outline_rounded, color: const Color(0xFFF59E0B)),
          const SizedBox(width: 10),
          Expanded(child: Text(title, style: boldLarge.copyWith(fontSize: 17))),
        ]),
        content: Text(message, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor, height: 1.4)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text(loadFailed ? 'Entendido' : 'Ahora no', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          ),
          if (!loadFailed)
            ElevatedButton(
              onPressed: () {
                Navigator.pop(ctx);
                Get.to(() => SellerPackagesScreen(storeId: storeId));
              },
              style: ElevatedButton.styleFrom(backgroundColor: MyColor.primaryColor, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius))),
              child: Text('Renovar Suscripción', style: boldDefault.copyWith(color: Colors.white)),
            ),
        ],
      ),
    );
  }
}
