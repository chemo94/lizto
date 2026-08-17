import 'package:flutter/material.dart';
import 'package:flutter_inappwebview/flutter_inappwebview.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/helper/string_format_helper.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';
import 'package:lizto_delivery/data/model/webview/webview_model.dart';
import 'package:lizto_delivery/presentation/components/app-bar/custom_appbar.dart';

class MyWebViewScreen extends StatefulWidget {
  final WebviewModel model;
  const MyWebViewScreen({super.key, required this.model});

  @override
  State<MyWebViewScreen> createState() => _MyWebViewScreenState();
}

class _MyWebViewScreenState extends State<MyWebViewScreen> {
  late String url;
  InAppWebViewController? _controller;
  bool _isLoading = true;
  double _progress = 0;

  @override
  void initState() {
    super.initState();
    url = widget.model.url;
    if (!url.startsWith('http')) {
      url = 'https://$url';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Color(0xFF009EE3), // MercadoPago blue
        title: Text('MercadoPago', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
        centerTitle: true,
        leading: IconButton(icon: Icon(Icons.close, color: Colors.white), onPressed: () => Get.back()),
        bottom: _isLoading ? PreferredSize(child: LinearProgressIndicator(value: _progress, backgroundColor: Colors.white24, valueColor: AlwaysStoppedAnimation(Colors.white)), preferredSize: Size.fromHeight(3)) : null,
      ),
      body: Stack(children: [
        InAppWebView(
          initialUrlRequest: URLRequest(url: WebUri(url)),
          initialSettings: InAppWebViewSettings(
            javaScriptEnabled: true,
            domStorageEnabled: true,
            allowFileAccess: true,
            useShouldOverrideUrlLoading: false,
            useOnLoadResource: true,
            javaScriptCanOpenWindowsAutomatically: true,
            supportZoom: false,
            mixedContentMode: MixedContentMode.MIXED_CONTENT_ALWAYS_ALLOW,
          ),
          onWebViewCreated: (c) => _controller = c,
          onLoadStart: (c, u) {
            setState(() => _isLoading = true);
            if (u != null) {
              final urlStr = u.toString();
              if (urlStr.contains('/user/order/detail/') || urlStr.contains('/user/deposit/history')) {
                Get.back(result: 'success');
              }
            }
          },
          onProgressChanged: (c, progress) {
            setState(() => _progress = progress / 100);
          },
          onLoadStop: (c, u) {
            setState(() => _isLoading = false);
            if (u != null) {
              final urlStr = u.toString();
              if (urlStr.contains('/user/order/detail/') || urlStr.contains('/user/deposit/history')) {
                Get.back(result: 'success');
              }
            }
          },
          onReceivedError: (c, request, error) {
            setState(() => _isLoading = false);
            Get.snackbar('Error', 'No se pudo cargar la página', backgroundColor: Colors.red, colorText: Colors.white);
          },
        ),
        if (_isLoading)
          Positioned(top: 0, left: 0, right: 0, child: LinearProgressIndicator(value: _progress, backgroundColor: Colors.white24, valueColor: AlwaysStoppedAnimation(Color(0xFF009EE3)))),
      ]),
    );
  }
}
