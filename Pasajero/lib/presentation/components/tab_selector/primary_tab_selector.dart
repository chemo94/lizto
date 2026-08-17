import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/route/route.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/services/api_client.dart';

class PrimaryTabSelector extends StatelessWidget {
  final int? selectedIndex;
  final ValueChanged<int>? onTap;

  const PrimaryTabSelector({
    super.key,
    this.selectedIndex,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    // Resolve active index: either explicitly provided, or loaded from preferences
    int activeIndex = selectedIndex ?? 0;
    if (selectedIndex == null) {
      if (Get.isRegistered<ApiClient>()) {
        final currentTab = Get.find<ApiClient>().getCurrentTab();
        activeIndex = int.tryParse(currentTab) ?? 0;
      }
    }

    // Force index in bounds (0 = Taxi, 1 = Delivery)
    if (activeIndex != 0 && activeIndex != 1) {
      activeIndex = 0;
    }

    return Container(
      height: 44,
      decoration: BoxDecoration(
        color: MyColor.colorWhite.withValues(alpha: 0.9),
        borderRadius: BorderRadius.circular(22),
        border: Border.all(
          color: Colors.white.withValues(alpha: 0.5),
          width: 1.5,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(22),
        child: Stack(
          children: [
            AnimatedAlign(
              duration: const Duration(milliseconds: 300),
              curve: Curves.easeOutBack,
              alignment: activeIndex == 0 ? Alignment.centerLeft : Alignment.centerRight,
              child: FractionallySizedBox(
                widthFactor: 0.5,
                heightFactor: 1.0,
                child: Container(
                  margin: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: MyColor.primaryColor,
                    borderRadius: BorderRadius.circular(18),
                    boxShadow: [
                      BoxShadow(
                        color: MyColor.primaryColor.withValues(alpha: 0.3),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            Row(
              children: [
                _buildTab(
                  context: context,
                  index: 0,
                  icon: Icons.local_taxi_rounded,
                  label: 'Taxi',
                  active: activeIndex == 0,
                ),
                _buildTab(
                  context: context,
                  index: 1,
                  icon: Icons.delivery_dining_rounded,
                  label: 'Delivery',
                  active: activeIndex == 1,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTab({
    required BuildContext context,
    required int index,
    required IconData icon,
    required String label,
    required bool active,
  }) {
    return Expanded(
      child: InkWell(
        onTap: () {
          if (onTap != null) {
            onTap!(index);
          } else {
            // Persist choice and navigate back to Dashboard
            if (Get.isRegistered<ApiClient>()) {
              Get.find<ApiClient>().storeCurrentTab(index.toString());
            }
            Get.offAllNamed(RouteHelper.dashboard, arguments: index);
          }
        },
        splashColor: Colors.transparent,
        highlightColor: Colors.transparent,
        child: Center(
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: 16,
                color: active ? MyColor.colorWhite : MyColor.bodyMutedTextColor,
              ),
              const SizedBox(width: 6),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: boldDefault.copyWith(
                  color: active ? MyColor.colorWhite : MyColor.primaryTextColor,
                  fontSize: Dimensions.fontMedium,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
