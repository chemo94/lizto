import 'package:flutter/material.dart';
import 'package:get/get.dart';

import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/presentation/components/shimmer/ride_services_shimmer.dart';
import 'package:liztogo/presentation/screens/home/widgets/service_card.dart';

class RideServiceSection extends StatelessWidget {
  const RideServiceSection({super.key});

  @override
  Widget build(BuildContext context) {
    return GetBuilder<HomeController>(
      builder: (controller) {
        final services = controller.appServicesList;

        return Container(
          padding: EdgeInsets.symmetric(
            horizontal: Dimensions.space16,
            vertical: Dimensions.space8,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.only(left: 4, bottom: 8),
                child: Text(
                  MyStrings.selectService.tr,
                  style: boldLarge.copyWith(
                    color: MyColor.getRideTitleColor(),
                    fontWeight: FontWeight.w600,
                    fontSize: Dimensions.fontLarge,
                  ),
                ),
              ),
              if (controller.isLoading) ...[
                const RideServiceShimmer(),
              ] else if (services.isNotEmpty) ...[
                Column(
                  children: [
                    for (var index = 0; index < services.length; index++) ...[
                      ServiceCard(
                        key: ValueKey(services[index].id),
                        service: services[index],
                        controller: controller,
                      ),
                      if (index < services.length - 1) const SizedBox(height: 8),
                    ],
                  ],
                ),
              ] else ...[
                Text(
                  MyStrings.noServiceAvailable.tr,
                  style: mediumSmall.copyWith(color: MyColor.redCancelTextColor),
                ),
              ],
            ],
          ),
        );
      },
    );
  }
}
