import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/route/route.dart';
import 'package:liztogo/core/route/route_middleware.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/auth/social_auth_controller.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/model/global/user/global_user_model.dart';
import 'package:liztogo/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo/presentation/components/otp_field_widget/otp_field_widget.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';

Future<void> showWhatsAppPhoneLoginDialog(
  BuildContext context, {
  String userType = 'user',
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
        final SocialAuthController socialController = Get.isRegistered<SocialAuthController>()
            ? Get.find<SocialAuthController>()
            : Get.put(SocialAuthController(
                authRepo: Get.isRegistered<SocialAuthRepo>()
                    ? Get.find<SocialAuthRepo>()
                    : Get.put(SocialAuthRepo(apiClient: Get.find())),
              ));

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

          try {
            ResponseModel res = await socialController.sendWhatsAppOtp(
              mobile: mobile,
              dialCode: dialCode,
              userType: userType,
            ).timeout(
              const Duration(seconds: 15),
              onTimeout: () => ResponseModel(false, 'Tiempo de espera agotado. Revisa tu WhatsApp o reintenta.', 408, ''),
            );

            if (res.statusCode == 200) {
              final dynamic rawJson = res.responseJson;
              final dynamic json = (rawJson is String) ? jsonDecode(rawJson) : rawJson;
              if (json is Map && json['status'] == 'success') {
                codeSent = true;
                startTimer();
                CustomSnackBar.success(successList: ['Código de 6 dígitos enviado a tu WhatsApp']);
              } else {
                final msgs = (json is Map && json['message'] != null)
                    ? (json['message'] is List ? List<String>.from(json['message']) : [json['message']?.toString() ?? 'Error al enviar código'])
                    : ['Error al enviar código'];
                CustomSnackBar.error(errorList: msgs);

                // Si el mensaje indica que ya se envió o alcanzó límite, permitir ingresar el código
                final combined = msgs.join(' ').toLowerCase();
                if (combined.contains('demasiados') || combined.contains('ingresarlo') || combined.contains('activo')) {
                  codeSent = true;
                  startTimer();
                }
              }
            } else {
              CustomSnackBar.error(errorList: [res.message.isNotEmpty ? res.message : 'Error al enviar código']);
            }
          } catch (e) {
            CustomSnackBar.error(errorList: ['No se pudo conectar para enviar el código. Intenta nuevamente.']);
          } finally {
            loading = false;
            setState(() {});
          }
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

          try {
            ResponseModel res = await socialController.verifyWhatsAppOtp(
              mobile: mobile,
              otpCode: otp,
              dialCode: dialCode,
              userType: userType,
            ).timeout(
              const Duration(seconds: 15),
              onTimeout: () => ResponseModel(false, 'Tiempo de espera agotado al verificar. Intenta nuevamente.', 408, ''),
            );

            if (res.statusCode == 200) {
              final dynamic rawJson = res.responseJson;
              final dynamic json = (rawJson is String) ? jsonDecode(rawJson) : rawJson;
              if (json is Map && json['status'] == 'success') {
                timer?.cancel();
                final data = json['data'] is Map ? Map<String, dynamic>.from(json['data']) : <String, dynamic>{};
                final token = (data['access_token'] ?? data['token'])?.toString() ?? '';
                final bool isNewUser = (data['is_new_user'] == true || data['is_new_user'] == 'true' || data['is_new_user'] == 1) ||
                                       ((data['user'] == null && data['passenger'] == null) || token.isEmpty);

                if (Navigator.of(dialogContext).canPop()) {
                  Navigator.of(dialogContext).pop();
                }
                await Future.delayed(const Duration(milliseconds: 150));

                if (isNewUser) {
                  // USUARIO NUEVO -> Ir a Registro con datos prellenados
                  CustomSnackBar.success(successList: ['✓ WhatsApp verificado. Completa tu registro.']);
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
                  // USUARIO ANTIGUO -> Login y directo al HOME
                  CustomSnackBar.success(successList: ['¡Bienvenido de nuevo!']);
                  final userData = data['user'] ?? data['passenger'];
                  final tokenType = data['token_type'] ?? 'Bearer';

                  GlobalUser? user;
                  if (userData != null && userData is Map) {
                    try {
                      user = GlobalUser.fromJson(Map<String, dynamic>.from(userData));
                    } catch (e) {
                      print('Error parsing user: $e');
                    }
                  }
                  if (user != null) {
                    user.sv = "1";
                    user.ev = "1";
                  }

                  await RouteMiddleware.checkNGotoNext(
                    user: user,
                    accessToken: token,
                    tokenType: tokenType,
                  );
                  if (user?.profileComplete != '0') {
                    Get.offAllNamed(RouteHelper.dashboard);
                  }
                }
                return;
              } else {
                final msgs = (json is Map && json['message'] != null)
                    ? (json['message'] is List ? List<String>.from(json['message']) : [json['message']?.toString() ?? 'Código inválido'])
                    : ['Código inválido'];
                CustomSnackBar.error(errorList: msgs);
              }
            } else {
              CustomSnackBar.error(errorList: [res.message.isNotEmpty ? res.message : 'Error al verificar']);
            }
          } catch (e) {
            CustomSnackBar.error(errorList: ['Error al verificar código. Intenta nuevamente.']);
          } finally {
            loading = false;
            setState(() {});
          }
        }

        return PopScope(
          canPop: true,
          onPopInvokedWithResult: (didPop, result) {
            timer?.cancel();
          },
          child: Dialog(
            backgroundColor: MyColor.colorWhite,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.space20)),
            insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  // Icono WhatsApp
                  Container(
                    width: 56,
                    height: 56,
                    decoration: BoxDecoration(
                      color: const Color(0xFF25D366).withValues(alpha: 0.15),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.chat_bubble_rounded,
                      color: Color(0xFF25D366),
                      size: 30,
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Título
                  Text(
                    codeSent ? 'Verifica tu WhatsApp' : 'Continuar con Celular',
                    style: boldMediumLarge.copyWith(
                      fontSize: 20,
                      color: MyColor.colorBlack,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),

                  // Subtítulo
                  Text(
                    codeSent
                        ? 'Ingresa el código de 6 dígitos enviado por WhatsApp al +$dialCode ${phoneController.text.trim()}'
                        : 'Ingresa tu número celular para recibir tu código de seguridad por WhatsApp.',
                    style: regularDefault.copyWith(
                      color: MyColor.bodyTextColor,
                      fontSize: 13,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 24),

                  if (!codeSent) ...[
                    // Input de teléfono con prefijo +51
                    Container(
                      decoration: BoxDecoration(
                        color: MyColor.textFieldBgColor,
                        borderRadius: BorderRadius.circular(Dimensions.space12),
                        border: Border.all(color: MyColor.getTextFieldDisableBorder()),
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                            decoration: BoxDecoration(
                              color: Colors.black.withValues(alpha: 0.04),
                              borderRadius: const BorderRadius.only(
                                topLeft: Radius.circular(12),
                                bottomLeft: Radius.circular(12),
                              ),
                            ),
                            child: const Row(
                              children: [
                                Text('🇵🇪', style: TextStyle(fontSize: 18)),
                                SizedBox(width: 6),
                                Text(
                                  '+51',
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                                ),
                              ],
                            ),
                          ),
                          Expanded(
                            child: TextField(
                              controller: phoneController,
                              keyboardType: TextInputType.phone,
                              maxLength: 9,
                              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                              decoration: const InputDecoration(
                                hintText: '987 654 321',
                                counterText: '',
                                border: InputBorder.none,
                                contentPadding: EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                              ),
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Botón Enviar Código
                    RoundedButton(
                      text: 'Enviar código por WhatsApp',
                      isLoading: loading,
                      bgColor: const Color(0xFF25D366),
                      textColor: Colors.white,
                      press: loading ? () {} : sendCode,
                    ),

                    const SizedBox(height: 12),
                    TextButton(
                      onPressed: loading
                          ? null
                          : () {
                              final mobile = phoneController.text.trim();
                              if (mobile.length < 8) {
                                CustomSnackBar.error(errorList: ['Ingresa primero tu número de celular']);
                                return;
                              }
                              codeSent = true;
                              startTimer();
                              setState(() {});
                            },
                      child: Text(
                        '¿Ya tienes un código en tu WhatsApp? Ingrésalo aquí',
                        style: regularDefault.copyWith(
                          color: const Color(0xFF25D366),
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ] else ...[
                    // Input de código OTP de 6 dígitos
                    OTPFieldWidget(
                      controller: otpController,
                      length: 6,
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
                            style: regularDefault.copyWith(color: MyColor.bodyTextColor, fontSize: 13),
                          )
                        else
                          TextButton(
                            onPressed: loading ? null : sendCode,
                            child: Text(
                              'Reenviar código por WhatsApp',
                              style: boldDefault.copyWith(color: const Color(0xFF25D366), fontSize: 13),
                            ),
                          ),
                      ],
                    ),
                    TextButton(
                      onPressed: loading
                          ? null
                          : () {
                              codeSent = false;
                              otpController.clear();
                              timer?.cancel();
                              setState(() {});
                            },
                      child: Text(
                        'Cambiar número de celular',
                        style: regularDefault.copyWith(color: MyColor.bodyTextColor, fontSize: 12),
                      ),
                    ),
                    const SizedBox(height: 12),

                    // Botón Verificar
                    RoundedButton(
                      text: 'Verificar y Continuar',
                      isLoading: loading,
                      press: loading ? () {} : verifyCode,
                    ),
                  ],

                  if (loading) ...[
                    const SizedBox(height: 14),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const SizedBox(
                          width: 14,
                          height: 14,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: Color(0xFF25D366),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Flexible(
                          child: Text(
                            codeSent
                                ? 'Verificando con el servidor...'
                                : 'Conectando con WhatsApp, espera unos segundos...',
                            style: regularDefault.copyWith(
                              color: MyColor.bodyTextColor,
                              fontSize: 12,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      ],
                    ),
                  ],

                  const SizedBox(height: 12),
                  // Botón Cancelar (siempre disponible para no atrapar al usuario)
                  TextButton(
                    onPressed: () {
                      timer?.cancel();
                      Navigator.of(dialogContext).pop();
                    },
                    child: Text(
                      'Cancelar',
                      style: regularDefault.copyWith(color: MyColor.colorGrey, fontSize: 14),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    ),
  );
  timer?.cancel();
}
