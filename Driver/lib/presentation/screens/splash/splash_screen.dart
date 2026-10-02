import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/helper/string_format_helper.dart';
import 'package:liztogo_pro/core/utils/my_color.dart';
import 'package:liztogo_pro/core/utils/my_images.dart';
import 'package:liztogo_pro/core/utils/util.dart';
import 'package:liztogo_pro/data/controller/splash/splash_controller.dart';
import 'package:liztogo_pro/presentation/components/annotated_region/annotated_region_widget.dart';
import 'package:liztogo_pro/presentation/components/custom_no_data_found_class.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    printX('🔵 SplashScreen.initState() called');
    MyUtils.splashScreen();
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((timeStamp) {
      printX('🔵 SplashScreen postFrameCallback fired');
      try {
        final controller = Get.find<SplashController>();
        printX('✅ SplashController found, calling gotoNextPage()');
        controller.gotoNextPage();
      } catch (e, stack) {
        printX('❌ SplashController not found: $e');
        printX('   Stack: $stack');
      }
    });
  }

  @override
  void dispose() {
    MyUtils.allScreen();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    printX('🔵 SplashScreen.build() called');
    return GetBuilder<SplashController>(
      builder: (controller) {
        printX('🔵 GetBuilder<SplashController> rebuilding, isLoading=${controller.isLoading}, noInternet=${controller.noInternet}');
        return AnnotatedRegionWidget(
          bottom: false,
          statusBarColor: MyColor.transparentColor,
          systemNavigationBarColor: MyColor.primaryColor,
          child: Scaffold(
            body: controller.noInternet
                ? NoDataOrInternetScreen(
                    isNoInternet: true,
                    onChanged: () {
                      controller.gotoNextPage();
                    },
                  )
                : Stack(
                    children: [
                      Positioned.fill(
                        child: Image.asset(
                          MyImages.backgroundImage,
                          height: double.infinity,
                          width: double.infinity,
                          fit: BoxFit.cover,
                        ),
                      ),
                      Positioned.fill(
                        child: Opacity(
                          opacity: 0.85,
                          child: Container(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                begin: Alignment.center,
                                end: Alignment.bottomCenter,
                                colors: [
                                  MyColor.primaryColor,
                                  MyColor.primaryColor.withValues(
                                    alpha: 0.8,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                      Align(
                        alignment: Alignment.center,
                        child: Image.asset(
                          MyImages.logoWhite,
                          height: double.infinity,
                          width: MediaQuery.of(context).size.height * 0.3,
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
