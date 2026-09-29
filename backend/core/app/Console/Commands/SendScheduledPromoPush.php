<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\FcmService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendScheduledPromoPush extends Command
{
    protected $signature = 'promo:push-horarios
                            {--slot= : Franja horaria concreta (manana|media_manana|almuerzo|lonche|cena|postre|super|mascotas|fin_semana). Si se omite, se decide por la hora actual}
                            {--dry-run : Solo muestra qué se enviaría, sin enviar}';

    protected $description = 'Envía push FCM automático a clientes según la franja del día, rotando variantes y filtrando tiendas cerradas';

    /**
     * Franjas horarias: hora objetivo y lista de `act` (variantes) de
     * notification_templates. El comando rota entre las variantes de cada
     * franja (elige aleatoria) y, antes de enviar, descarta las plantillas
     * cuyas tiendas están cerradas (verificación real de horarios de apertura).
     *
     * Cada variante declara las tiendas que menciona (por nombre o id); si
     * TODAS están cerradas, la variante se descarta. Las horas se evalúan en
     * el timezone de la app (America/Lima).
     */
    private const SLOTS = [
        'manana' => [
            'hour'     => 8,
            'variants' => [
                ['act' => 'PROMO_MANANA', 'stores' => ['Shaddaii']],
            ],
        ],
        'media_manana' => [
            'hour'     => 11,
            'variants' => [
                ['act' => 'PROMO_MEDIA_MANANA', 'stores' => ['Tinpu', 'Shaddaii']],
            ],
        ],
        'almuerzo' => [
            'hour'     => 12,
            'variants' => [
                ['act' => 'PROMO_ALMUERZO',   'stores' => []],
                ['act' => 'PROMO_ALMUERZO_2', 'stores' => ['Puerto Azul']],
                ['act' => 'PROMO_ALMUERZO_3', 'stores' => ['El Parrillero Jhak']],
            ],
        ],
        'lonche' => [
            'hour'     => 16,
            'variants' => [
                ['act' => 'PROMO_LONCHE',   'stores' => []],
                ['act' => 'PROMO_LONCHE_2', 'stores' => ['Shaddaii']],
            ],
        ],
        'cena' => [
            'hour'     => 18,
            'variants' => [
                ['act' => 'PROMO_CENA',   'stores' => []],
                ['act' => 'PROMO_CENA_2', 'stores' => ['El Parrillero Jhak', 'Puerto Azul']],
            ],
        ],
        'postre' => [
            'hour'     => 21,
            'variants' => [
                ['act' => 'PROMO_POSTRE',   'stores' => []],
                ['act' => 'PROMO_POSTRE_2', 'stores' => ['Clandestino & Cuket', 'El Parrillero Jhak']],
            ],
        ],
        'super' => [
            'hour'     => 14,
            'variants' => [
                ['act' => 'PROMO_SUPER', 'stores' => ['Ari Market']],
            ],
        ],
        'mascotas' => [
            'hour'     => 11,
            'variants' => [
                ['act' => 'PROMO_MASCOTAS', 'stores' => ['CATDOG']],
            ],
        ],
        'fin_semana' => [
            'hour'     => 12,
            'variants' => [
                ['act' => 'PROMO_FIN_SEMANA', 'stores' => []],
            ],
        ],
    ];

    public function handle(): int
    {
        $slotKey = $this->option('slot');
        if ($slotKey) {
            if (!isset(self::SLOTS[$slotKey])) {
                $this->error("Franja no válida: {$slotKey}. Opciones: " . implode(', ', array_keys(self::SLOTS)));
                return self::FAILURE;
            }
            return $this->dispatchSlot($slotKey, self::SLOTS[$slotKey]);
        }

        // Sin --slot: decidir según la hora actual (America/Lima).
        $nowHour = (int) now()->format('G'); // 0-23

        $slot = $this->resolveSlotForHour($nowHour);
        if ($slot === null) {
            $this->info("Fuera de franja (hora {$nowHour}). No se envía nada.");
            return self::SUCCESS;
        }

        [$key, $data] = $slot;
        return $this->dispatchSlot($key, $data);
    }

    /**
     * Lee título y cuerpo desde notification_templates; si el registro no existe
     * o está vacío, la variante se descarta (no hay fallback hardcodeado).
     */
    private function resolveContent(string $act): ?array
    {
        $record = DB::table('notification_templates')
            ->where('act', $act)
            ->first(['push_title', 'push_body']);

        $title = $record->push_title ?? null;
        $body  = $record->push_body ?? null;

        if (empty($title) || empty($body)) {
            return null;
        }

        return [$title, $body];
    }

    /**
     * Resuelve la franja según la hora actual, respetando la duración definida.
     */
    private function resolveSlotForHour(int $hour): ?array
    {
        foreach (self::SLOTS as $key => $slot) {
            $target = $slot['hour'];
            $end = $target + ($key === 'cena' ? 2 : 1);
            if ($hour >= $target && $hour < $end) {
                return [$key, $slot];
            }
        }
        return null;
    }

    /**
     * Verifica si una tienda está abierta AHORA, mirando primero los horarios
     * por día (store_schedules) y cayendo a opening_time/closing_time. Reutiliza
     * la misma lógica del accessor is_open_now del modelo Store.
     */
    private function isStoreOpenNow(string $storeName): bool
    {
        $store = Store::where('name', $storeName)->first();
        if (!$store) {
            // Si no encontramos la tienda por nombre, intentamos por coincidencia.
            $store = Store::where('name', 'LIKE', "%{$storeName}%")->first();
            if (!$store) {
                return false;
            }
        }

        return (bool) $store->is_open_now;
    }

    /**
     * Dado el listado de tiendas de una variante, decide si es válida enviarla:
     * - Sin tiendas declaradas → genérico, siempre válido.
     * - Con tiendas → al menos una debe estar abierta AHORA.
     */
    private function variantIsSendable(array $variant): bool
    {
        $stores = $variant['stores'] ?? [];

        if (empty($stores)) {
            return true;
        }

        foreach ($stores as $storeName) {
            if ($this->isStoreOpenNow($storeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Selecciona una variante válida de la franja (rotación aleatoria entre las
     * que tienen sus tiendas abiertas). Devuelve null si ninguna es enviable.
     */
    private function pickVariant(array $slot): ?array
    {
        $variants = $slot['variants'] ?? [];

        // Filtrar variantes sin contenido o con todas sus tiendas cerradas.
        $sendable = [];
        foreach ($variants as $variant) {
            if (!$this->variantIsSendable($variant)) {
                continue;
            }
            $content = $this->resolveContent($variant['act']);
            if ($content === null) {
                continue;
            }
            $sendable[] = ['act' => $variant['act'], 'title' => $content[0], 'body' => $content[1]];
        }

        if (empty($sendable)) {
            return null;
        }

        return $sendable[array_rand($sendable)];
    }

    private function dispatchSlot(string $key, array $slot): int
    {
        $variant = $this->pickVariant($slot);

        if ($variant === null) {
            $this->warn("Franja '{$key}': sin variantes enviables (sin contenido o tiendas cerradas).");
            return self::SUCCESS;
        }

        $title = $variant['title'];
        $body  = $variant['body'];
        $act   = $variant['act'];

        $data = [
            'type'         => 'promo',
            'slot'         => $key,
            'act'          => $act,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'for_app'      => 'home',
        ];

        if ($this->option('dry-run')) {
            $this->info("[dry-run] Franja '{$key}' ({$act}) → '{$title}' / '{$body}'");
            return self::SUCCESS;
        }

        $this->info("Enviando push '{$key}' ({$act}) → '{$title}' / '{$body}'");

        $ok = FcmService::sendToAllUsers($title, $body, $data);

        if ($ok) {
            $this->info('Push enviado correctamente.');
            return self::SUCCESS;
        }

        $this->warn('No se pudo enviar el push (sin tokens o push deshabilitado). Revisa el log.');
        return self::FAILURE;
    }
}
