import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/utils/my_color.dart';
import 'package:liztogo_pro/core/utils/style.dart';
import 'package:liztogo_pro/data/controller/vehicle_verification/vehicle_verification_controller.dart';
import 'package:liztogo_pro/presentation/components/bottom-sheet/bottom_sheet_header_row.dart';
import 'package:liztogo_pro/presentation/components/bottom-sheet/custom_bottom_sheet.dart';

class VehicleBottomSheet {
  static void vehicleModelBottomSheet(
    BuildContext context,
    VehicleVerificationController controller,
  ) {
    CustomBottomSheet(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          BottomSheetHeaderRow(header: 'Seleccionar Modelo'),
          ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.of(context).size.height * 0.5,
            ),
            child: ListView.builder(
              shrinkWrap: true,
              itemCount: controller.modelList.length,
              itemBuilder: (context, index) {
                final model = controller.modelList[index];
                final isSelected = controller.selectedModel?.id == model.id;
                return ListTile(
                  title: Text(
                    model.name ?? '',
                    style: regularDefault.copyWith(
                      color: isSelected ? MyColor.primaryColor : MyColor.colorBlack,
                    ),
                  ),
                  trailing: isSelected
                      ? Icon(Icons.check_circle, color: MyColor.primaryColor)
                      : null,
                  onTap: () {
                    controller.selectModel(model);
                    Get.back();
                  },
                );
              },
            ),
          ),
        ],
      ),
    ).customBottomSheet(context);
  }

  static void vehicleYearBottomSheet(
    BuildContext context,
    VehicleVerificationController controller,
  ) {
    CustomBottomSheet(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          BottomSheetHeaderRow(header: 'Seleccionar Año'),
          ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.of(context).size.height * 0.5,
            ),
            child: ListView.builder(
              shrinkWrap: true,
              itemCount: controller.yearList.length,
              itemBuilder: (context, index) {
                final year = controller.yearList[index];
                final isSelected = controller.selectedYear?.id == year.id;
                return ListTile(
                  title: Text(
                    year.name ?? '',
                    style: regularDefault.copyWith(
                      color: isSelected ? MyColor.primaryColor : MyColor.colorBlack,
                    ),
                  ),
                  trailing: isSelected
                      ? Icon(Icons.check_circle, color: MyColor.primaryColor)
                      : null,
                  onTap: () {
                    controller.selectYear(year);
                    Get.back();
                  },
                );
              },
            ),
          ),
        ],
      ),
    ).customBottomSheet(context);
  }

  static void vehicleColorBottomSheet(
    BuildContext context,
    VehicleVerificationController controller,
  ) {
    CustomBottomSheet(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          BottomSheetHeaderRow(header: 'Seleccionar Color'),
          ConstrainedBox(
            constraints: BoxConstraints(
              maxHeight: MediaQuery.of(context).size.height * 0.5,
            ),
            child: ListView.builder(
              shrinkWrap: true,
              itemCount: controller.colorList.length,
              itemBuilder: (context, index) {
                final color = controller.colorList[index];
                final isSelected = controller.selectedColor?.id == color.id;
                return ListTile(
                  title: Text(
                    color.name ?? '',
                    style: regularDefault.copyWith(
                      color: isSelected ? MyColor.primaryColor : MyColor.colorBlack,
                    ),
                  ),
                  trailing: isSelected
                      ? Icon(Icons.check_circle, color: MyColor.primaryColor)
                      : null,
                  onTap: () {
                    controller.selectColor(color);
                    Get.back();
                  },
                );
              },
            ),
          ),
        ],
      ),
    ).customBottomSheet(context);
  }
}
