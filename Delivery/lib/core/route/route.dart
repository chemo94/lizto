import 'package:get/get.dart';
import 'package:lizto_delivery/presentation/screens/auth/email_verification_page/email_verification_screen.dart';
import 'package:lizto_delivery/presentation/screens/auth/forget_password/forget_password/forget_password.dart';
import 'package:lizto_delivery/presentation/screens/auth/forget_password/reset_password/reset_password_screen.dart';
import 'package:lizto_delivery/presentation/screens/auth/forget_password/verify_forget_password/verify_forget_password_screen.dart';
import 'package:lizto_delivery/presentation/screens/auth/login/login_screen.dart';
import 'package:lizto_delivery/presentation/screens/auth/profile_complete/profile_complete_screen.dart';
import 'package:lizto_delivery/presentation/screens/auth/registration/registration_screen.dart';
import 'package:lizto_delivery/presentation/screens/auth/sms_verification_page/sms_verification_screen.dart';
import 'package:lizto_delivery/presentation/screens/dashboard/dashboard_screen.dart';
import 'package:lizto_delivery/presentation/screens/edit_profile/edit_profile_screen.dart';
import 'package:lizto_delivery/presentation/screens/faq/faq_screen.dart';
import 'package:lizto_delivery/presentation/screens/image_preview/preview_image_screen.dart';
import 'package:lizto_delivery/presentation/screens/language/language_screen.dart';
import 'package:lizto_delivery/presentation/screens/maintenance/maintanance_screen.dart';
import 'package:lizto_delivery/presentation/screens/onbaord/onboard_intro_screen.dart';
import 'package:lizto_delivery/presentation/screens/privacy_policy/privacy_policy_screen.dart';
import 'package:lizto_delivery/presentation/screens/profile/profile_screen.dart';
import 'package:lizto_delivery/presentation/screens/profile_and_settings/profile_and_settings_screen.dart';
import 'package:lizto_delivery/presentation/screens/splash/splash_screen.dart';
import 'package:lizto_delivery/presentation/screens/support_ticket/support_ticket_screen.dart';
import 'package:lizto_delivery/presentation/screens/support_ticket/ticket_details/ticket_details_screen.dart';
import 'package:lizto_delivery/presentation/screens/support_ticket/new_ticket_screen/add_new_ticket_screen.dart';
import 'package:lizto_delivery/presentation/screens/web_view/web_view_screen.dart';
import 'package:lizto_delivery/presentation/screens/account/change-password/change_password_screen.dart';

class RouteHelper {
  static const String splashScreen = "/splash_screen";
  static const String onboardScreen = "/onboard_screen";
  static const String loginScreen = "/login_screen";
  static const String forgotPasswordScreen = "/forgot_password_screen";
  static const String changePasswordScreen = "/change_password_screen";
  static const String registrationScreen = "/registration_screen";
  static const String profileCompleteScreen = "/profile_complete_screen";
  static const String dashboard = "/dashboard_screen";
  static const String webViewScreen = "/my_web_view_screen";
  static const String profileScreen = "/profile_screen";
  static const String editProfileScreen = "/edit_profile_screen";
  static const String profileAndSettingsScreen = "/profile_and_settings_screen";
  static const String emailVerificationScreen = "/verify_email_screen";
  static const String smsVerificationScreen = "/verify_sms_screen";
  static const String verifyForgotPasswordCodeScreen = "/verify_pass_code_screen";
  static const String resetPasswordScreen = "/reset_pass_screen";
  static const String privacyScreen = "/privacy-screen";
  static const String languageScreen = "/language_screen";
  static const String faqScreen = "/faq_screen";
  static const String createSupportTicketScreen = "/create_support_ticket_screen";
  static const String supportTicketScreen = "/support_ticket_screen";
  static const String supportTicketDetailsScreen = "/support_ticket_details_screen";
  static const String previewImageScreen = "/preview_image_screen";
  static const String maintenanceScreen = "/maintenance_screen";

  List<GetPage> routes = [
    GetPage(name: splashScreen, page: () => const SplashScreen()),
    GetPage(name: onboardScreen, page: () => const OnBoardIntroScreen()),
    GetPage(name: loginScreen, page: () => const LoginScreen()),
    GetPage(name: forgotPasswordScreen, page: () => const ForgetPasswordScreen()),
    GetPage(name: changePasswordScreen, page: () => const ChangePasswordScreen()),
    GetPage(name: registrationScreen, page: () => const RegistrationScreen()),
    GetPage(name: profileCompleteScreen, page: () => const ProfileCompleteScreen()),
    GetPage(name: dashboard, page: () => const DashBoardScreen()),
    GetPage(name: webViewScreen, page: () => MyWebViewScreen(model: Get.arguments)),
    GetPage(name: profileScreen, page: () => const ProfileScreen()),
    GetPage(name: editProfileScreen, page: () => const EditProfileScreen()),
    GetPage(name: profileAndSettingsScreen, page: () => const ProfileAndSettingsScreen()),
    GetPage(name: emailVerificationScreen, page: () => EmailVerificationScreen(needSmsVerification: Get.arguments[0] ?? false, isProfileCompleteEnabled: Get.arguments[1] ?? false, needTwoFactor: Get.arguments[2] ?? false)),
    GetPage(name: smsVerificationScreen, page: () => const SmsVerificationScreen()),
    GetPage(name: verifyForgotPasswordCodeScreen, page: () => const VerifyForgetPassScreen()),
    GetPage(name: resetPasswordScreen, page: () => const ResetPasswordScreen()),
    GetPage(name: privacyScreen, page: () => const PrivacyPolicyScreen()),
    GetPage(name: languageScreen, page: () => const LanguageScreen()),
    GetPage(name: faqScreen, page: () => const FaqScreen()),
    GetPage(name: createSupportTicketScreen, page: () => AddNewTicketScreen()),
    GetPage(name: supportTicketScreen, page: () => const SupportTicketScreen()),
    GetPage(name: supportTicketDetailsScreen, page: () => const TicketDetailsScreen()),
    GetPage(name: previewImageScreen, page: () => PreviewImageScreen(url: Get.arguments)),
    GetPage(name: maintenanceScreen, page: () => MaintenanceScreen()),
  ];
}
