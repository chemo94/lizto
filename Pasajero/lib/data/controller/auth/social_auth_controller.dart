import 'package:get/get.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/route/route_middleware.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/data/model/auth/login/login_response_model.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/repo/auth/social_auth_repo.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';
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
      try {
        await googleSignIn.signOut();
      } catch (_) {}
      await googleSignIn.initialize();
      var googleUser = await googleSignIn.authenticate();
      final GoogleSignInAuthentication googleAuth = googleUser.authentication;

      GoogleSignInClientAuthorization? authorization;
      try {
        authorization = await googleUser.authorizationClient.authorizationForScopes(scopes);
        authorization ??= await googleUser.authorizationClient.authorizeScopes(scopes);
      } catch (authErr) {
        printX("authorization error: $authErr");
      }

      final String token = (authorization?.accessToken != null && authorization!.accessToken!.isNotEmpty)
          ? authorization.accessToken!
          : (googleAuth.idToken ?? '');

      if (token.isEmpty) {
        CustomSnackBar.error(errorList: [MyStrings.loginFailedTryAgain.tr]);
        return;
      }

      printX("Google token obtained: ${token.substring(0, token.length > 10 ? 10 : token.length)}...");

      await socialLoginUser(
        provider: 'google',
        accessToken: token,
      );
    } catch (e) {
      printX(e.toString());
      // CustomSnackBar.error(errorList: [e.toString()]);
    } finally {
      isGoogleSignInLoading = false;
      update();
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
        printX("El usuario canceló el flujo");
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
