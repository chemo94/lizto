import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/route/route.dart';
import 'package:lizto_store/core/route/route_middleware.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/auth/social_auth_controller.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/model/global/user/global_user_model.dart';
import 'package:lizto_store/presentation/components/buttons/rounded_button.dart';
import 'package:lizto_store/presentation/components/otp_field_widget/otp_field_widget.dart';
import 'package:lizto_store/presentation/components/snack_bar/show_custom_snackbar.dart';

Future<void> showWhatsAppPhoneLoginDialog(
  BuildContext context, {
  String userType = 'seller',
  void Function(Map<String, dynamic> verifiedData)? onNewUser,
}) async {
  final phoneController = TextEditingController();
  final otpController = TextEditingController();
  final dialCode = '51';

  bool codeSent = false;
  bool loading = false;
  int countdownSeconds = 60;
  Timer? timer;

  await showDialog<void>(
    context: context,
    barrierDismissible: false,
    builder: (dialogContext) => StatefulBuilder(
      builder: (context, setState) {
        void startTimer() {
          countdownSeconds = 60;
          timer?.cancel();
          timer = Timer.periodic(const Duration(seconds: 1), (t) {
            if (countdownSeconds > 0) {
              countdownSeconds--;
              setState(() {});
            } else {
              t.cancel();
            }
          });
        }

        Future<void> sendCode() async {
          final mobile = phoneController.text.trim();
          if (mobile.length < 8) {
            CustomSnackBar.error(errorList: ['Ingresa un número de celular válido (ej: 987654321)']);
            return;
          }

          loading = true;
          setState(() {});

          final socialController = Get.find<SocialAuthController>();
          ResponseModel res = await socialController.sendWhatsAppOtp(
            mobile: mobile,
            dialCode: dialCode,
            userType: userType,
          );

          loading = false;
          if (res.statusCode == 200) {
            final json = res.responseJson is String ? jsonDecode(res.responseJson) : res.responseJson;
            if (json['status'] == 'success') {
              codeSent = true;
              startTimer();
              setState(() {});
              CustomSnackBar.success(successList: ['Código de 6 dígitos enviado a tu WhatsApp']);
            } else {
              final msgs = json['message'] is List ? List<String>.from(json['message']) : [json['message']?.toString() ?? 'Error al enviar código'];
              CustomSnackBar.error(errorList: msgs);
            }
          } else {
            CustomSnackBar.error(errorList: [res.message]);
          }
          setState(() {});
        }

        Future<void> verifyCode() async {
          final mobile = phoneController.text.trim();
          final otp = otpController.text.trim();
          if (otp.length != 6) {
            CustomSnackBar.error(errorList: ['Ingresa el código completo de 6 dígitos']);
            return;
          }

          loading = true;
          setState(() {});

          final socialController = Get.find<SocialAuthController>();
          ResponseModel res = await socialController.verifyWhatsAppOtp(
            mobile: mobile,
            otp: otp,
            dialCode: dialCode,
            userType: userType,
          );

          loading = false;
          if (res.statusCode == 200) {
            final json = res.responseJson is String ? jsonDecode(res.responseJson) : res.responseJson;
            if (json['status'] == 'success') {
              timer?.cancel();
              final data = json['data'] is Map ? Map<String, dynamic>.from(json['data']) : <String, dynamic>{};
              final token = (data['access_token'] ?? data['token'])?.toString() ?? '';
              final bool isNewUser = (data['is_new_user'] == true || data['is_new_user'] == 'true' || data['is_new_user'] == 1) ||
                                     ((data['user'] == null && data['seller'] == null) || token.isEmpty);

              if (Navigator.of(dialogContext).canPop()) {
                Navigator.of(dialogContext).pop();
              }
              await Future.delayed(const Duration(milliseconds: 150));

              if (isNewUser) {
                // TIENDA NUEVA -> Registro con datos prellenados
                CustomSnackBar.success(successList: ['✓ WhatsApp verificado. Completa el registro de tu tienda.']);
                final args = {
                  'mobile': (data['mobile'] ?? mobile).toString(),
                  'dial_code': (data['dial_code'] ?? dialCode).toString(),
                  'phone_token': (data['phone_token'] ?? '').toString(),
                };
                if (onNewUser != null) {
                  onNewUser(args);
                } else {
                  Get.toNamed(
                    RouteHelper.registrationScreen,
                    arguments: args,
                  );
                }
              } else {
                // TIENDA ANTIGUA -> Dashboard directo
                CustomSnackBar.success(successList: ['¡Bienvenido de nuevo a tu panel!']);
                final sellerData = data['seller'] ?? data['user'];
                GlobalUser? user;
                if (sellerData != null && sellerData is Map) {
                  final map = Map<String, dynamic>.from(sellerData);
                  user = GlobalUser(
                    id: map['id']?.toString(),
                    firstname: map['name']?.toString() ?? (map['firstname']?.toString() ?? ''),
                    lastname: map['lastname']?.toString() ?? '',
                    email: map['email']?.toString() ?? '',
                    mobile: (map['phone'] ?? map['mobile'])?.toString() ?? '',
                    username: (map['username'] ?? map['email'])?.toString() ?? '',
                    profileComplete: '1',
                    sv: '1',
                    ev: '1',
                  );
                }
                final tokenType = data['token_type'] ?? 'Bearer';

                await RouteMiddleware.checkNGotoNext(
                  user: user,
                  accessToken: token,
                  tokenType: tokenType,
                );
                Get.offAllNamed(RouteHelper.sellerDashboardScreen);
              }
              return;
            } else {
              final msgs = json['message'] is List ? List<String>.from(json['message']) : [json['message']?.toString() ?? 'Código incorrecto'];
              CustomSnackBar.error(errorList: msgs);
            }
          } else {
            CustomSnackBar.error(errorList: [res.message]);
          }
          setState(() {});
        }

        return Dialog(
          backgroundColor: Colors.white,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
          child: SingleChildScrollView(
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Cabecera con Icono WhatsApp y botón cerrar
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: const Color(0xFF25D366).withValues(alpha: 0.15),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(
                              Icons.chat_bubble_rounded,
                              color: Color(0xFF25D366),
                              size: 26,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Text(
                            codeSent ? 'Verificar WhatsApp' : 'Ingreso con Celular',
                            style: boldLarge.copyWith(
                              fontSize: 18,
                              color: MyColor.getTextColor(),
                            ),
                          ),
                        ],
                      ),
                      IconButton(
                        icon: const Icon(Icons.close, color: Colors.grey),
                        onPressed: () {
                          timer?.cancel();
                          Navigator.of(dialogContext).pop();
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    codeSent
                        ? 'Enviamos un código de seguridad de 6 dígitos a tu WhatsApp al número +$dialCode ${phoneController.text.trim()}.'
                        : 'Ingresa tu número de celular para recibir un código de acceso único por WhatsApp.',
                    style: regularDefault.copyWith(
                      color: MyColor.getContentTextColor(),
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 20),

                  if (!codeSent) ...[
                    // Input de teléfono con prefijo +51
                    Container(
                      decoration: BoxDecoration(
                        border: Border.all(color: MyColor.borderColor, width: 1.2),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                            decoration: BoxDecoration(
                              color: MyColor.colorGrey.withValues(alpha: 0.1),
                              borderRadius: const BorderRadius.only(
                                topLeft: Radius.circular(11),
                                bottomLeft: Radius.circular(11),
                              ),
                            ),
                            child: Row(
                              children: [
                                const Text('🇵🇪', style: TextStyle(fontSize: 18)),
                                const SizedBox(width: 6),
                                Text(
                                  '+$dialCode',
                                  style: boldDefault.copyWith(fontSize: 15),
                                ),
                              ],
                            ),
                          ),
                          Expanded(
                            child: TextField(
                              controller: phoneController,
                              keyboardType: TextInputType.phone,
                              inputFormatters: [
                                FilteringTextInputFormatter.digitsOnly,
                                LengthLimitingTextInputFormatter(10),
                              ],
                              decoration: const InputDecoration(
                                hintText: '987 654 321',
                                border: InputBorder.none,
                                contentPadding: EdgeInsets.symmetric(horizontal: 14),
                              ),
                              style: regularDefault.copyWith(fontSize: 16),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Botón Enviar Código
                    RoundedButton(
                      text: loading ? 'Enviando código...' : 'Continuar por WhatsApp',
                      bgColor: const Color(0xFF25D366),
                      textColor: Colors.white,
                      press: loading ? () {} : sendCode,
                    ),
                  ] else ...[
                    // Input de código OTP de 6 dígitos
                    OTPFieldWidget(
                      controller: otpController,
                      onChanged: (val) {
                        if (val.length == 6) {
                          verifyCode();
                        }
                      },
                    ),
                    const SizedBox(height: 16),

                    // Temporizador y reenvío
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        if (countdownSeconds > 0)
                          Text(
                            'Reenviar código en ${countdownSeconds}s',
                            style: regularDefault.copyWith(
                              color: MyColor.getContentTextColor(),
                              fontSize: 13,
                            ),
                          )
                        else
                          TextButton(
                            onPressed: loading ? null : sendCode,
                            child: Text(
                              'Reenviar código por WhatsApp',
                              style: boldDefault.copyWith(
                                color: const Color(0xFF25D366),
                                fontSize: 14,
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Botón Verificar
                    RoundedButton(
                      text: loading ? 'Verificando...' : 'Verificar y Continuar',
                      bgColor: const Color(0xFF25D366),
                      textColor: Colors.white,
                      press: loading ? () {} : verifyCode,
                    ),
                    const SizedBox(height: 8),

                    // Botón cambiar número
                    TextButton(
                      onPressed: loading
                          ? null
                          : () {
                              timer?.cancel();
                              setState(() {
                                codeSent = false;
                                otpController.clear();
                              });
                            },
                      child: Text(
                        'Cambiar número de celular',
                        style: regularDefault.copyWith(
                          color: MyColor.primaryColor,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        );
      },
    ),
  );
}
