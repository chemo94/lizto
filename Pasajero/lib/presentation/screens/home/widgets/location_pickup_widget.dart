import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:liztogo/core/route/route.dart';
import 'package:liztogo/core/utils/my_icons.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/data/controller/map/home_map_controller.dart';
import 'package:liztogo/presentation/components/card/inner_shadow_container.dart';
import 'package:liztogo/presentation/components/image/custom_svg_picture.dart';

import '../../../../core/utils/dimensions.dart';
import '../../../../core/utils/my_color.dart';
import '../../../../core/utils/my_strings.dart';
import '../../../../core/utils/style.dart';
import '../../../components/divider/custom_spacer.dart';

class LocationPickUpHomeWidget extends StatefulWidget {
  final HomeController controller;
  const LocationPickUpHomeWidget({super.key, required this.controller});

  @override
  State<LocationPickUpHomeWidget> createState() => _LocationPickUpHomeWidgetState();
}

class _LocationPickUpHomeWidgetState extends State<LocationPickUpHomeWidget> {
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(horizontal: Dimensions.space16),
      child: InkWell(
        onTap: () {
          widget.controller.updateIsServiceShake(false);
          Get.toNamed(RouteHelper.locationPickUpScreen, arguments: [1])?.then((v) {
            if (widget.controller.selectedLocations.length > 1) {
              widget.controller.getRideFare(defaultToCheapest: true);
              final dest = widget.controller.selectedLocations[1];
              final lat = dest.latitude;
              final lng = dest.longitude;
              if (lat != null && lng != null && lat != 0 && lng != 0) {
                try {
                  Get.find<HomeMapController>().drawRouteTo(LatLng(lat, lng));
                } catch (_) {}
              }
            }
          });
        },
        child: InnerShadowContainer(
          width: double.infinity,
          backgroundColor: MyColor.neutral50,
          borderRadius: Dimensions.largeRadius,
          blur: 6,
          offset: Offset(3, 3),
          shadowColor: MyColor.colorBlack.withValues(alpha: 0.04),
          isShadowTopLeft: true,
          isShadowBottomRight: true,
          padding: EdgeInsetsGeometry.symmetric(vertical: Dimensions.space16, horizontal: Dimensions.space16),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              CustomSvgPicture(
                image: MyIcons.location,
                color: MyColor.primaryColor,
              ),
              spaceSide(Dimensions.space10),
              Expanded(
                child: Text(
                  (widget.controller.getSelectedLocationInfoAtIndex(1)?.getFullAddress(showFull: true) ?? MyStrings.whereToGo.tr),
                  style: regularDefault.copyWith(),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
              if (widget.controller.distance > 0) ...[
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                  decoration: BoxDecoration(
                    color: MyColor.primaryColor.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    '${widget.controller.distance.toStringAsFixed(1)} km',
                    style: boldDefault.copyWith(
                      fontSize: 12,
                      color: MyColor.primaryColor,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
