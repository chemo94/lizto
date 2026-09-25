import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/my_images.dart';
import 'package:liztogo_repartidor/core/utils/my_strings.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/core/utils/url_container.dart';
import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:liztogo_repartidor/data/controller/driver_kyc_controller/driver_kyc_controller.dart';
import 'package:liztogo_repartidor/presentation/components/card/inner_shadow_container.dart';
import 'package:liztogo_repartidor/presentation/components/divider/custom_spacer.dart';
import 'package:liztogo_repartidor/presentation/components/file_download_dialog/download_dialogue.dart';
import 'package:liztogo_repartidor/presentation/components/image/custom_svg_picture.dart';
import 'package:liztogo_repartidor/presentation/components/step_indicator/registration_step_indicator.dart';

class AlreadyVerifiedWidget extends StatefulWidget {
  final bool isPending;
  final String title;

  const AlreadyVerifiedWidget({
    super.key,
    this.isPending = false,
    this.title = MyStrings.kycUnderReviewMsg,
  });

  @override
  State<AlreadyVerifiedWidget> createState() => _AlreadyVerifiedWidgetState();
}

class _AlreadyVerifiedWidgetState extends State<AlreadyVerifiedWidget> {
  @override
  Widget build(BuildContext context) {
    return GetBuilder<DriverKycController>(
      builder: (controller) {
        return SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          child: Container(
            padding: const EdgeInsets.all(Dimensions.space20),
            margin: const EdgeInsets.all(5),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(10),
              color: MyColor.screenBgColor,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const RegistrationStepIndicator(currentStep: 2),
                const SizedBox(height: 12),
                if (controller.pendingData.isNotEmpty) ...[
                  ListView.separated(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    separatorBuilder: (context, index) {
                      return spaceDown(Dimensions.space15);
                    },
                    itemCount: controller.pendingData.length,
                    itemBuilder: (context, index) {
                      return controller.pendingData[index].value != null && controller.pendingData[index].value!.isNotEmpty
                          ? InnerShadowContainer(
                              width: double.infinity,
                              backgroundColor: MyColor.textFieldBgColor,
                              borderRadius: Dimensions.largeRadius,
                              blur: 6,
                              offset: const Offset(3, 3),
                              shadowColor: MyColor.colorBlack.withValues(
                                alpha: 0.04,
                              ),
                              isShadowTopLeft: true,
                              isShadowBottomRight: true,
                              alignment: AlignmentDirectional.centerStart,
                              padding: const EdgeInsets.symmetric(
                                horizontal: Dimensions.space10,
                                vertical: Dimensions.space10,
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                mainAxisAlignment: MainAxisAlignment.start,
                                children: [
                                  Text(
                                    controller.pendingData[index].name ?? '',
                                    style: semiBoldDefault.copyWith(
                                      fontSize: Dimensions.fontDefault,
                                      color: MyColor.getHeadingTextColor(),
                                    ),
                                  ),
                                  const SizedBox(height: Dimensions.space5),
                                  if (controller.pendingData[index].type == "file") ...[
                                    GestureDetector(
                                      onTap: () {
                                        String url = "${UrlContainer.domainUrl}/${controller.path}/${controller.pendingData[index].value.toString()}";
                                        printX(url);
                                        showDialog(
                                          context: context,
                                          builder: (context) {
                                            return DownloadingDialog(
                                              url: url,
                                              fileName: MyStrings.kycData,
                                            );
                                          },
                                        );
                                      },
                                      child: Row(
                                        children: [
                                          const Icon(
                                            Icons.file_download,
                                            size: 17,
                                            color: MyColor.primaryColor,
                                          ),
                                          const SizedBox(width: 12),
                                          Text(
                                            MyStrings.attachment.tr,
                                            style: regularDefault.copyWith(
                                              color: MyColor.primaryColor,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ] else ...[
                                    Text(
                                      StringConverter.removeQuotationAndSpecialCharacterFromString(
                                        controller.pendingData[index].value ?? '',
                                      ),
                                      style: regularDefault.copyWith(
                                        color: MyColor.getBodyTextColor(),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            )
                          : const SizedBox.shrink();
                    },
                  ),
                ] else ...[
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      CustomSvgPicture(
                        image: widget.isPending ? MyImages.pendingIcon : MyImages.verifiedIcon,
                        height: 90,
                        width: 90,
                        fit: BoxFit.cover,
                      ),
                      const SizedBox(height: 20),
                      Text(
                        widget.isPending ? widget.title.tr : MyStrings.kycAlreadyVerifiedMsg.tr,
                        style: regularDefault.copyWith(
                          color: MyColor.colorBlack,
                          fontSize: Dimensions.fontLarge,
                          fontWeight: FontWeight.w600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 28),
                ElevatedButton.icon(
                  onPressed: () => Get.offNamed(RouteHelper.vehicleVerificationScreen),
                  icon: const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 20),
                  label: Text(
                    'Continuar con Vehículo (Paso 3)',
                    style: boldDefault.copyWith(color: Colors.white, fontSize: 15),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: MyColor.primaryColor,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
                const SizedBox(height: 12),
                TextButton(
                  onPressed: () => Get.offAllNamed(RouteHelper.loginScreen),
                  child: Text(
                    'Cerrar sesión',
                    style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 13),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
