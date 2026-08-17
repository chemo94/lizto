import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/ride/ride_bid_list/ride_bid_list_controller.dart';
import 'package:liztogo/data/repo/ride/ride_repo.dart';
import 'package:liztogo/presentation/components/app-bar/custom_appbar.dart';
import 'package:liztogo/presentation/components/divider/custom_spacer.dart';
import 'package:liztogo/presentation/components/no_data.dart';
import 'package:liztogo/presentation/components/shimmer/ride_shimmer.dart';
import 'package:liztogo/presentation/screens/ride_bid_list/widget/bid_info_card.dart';
import 'package:flutter/material.dart';

class RideBidListScreen extends StatefulWidget {
  const RideBidListScreen({super.key});

  @override
  State<RideBidListScreen> createState() => _RideBidListScreenState();
}

class _RideBidListScreenState extends State<RideBidListScreen> {
  @override
  void initState() {
    Get.put(RideRepo(apiClient: Get.find()));
    final controller = Get.put(RideBidListController(repo: Get.find()));
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((time) {
      controller.initialData(Get.arguments);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.secondaryScreenBgColor,
      appBar: CustomAppBar(
        title: MyStrings.availableBids,
        backBtnPress: () {
          Get.back();
        },
      ),
      body: GetBuilder<RideBidListController>(
        builder: (controller) {
          return RefreshIndicator(
            color: MyColor.primaryColor,
            backgroundColor: MyColor.colorWhite,
            onRefresh: () async {
              controller.getRideBidList(
                controller.ride.id.toString(),
                isShouldLoading: false,
              );
            },
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                spaceDown(Dimensions.space10),
                SizedBox(
                  height: 30,
                  child: Stack(
                    children: [
                      Align(
                        alignment: Alignment.center,
                        child: LinearProgressIndicator(
                          color: MyColor.primaryColor,
                          borderRadius: BorderRadius.circular(
                            Dimensions.mediumRadius,
                          ),
                        ),
                      ),
                      Positioned.fill(
                        child: Align(
                          alignment: Alignment.center,
                          child: Container(
                            width: 120,
                            height: 20,
                            decoration: BoxDecoration(
                              color: MyColor.screenBgColor,
                              borderRadius: BorderRadius.circular(2),
                            ),
                            child: Center(
                              child: Text(
                                MyStrings.findingDrivers.tr,
                                style: boldDefault.copyWith(),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: controller.isLoading
                      ? ListView.separated(
                          itemCount: 10,
                          physics: const BouncingScrollPhysics(),
                          separatorBuilder: (_, __) => const SizedBox(height: Dimensions.space10),
                          itemBuilder: (_, __) => const RideShimmer(),
                        )
                      : controller.isLoading == false && controller.bids.isEmpty
                          ? NoDataWidget(fromRide: true, text: MyStrings.noBidFound)
                          : ListView.separated(
                              padding: EdgeInsets.symmetric(
                                horizontal: Dimensions.space16,
                                vertical: Dimensions.space10,
                              ),
                              physics: BouncingScrollPhysics(parent: AlwaysScrollableScrollPhysics()),
                              itemCount: controller.bids.length,
                              shrinkWrap: true,
                              separatorBuilder: (context, index) => spaceDown(Dimensions.space10),
                              itemBuilder: (context, index) => BidInfoCard(
                                bid: controller.bids[index],
                                ride: controller.ride,
                                currency: controller.defaultCurrencySymbol,
                              ),
                            ),
                )
              ],
            ),
          );
        },
      ),
    );
  }
}
