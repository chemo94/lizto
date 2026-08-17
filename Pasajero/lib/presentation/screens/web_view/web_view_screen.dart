import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/route/route.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/model/webview/webview_model.dart';
import 'package:liztogo/presentation/components/app-bar/custom_appbar.dart';
import 'package:liztogo/presentation/components/custom_loader/custom_loader.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';
import 'package:url_launcher/url_launcher.dart';

class MyWebViewScreen extends StatefulWidget {
  final WebviewModel model;
  const MyWebViewScreen({super.key, required this.model});

  @override
  State<MyWebViewScreen> createState() => _MyWebViewScreenState();
}

class _MyWebViewScreenState extends State<MyWebViewScreen> {
  String url = '';
  final GlobalKey webViewKey = GlobalKey();
  bool isLoading = true;

  @override
  void initState() {
    url = widget.model.url;
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
              printD('url>>: ${url?.path}');
              final urlStr = url.toString();
              if (urlStr.contains('/user/order/detail/')) {
                Get.back(result: 'success');
                return;
              }
              if (urlStr == '${UrlContainer.domainUrl}/user/deposit/history') {
                Future.delayed(const Duration(seconds: 1), () {
                  printD('url<< $url');
                  Get.offNamed(
                    RouteHelper.rideReviewScreen,
                    arguments: widget.model.rideId,
                  );
                });
                CustomSnackBar.success(successList: [MyStrings.requestSuccess]);
              } else if (urlStr == '${UrlContainer.domainUrl}/user/deposit') {
                Get.back();
                CustomSnackBar.error(errorList: [MyStrings.requestFail]);
              }
              setState(() {
                this.url = urlStr;
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
                if (await canLaunchUrl(Uri.parse(widget.model.url))) {
                  await launchUrl(Uri.parse(widget.model.url));
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
              if (url != null && url.toString().contains('/user/order/detail/')) {
                Get.back(result: 'success');
              }
            },
          ),
          isLoading ? const Center(child: CustomLoader(isFullScreen: true)) : const SizedBox(),
        ],
      ),
    );
  }
}
