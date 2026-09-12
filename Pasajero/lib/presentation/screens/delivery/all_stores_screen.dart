import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/data/model/delivery/delivery_models.dart';
import 'package:liztogo/presentation/components/animated_screen_entrance.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';
import 'package:liztogo/presentation/screens/delivery/store_screen.dart';

class AllStoresScreen extends StatefulWidget {
  const AllStoresScreen({super.key});

  @override
  State<AllStoresScreen> createState() => _AllStoresScreenState();
}

class _AllStoresScreenState extends State<AllStoresScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<DeliveryController>().loadNearbyStores();
    });
  }

  @override
  Widget build(BuildContext context) => GetBuilder<DeliveryController>(
        builder: (c) => Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Tiendas cerca de ti', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            leading: IconButton(icon: const Icon(Icons.arrow_back, color: MyColor.colorWhite), onPressed: () => Get.back()),
          ),
          body: c.isLoading && c.nearbyStores.isEmpty
              ? const Center(child: CircularProgressIndicator(color: MyColor.primaryColor))
              : c.nearbyStores.isEmpty
                  ? Center(child: Text('No hay tiendas cerca', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
                  : ListView.separated(
                      padding: EdgeInsets.all(16),
                      itemCount: c.nearbyStores.length,
                      separatorBuilder: (_, __) => SizedBox(height: 12),
                      itemBuilder: (_, i) {
                        final s = c.nearbyStores[i];
                        return GestureDetector(
                          onTap: () => Get.to(() => StoreScreen(storeId: s.id ?? 0)),
                          child: Container(
                            padding: EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: MyColor.colorWhite,
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: Offset(0, 2))],
                            ),
                            child: Row(children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(12),
                                child: MyImageWidget(
                                  imageUrl: '${c.storeImagePath}/${s.image}',
                                  height: 64,
                                  width: 64,
                                  boxFit: BoxFit.cover,
                                ),
                              ),
                              SizedBox(width: 14),
                              Expanded(
                                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                Text(s.name ?? '', style: boldDefault.copyWith(fontSize: 15), maxLines: 1, overflow: TextOverflow.ellipsis),
                                SizedBox(height: 4),
                                Text(s.address ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 1, overflow: TextOverflow.ellipsis),
                                SizedBox(height: 4),
                                Row(children: [
                                  if (s.distanceFormatted != null) ...[
                                    Icon(Icons.location_on_rounded, size: 13, color: MyColor.primaryColor),
                                    SizedBox(width: 2),
                                    Text(s.distanceFormatted!, style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 12)),
                                    SizedBox(width: 12),
                                  ],
                                  Container(padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2), decoration: BoxDecoration(color: s.isOpenNow ? MyColor.greenSuccessColor.withValues(alpha: 0.1) : MyColor.redCancelTextColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(6)), child: Text(s.isOpenNow ? 'Abierto' : 'Cerrado', style: boldDefault.copyWith(color: s.isOpenNow ? MyColor.greenSuccessColor : MyColor.redCancelTextColor, fontSize: 10))),
                                ]),
                              ])),
                              Icon(Icons.chevron_right_rounded, color: MyColor.bodyMutedTextColor),
                            ]),
                          ),
                        ).animatedStagger(index: i);
                      },
                    ),
        ),
      );
}
