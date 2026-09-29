import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/utils/util.dart';
import 'package:liztogo_repartidor/presentation/components/annotated_region/annotated_region_widget.dart';
import 'package:liztogo_repartidor/presentation/components/divider/custom_spacer.dart';
import 'package:liztogo_repartidor/presentation/components/otp_field_widget/otp_field_widget.dart';
import 'package:liztogo_repartidor/presentation/components/text/default_text.dart';
import 'package:liztogo_repartidor/presentation/components/text/header_text.dart';

import 'package:liztogo_repartidor/core/route/route.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/my_images.dart';
import 'package:liztogo_repartidor/core/utils/my_strings.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/controller/auth/auth/sms_verification_controler.dart';
import 'package:liztogo_repartidor/data/repo/auth/sms_email_verification_repo.dart';
import 'package:liztogo_repartidor/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo_repartidor/presentation/components/will_pop_widget.dart';

class SmsVerificationScreen extends StatefulWidget {
  const SmsVerificationScreen({super.key});

  @override
  State<SmsVerificationScreen> createState() => _SmsVerificationScreenState();
}

class _SmsVerificationScreenState extends State<SmsVerificationScreen> {
  @override
  void initState() {
    Get.put(SmsEmailVerificationRepo(apiClient: Get.find()));
    final controller = Get.put(SmsVerificationController(repo: Get.find()));
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      controller.loadBefore();
    });
  }

  @override
  void dispose() {
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return WillPopWidget(
      nextRoute: RouteHelper.loginScreen,
      child: AnnotatedRegionWidget(
        statusBarColor: MyColor.screenBgColor,
        systemNavigationBarColor: MyColor.screenBgColor,
        top: true,
        child: Scaffold(
          backgroundColor: MyColor.screenBgColor,
          body: GetBuilder<SmsVerificationController>(
            builder: (controller) => controller.isLoading
                ? Center(
                    child: CircularProgressIndicator(
                      color: MyColor.getPrimaryColor(),
                    ),
                  )
                : SingleChildScrollView(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          Align(
                            alignment: AlignmentDirectional.centerEnd,
                            child: Padding(
                              padding: const EdgeInsetsDirectional.only(
                                end: Dimensions.space5,
                              ),
                              child: IconButton(
                                onPressed: () {
                                  Get.offAllNamed(RouteHelper.loginScreen);
                                },
                                icon: Icon(
                                  Icons.close,
                                  size: Dimensions.space30,
                                  color: MyColor.getHeadingTextColor(),
                                ),
                              ),
                            ),
                          ),
                          Stack(
                            alignment: Alignment.bottomRight,
                            children: [
                              Image.asset(
                                MyImages.phoneVerificationImage,
                                width: Dimensions.space50 * 4,
                              ),
                              Container(
                                padding: const EdgeInsets.all(8),
                                margin: const EdgeInsets.only(right: 12, bottom: 4),
                                decoration: const BoxDecoration(
                                  color: Color(0xFF25D366),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(
                                  Icons.chat_bubble_rounded,
                                  color: Colors.white,
                                  size: 28,
                                ),
                              ),
                            ],
                          ),
                          spaceDown(Dimensions.space30),
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 25),
                            child: Column(
                              children: [
                                HeaderText(
                                  text: MyStrings.verifyYourNumber.tr,
                                  textAlign: TextAlign.center,
                                  style: boldExtraLarge.copyWith(
                                    fontWeight: FontWeight.w700,
                                    fontSize: Dimensions.fontOverLarge22,
                                  ),
                                ),
                                spaceDown(Dimensions.space8),
                                DefaultText(
                                  text: 'Enviamos un código de 6 dígitos a tu WhatsApp al número ${MyUtils.maskSensitiveInformation(controller.userPhone)}',
                                  textAlign: TextAlign.center,
                                  fontSize: Dimensions.fontLarge,
                                  textColor: MyColor.getBodyTextColor(),
                                ),
                                const SizedBox(height: Dimensions.space40),
                                OTPFieldWidget(
                                  controller: controller.otpTextController,
                                  onChanged: (value) {
                                    controller.currentText = value;
                                  },
                                ),
                                spaceDown(Dimensions.space30),
                                RoundedButton(
                                  isLoading: controller.submitLoading,
                                  text: MyStrings.verify.tr,
                                  press: () {
                                    controller.verifyYourSms(
                                      controller.currentText,
                                    );
                                  },
                                ),
                                spaceDown(Dimensions.space25),
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text(
                                      MyStrings.didNotReceiveCode.tr,
                                      overflow: TextOverflow.ellipsis,
                                      style: boldLarge.copyWith(
                                        color: MyColor.getBodyTextColor(),
                                        fontWeight: FontWeight.normal,
                                      ),
                                    ),
                                    const SizedBox(width: Dimensions.space5),
                                    TextButton(
                                      onPressed: () {
                                        controller.sendCodeAgain();
                                      },
                                      child: controller.resendLoading
                                          ? const SizedBox(
                                              height: Dimensions.space16,
                                              width: Dimensions.space16,
                                              child: CircularProgressIndicator(
                                                color: MyColor.primaryColor,
                                              ),
                                            )
                                          : Text(
                                              'Reenviar por WhatsApp',
                                              maxLines: 2,
                                              overflow: TextOverflow.ellipsis,
                                              style: boldLarge.copyWith(
                                                color: const Color(0xFF25D366),
                                              ),
                                            ),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
          ),
        ),
      ),
    );
  }
}
