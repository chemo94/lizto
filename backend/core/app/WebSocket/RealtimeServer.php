<?php

namespace App\WebSocket;

use Exception;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Ratchet\RFC6455\Messaging\CloseFrameChecker;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\Message;
use Ratchet\RFC6455\Messaging\MessageBuffer;
use React\EventLoop\Loop;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;

/**
 * Servidor WebSocket nativo (sin Reverb/Pusher, sin plataformas externas).
 *
 * Protocolo WebSocket (RFC 6455):
 *   - El handshake lo hace esta clase directamente (SHA1 + GUID, estable).
 *   - El parsing de frames lo delega a ratchet/rfc6455 (ya instalado).
 *
 * Flujo de datos en vivo (taxi / repartidor moviéndose en el mapa):
 *   1. La app del repartidor/taxi envía su posición por HTTP a la API.
 *   2. La API publica en Redis Pub/Sub el canal "realtime.driver.<id>" un JSON
 *      con {lat, lng, bearing, speed, ...}.
 *   3. Este servidor está suscrito a Redis vía un subproceso ligero y reenvía
 *      el mensaje a cada cliente Flutter suscrito a ese topic.
 *
 * Protocolo de mensajes (JSON) entre cliente Flutter y este servidor:
 *
 *   Cliente -> Servidor:
 *     {"action":"subscribe","topic":"driver.123"}
 *     {"action":"unsubscribe","topic":"driver.123"}
 *     {"action":"ping"}
 *
 *   Servidor -> Cliente:
 *     {"type":"ready"}
 *     {"type":"subscribed","topic":"driver.123"}
 *     {"type":"unsubscribed","topic":"driver.123"}
 *     {"type":"broadcast","topic":"driver.123","payload":{...},"ts":...}
 *     {"type":"pong"}
 *     {"type":"error","message":"..."}
 */
class RealtimeServer
{
    protected SocketServer $socket;

    /** @var array<string, \SplObjectStorage> topic => conexiones suscritas */
    protected array $subscriptions = [];

    /** @var \Redis|null */
    protected ?\Redis $redisSub = null;

    /** @var resource|null stream hacia Redis (cliente RESP nativo) */
    protected $redisStream = null;

    /** @var string buffer de entrada del stream Redis */
    protected string $redisBuffer = '';

    /** @var array<int, array> auth resuelta por conexión (splObjectId) => {type, id, service_type} */
    protected array $authByConn = [];

    /** @var array<int, bool> conexiones ya autenticadas (splObjectId => true) */
    protected array $authedByConn = [];

    public function __construct(
        protected string $host = '0.0.0.0',
        protected int $port = 8085,
    ) {
    }

    public function run(): void
    {
        $loop = Loop::get();

        $this->socket = new SocketServer("{$this->host}:{$this->port}", [], $loop);
        $this->log("WebSocket nativo escuchando en {$this->host}:{$this->port}");

        $this->socket->on('connection', function (ConnectionInterface $conn) {
            $this->handleConnection($conn);
        });

        $this->socket->on('error', function (Exception $e) {
            $this->log('Socket error: ' . $e->getMessage());
        });

        $this->connectRedis();

        // Keepalive / estado.
        $loop->addPeriodicTimer(30, function () {
            $this->log('Estado: ' . count($this->subscriptions) . ' topics activos');
        });

        $loop->run();
    }

    protected function handleConnection(ConnectionInterface $conn): void
    {
        // Estado del handshake para esta conexión.
        $state = new \stdClass();
        $state->handshaken = false;
        $state->buffer = null;

        $conn->on('data', function (?string $data) use ($conn, $state) {
            if ($data === null || $data === '') {
                return;
            }

            if (!$state->handshaken) {
                if (!$this->doHandshake($conn, $data)) {
                    $conn->end();
                    return;
                }
                $state->handshaken = true;
                $this->send($conn, ['type' => 'ready', 'message' => 'Conectado']);

                // Crear el buffer de frames para lo que venga después.
                $state->buffer = new MessageBuffer(
                    new CloseFrameChecker(),
                    function (Message $message) use ($conn) {
                        $this->onMessage($conn, (string) $message);
                    },
                    function (Frame $frame) use ($conn) {
                        if ($frame->getOpcode() === Frame::OP_PING) {
                            $pong = new Frame($frame->getPayload(), true, Frame::OP_PONG);
                            $conn->write($pong->getContents());
                        }
                    },
                    true,
                    null,
                    8388608,
                    1048576,
                    function (string $out) use ($conn) {
                        $conn->write($out);
                    }
                );

                // Si en el mismo paquete vinieron bytes de frames después del
                // handshake, procesarlos.
                $remainder = substr($data, strpos($data, "\r\n\r\n") + 4);
                if ($remainder !== '' && $remainder !== false) {
                    $state->buffer->onData($remainder);
                }
                return;
            }

            // Post-handshake: alimentar el buffer de frames.
            if ($state->buffer) {
                $state->buffer->onData($data);
            }
        });

        $conn->on('close', function () use ($conn) {
            $this->onClose($conn);
        });
    }

