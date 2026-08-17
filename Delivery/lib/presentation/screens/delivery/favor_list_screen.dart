import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/favor_controller.dart';
import 'package:lizto_delivery/data/repo/delivery/favor_repo.dart';
import 'package:lizto_delivery/data/services/api_client.dart';
import 'package:lizto_delivery/presentation/components/animated_screen_entrance.dart';
import 'package:lizto_delivery/presentation/screens/delivery/favor_tracking_screen.dart';

class FavorListScreen extends StatefulWidget {
  const FavorListScreen({super.key});

  @override
  State<FavorListScreen> createState() => _FavorListScreenState();
}

class _FavorListScreenState extends State<FavorListScreen> {
  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<FavorController>()) {
      Get.put(FavorController(favorRepo: FavorRepo(apiClient: Get.find<ApiClient>())));
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<FavorController>().loadFavors();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<FavorController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Historial de Favores', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
          ),
          body: c.isLoading
              ? const Center(child: CircularProgressIndicator())
              : c.favors.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.volunteer_activism_rounded, size: 64, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3)),
                          SizedBox(height: Dimensions.space12),
                          Text('Sin favores solicitados', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: () => c.loadFavors(),
                      child: ListView.builder(
                        padding: EdgeInsets.all(Dimensions.space16),
                        itemCount: c.favors.length,
                        itemBuilder: (_, i) {
                          final favor = c.favors[i];
                          String dateStr = '';
                          if (favor.createdAt != null && favor.createdAt!.length >= 10) {
                            dateStr = favor.createdAt!.substring(0, 10);
                          }
                          return GestureDetector(
                            onTap: () {
                              if (favor.isActive) {
                                Get.to(() => FavorTrackingScreen(favorId: favor.id ?? 0));
                              }
                            },
                            child: Container(
                              margin: EdgeInsets.only(bottom: Dimensions.space10),
                              padding: EdgeInsets.all(Dimensions.space12),
                              decoration: BoxDecoration(
                                color: MyColor.colorWhite,
                                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
                                border: favor.isActive
                                    ? Border.all(color: MyColor.primaryColor.withValues(alpha: 0.3))
                                    : null,
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    padding: EdgeInsets.all(Dimensions.space10),
                                    decoration: BoxDecoration(
                                      color: favor.statusColor.withValues(alpha: 0.1),
                                      borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                                    ),
                                    child: Icon(favor.statusIcon, color: favor.statusColor, size: 24),
                                  ),
                                  SizedBox(width: Dimensions.space12),
                                  Expanded(
                                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                      Row(children: [
                                        Icon(favor.typeIcon, size: 14, color: MyColor.bodyMutedTextColor),
                                        SizedBox(width: 4),
                                        Text(favor.typeLabel, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                                        SizedBox(width: 8),
                                        Text(favor.orderNo ?? '', style: boldDefault),
                                      ]),
                                      SizedBox(height: 2),
                                      Text(favor.description ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 1, overflow: TextOverflow.ellipsis),
                                      SizedBox(height: 4),
                                      if (favor.total != null)
                                        Text('S/ \${favor.total!.toStringAsFixed(2)}', style: boldDefault.copyWith(color: MyColor.primaryColor)),
                                    ]),
                                  ),
                                  Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                                    Container(
                                      padding: EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: Dimensions.space4),
                                      decoration: BoxDecoration(
                                        color: favor.statusColor.withValues(alpha: 0.1),
                                        borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                                      ),
                                      child: Text(favor.statusLabel, style: regularSmall.copyWith(fontWeight: FontWeight.w600, color: favor.statusColor, fontSize: 10)),
                                    ),
                                    SizedBox(height: 4),
                                    Text(dateStr, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                                  ]),
                                ],
                              ),
                            ),
                          ).animatedStagger(index: i);
                        },
                      ),
                    ),
        );
      },
    );
  }
}
