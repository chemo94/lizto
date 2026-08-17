import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/presentation/screens/delivery/courier_earnings_screen.dart';

class CourierEarningsHeroCard extends StatelessWidget {
  final String walletBalance;
  final bool isOnline;
  final VoidCallback onToggleOnline;

  const CourierEarningsHeroCard({
    super.key,
    required this.walletBalance,
    required this.isOnline,
    required this.onToggleOnline,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.symmetric(
        horizontal: Dimensions.space15,
        vertical: Dimensions.space10,
      ),
      padding: const EdgeInsets.all(Dimensions.space20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDark
              ? [const Color(0xFF1E293B), const Color(0xFF0F172A)]
              : [MyColor.primaryColor, const Color(0xFF0D700B)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(Dimensions.space20),
        boxShadow: [
          BoxShadow(
            color: (isDark ? Colors.black : MyColor.primaryColor).withValues(alpha: 0.25),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header: Label & Online Switch Toggle
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(Dimensions.space8),
                    decoration: BoxDecoration(
                      color: MyColor.colorWhite.withValues(alpha: 0.15),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.account_balance_wallet_rounded,
                      color: MyColor.colorWhite,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: Dimensions.space10),
                  Text(
                    'Balance Disponible',
                    style: regularDefault.copyWith(
                      color: MyColor.colorWhite.withValues(alpha: 0.9),
                      fontSize: Dimensions.fontSmall + 1,
                    ),
                  ),
                ],
              ),
              GestureDetector(
                onTap: onToggleOnline,
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.space12,
                    vertical: Dimensions.space6,
                  ),
                  decoration: BoxDecoration(
                    color: isOnline
                        ? const Color(0xFF10B981)
                        : MyColor.colorWhite.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(Dimensions.space20),
                    border: Border.all(
                      color: isOnline
                          ? const Color(0xFF34D399)
                          : MyColor.colorWhite.withValues(alpha: 0.4),
                      width: 1.5,
                    ),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      AnimatedContainer(
                        duration: const Duration(milliseconds: 300),
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: isOnline ? MyColor.colorWhite : Colors.redAccent,
                        ),
                      ),
                      const SizedBox(width: Dimensions.space6),
                      Text(
                        isOnline ? 'CONECTADO' : 'DESCONECTADO',
                        style: boldDefault.copyWith(
                          color: MyColor.colorWhite,
                          fontSize: Dimensions.fontExtraSmall,
                          letterSpacing: 0.5,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: Dimensions.space15),

          // Main Balance Display
          Row(
            crossAxisAlignment: CrossAxisAlignment.baseline,
            textBaseline: TextBaseline.alphabetic,
            children: [
              Text(
                'S/ ',
                style: boldOverLarge.copyWith(
                  color: MyColor.colorWhite.withValues(alpha: 0.9),
                  fontSize: 22,
                ),
              ),
              Text(
                walletBalance,
                style: boldOverLarge.copyWith(
                  color: MyColor.colorWhite,
                  fontSize: 34,
                  letterSpacing: -0.5,
                ),
              ),
            ],
          ),
          const SizedBox(height: Dimensions.space15),
          Divider(color: MyColor.colorWhite.withValues(alpha: 0.2), height: 1),
          const SizedBox(height: Dimensions.space12),

          // Bottom Bar: Withdraw action & Earnings shortcut
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              GestureDetector(
                onTap: () => Get.to(() => const CourierEarningsScreen()),
                child: Row(
                  children: [
                    Icon(
                      Icons.insights_rounded,
                      color: MyColor.colorWhite.withValues(alpha: 0.9),
                      size: 18,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      'Ver reporte de ganancias',
                      style: regularDefault.copyWith(
                        color: MyColor.colorWhite,
                        fontSize: Dimensions.fontSmall,
                        decoration: TextDecoration.underline,
                      ),
                    ),
                  ],
                ),
              ),
              InkWell(
                onTap: () => Get.toNamed(RouteHelper.newDepositScreenScreen),
                borderRadius: BorderRadius.circular(Dimensions.space10),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: Dimensions.space12,
                    vertical: Dimensions.space6,
                  ),
                  decoration: BoxDecoration(
                    color: MyColor.colorWhite,
                    borderRadius: BorderRadius.circular(Dimensions.space10),
                  ),
                  child: Text(
                    'Recargar',
                    style: boldDefault.copyWith(
                      color: isDark ? MyColor.colorBlack : MyColor.primaryColor,
                      fontSize: Dimensions.fontSmall,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
