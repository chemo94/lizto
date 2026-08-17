import 'dart:convert';
import 'dart:io';

import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:liztogo_repartidor/data/services/local_auth_proxy.dart';
import 'package:liztogo_repartidor/environment.dart';
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
  static const int _maxReconnectAttempts = 20;
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
    for (var listener in _listeners) {
      listener(pusherEvent);
    }
    printX("EVENTO RECIBIDO");
    printX("CANAL: ${event.channelName}");
    printX("EVENTO: ${event.eventName}");
    printX("DATA: ${event.data}");
  }

  Future<void> _resubscribeAll() async {
    final channels = List<String>.from(_subscribedChannels);
    printX("Re-suscribiendo ${channels.length} canales: $channels");
    for (final channelName in channels) {
      try {
        final channel = channelName.startsWith('private-') ? _client!.subscribeToPrivateChannel(channelName) : _client!.subscribeToChannel(channelName);

        channel.addStateListener((state) {
          printX("Channel $channelName state -> $state");
        });

        await channel.subscribe();
        channel.stream.listen(_onEventReceived);
        printX("Canal $channelName suscrito - state=${channel.state}");
      } catch (e) {
        printE("Resubscribe error for $channelName: $e");
      }
    }
  }

  Future<void> init(String channelName) async {
    if (_isConnecting) return;

    _subscribedChannels.add(channelName);
    _disconnect(clearChannels: false);
    _isConnecting = true;

    final reverbConfig = apiClient.getReverbConfig();
    final host = reverbConfig?.host ?? '0.0.0.0';
    final port = reverbConfig?.port ?? 8080;
    final appKey = reverbConfig?.appKey ?? '';

    if (!await _canResolveHost(host)) {
      printE("Reverb host no resuelve DNS: $host");
      _isConnecting = false;
      _isConnected = false;
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
      printE("Reverb init error: $e");
      _isConnecting = false;
      _scheduleReconnect();
    }
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

  void _scheduleReconnect() {
    if (!_shouldReconnect || _subscribedChannels.isEmpty || _isConnected) return;
    if (_reconnectAttempts >= _maxReconnectAttempts) {
      printE('Reverb reconnection paused after $_maxReconnectAttempts attempts');
      return;
    }
    _reconnectAttempts++;
    final delay = Duration(seconds: (_reconnectAttempts * 2).clamp(2, 30).toInt());
    Future.delayed(delay, () {
      if (!_isConnected && !_isConnecting && _subscribedChannels.isNotEmpty) {
        init(_subscribedChannels.first);
      }
    });
  }

  void _disconnect({bool clearChannels = true}) {
    _isConnecting = false;
    _isConnected = false;
    if (clearChannels) {
      _subscribedChannels.clear();
    }
    try {
      _client?.disconnect();
      _client = null;
    } catch (_) {}
  }

  void addListener(void Function(PusherEvent) listener) {
    if (!_listeners.contains(listener)) _listeners.add(listener);
  }

  void removeListener(void Function(PusherEvent) listener) {
    _listeners.remove(listener);
  }

  bool isConnected() => _isConnected;

  Future<void> checkAndInitIfNeeded(String channelName) async {
    if (_isConnecting) {
      _subscribedChannels.add(channelName);
      return;
    }

    if (!isConnected()) {
      _subscribedChannels.add(channelName);
      await init(channelName);
    } else if (!_subscribedChannels.contains(channelName)) {
      try {
        final channel = channelName.startsWith('private-') ? _client!.subscribeToPrivateChannel(channelName) : _client!.subscribeToChannel(channelName);
        await channel.subscribe();
        channel.stream.listen(_onEventReceived);
        _subscribedChannels.add(channelName);
      } catch (_) {}
    }
  }

  Future<Map<String, String>> onAuthorizer(String channelName, String socketId) async {
    apiClient.initToken();
    final token = apiClient.token;
    final tokenType = apiClient.tokenType;
    if (token.isEmpty) {
      printX("Auth: token vacío para $channelName");
      return {};
    }
    printX("Pusher auth preparado para $channelName");
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
