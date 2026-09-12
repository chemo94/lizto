import 'package:firebase_app_check/firebase_app_check.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:flutter/foundation.dart';

class FirebasePhoneAuthService {
  FirebasePhoneAuthService({FirebaseAuth? auth}) : _auth = auth ?? FirebaseAuth.instance;

  final FirebaseAuth _auth;
  String? verificationId;
  int? resendToken;

  static Future<void>? _appCheckActivation;

  Future<void> activateAppCheck() => _appCheckActivation ??= FirebaseAppCheck.instance.activate(
        providerAndroid: kDebugMode ? const AndroidDebugProvider() : const AndroidPlayIntegrityProvider(),
        providerApple: kDebugMode ? const AppleDebugProvider() : const AppleAppAttestWithDeviceCheckFallbackProvider(),
      );

  Future<void> sendCode({
    required String phoneNumber,
    required Future<void> Function(String idToken) onAutoVerified,
    required void Function() onCodeSent,
    required void Function(FirebaseAuthException error) onError,
    bool resend = false,
  }) =>
      _auth.verifyPhoneNumber(
        phoneNumber: phoneNumber,
        forceResendingToken: resend ? resendToken : null,
        verificationCompleted: (credential) async {
          try {
            onAutoVerified(await _tokenFor(credential));
          } on FirebaseAuthException catch (error) {
            onError(error);
          }
        },
        verificationFailed: onError,
        codeSent: (id, token) {
          verificationId = id;
          resendToken = token;
          onCodeSent();
        },
        codeAutoRetrievalTimeout: (id) => verificationId = id,
      );

  Future<String> confirmCode(String code) async {
    final id = verificationId;
    if (id == null) throw FirebaseAuthException(code: 'session-expired', message: 'Solicita un nuevo código de verificación');
    return _tokenFor(PhoneAuthProvider.credential(verificationId: id, smsCode: code));
  }

  Future<String> _tokenFor(PhoneAuthCredential credential) async {
    final result = await _auth.signInWithCredential(credential);
    final token = await result.user?.getIdToken(true);
    if (token == null) throw FirebaseAuthException(code: 'token-missing', message: 'Firebase no devolvió un token válido');
    return token;
  }
}
