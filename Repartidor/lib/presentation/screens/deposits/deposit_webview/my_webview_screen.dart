import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/data/model/deposit/deposit_insert_response_model.dart';
import 'package:liztogo_repartidor/presentation/components/app-bar/custom_appbar.dart';
import 'package:liztogo_repartidor/presentation/components/custom_loader/custom_loader.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../core/route/route.dart';
import '../../../../core/utils/my_strings.dart';
import '../../../../core/utils/url_container.dart';
import '../../../../data/controller/dashboard/dashboard_controller.dart';
import '../../../components/snack_bar/show_custom_snackbar.dart';

class MyWebViewScreen extends StatefulWidget {
  final DepositInsertData depositInsertData;
  const MyWebViewScreen({super.key, required this.depositInsertData});

  @override
  State<MyWebViewScreen> createState() => _MyWebViewScreenState();
}

class _MyWebViewScreenState extends State<MyWebViewScreen> {
  String url = '';
  final GlobalKey webViewKey = GlobalKey();
  bool isLoading = true;

  @override
  void initState() {
    url = widget.depositInsertData.redirectUrl ?? "";
    super.initState();
  }

  InAppWebViewController? webViewController;
  // ignore: deprecated_member_use
  InAppWebViewSettings options = InAppWebViewSettings(
    allowFileAccess: true,
    allowsInlineMediaPlayback: true,
    useHybridComposition: true,
    useShouldOverrideUrlLoading: true,
    mediaPlaybackRequiresUserGesture: false,
    cacheEnabled: false,
    javaScriptEnabled: true,
  );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(title: MyStrings.payNow, isTitleCenter: true),
      body: Stack(
        children: [
          InAppWebView(
            key: webViewKey,
            initialUrlRequest: URLRequest(url: WebUri(url)),
            initialSettings: options,
            onWebViewCreated: (controller) {
              webViewController = controller;
            },
            onLoadStart: (controller, url) {
              printX("url >> ${url.toString()}");
              printX("${widget.depositInsertData.deposit?.successUrl}");
              if (url.toString().contains("ipn/") && Platform.isIOS == true) {
                _refreshDashboard();
                Get.offAndToNamed(RouteHelper.depositsScreen);
                CustomSnackBar.success(successList: [MyStrings.requestSuccess]);
              } else if (url.toString() == '${UrlContainer.domainUrl}${widget.depositInsertData.deposit?.successUrl ?? ""}') {
                _refreshDashboard();
                Get.offAndToNamed(RouteHelper.depositsScreen);
                CustomSnackBar.success(successList: [MyStrings.requestSuccess]);
              } else if (url.toString() == '${UrlContainer.domainUrl}${widget.depositInsertData.deposit?.failedUrl ?? ""}') {
                Get.back();
                CustomSnackBar.error(errorList: [MyStrings.requestFail]);
              }
              setState(() {
                this.url = url.toString();
              });
            },
            shouldOverrideUrlLoading: (controller, navigationAction) async {
              var uri = navigationAction.request.url!;

              if (![
                "http",
                "https",
                "file",
                "chrome",
                "data",
                "javascript",
                "about",
              ].contains(uri.scheme)) {
                if (await canLaunchUrl(
                  Uri.parse(widget.depositInsertData.redirectUrl ?? ""),
                )) {
                  await launchUrl(
                    Uri.parse(widget.depositInsertData.redirectUrl ?? ""),
                  );
                  return NavigationActionPolicy.CANCEL;
                }
              }
              return NavigationActionPolicy.ALLOW;
            },
            onLoadStop: (controller, url) async {
              isLoading = false;
              setState(() {
                this.url = url.toString();
              });
            },
          ),
          isLoading ? const Center(child: CustomLoader(isFullScreen: true)) : const SizedBox(),
        ],
      ),
    );
  }

  void _refreshDashboard() {
    if (Get.isRegistered<DashBoardController>()) {
      Get.find<DashBoardController>().loadData();
    }
  }
}