    /**
     * Handshake WebSocket (RFC 6455). Retorna true si respondió 101.
     */
    protected function doHandshake(ConnectionInterface $conn, string $raw): bool
    {
        // Buscar el final del encabezado HTTP.
        $pos = strpos($raw, "\r\n\r\n");
        if ($pos === false) {
            // Encabezado incompleto; esperar más datos es complejo, asumimos
            // que el primer chunk ya trae el handshake completo (caso habitual).
            return false;
        }

        $header = substr($raw, 0, $pos);
        $lines = explode("\r\n", $header);

        if (!isset($lines[0]) || !str_contains($lines[0], 'GET')) {
            return false;
        }

        $headers = [];
        foreach ($lines as $i => $line) {
            if ($i === 0) {
                continue;
            }
            $sep = strpos($line, ':');
            if ($sep === false) {
                continue;
            }
            $name = strtolower(trim(substr($line, 0, $sep)));
            $value = trim(substr($line, $sep + 1));
            $headers[$name] = $value;
        }

        $key = $headers['sec-websocket-key'] ?? null;
        if (!$key) {
            return false;
        }

        $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $response = "HTTP/1.1 101 Switching Protocols\r\n"
            . "Upgrade: websocket\r\n"
            . "Connection: Upgrade\r\n"
            . "Sec-WebSocket-Accept: {$accept}\r\n"
            . "X-Powered-By: Liz-Realtime\r\n\r\n";

        $conn->write($response);
        $this->log('Handshake OK desde ' . $conn->getRemoteAddress());
        return true;
    }

    protected function onMessage(ConnectionInterface $conn, string $raw): void
    {
        $data = json_decode($raw, true);

        if (!is_array($data) || !isset($data['action'])) {
            $this->send($conn, ['type' => 'error', 'message' => 'Falta "action"']);
            return;
        }

        switch ($data['action']) {
            case 'auth':
                $this->handleAuth($conn, $data);
                break;

            case 'subscribe':
                if (empty($this->authedByConn[$this->oid($conn)])) {
                    $this->send($conn, ['type' => 'error', 'message' => 'Autentícate primero (action=auth)']);
                    return;
                }
                $topic = $data['topic'] ?? null;
                if (!$topic) {
                    $this->send($conn, ['type' => 'error', 'message' => 'Falta "topic"']);
                    return;
                }
                if (!$this->canSubscribe($conn, $topic)) {
                    $this->send($conn, ['type' => 'error', 'message' => 'No autorizado para el topic ' . $topic]);
                    return;
                }
                $this->subscribe($conn, $topic);
                break;

            case 'unsubscribe':
                $topic = $data['topic'] ?? null;
                if ($topic) {
                    $this->unsubscribe($conn, $topic);
                    $this->send($conn, ['type' => 'unsubscribed', 'topic' => $topic]);
                }
                break;

            case 'ping':
                $this->send($conn, ['type' => 'pong', 'ts' => time()]);
                break;

            default:
                $this->send($conn, ['type' => 'error', 'message' => 'Acción no reconocida: ' . $data['action']]);
        }
    }

