import 'dart:convert';
import 'dart:io';

import 'package:get/get.dart';
import 'package:liztogo/core/helper/shared_preference_helper.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'package:liztogo/environment.dart';
import 'package:liztogo/data/services/local_auth_proxy.dart';
import 'package:pusher_reverb_flutter/pusher_reverb_flutter.dart';

class PusherManager {
  static final PusherManager _instance = PusherManager._internal();
  factory PusherManager() => _instance;
  PusherManager._internal();

  final ApiClient apiClient = ApiClient(sharedPreferences: Get.find());
  ReverbClient? _client;
  final List<void Function(PusherEvent)> _listeners = [];
  final Set<String> _subscribedChannels = {};
  bool _isConnecting = false;
  bool _isConnected = false;
  bool _shouldReconnect = true;
  int _reconnectAttempts = 0;
  static const int _maxReconnectAttempts = 5;
  LocalAuthProxy? _authProxy;

  String _normalizeEventData(dynamic data) {
    if (data == null) return '{}';
    if (data is String) return data;
    return jsonEncode(data);
  }

  void _onEventReceived(ChannelEvent event) {
    final pusherEvent = PusherEvent(
      channelName: event.channelName,
      eventName: event.eventName,
      data: _normalizeEventData(event.data),
    );
    for (var listener in List.from(_listeners)) {
      listener(pusherEvent);
    }
  }

  Future<void> _resubscribeAll() async {
    final channels = List<String>.from(_subscribedChannels);
    for (final channelName in channels) {
      try {
        final channel = channelName.startsWith('private-') ? _client!.subscribeToPrivateChannel(channelName) : _client!.subscribeToChannel(channelName);
        await channel.subscribe();
        channel.stream.listen(_onEventReceived);
      } catch (e) {
        printE("Resubscribe error for $channelName: $e");
      }
    }
  }

  Future<void> _connect() async {
    if (_isConnecting) return;
    _isConnecting = true;

    final reverbConfig = apiClient.getReverbConfig();
    final host = reverbConfig?.host ?? '0.0.0.0';
    final port = reverbConfig?.port ?? 8080;
    final appKey = reverbConfig?.appKey ?? '';

    if (!await _canResolveHost(host)) {
      printE("Reverb host no resuelve DNS: $host");
      _isConnecting = false;
      _isConnected = false;
      _scheduleReconnect();
      return;
    }

    try {
      _authProxy = LocalAuthProxy(apiClient: apiClient);
      await _authProxy!.start();
      final authUrl = _authProxy!.authUrl;
      printX("PusherManager auth proxy en: $authUrl");

      _client = ReverbClient.instance(
        host: host,
        port: port,
        appKey: appKey,
        useTLS: reverbConfig?.useTls ?? false,
        authorizer: onAuthorizer,
        authEndpoint: authUrl,
        onConnected: (socketId) {
          printX("Reverb Connected: $socketId");
          _isConnecting = false;
          _isConnected = true;
          _reconnectAttempts = 0;
          _resubscribeAll();
        },
        onDisconnected: () {
          printX("Reverb Disconnected");
          _isConnecting = false;
          _isConnected = false;
          _scheduleReconnect();
        },
        onError: (error) {
          printE("Reverb Error: $error");
          _isConnecting = false;
          _scheduleReconnect();
        },
      );

      await _client!.connect();
    } catch (e) {
      printE("Reverb connect error: $e");
      _isConnecting = false;
      _scheduleReconnect();
    }
  }

  void _scheduleReconnect() {
    if (!_shouldReconnect) return;
    if (_reconnectAttempts >= _maxReconnectAttempts) {
      printE("Reverb: max reconnect attempts reached");
      return;
    }
    _reconnectAttempts++;
    final delay = Duration(seconds: 2 * _reconnectAttempts);
    printX("Reverb: reconnecting in ${delay.inSeconds}s (attempt $_reconnectAttempts/$_maxReconnectAttempts)");
    Future.delayed(delay, () {
      if (!_isConnected && _subscribedChannels.isNotEmpty) {
        _connect();
      }
    });
  }

  Future<bool> _canResolveHost(String host) async {
    if (host.isEmpty || host == '0.0.0.0') return false;
    try {
      final addresses = await InternetAddress.lookup(host);
      return addresses.isNotEmpty && addresses.first.rawAddress.isNotEmpty;
    } catch (_) {
      return false;
    }
  }

  void addListener(void Function(PusherEvent) listener) {
    if (!_listeners.contains(listener)) _listeners.add(listener);
  }

  void removeListener(void Function(PusherEvent) listener) {
    _listeners.remove(listener);
  }

  bool isConnected() => _isConnected;

  Future<void> checkAndInitIfNeeded(String channelName) async {
    if (_subscribedChannels.contains(channelName)) return;

    _subscribedChannels.add(channelName);

    if (_isConnecting) return;

    if (!_isConnected) {
      await _connect();
    } else {
      try {
        final channel = channelName.startsWith('private-') ? _client!.subscribeToPrivateChannel(channelName) : _client!.subscribeToChannel(channelName);
        await channel.subscribe();
        channel.stream.listen(_onEventReceived);
      } catch (e) {
        printE("Pusher subscribe error for $channelName: $e");
        _subscribedChannels.remove(channelName);
      }
    }
  }

  Future<Map<String, String>> onAuthorizer(String channelName, String socketId) async {
    String token = '';
    String tokenType = 'Bearer';

    if (channelName.startsWith('private-seller.')) {
      token = apiClient.sharedPreferences.getString(SharedPreferenceHelper.sellerTokenKey) ?? '';
    } else {
      apiClient.initToken();
      token = apiClient.token;
      tokenType = apiClient.tokenType;
    }

    if (token.isEmpty) return {};
    return {
      'Authorization': '$tokenType $token',
      'dev-token': Environment.devToken,
    };
  }
}

class PusherEvent {
  final String channelName;
  final String eventName;
  final String data;

  PusherEvent({
    required this.channelName,
    required this.eventName,
    required this.data,
  });
}
