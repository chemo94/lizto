<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\KioskController;

// Public routes (no auth)
Route::post('kiosk/lookup-ruc', [KioskController::class, 'lookupRuc']);
Route::post('kiosk/login', [KioskController::class, 'login']);

// Protected routes (kiosk token auth)
Route::middleware('auth:sanctum')->prefix('kiosk')->group(function () {
    Route::post('logout', [KioskController::class, 'logout']);
    Route::get('validate', [KioskController::class, 'validateToken']);
    Route::get('menu/{storeId}', [KioskController::class, 'menu']);
    Route::get('tables/{storeId}', [KioskController::class, 'tables']);
    Route::get('payment-methods/{storeId}', [KioskController::class, 'paymentMethods']);
    Route::post('order/create', [KioskController::class, 'orderCreate']);
    Route::get('orders/active/{storeId}', [KioskController::class, 'ordersActive']);
});
