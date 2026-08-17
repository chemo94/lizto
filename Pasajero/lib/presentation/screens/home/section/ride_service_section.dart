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
              Text(
                MyStrings.selectService.tr,
                style: boldLarge.copyWith(
                  color: MyColor.getRideTitleColor(),
                  fontWeight: FontWeight.w500,
                  fontSize: Dimensions.fontLarge,
                ),
              ),
              SizedBox(height: Dimensions.space10),
              if (controller.isLoading) ...[
                const RideServiceShimmer(),
              ] else if (services.isNotEmpty) ...[
                SizedBox(
                  height: 108,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    physics: const BouncingScrollPhysics(),
                    itemCount: services.length,
                    separatorBuilder: (_, __) => const SizedBox(width: Dimensions.space10),
                    itemBuilder: (context, index) {
                      return ServiceCard(
                        service: services[index],
                        controller: controller,
                      );
                    },
                  ),
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
