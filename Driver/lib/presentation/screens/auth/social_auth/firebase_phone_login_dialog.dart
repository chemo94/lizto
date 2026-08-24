import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/material.dart';
import 'package:liztogo_pro/data/services/firebase_phone_auth_service.dart';

Future<void> showFirebasePhoneLoginDialog(BuildContext context, {required Future<void> Function(String token) onAuthenticated}) async {
  final phone = TextEditingController(text: '+51');
  final otp = TextEditingController();
  final auth = FirebasePhoneAuthService();
  var codeSent = false;
  var loading = false;
  await showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (dialogContext) => StatefulBuilder(builder: (context, setState) {
            Future<void> finish(String token) async {
              if (dialogContext.mounted) Navigator.pop(dialogContext);
              await onAuthenticated(token);
            }

            void error(Object value) {
              loading = false;
              setState(() {});
              final message = value is FirebaseAuthException ? (value.message ?? value.code) : value.toString();
              ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
            }

            Future<void> send() async {
              loading = true;
              setState(() {});
              try {
                await auth.sendCode(
                    phoneNumber: phone.text.trim(),
                    onAutoVerified: finish,
                    onCodeSent: () {
                      codeSent = true;
                      loading = false;
                      setState(() {});
                    },
                    onError: error);
              } catch (e) {
                error(e);
              }
            }

            Future<void> verify() async {
              loading = true;
              setState(() {});
              try {
                await finish(await auth.confirmCode(otp.text.trim()));
              } catch (e) {
                error(e);
              }
            }

            return AlertDialog(
                title: const Text('Continuar con celular'),
                content: Column(mainAxisSize: MainAxisSize.min, children: [
                  TextField(controller: phone, enabled: !codeSent, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Número con código de país')),
                  if (codeSent) ...[const SizedBox(height: 16), TextField(controller: otp, keyboardType: TextInputType.number, maxLength: 6, decoration: const InputDecoration(labelText: 'Código SMS'))]
                ]),
                actions: [TextButton(onPressed: loading ? null : () => Navigator.pop(dialogContext), child: const Text('Cancelar')), FilledButton(onPressed: loading ? null : (codeSent ? verify : send), child: loading ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2)) : Text(codeSent ? 'Verificar' : 'Enviar código'))]);
          }));
}
