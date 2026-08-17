import 'package:get/get.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:lizto_delivery/core/helper/string_format_helper.dart';
import 'package:lizto_delivery/core/route/route_middleware.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';
import 'package:lizto_delivery/data/model/auth/login/login_response_model.dart';
import 'package:lizto_delivery/data/model/global/response_model/response_model.dart';
import 'package:lizto_delivery/data/repo/auth/social_auth_repo.dart';
import 'package:lizto_delivery/presentation/components/snack_bar/show_custom_snackbar.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';

class SocialAuthController extends GetxController {
  SocialAuthRepo authRepo;
  SocialAuthController({required this.authRepo});

  final GoogleSignIn googleSignIn = GoogleSignIn.instance;
  bool isGoogleSignInLoading = false;

  Future<void> signInWithGoogle() async {
    try {
      isGoogleSignInLoading = true;
      update();
      const List<String> scopes = <String>['email', 'profile'];

      await googleSignIn.initialize();
      final googleUser = await googleSignIn.authenticate(scopeHint: scopes);

      final googleAuth = await googleUser.authentication;
      if (googleAuth.idToken == null) {
        isGoogleSignInLoading = false;
        update();
        CustomSnackBar.error(errorList: ['No se pudo autenticar con Google. Verifica tu conexion.']);
        return;
      }

      String? accessToken;
      try {
        final auth = await googleUser.authorizationClient.authorizationForScopes(scopes);
        accessToken = auth?.accessToken;
        printX('authorizationForScopes accessToken: $accessToken');
      } catch (_) {
        printX('authorizationForScopes failed');
      }

      if (accessToken == null || accessToken.isEmpty) {
        isGoogleSignInLoading = false;
        update();
        CustomSnackBar.error(errorList: ['No se pudo obtener el token de Google. Intenta de nuevo.']);
        return;
      }

      await socialLoginUser(
        provider: 'google',
        accessToken: accessToken,
      );
    } catch (e) {
      final err = e.toString();
      printX('Google sign-in error: $err');
      isGoogleSignInLoading = false;
      update();
      if (err.contains('reauth') || err.contains('canceled')) {
        CustomSnackBar.error(errorList: ['Sesion de Google expirada. Ve a Ajustes > Cuentas > Google y vuelve a iniciar sesion.']);
      } else {
        CustomSnackBar.error(errorList: ['$e']);
      }
      return;
    }
  }

  bool isAppleSignInLoading = false;
  Future signInWithApple() async {
    isAppleSignInLoading = true;
    update();
    try {
      final AuthorizationCredentialAppleID credential = await SignInWithApple.getAppleIDCredential(
        scopes: [
          AppleIDAuthorizationScopes.email,
          AppleIDAuthorizationScopes.fullName,
        ],
      );
      printX(credential.email);
      printX(credential.givenName);
      printX(credential.familyName);
      printX(credential.authorizationCode);
      printX(credential.identityToken);
      socialLoginUser(
        provider: 'apple',
        accessToken: credential.identityToken ?? '',
      );
    } catch (e) {
      if (e is SignInWithAppleAuthorizationException &&
          e.code == AuthorizationErrorCode.canceled) {
        printX("El usuario cancelo el flujo");
      } else {
        printX("Error: $e");
      }
    } finally {
      isAppleSignInLoading = false;
      update();
    }
  }

  Future socialLoginUser({String accessToken = '', String? provider}) async {
    try {
      ResponseModel responseModel = await authRepo.socialLoginUser(
        accessToken: accessToken,
        provider: provider,
      );
      if (responseModel.statusCode == 200) {
        LoginResponseModel loginModel = LoginResponseModel.fromJson((responseModel.responseJson));
        if (loginModel.status.toString().toLowerCase() == MyStrings.success.toLowerCase()) {
          RouteMiddleware.checkNGotoNext(
            user: loginModel.data?.user,
            accessToken: loginModel.data?.accessToken ?? '',
            tokenType: loginModel.data?.tokenType ?? '',
          );
        } else {
          CustomSnackBar.error(
            errorList: loginModel.message ?? [MyStrings.loginFailedTryAgain.tr],
          );
        }
      } else {
        CustomSnackBar.error(errorList: [responseModel.message]);
      }
    } catch (e) {
      printX(e.toString());
    }
  }
}
