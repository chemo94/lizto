import 'dart:io';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/utils/dimensions.dart';
import 'package:liztogo_pro/core/utils/my_color.dart';
import 'package:liztogo_pro/core/utils/my_images.dart';
import 'package:liztogo_pro/core/utils/style.dart';
import 'package:liztogo_pro/data/controller/auth/social_auth_controller.dart';
import 'package:liztogo_pro/data/repo/auth/social_auth_repo.dart';
import 'package:liztogo_pro/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo_pro/presentation/components/divider/custom_spacer.dart';
import 'package:liztogo_pro/presentation/components/image/my_local_image_widget.dart';
import 'package:liztogo_pro/presentation/screens/auth/login/widgets/login_or_bar.dart';
import 'package:liztogo_pro/presentation/screens/auth/social_auth/whatsapp_phone_login_dialog.dart';

class SocialAuthSection extends StatefulWidget {
  final String googleAuthTitle;
  final String appleAuthTitle;
  final String phoneAuthTitle;
  final String userType;
  final void Function(Map<String, dynamic> verifiedData)? onNewUserPhoneVerified;

  const SocialAuthSection({
    super.key,
    this.googleAuthTitle = 'Continuar con Google',
    this.appleAuthTitle = 'Continuar con Apple',
    this.phoneAuthTitle = 'Continuar con celular',
    this.userType = 'driver',
    this.onNewUserPhoneVerified,
  });

  @override
  State<SocialAuthSection> createState() => _SocialAuthSectionState();
}

class _SocialAuthSectionState extends State<SocialAuthSection> {
  @override
  void initState() {
    Get.put(SocialAuthRepo(apiClient: Get.find()));
    Get.put(SocialAuthController(authRepo: Get.find()));
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SocialAuthController>(
      builder: (controller) {
        return Column(
          children: [
            // Botón Google
            RoundedButton(
              text: "",
              isOutlined: true,
              child: Row(
                mainAxisSize: MainAxisSize.max,
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  controller.isGoogleSignInLoading
                      ? SizedBox(
                          height: 18,
                          width: 18,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: MyColor.primaryColor,
                          ),
                        )
                      : MyLocalImageWidget(
                          imagePath: MyImages.google,
                          height: 22,
                          width: 22,
                          boxFit: BoxFit.contain,
                        ),
                  SizedBox(width: Dimensions.space10),
                  Text(
                    widget.googleAuthTitle.tr,
                    style: regularDefault.copyWith(
                      fontWeight: FontWeight.w600,
                      fontSize: 15,
                    ),
                  ),
                ],
              ),
              press: () {
                if (!controller.isGoogleSignInLoading) {
                  controller.signInWithGoogle();
                }
              },
            ),

            // Botón Apple
            if (Platform.isIOS) ...[
              spaceDown(Dimensions.space10),
              RoundedButton(
                text: "",
                isOutlined: true,
                child: Row(
                  mainAxisSize: MainAxisSize.max,
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    controller.isAppleSignInLoading
                        ? SizedBox(
                            height: 18,
                            width: 18,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: MyColor.primaryColor,
                            ),
                          )
                        : MyLocalImageWidget(
                            imagePath: MyImages.apple,
                            height: 22,
                            width: 22,
                            boxFit: BoxFit.contain,
                          ),
                    SizedBox(width: Dimensions.space10),
                    Text(
                      widget.appleAuthTitle.tr,
                      style: regularDefault.copyWith(
                        fontWeight: FontWeight.w600,
                        fontSize: 15,
                      ),
                    ),
                  ],
                ),
                press: () {
                  if (!controller.isAppleSignInLoading) {
                    controller.signInWithApple();
                  }
                },
              ),
            ],

            spaceDown(Dimensions.space10),

            // Botón Celular (WhatsApp OTP)
            RoundedButton(
              text: '',
              isOutlined: true,
              press: () => showWhatsAppPhoneLoginDialog(
                context,
                userType: widget.userType,
                onNewUser: widget.onNewUserPhoneVerified,
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(
                    Icons.chat_bubble_rounded,
                    color: Color(0xFF25D366),
                    size: 22,
                  ),
                  const SizedBox(width: 10),
                  Text(
                    widget.phoneAuthTitle.tr,
                    style: regularDefault.copyWith(
                      fontWeight: FontWeight.w600,
                      fontSize: 15,
                    ),
                  ),
                ],
              ),
            ),

            spaceDown(Dimensions.space15),
            const LoginOrBar(stock: 0.8),
          ],
        );
      },
    );
  }
}
