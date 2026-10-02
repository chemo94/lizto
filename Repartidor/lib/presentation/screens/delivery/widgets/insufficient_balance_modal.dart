import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';

class InsufficientBalanceModal extends StatelessWidget {
  final double currentBalance;
  final double minRecharge;
  final String? reason;

  const InsufficientBalanceModal({
    super.key,
    this.currentBalance = 0.0,
    this.minRecharge = 8.0,
    this.reason,
  });

  static Future<void> show({
    required BuildContext context,
    double currentBalance = 0.0,
    double minRecharge = 8.0,
    String? reason,
  }) async {
    return showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => InsufficientBalanceModal(
        currentBalance: currentBalance,
        minRecharge: minRecharge,
        reason: reason,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(Dimensions.space25)),
      ),
      padding: const EdgeInsets.symmetric(horizontal: Dimensions.space20, vertical: Dimensions.space25),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Icon Badge
            Center(
              child: Container(
                width: 70,
                height: 70,
                decoration: BoxDecoration(
                  color: Colors.amber.shade100,
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.account_balance_wallet_rounded, size: 36, color: Colors.amber.shade800),
              ),
            ),

            const SizedBox(height: Dimensions.space20),

            // Title
            Text(
              'Recarga Requerida',
              style: boldExtraLarge.copyWith(fontSize: 22),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: Dimensions.space8),

            // Explanation based on Lizto's Economic Policy
            Text(
              reason ??
                  'Has finalizado tu saldo promocional inicial de S/ 15.00 donde conservaste el 100% de tus ganancias. '
                  'Para continuar recibiendo pedidos individuales y lotes de alta demanda, realiza una recarga mínima.',
              style: regularDefault.copyWith(
                color: isDark ? Colors.white70 : Colors.black87,
                fontSize: Dimensions.fontDefault,
                height: 1.4,
              ),
              textAlign: TextAlign.center,
            ),

            const SizedBox(height: Dimensions.space20),

            // Balance Details Card
            Container(
              padding: const EdgeInsets.all(Dimensions.space15),
              decoration: BoxDecoration(
                color: isDark ? const Color(0xFF0F172A) : Colors.grey.shade50,
                borderRadius: BorderRadius.circular(Dimensions.space15),
                border: Border.all(color: isDark ? Colors.white12 : Colors.grey.shade200),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceAround,
                children: [
                  Column(
                    children: [
                      Text(
                        'Saldo Actual',
                        style: regularDefault.copyWith(fontSize: Dimensions.fontSmall, color: Colors.grey),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'S/ ${currentBalance.toStringAsFixed(2)}',
                        style: boldDefault.copyWith(
                          fontSize: Dimensions.fontLarge,
                          color: currentBalance <= 0 ? Colors.redAccent : Colors.amber.shade800,
                        ),
                      ),
                    ],
                  ),
                  Container(width: 1, height: 40, color: Colors.grey.shade300),
                  Column(
                    children: [
                      Text(
                        'Recarga Mínima',
                        style: regularDefault.copyWith(fontSize: Dimensions.fontSmall, color: Colors.grey),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'S/ ${minRecharge.toStringAsFixed(2)}',
                        style: boldDefault.copyWith(
                          fontSize: Dimensions.fontLarge,
                          color: const Color(0xFF16A34A),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: Dimensions.space25),

            // Action: Recharge Now Button
            ElevatedButton(
              onPressed: () {
                Get.back();
                Get.toNamed(RouteHelper.newDepositScreenScreen);
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: MyColor.primaryColor,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: Dimensions.space14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.space12)),
                elevation: 2,
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.add_card_rounded, size: 20),
                  const SizedBox(width: Dimensions.space8),
                  Text(
                    'Recargar Billetera Ahora',
                    style: boldDefault.copyWith(fontSize: Dimensions.fontDefault, color: Colors.white),
                  ),
                ],
              ),
            ),

            const SizedBox(height: Dimensions.space10),

            // Close button
            TextButton(
              onPressed: () => Get.back(),
              child: Text(
                'Entendido, más tarde',
                style: regularDefault.copyWith(color: Colors.grey),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
