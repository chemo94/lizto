<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Config;

class ExtendSellerSession
{
    public function handle($request, Closure $next)
    {
        if ($request->is('seller*')) {
            Config::set('session.lifetime', 525600);
        }

        return $next($request);
    }
}
