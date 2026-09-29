import 'dart:async';
import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:web_socket_channel/web_socket_channel.dart';

/// Resultado de la autenticación en el servidor WS.
class RealtimeAuth {
  final String type; // 'user' | 'driver' | 'seller'
  final int id;
  RealtimeAuth({required this.type, required this.id});
}

/// Servicio de tiempo real sobre WebSocket nativo con reconexión automática y re-suscripción.
class RealtimeService {
  final String wsUrl; // wss://liztodelivery.com/ws
  final String token; // Token Sanctum

  WebSocketChannel? _channel;
  Completer<RealtimeAuth>? _authCompleter;
  RealtimeAuth? _currentAuth;

  final _broadcastController = StreamController<Map<String, dynamic>>.broadcast();
  Stream<Map<String, dynamic>> get onBroadcast => _broadcastController.stream;

  final Set<String> _activeTopics = {};
  bool _isConnected = false;
  bool _isDisposed = false;
  Timer? _reconnectTimer;
  Timer? _pingTimer;

  bool get isConnected => _isConnected;
  RealtimeAuth? get auth => _currentAuth;
  Set<String> get activeTopics => Set.unmodifiable(_activeTopics);

  RealtimeService({required this.wsUrl, required this.token});

  Future<RealtimeAuth> connect() async {
    _isDisposed = false;
    _authCompleter = Completer<RealtimeAuth>();
    _initSocket();

    return _authCompleter!.future.timeout(
      const Duration(seconds: 12),
      onTimeout: () {
        if (!_isConnected) {
          throw Exception('Timeout de autenticación en WebSocket');
        }
        return _currentAuth ?? RealtimeAuth(type: 'unknown', id: 0);
      },
    );
  }

  void _initSocket() {
    try {
      _channel = WebSocketChannel.connect(Uri.parse(wsUrl));
      _channel!.stream.listen(
        _handleMessage,
        onDone: _onDone,
        onError: _onError,
        cancelOnError: true,
      );

      // Enviar token Sanctum (action=auth)
      _send({'action': 'auth', 'token': token});
      _startPing();
    } catch (e) {
      debugPrint('[RealtimeService] Error al conectar: $e');
      _scheduleReconnect();
    }
  }

  void _startPing() {
    _pingTimer?.cancel();
    _pingTimer = Timer.periodic(const Duration(seconds: 35), (_) {
      if (_isConnected) {
        _send({'action': 'ping'});
      }
    });
  }

  void _handleMessage(dynamic raw) {
    try {
      final data = jsonDecode(raw as String) as Map<String, dynamic>;
      final type = data['type'] as String?;

      switch (type) {
        case 'authenticated':
          _isConnected = true;
          final authMap = data['auth'] as Map<String, dynamic>? ?? {};
          _currentAuth = RealtimeAuth(
            type: authMap['type']?.toString() ?? '',
            id: int.tryParse(authMap['id']?.toString() ?? '0') ?? 0,
          );
          if (_authCompleter != null && !_authCompleter!.isCompleted) {
            _authCompleter!.complete(_currentAuth);
          }
          // Re-suscribir topics que estaban activos
          for (final topic in _activeTopics) {
            _send({'action': 'subscribe', 'topic': topic});
          }
          break;

        case 'broadcast':
          if (!_broadcastController.isClosed) {
            _broadcastController.add(data);
          }
          break;

        case 'auth_error':
          _isConnected = false;
          if (_authCompleter != null && !_authCompleter!.isCompleted) {
            _authCompleter!.completeError(Exception(data['message'] ?? 'Error de autenticación WS'));
          }
          break;

        case 'subscribed':
        case 'unsubscribed':
        case 'pong':
        case 'ready':
          break;
      }
    } catch (e) {
      debugPrint('[RealtimeService] Error al decodificar mensaje: $e');
    }
  }

  /// Se suscribe a un topic
  void subscribe(String topic) {
    if (topic.trim().isEmpty) return;
    _activeTopics.add(topic);
    if (_isConnected) {
      _send({'action': 'subscribe', 'topic': topic});
    }
  }

  /// Cancela la suscripción a un topic
  void unsubscribe(String topic) {
    _activeTopics.remove(topic);
    if (_isConnected) {
      _send({'action': 'unsubscribe', 'topic': topic});
    }
  }

  void _send(Map<String, dynamic> msg) {
    try {
      _channel?.sink.add(jsonEncode(msg));
    } catch (e) {
      debugPrint('[RealtimeService] Error enviando mensaje: $e');
    }
  }

  void _onDone() {
    _isConnected = false;
    _scheduleReconnect();
  }

  void _onError(Object error) {
    _isConnected = false;
    debugPrint('[RealtimeService] Error de canal WS: $error');
    if (_authCompleter != null && !_authCompleter!.isCompleted) {
      _authCompleter!.completeError(error);
    }
    _scheduleReconnect();
  }

  void _scheduleReconnect() {
    if (_isDisposed) return;
    _pingTimer?.cancel();
    _reconnectTimer?.cancel();
    _reconnectTimer = Timer(const Duration(seconds: 4), () {
      if (!_isDisposed && !_isConnected) {
        debugPrint('[RealtimeService] Intentando reconexión automática...');
        _initSocket();
      }
    });
  }

  void dispose() {
    _isDisposed = true;
    _isConnected = false;
    _pingTimer?.cancel();
    _reconnectTimer?.cancel();
    _channel?.sink.close();
    if (!_broadcastController.isClosed) {
      _broadcastController.close();
    }
  }
}

/// Singleton para acceder al servicio de tiempo real en cualquier parte de la app.
class RealtimeManager {
  static final RealtimeManager _instance = RealtimeManager._internal();
  factory RealtimeManager() => _instance;
  RealtimeManager._internal();

  RealtimeService? _service;

  RealtimeService? get service => _service;
  bool get isConnected => _service?.isConnected ?? false;
  Stream<Map<String, dynamic>> get onBroadcast =>
      _service?.onBroadcast ?? const Stream.empty();

  Future<RealtimeAuth?> init({required String wsUrl, required String token}) async {
    if (_service != null && _service!.isConnected && _service!.token == token) {
      return _service!.auth;
    }
    _service?.dispose();
    _service = RealtimeService(wsUrl: wsUrl, token: token);
    return await _service!.connect();
  }

  void subscribe(String topic) => _service?.subscribe(topic);
  void unsubscribe(String topic) => _service?.unsubscribe(topic);

  Stream<Map<String, dynamic>> onTopic(String topic) {
    return onBroadcast.where((event) => event['topic'] == topic);
  }

  void dispose() {
    _service?.dispose();
    _service = null;
  }
}