    /**
     * Resuelve el token Sanctum y autoriza a la conexión según su rol
     * (user=cliente/pasajero, driver=taxi/repartidor, seller=vendedor).
     */
    protected function handleAuth(ConnectionInterface $conn, array $data): void
    {
        $token = $data['token'] ?? null;
        if (!$token) {
            $this->send($conn, ['type' => 'auth_error', 'message' => 'Falta token']);
            return;
        }

        try {
            $accessToken = PersonalAccessToken::findToken($token);
            $tokenable = $accessToken?->tokenable;
            if (!$tokenable) {
                $this->send($conn, ['type' => 'auth_error', 'message' => 'Token inválido o expirado']);
                return;
            }

            $oid = $this->oid($conn);

            if ($tokenable instanceof \App\Models\Driver) {
                $this->authByConn[$oid] = [
                    'type'         => 'driver',
                    'id'           => (int) $tokenable->id,
                    'service_type' => $tokenable->service_type, // 'ride' (taxi) | 'delivery' (repartidor)
                ];
            } elseif ($tokenable instanceof \App\Models\Seller) {
                $this->authByConn[$oid] = ['type' => 'seller', 'id' => (int) $tokenable->id];
            } elseif ($tokenable instanceof \App\Models\User) {
                $this->authByConn[$oid] = ['type' => 'user', 'id' => (int) $tokenable->id];
            } else {
                $this->send($conn, ['type' => 'auth_error', 'message' => 'Tipo de token no soportado']);
                return;
            }

            $this->authedByConn[$oid] = true;
            $this->send($conn, ['type' => 'authenticated', 'auth' => [
                'type' => $this->authByConn[$oid]['type'],
                'id'   => $this->authByConn[$oid]['id'],
            ]]);
            $this->log('Auth OK -> ' . $this->authByConn[$oid]['type'] . ' ' . $this->authByConn[$oid]['id']);
        } catch (\Throwable $e) {
            $this->send($conn, ['type' => 'auth_error', 'message' => 'Error de autenticación']);
            $this->log('Auth error: ' . $e->getMessage());
        }
    }

    protected function oid(ConnectionInterface $conn): int
    {
        return spl_object_id($conn);
    }

    /**
     * Reglas de autorización por rol.
     *   - user (cliente/pasajero): puede ver rider del viaje/delivery donde es
     *     partícipe (ride.{id} / order.{id} / favor.{id} si le pertenece).
     *   - driver: ve su propio canal driver.{id}; un ride/order donde está asignado.
     *   - seller: ve order.{id}/favor.{id} de su tienda.
     *
     * Para verificación de pertenencia se consulta la BD (barato, solo en
     * subscribe). Se usa DB::table con conexión configurada.
     */
    protected function canSubscribe(ConnectionInterface $conn, string $topic): bool
    {
        $oid = $this->oid($conn);
        $auth = $this->authByConn[$oid] ?? null;
        if (!$auth) {
            return false;
        }

        $type = $auth['type'];
        $id   = $auth['id'];

        // driver.{id}: solo el propio driver o un user autorizado (ride/order activo).
        if (preg_match('/^driver\.(\d+)$/', $topic, $m)) {
            $driverId = (int) $m[1];
            if ($type === 'driver' && $id === $driverId) {
                return true;
            }
            // user: permitir solo si tiene un ride/order/favor activo con ese driver.
            if ($type === 'user') {
                return $this->userHasActiveTripWithDriver($id, $driverId);
            }
            return false;
        }

        // ride.{id}: pasajero (user) dueño del ride, o taxista asignado.
        if (preg_match('/^ride\.(\d+)$/', $topic, $m)) {
            $rideId = (int) $m[1];
            $ride = DB::table('rides')->where('id', $rideId)->first(['user_id', 'driver_id']);
            if (!$ride) {
                return false;
            }
            if ($type === 'user' && (int) $ride->user_id === $id) {
                return true;
            }
            if ($type === 'driver' && (int) $ride->driver_id === $id) {
                return true;
            }
            return false;
        }

        // order.{id}: cliente (user) dueño, repartidor asignado, o seller de la tienda.
        if (preg_match('/^order\.(\d+)$/', $topic, $m)) {
            $orderId = (int) $m[1];
            $order = DB::table('delivery_orders')->where('id', $orderId)->first(['user_id', 'driver_id', 'courier_id', 'store_id']);
            if (!$order) {
                return false;
            }
            if ($type === 'user' && (int) $order->user_id === $id) {
                return true;
            }
            $courierId = (int) ($order->courier_id ?: $order->driver_id ?? 0);
            if ($type === 'driver' && $courierId === $id) {
                return true;
            }
            if ($type === 'seller' && (int) $order->store_id === $id) {
                return true;
            }
            return false;
        }

        // favor.{id}: cliente (user), courier (driver), o seller.
        if (preg_match('/^favor\.(\d+)$/', $topic, $m)) {
            $favorId = (int) $m[1];
            $favor = DB::table('favors')->where('id', $favorId)->first(['user_id', 'seller_id', 'courier_id']);
            if (!$favor) {
                return false;
            }
            if ($type === 'user' && (int) $favor->user_id === $id) {
                return true;
            }
            if ($type === 'driver' && (int) ($favor->courier_id ?? 0) === $id) {
                return true;
            }
            if ($type === 'seller' && (int) $favor->seller_id === $id) {
                return true;
            }
            return false;
        }

        return false;
    }

