<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;

class ExternalApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Token de autenticación requerido. Usa el header Authorization: Bearer {token}'], 401);
        }

        $store = Store::where('api_token', $token)->where('status', 1)->first();

        if (!$store) {
            return response()->json(['error' => 'Token inválido o tienda inactiva'], 401);
        }

        $request->merge(['external_store' => $store]);
        $request->setUserResolver(fn() => $store->seller);

        return $next($request);
    }
}
