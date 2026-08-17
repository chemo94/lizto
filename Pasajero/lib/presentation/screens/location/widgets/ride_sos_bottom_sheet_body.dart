import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/data/controller/ride/ride_details/ride_details_controller.dart';
import 'package:liztogo/presentation/components/bottom-sheet/bottom_sheet_header_row.dart';
import 'package:liztogo/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';
import 'package:liztogo/presentation/components/text-form-field/custom_text_field.dart';

class RideDetailsSosBottomSheetBody extends StatelessWidget {
  RideDetailsController controller;
  String id;
  RideDetailsSosBottomSheetBody({
    super.key,
    required this.controller,
    required this.id,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const BottomSheetHeaderRow(),
        const SizedBox(height: Dimensions.space10),
        CustomTextField(
          onChanged: (v) {},
          hintText: MyStrings.sosSubTitle.tr,
          maxLines: 5,
          controller: controller.sosMsgController,
        ),
        const SizedBox(height: Dimensions.space20),
        RoundedButton(
          text: MyStrings.submit,
          press: () async {
            if (controller.sosMsgController.text.isEmpty) {
              CustomSnackBar.error(errorList: ['Please Enter Message']);
            } else {
              Get.back();
              await controller.sos(id);
            }
          },
        ),
        const SizedBox(height: Dimensions.space10),
      ],
    );
  }
}
