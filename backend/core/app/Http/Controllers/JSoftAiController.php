<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DeepSeekService;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;

class JSoftAiController extends Controller
{
    protected $deepSeekService;

    public function __construct(DeepSeekService $deepSeekService)
    {
        $this->deepSeekService = $deepSeekService;
    }

    public function chat(Request $request)
    {
        $isAdmin = auth('admin')->check();
        $isSeller = session()->has('seller_id');

        if (!$isAdmin && !$isSeller) {
            return response()->json([
                'success' => false,
                'message' => [
                    'role' => 'assistant', 
                    'content' => 'No autorizado. Debes iniciar sesión como vendedor o administrador para conversar con JSoft AI.'
                ]
            ], 403);
        }

        $sellerId = $isSeller ? session()->get('seller_id') : null;
        $cacheKey = null;
        $usage = 0;

        // Plan limitation check for Sellers
        if ($isSeller && !$isAdmin) {
            $store = Store::withActivePackages()->where('seller_id', $sellerId)->first();
            if (!$store || !$store->hasPaidPackage()) {
                return response()->json([
                    'success' => false,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Se requiere un plan de suscripción activo para poder utilizar el asistente JSoft AI. Por favor, adquiere un plan de suscripción.'
                    ]
                ]);
            }

            $packages = $store->storePackages->filter(fn($sp) => $sp->isActive());
            $maxLimit = 0;
            $planName = 'Sin Plan';

            foreach ($packages as $sp) {
                $type = $sp->package?->type; // basic, featured, premium
                if ($type === 'premium') {
                    $maxLimit = 999999;
                    $planName = 'Premium (Ilimitado)';
                    break;
                } elseif ($type === 'featured') {
                    $maxLimit = max($maxLimit, 100);
                    $planName = 'Destacado';
                } elseif ($type === 'basic') {
                    $maxLimit = max($maxLimit, 20);
                    $planName = 'Básico';
                }
            }

            // If limit is reached
            if ($maxLimit > 0 && $maxLimit < 999999) {
                $cacheKey = "jsoft_ai_queries_count_" . $store->id . "_" . date('Y_m');
                $usage = Cache::get($cacheKey, 0);

                if ($usage >= $maxLimit) {
                    return response()->json([
                        'success' => false,
                        'message' => [
                            'role' => 'assistant',
                            'content' => "Has alcanzado el límite mensual de consultas a JSoft AI para tu plan **{$planName}** ({$maxLimit} consultas). " .
                                         "Por favor, actualiza a un plan superior en la sección de planes para disfrutar de consultas ilimitadas."
                        ]
                    ]);
                }
            }
        }

        $request->validate([
            'messages' => 'required|array',
            'messages.*.role' => 'required|string|in:user,assistant,system,tool',
            'messages.*.content' => 'required|string',
        ]);

        $messages = $request->input('messages');
        $role = $isAdmin ? 'admin' : 'seller';
        
        $responseMessage = $this->deepSeekService->chat($messages, $role, $sellerId);

        // Increment query usage if call was successful and limits apply
        if ($cacheKey !== null && isset($responseMessage['content']) && strpos($responseMessage['content'], 'Error') === false) {
            Cache::put($cacheKey, $usage + 1, now()->addMonth());
        }

        return response()->json([
            'success' => true,
            'message' => $responseMessage
        ]);
    }
}
