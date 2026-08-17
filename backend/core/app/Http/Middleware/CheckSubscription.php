<?php

namespace App\Http\Middleware;

use App\Models\PosStaff;
use App\Models\Store;

use Closure;
use Illuminate\Http\Request;

class CheckSubscription
{
    private array $featureMap = [
        'invoicing'     => ['basic', 'featured', 'premium'],
        'reports'       => ['featured', 'premium'],
        'notifications' => ['premium'],
        'inventory'     => ['featured', 'premium'],
        'cover_video'   => ['premium'],
    ];

    public function handle(Request $request, Closure $next, string $feature = '')
    {
        // Resolve seller ID from web session OR Sanctum API token
        $sellerId = session('seller_id');

        if (!$sellerId) {
            $user = auth()->user();
            if ($user instanceof \App\Models\Seller) {
                $sellerId = $user->id;
            } elseif ($user instanceof \App\Models\PosStaff) {
                $sellerId = $user->seller_id;
            }
        }

        if (!$sellerId) {
            return $next($request);
        }

        $store = Store::withActivePackages()->where('seller_id', $sellerId)->first();
        $hasPaidPlan = $store && $store->hasPaidPackage();


        $route = $request->route() ? $request->route()->getName() : '';
        $exempted = [
            'seller.pricing',
            'seller.pricing.checkout',
            'seller.pricing.return',
            'seller.delivery.request',
            'seller.delivery.request.submit',
            'seller.delivery.fee-calculate',
            'seller.delivery.request.status.show',
            'seller.delivery.request.cancel',
            'seller.delivery.request.status',
            'seller.delivery.request.return',
            'seller.delivery.order.status.show',
            'seller.logout',
            'seller.login',
            'seller.web.login',
            'seller.register',
            'seller.token.login',
            'seller.broadcasting.auth',
            'seller.save-token'
        ];

        if (in_array($route, $exempted) || $request->is('seller/delivery*') || ($route && str_starts_with($route, 'seller.delivery'))) {
            return $next($request);
        }

        if (!$hasPaidPlan) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['status' => 'error', 'message' => 'Se requiere un plan activo para esta función.'], 403);
            }
            return redirect()->route('seller.pricing')->with('error', 'Selecciona un plan para acceder a todas las funcionalidades del sistema.');
        }

        $activePackage = $store->storePackages->filter(fn($sp) => $sp->isActive())->first();
        $hasAccess = false;

        if ($activePackage && $activePackage->package) {
            $features = (array) ($activePackage->package->features ?? []);
            foreach ($features as $f) {
                $fObj = (object) $f;
                if (isset($fObj->key) && $fObj->key === $feature) {
                    $val = strtolower(trim($fObj->value));
                    if (in_array($val, ['sí', 'si', 'yes', '1', 'ilimitado', 'unlimited', 'activo', 'habilitado'])) {
                        $hasAccess = true;
                        break;
                    }
                    if (is_numeric($val) && (int)$val > 0) {
                        $hasAccess = true;
                        break;
                    }
                }
            }
        }

        $required = $this->featureMap[$feature] ?? null;
        if (!$hasAccess && $required) {
            $requiredArray = is_array($required) ? $required : [$required];
            $hasAccess = $store->storePackages
                ->filter(fn($sp) => $sp->isActive())
                ->pluck('package.type')
                ->intersect($requiredArray)
                ->isNotEmpty();
        }

        if ($required && !$hasAccess) {
            $requiredLabels = implode(' o ', array_map('ucfirst', $requiredArray));
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['status' => 'error', 'message' => 'Se requiere plan ' . $requiredLabels . ' para esta función'], 403);
            }
            return redirect()->route('seller.pricing')->with('error', 'Esta función requiere un plan ' . $requiredLabels . '. Actualiza tu plan para desbloquear esta funcionalidad.');
        }

        return $next($request);
    }
}