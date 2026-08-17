import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:crypto/crypto.dart';
import 'package:dio/dio.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/utils/url_container.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:liztogo_repartidor/environment.dart';

class LocalAuthProxy {
  HttpServer? _server;
  int? _port;
  final ApiClient _apiClient;

  LocalAuthProxy({required ApiClient apiClient}) : _apiClient = apiClient;

  int? get port => _port;
  bool get isRunning => _server != null;

  String get authUrl => 'http://127.0.0.1:$_port/broadcasting/auth';

  Future<void> start() async {
    if (_server != null) return;

    try {
      _server = await HttpServer.bind(InternetAddress.loopbackIPv4, 0);
      _port = _server!.port;
      printX("LocalAuthProxy started on port $_port");

      _server!.listen(_handleRequest);
    } catch (e) {
      printX("LocalAuthProxy error starting: $e");
    }
  }

  Future<void> stop() async {
    try {
      await _server?.close(force: true);
      _server = null;
      _port = null;
      printX("LocalAuthProxy stopped");
    } catch (_) {}
  }

  Future<void> _handleRequest(HttpRequest request) async {
    if (request.method != 'POST') {
      request.response
        ..statusCode = HttpStatus.methodNotAllowed
        ..close();
      return;
    }

    try {
      final body = await utf8.decoder.bind(request).join();
      final Map<String, dynamic> data = jsonDecode(body);
      final socketId = data['socket_id'] ?? '';
      final channelName = data['channel_name'] ?? '';

      printX("LocalAuthProxy auth request: socket_id=$socketId, channel=$channelName");

      final authKey = await _getAuthFromServer(socketId, channelName);

      if (authKey != null && authKey.isNotEmpty) {
        printX("LocalAuthProxy auth success: $authKey");
        request.response
          ..headers.contentType = ContentType.json
          ..write(jsonEncode({'auth': authKey}))
          ..close();
      } else {
        printX("LocalAuthProxy: auth key null from server, trying local generation");
        final localAuth = _generateAuthLocal(socketId, channelName);
        request.response
          ..headers.contentType = ContentType.json
          ..write(jsonEncode({'auth': localAuth}))
          ..close();
      }
    } catch (e) {
      printX("LocalAuthProxy request error: $e");
      request.response
        ..statusCode = HttpStatus.internalServerError
        ..close();
    }
  }

  Future<String?> _getAuthFromServer(String socketId, String channelName) async {
    try {
      _apiClient.initToken();
      final token = _apiClient.token;
      final tokenType = _apiClient.tokenType;

      if (token.isEmpty) return null;

      final dio = Dio();
      dio.options.connectTimeout = const Duration(seconds: 10);
      dio.options.receiveTimeout = const Duration(seconds: 10);

      final authUrl = '${UrlContainer.baseUrl}driver/pusher/auth/$socketId/$channelName';
      printX("LocalAuthProxy requesting: $authUrl");

      final response = await dio.post(
        authUrl,
        options: Options(
          headers: {
            'Authorization': '$tokenType $token',
            'dev-token': Environment.devToken,
            'Accept': 'application/json',
          },
        ),
      );

      printX("LocalAuthProxy server response [${response.statusCode}]: ${response.data}");

      if (response.statusCode == 200) {
        final dynamic raw = response.data;
        final Map<String, dynamic> responseData = raw is String ? jsonDecode(raw) : Map<String, dynamic>.from(raw);

        if (responseData.containsKey('auth')) {
          return responseData['auth'].toString();
        }

        if (responseData.containsKey('data') && responseData['data'] is Map) {
          final innerData = responseData['data'] as Map<String, dynamic>;
          if (innerData.containsKey('auth')) {
            return innerData['auth'].toString();
          }
        }

        printX("LocalAuthProxy: no 'auth' key found in response. Keys: ${responseData.keys.toList()}");
      }
    } on DioException catch (e) {
      printX("LocalAuthProxy DioException: type=${e.type}, message=${e.message}, response=${e.response?.data}");
    } catch (e) {
      printX("LocalAuthProxy server request error: $e");
    }
    return null;
  }

  String _generateAuthLocal(String socketId, String channelName) {
    final appKey = _apiClient.getReverbConfig()?.appKey ?? '';
    final appSecret = _apiClient.getReverbConfig()?.appSecret ?? '';

    if (appKey.isEmpty || appSecret.isEmpty) {
      printX("LocalAuthProxy: cannot generate local auth - appKey or appSecret missing");
      return '';
    }

    final String toSign = '$socketId:$channelName';
    final hmac = Hmac(sha256, utf8.encode(appSecret));
    final digest = hmac.convert(utf8.encode(toSign));
    final signature = digest.bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();

    return '$appKey:$signature';
  }
}