    protected function userHasActiveTripWithDriver(int $userId, int $driverId): bool
    {
        // Ride activo (no finalizado/cancelado).
        $ride = DB::table('rides')
            ->where('user_id', $userId)
            ->where('driver_id', $driverId)
            ->whereNotIn('status', [9, 0]) // ajustar según estados de Ride
            ->exists();
        if ($ride) {
            return true;
        }

        $order = DB::table('delivery_orders')
            ->where('user_id', $userId)
            ->where(function ($q) use ($driverId) {
                $q->where('driver_id', $driverId)->orWhere('courier_id', $driverId);
            })
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->exists();
        if ($order) {
            return true;
        }

        $favor = DB::table('favors')
            ->where('user_id', $userId)
            ->where('courier_id', $driverId)
            ->exists();

        return (bool) $favor;
    }

    protected function subscribe(ConnectionInterface $conn, string $topic): void
    {
        if (!isset($this->subscriptions[$topic])) {
            $this->subscriptions[$topic] = new \SplObjectStorage();
        }
        $this->subscriptions[$topic]->attach($conn);
        $this->send($conn, ['type' => 'subscribed', 'topic' => $topic]);
        $this->log('Suscripción -> ' . $topic . ' (' . $this->subscriptions[$topic]->count() . ' cliente/s)');
    }

    protected function unsubscribe(ConnectionInterface $conn, string $topic): void
    {
        if (isset($this->subscriptions[$topic])) {
            $this->subscriptions[$topic]->detach($conn);
            if ($this->subscriptions[$topic]->count() === 0) {
                unset($this->subscriptions[$topic]);
            }
        }
    }

    protected function onClose(ConnectionInterface $conn): void
    {
        foreach ($this->subscriptions as $topic => $set) {
            if ($set->contains($conn)) {
                $set->detach($conn);
                if ($set->count() === 0) {
                    unset($this->subscriptions[$topic]);
                }
            }
        }
        $this->log('Cliente desconectado');
    }

    /**
     * Conecta a Redis y se suscribe a Pub/Sub usando el protocolo RESP nativo
     * dentro del event loop de React (sin fork, sin subproceso, sin socketpair).
     *
     * Esto es robusto y usa únicamente react/socket (ya instalado).
     */
    protected function connectRedis(): void
    {
        $host = (string) config('database.redis.default.host', '127.0.0.1');
        $port = (int) config('database.redis.default.port', 6379);
        $password = (string) config('database.redis.default.password', '');

        $connector = new \React\Socket\Connector(Loop::get());

        $connector->connect($host . ':' . $port)->then(
            function (ConnectionInterface $conn) use ($password) {
                $this->redisStream = $conn;
                $this->redisBuffer = '';

                $conn->on('data', function (string $data) {
                    $this->onRedisData($data);
                });

                $conn->on('close', function () {
                    $this->log('Redis disconnected — reintentando en 3s');
                    $this->redisStream = null;
                    Loop::addTimer(3, fn () => $this->connectRedis());
                });

                $conn->on('error', function (\Throwable $e) {
                    $this->log('Redis error: ' . $e->getMessage());
                });

                // AUTH opcional y luego PSUBSCRIBE.
                if ($password !== '') {
                    $conn->write($this->redisCommand('AUTH', [$password]));
                }
                $conn->write($this->redisCommand('PSUBSCRIBE', ['realtime.*']));
                $this->log('Redis Pub/Sub conectado (realtime.*)');
            },
            function (\Throwable $e) {
                $this->log('No se pudo conectar a Redis: ' . $e->getMessage() . ' — reintentando en 3s');
                Loop::addTimer(3, fn () => $this->connectRedis());
            }
        );
    }

    /**
     * Procesa bytes entrantes desde el stream de Redis (RESP).
     * Sólo nos interesan los mensajes de Pub/Sub: el array de 3 o 4 elementos
     * donde el segundo es "pmessage"/"message" y el último es el payload.
     */
    protected function onRedisData(string $data): void
    {
        $this->redisBuffer .= $data;

        while (true) {
            $frame = $this->parseResp($this->redisBuffer);
            if ($frame === null) {
                break; // buffer incompleto, esperar más datos
            }
            [$value] = $frame;
            $this->handleRedisMessage($value);
        }
    }

    /**
     * Parsea el primer valor RESP del buffer. Devuelve [valor] y consume el
     * buffer, o null si el buffer está incompleto. Devuelve [null] si el
     * formato es inválido (y descarta un byte para re-sincronizar).
     *
     * @return array{0:mixed,1?:int}|null
     */
    protected function parseResp(string &$buffer): ?array
    {
        if ($buffer === '') {
            return null;
        }

        $type = $buffer[0];
        $crlf = strpos($buffer, "\r\n");

        if ($type === '*') {
            // Array (multibulk)
            if ($crlf === false) {
                return null;
            }
            $count = (int) substr($buffer, 1, $crlf - 1);
            $offset = $crlf + 2;
            $items = [];
            for ($i = 0; $i < $count; $i++) {
                $sub = substr($buffer, $offset);
                $parsed = $this->parseResp($sub);
                if ($parsed === null) {
                    return null; // incompleto
                }
                $items[] = $parsed[0];
                $offset += $parsed[1];
            }
            $buffer = substr($buffer, $offset);
            return [$items, $offset];
        }

        if ($crlf === false) {
            return null;
        }

        if ($type === '$') {
            // Bulk string
            $len = (int) substr($buffer, 1, $crlf - 1);
            $dataStart = $crlf + 2;
            if (strlen($buffer) < $dataStart + $len + 2) {
                return null;
            }
            $value = substr($buffer, $dataStart, $len);
            $total = $dataStart + $len + 2;
            $buffer = substr($buffer, $total);
            return [$value, $total];
        }

        if ($type === ':' || $type === '+') {
            // Integer / simple string
            $value = substr($buffer, 1, $crlf - 1);
            $total = $crlf + 2;
            $buffer = substr($buffer, $total);
            return [$value, $total];
        }

        if ($type === '-') {
            // Error
            $value = substr($buffer, 1, $crlf - 1);
            $total = $crlf + 2;
            $buffer = substr($buffer, $total);
            return [$value, $total];
        }

        // Tipo desconocido: descartar un byte.
        $buffer = substr($buffer, 1);
        return [null, 1];
    }

    protected function handleRedisMessage(mixed $value): void
    {
        if (!is_array($value)) {
            return;
        }

        $kind = $value[0] ?? null;

        // pmessage: [pmessage, pattern, channel, payload]
        // message:  [message, channel, payload]
        if ($kind === 'pmessage' && count($value) >= 4) {
            $channel = (string) $value[2];
            $payload = (string) $value[3];
            // El canal Redis es "realtime.driver.123"; el topic WS es sin prefijo.
            $topic = str_starts_with($channel, 'realtime.') ? substr($channel, 9) : $channel;
            $this->publishToTopic($topic, $payload);
        } elseif ($kind === 'message' && count($value) >= 3) {
            $channel = (string) $value[1];
            $payload = (string) $value[2];
            $topic = str_starts_with($channel, 'realtime.') ? substr($channel, 9) : $channel;
            $this->publishToTopic($topic, $payload);
        }
    }

    /**
     * Construye un comando RESP a partir de nombre + argumentos.
     */
    protected function redisCommand(string $cmd, array $args = []): string
    {
        $parts = array_merge([$cmd], $args);
        $out = '*' . count($parts) . "\r\n";
        foreach ($parts as $p) {
            $p = (string) $p;
            $out .= '$' . strlen($p) . "\r\n" . $p . "\r\n";
        }
        return $out;
    }

    /**
     * Distribuye un payload JSON a todas las conexiones suscritas al topic.
     */
    public function publishToTopic(string $topic, string $payloadJson): void
    {
        if (!isset($this->subscriptions[$topic]) || $this->subscriptions[$topic]->count() === 0) {
            return;
        }

        $decoded = json_decode($payloadJson, true);
        $envelope = [
            'type'    => 'broadcast',
            'topic'   => $topic,
            'payload' => $decoded !== null ? $decoded : $payloadJson,
            'ts'      => time(),
        ];

        /** @var ConnectionInterface $conn */
        foreach ($this->subscriptions[$topic] as $conn) {
            $this->send($conn, $envelope);
        }
    }

    protected function send(ConnectionInterface $conn, array $data): void
    {
        try {
            $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
            $frame = new Frame($payload, true, Frame::OP_TEXT);
            $conn->write($frame->getContents());
        } catch (\Throwable $e) {
            // conexión probablemente cerrada
        }
    }

    protected function log(string $msg): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
        @file_put_contents(storage_path('logs/realtime-server.log'), $line, FILE_APPEND);
        echo $line;
    }

    /** Accesor para tests. */
    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }
}
