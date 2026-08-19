<?php

use Illuminate\Support\Facades\Route;

Route::get('/clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});

Route::get('app/deposit/confirm/{hash}', 'Gateway\PaymentController@appDepositConfirm')->name('deposit.app.confirm');
Route::get('cron', 'CronController@cron')->name('cron');

// Kiosco React SPA
Route::get('/seller/kiosk/{path?}', function ($path = null) {
    $base = public_path('build/kiosk');
    if ($path) {
        $file = $base . '/' . $path;
        if (file_exists($file) && is_file($file)) {
            $mimeMap = [
                'js'   => 'application/javascript; charset=utf-8',
                'css'  => 'text/css; charset=utf-8',
                'json' => 'application/json',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'svg'  => 'image/svg+xml',
                'woff' => 'font/woff',
                'woff2'=> 'font/woff2',
                'ttf'  => 'font/ttf',
                'ico'  => 'image/x-icon',
            ];
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = $mimeMap[$ext] ?? mime_content_type($file) ?: 'application/octet-stream';
            return response()->file($file, [
                'Content-Type' => $mime,
                'Access-Control-Allow-Origin' => '*',
            ]);
        }
        abort(404);
    }
    $index = $base . '/index.html';
    if (file_exists($index)) {
        return response()->file($index, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }
    return response('Kiosco no disponible. Ejecuta: cd resources/kiosk && npm run build', 404);
})->where('path', '.*')->name('seller.kiosk');

Route::controller('TicketController')->prefix('ticket')->name('ticket.')->group(function () {
    Route::get('view/{ticket}', 'viewTicket')->name('view');
    Route::post('reply/{id}', 'replyTicket')->name('reply');
    Route::post('close/{id}', 'closeTicket')->name('close');
    Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
});

Route::controller('SiteController')->group(function () {
    Route::get('/api/docs', 'apiDocs')->name('api.docs');
    Route::get('/auth/token-login', 'tokenLogin')->name('token.login');

    // Cart (session-based) — /cart-count MUST be before /cart/{storeId}
    Route::get('/cart-count', 'cartCount');
    Route::get('/cart/{storeId}', 'cartGet');
    Route::post('/cart/{storeId}/add', 'cartAdd');
    Route::post('/cart/{storeId}/remove', 'cartRemove');
    Route::post('/cart/{storeId}/clear', 'cartClear');

    // Delivery checkout
    Route::get('/delivery/checkout', 'checkout')->name('delivery.checkout');
    Route::post('/delivery/checkout/submit', 'checkoutSubmit')->name('delivery.checkout.submit')->middleware('auth');

    // User location (session-based)
    Route::get('/location/get', 'locationGet');
    Route::post('/location/save', 'locationSave');

    Route::post('/solicitar-servicio', 'serviceRequestSubmit')->name('service.request');
    Route::get('/inicio', function () { return redirect()->route('home', [], 301); }); // Legacy redirect
    Route::get('/', 'deliveryMarketplace')->name('home'); // Superapp Marketplace B2C en la raíz
    Route::get('/delivery', 'deliveryMarketplace')->name('delivery.marketplace');
    Route::get('/delivery/tienda/{store}', 'deliveryStore')->name('delivery.store');
    Route::get('/taxi', 'taxiPage')->name('taxi');
    Route::get('/software', 'index')->name('software');
    Route::get('/negocios', 'businessLanding')->name('negocios');
    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contactSubmit');
    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');
    Route::post('subscribe', 'subscribe')->name('subscribe');
    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');
    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');
    Route::get('blog', 'blog')->name('blog');
    Route::get('blog/{slug}', 'blogDetails')->name('blog.details');
    Route::get('policy/{slug}', 'policyPages')->name('policy.pages');
    Route::get('placeholder-image/{size}', 'placeholderImage')->withoutMiddleware('maintenance')->name('placeholder.image');
Route::get('maintenance-mode', 'maintenance')->withoutMiddleware('maintenance')->name('maintenance');

Route::get('/seguir/favor/{token}', [\App\Http\Controllers\PublicFavorTrackingController::class, 'show'])->name('tracking.favor.show');
Route::get('/seguir/favor/{token}/data', [\App\Http\Controllers\PublicFavorTrackingController::class, 'data'])->name('tracking.favor.data');

    // User Panel
    Route::controller('UserPanelController')->prefix('user')->name('user.')->middleware('auth')->group(function () {
        Route::get('/dashboard', 'dashboard')->name('dashboard');
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile', 'profileUpdate')->name('profile.update');
        Route::get('/wallet', 'wallet')->name('wallet');
        Route::get('/order/{id}', 'orderDetail')->name('order.detail');
        Route::get('/order/{id}/driver-location', 'driverLocation')->name('order.driver-location');
    });

    Route::get('/favor', 'favorPage')->name('favor');
    Route::post('/favor/create', 'favorCreate')->name('favor.create')->middleware('auth');
    Route::get('/favor/{orderNo}', 'favorDetail')->name('favor.detail')->middleware('auth');
    Route::get('/ride/{id}/status', 'rideStatus')->name('ride.status')->middleware('auth');
    Route::get('/ride/user-rides', 'userRides')->name('ride.userRides')->middleware('auth');
    Route::post('/delivery/fee-estimate', 'feeEstimate')->name('fee.estimate');
Route::get('/delivery/store-fee-estimate', 'storeFeeEstimate')->name('store.fee.estimate');
    Route::post('/api/validate-coupon', 'validateCoupon');

    // Fleet Panel
    Route::get('/fleet/login', [\App\Http\Controllers\FleetPanelController::class, 'showLogin'])->name('fleet.login');
    Route::post('/fleet/login', [\App\Http\Controllers\FleetPanelController::class, 'webLogin'])->name('fleet.web.login');
    Route::get('/fleet/register', [\App\Http\Controllers\FleetPanelController::class, 'showRegister'])->name('fleet.register');
    Route::post('/fleet/register', [\App\Http\Controllers\FleetPanelController::class, 'register'])->name('fleet.register.submit');
    Route::get('/fleet/logout', [\App\Http\Controllers\FleetPanelController::class, 'webLogout'])->name('fleet.logout');

    Route::prefix('fleet')->name('fleet.')->middleware(['auth:fleet_owner'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\FleetPanelController::class, 'dashboard'])->name('dashboard');
        Route::get('/drivers', [\App\Http\Controllers\FleetPanelController::class, 'drivers'])->name('drivers');
        Route::post('/drivers/add', [\App\Http\Controllers\FleetPanelController::class, 'addDriver'])->name('drivers.add');
        Route::get('/fares', [\App\Http\Controllers\FleetPanelController::class, 'fares'])->name('fares');
        Route::post('/fares/update', [\App\Http\Controllers\FleetPanelController::class, 'updateFares'])->name('fares.update');
    });

    // Seller POS
    Route::get('/seller/login', [\App\Http\Controllers\SellerPosController::class, 'showLogin'])->name('seller.login');
    Route::post('/seller/login', [\App\Http\Controllers\SellerPosController::class, 'webLogin'])->name('seller.web.login');
    Route::post('/seller/register', [\App\Http\Controllers\SellerPosController::class, 'register'])->name('seller.register');
    Route::get('/seller/logout', [\App\Http\Controllers\SellerPosController::class, 'webLogout'])->name('seller.logout');
    Route::get('/seller/token-login', [\App\Http\Controllers\SellerPosController::class, 'tokenLogin'])->name('seller.token.login');
    Route::prefix('seller')->name('seller.')->middleware([\App\Http\Middleware\CheckSubscription::class])->group(function () {
        Route::get('/pos', [\App\Http\Controllers\SellerPosController::class, 'pos'])->name('pos');
        Route::get('/dashboard', [\App\Http\Controllers\SellerPosController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [\App\Http\Controllers\SellerPosController::class, 'orders'])->name('orders');
        Route::get('/customers', [\App\Http\Controllers\SellerPosController::class, 'customers'])->name('customers');
        Route::get('/products', [\App\Http\Controllers\SellerPosController::class, 'products'])->name('products');
        Route::get('/reports', [\App\Http\Controllers\SellerPosController::class, 'reports'])->name('reports');
        Route::get('/declarations', [\App\Http\Controllers\SellerPosController::class, 'declarations'])->name('declarations');
        Route::get('/reports/export/excel', [\App\Http\Controllers\SellerPosController::class, 'reportsExportExcel'])->name('reports.export.excel');
        Route::get('/reports/export/pdf', [\App\Http\Controllers\SellerPosController::class, 'reportsExportPdf'])->name('reports.export.pdf');
        Route::middleware(\App\Http\Middleware\CheckSubscription::class . ':reports')->group(function () {
            Route::get('/reports/advanced', [\App\Http\Controllers\SellerPosController::class, 'reportsAdvanced'])->name('reports.advanced');
            Route::get('/reports/export/rvie', [\App\Http\Controllers\SellerPosController::class, 'exportRVIE'])->name('reports.export.rvie');
            Route::get('/reports/export/rce', [\App\Http\Controllers\SellerPosController::class, 'exportRCE'])->name('reports.export.rce');
            Route::get('/reports/export/rec', [\App\Http\Controllers\SellerPosController::class, 'exportREC'])->name('reports.export.rec');
            Route::get('/reports/export/diario', [\App\Http\Controllers\SellerPosController::class, 'exportDiario'])->name('reports.export.diario');
            Route::get('/reports/export/mayor', [\App\Http\Controllers\SellerPosController::class, 'exportMayor'])->name('reports.export.mayor');
        });
        Route::post('/sunat-lookup', [\App\Http\Controllers\SellerPosController::class, 'sunatLookup'])->name('sunat');
        Route::post('/save-token', [\App\Http\Controllers\SellerPosController::class, 'saveDeviceToken'])->name('save-token');

        Route::get('/cash', [\App\Http\Controllers\SellerPosController::class, 'cash'])->name('cash');
        Route::post('/cash/open', [\App\Http\Controllers\SellerPosController::class, 'cashOpen'])->name('cash.open');
        Route::post('/cash/close', [\App\Http\Controllers\SellerPosController::class, 'cashClose'])->name('cash.close');
        Route::get('/cash/arqueo/{id}', [\App\Http\Controllers\SellerPosController::class, 'cashArqueo'])->name('cash.arqueo');
        Route::post('/cash/transaction', [\App\Http\Controllers\SellerPosController::class, 'cashTransaction'])->name('cash.transaction');

        // POS Registers (Cajas)
        Route::get('/registers', [\App\Http\Controllers\SellerPosController::class, 'registers'])->name('registers');
        Route::post('/registers/store', [\App\Http\Controllers\SellerPosController::class, 'registerStore'])->name('registers.store');
        Route::post('/registers/{id}/toggle', [\App\Http\Controllers\SellerPosController::class, 'registerToggle'])->name('registers.toggle');
        Route::post('/registers/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'registerDelete'])->name('registers.delete');
        Route::post('/registers/{id}/assign-staff', [\App\Http\Controllers\SellerPosController::class, 'registerAssignStaff'])->name('registers.assign-staff');


        Route::get('/expenses', [\App\Http\Controllers\SellerPosController::class, 'expenses'])->name('expenses');
        Route::post('/expenses/store', [\App\Http\Controllers\SellerPosController::class, 'expenseStore'])->name('expenses.store');
        Route::post('/expenses/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'expenseDelete'])->name('expenses.delete');

        Route::post('/orders/{id}/cancel', [\App\Http\Controllers\SellerPosController::class, 'cancelOrder'])->name('orders.cancel');
        Route::post('/orders/{id}/status', [\App\Http\Controllers\SellerPosController::class, 'orderStatus'])->name('orders.status');

        Route::get('/categories', [\App\Http\Controllers\SellerPosController::class, 'categories'])->name('categories');
        Route::post('/categories/store', [\App\Http\Controllers\SellerPosController::class, 'categoryStore'])->name('categories.store');
        Route::post('/categories/{id}/update', [\App\Http\Controllers\SellerPosController::class, 'categoryUpdate'])->name('categories.update');
        Route::post('/categories/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'categoryDelete'])->name('categories.delete');

        Route::post('/products/store', [\App\Http\Controllers\SellerPosController::class, 'productStore'])->name('products.store');
        Route::post('/products/{id}/update', [\App\Http\Controllers\SellerPosController::class, 'productUpdate'])->name('products.update');
        Route::post('/products/{id}/adjust-stock', [\App\Http\Controllers\SellerPosController::class, 'productAdjustStock'])->name('products.adjust-stock');
        Route::post('/products/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'productDelete'])->name('products.delete');
        Route::get('/products/bulk', [\App\Http\Controllers\SellerPosController::class, 'bulkUpload'])->name('products.bulk');
        Route::get('/products/bulk-template', [\App\Http\Controllers\SellerPosController::class, 'downloadTemplate'])->name('products.bulk-template');
        Route::post('/products/bulk-store', [\App\Http\Controllers\SellerPosController::class, 'bulkStore'])->name('products.bulk-store');
        Route::post('/products/ocr-parse', [\App\Http\Controllers\SellerPosController::class, 'ocrParse'])->name('products.ocr-parse');

        Route::get('/delivery', [\App\Http\Controllers\SellerPosController::class, 'delivery'])->name('delivery');
        Route::post('/logo-update', [\App\Http\Controllers\SellerPosController::class, 'logoUpdate'])->name('logo.update');
        Route::middleware(\App\Http\Middleware\CheckSubscription::class . ':cover_video')->group(function () {
            Route::post('/cover-video-update', [\App\Http\Controllers\SellerPosController::class, 'coverVideoUpdate'])->name('cover.video.update');
        });
        Route::middleware(\App\Http\Middleware\CheckSubscription::class . ':invoicing')->group(function () {
            Route::get('/invoicing', [\App\Http\Controllers\SellerPosController::class, 'invoicing'])->name('invoicing');
            Route::post('/sunat-config', [\App\Http\Controllers\SellerPosController::class, 'sunatConfig'])->name('sunat.config');
            Route::post('/invoice-type/store', [\App\Http\Controllers\SellerPosController::class, 'invoiceTypeStore'])->name('invoice.type.store');
            Route::post('/invoice-type/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'invoiceTypeDelete'])->name('invoice.type.delete');
            Route::post('/invoice-series/store', [\App\Http\Controllers\SellerPosController::class, 'invoiceSeriesStore'])->name('invoice.series.store');
            Route::post('/invoice-series/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'invoiceSeriesDelete'])->name('invoice.series.delete');
            Route::get('/invoicing/invoice/{id}', [\App\Http\Controllers\SellerPosController::class, 'invoiceDetail'])->name('invoice.detail');
            Route::get('/invoicing/invoice/{id}/pdf/{format}', [\App\Http\Controllers\SellerPosController::class, 'invoicePdf'])->name('invoice.pdf');
            Route::get('/invoicing/invoice/{id}/cdr', [\App\Http\Controllers\SellerPosController::class, 'invoiceCdr'])->name('invoice.cdr');
            Route::get('/invoicing/invoice/{id}/xml', [\App\Http\Controllers\SellerPosController::class, 'invoiceXml'])->name('invoice.xml');
            Route::post('/invoicing/invoice/{id}/void', [\App\Http\Controllers\SellerPosController::class, 'invoiceVoid'])->name('invoice.void');
            Route::post('/invoicing/invoice/{id}/resend', [\App\Http\Controllers\SellerPosController::class, 'resendToSunat'])->name('invoice.resend');
            Route::post('/orders/{id}/resend-receipt', [\App\Http\Controllers\SellerPosController::class, 'resendReceipt'])->name('orders.resend-receipt');
        });
        Route::get('/crm/customers', [\App\Http\Controllers\SellerPosController::class, 'crmCustomers'])->name('crm.customers');
        Route::get('/crm/customers/{doc}', [\App\Http\Controllers\SellerPosController::class, 'crmCustomerProfile'])->name('crm.customer.profile');
        Route::post('/register-ruc', [\App\Http\Controllers\SellerPosController::class, 'registerRuc'])->name('register.ruc');
        Route::post('/companies/store', [\App\Http\Controllers\SellerPosController::class, 'companyStore'])->name('companies.store');
        Route::post('/companies/{id}/update', [\App\Http\Controllers\SellerPosController::class, 'companyUpdate'])->name('companies.update');
        Route::post('/companies/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'companyDelete'])->name('companies.delete');
        Route::post('/companies/{id}/switch', [\App\Http\Controllers\SellerPosController::class, 'switchCompany'])->name('companies.switch');
        Route::get('/pos/tables', [\App\Http\Controllers\SellerPosController::class, 'tables'])->name('pos.tables');
        Route::post('/pos/tables/save', [\App\Http\Controllers\SellerPosController::class, 'tableSave'])->name('pos.tables.save');
        Route::post('/pos/tables/{id}/update', [\App\Http\Controllers\SellerPosController::class, 'tableUpdate'])->name('pos.tables.update');
        Route::post('/pos/tables/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'tableDelete'])->name('pos.tables.delete');
        Route::post('/pos/tables/{id}/toggle', [\App\Http\Controllers\SellerPosController::class, 'tableStatus'])->name('pos.tables.toggle');
        Route::post('/pos/areas/store', [\App\Http\Controllers\SellerPosController::class, 'areaStore'])->name('pos.areas.store');
        Route::post('/pos/areas/delete', [\App\Http\Controllers\SellerPosController::class, 'areaDelete'])->name('pos.areas.delete');
        Route::post('/pos/tables/position', [\App\Http\Controllers\SellerPosController::class, 'tablePosition'])->name('pos.tables.position');
        Route::get('/pos/floorplan', [\App\Http\Controllers\SellerPosController::class, 'floorPlan'])->name('pos.floorplan');
        Route::post('/pos/floorplan/upload', [\App\Http\Controllers\SellerPosController::class, 'floorPlanUpload'])->name('pos.floorplan.upload');
        Route::middleware(\App\Http\Middleware\CheckSubscription::class . ':notifications')->group(function () {
            Route::get('/notifications', [\App\Http\Controllers\SellerPosController::class, 'notifications'])->name('notifications');
            Route::post('/notifications/send', [\App\Http\Controllers\SellerPosController::class, 'sendNotification'])->name('notifications.send');
        });
        Route::get('/qrmenu', [\App\Http\Controllers\SellerPosController::class, 'qrMenu'])->name('qrmenu');
        Route::match(['get','post'], '/external-order', [\App\Http\Controllers\SellerPosController::class, 'externalOrder'])->name('external.order');
        Route::get('/pricing', [\App\Http\Controllers\SellerPosController::class, 'pricing'])->name('pricing');
        Route::post('/pricing/checkout', [\App\Http\Controllers\SellerPosController::class, 'pricingCheckout'])->name('pricing.checkout');
        Route::post('/pricing/checkout/process', [\App\Http\Controllers\SellerPosController::class, 'pricingProcess'])->name('pricing.process');
        Route::get('/pricing/return/{trx}', [\App\Http\Controllers\SellerPosController::class, 'pricingReturn'])->name('pricing.return');


        Route::get('/delivery/request', [\App\Http\Controllers\SellerPosController::class, 'deliveryRequestForm'])->name('delivery.request');
        Route::post('/delivery/request/submit', [\App\Http\Controllers\SellerPosController::class, 'deliveryRequestSubmit'])->name('delivery.request.submit');
        Route::post('/delivery/request/fee-calculate', [\App\Http\Controllers\SellerPosController::class, 'deliveryRequestFeeCalculate'])->name('delivery.request.fee-calculate');
        Route::get('/delivery/request/status/{id}', [\App\Http\Controllers\SellerPosController::class, 'deliveryRequestStatus'])->name('delivery.request.status.show');
        Route::post('/delivery/request/{id}/cancel', [\App\Http\Controllers\SellerPosController::class, 'cancelFavor'])->name('delivery.request.cancel');
        Route::post('/delivery/request/{id}/status', [\App\Http\Controllers\SellerPosController::class, 'favorStatus'])->name('delivery.request.status');
        Route::post('/delivery/request/{id}/request-return', [\App\Http\Controllers\SellerPosController::class, 'requestReturn'])->name('delivery.request.return');
        Route::get('/delivery/order/{id}/status', [\App\Http\Controllers\SellerPosController::class, 'deliveryOrderStatus'])->name('delivery.order.status.show');
        Route::get('/api-settings', [\App\Http\Controllers\SellerPosController::class, 'apiSettings'])->name('api.settings');
        Route::post('/api-token-regenerate', [\App\Http\Controllers\SellerPosController::class, 'regenerateApiToken'])->name('api.token.regenerate');
        Route::post('/api-webhook-save', [\App\Http\Controllers\SellerPosController::class, 'saveWebhookSettings'])->name('api.webhook.save');
        Route::post('/api-qr-save', [\App\Http\Controllers\SellerPosController::class, 'saveQrSettings'])->name('qr.save');
        Route::post('/api-webhook-test', [\App\Http\Controllers\SellerPosController::class, 'testWebhook'])->name('api.webhook.test');

        // Inventory
        Route::middleware(\App\Http\Middleware\CheckSubscription::class . ':inventory')->group(function () {
            Route::get('/inventory/items', [\App\Http\Controllers\InventoryController::class, 'items'])->name('inventory.items');
            Route::post('/inventory/items/store', [\App\Http\Controllers\InventoryController::class, 'itemStore'])->name('inventory.items.store');
            Route::post('/inventory/items/{id}/update', [\App\Http\Controllers\InventoryController::class, 'itemUpdate'])->name('inventory.items.update');
            Route::post('/inventory/items/{id}/delete', [\App\Http\Controllers\InventoryController::class, 'itemDelete'])->name('inventory.items.delete');
            Route::get('/inventory/suppliers', [\App\Http\Controllers\InventoryController::class, 'suppliers'])->name('inventory.suppliers');
            Route::post('/inventory/suppliers/store', [\App\Http\Controllers\InventoryController::class, 'supplierStore'])->name('inventory.suppliers.store');
            Route::post('/inventory/suppliers/{id}/update', [\App\Http\Controllers\InventoryController::class, 'supplierUpdate'])->name('inventory.suppliers.update');
            Route::post('/inventory/suppliers/{id}/delete', [\App\Http\Controllers\InventoryController::class, 'supplierDelete'])->name('inventory.suppliers.delete');
            Route::get('/inventory/purchases', [\App\Http\Controllers\InventoryController::class, 'purchases'])->name('inventory.purchases');
            Route::post('/inventory/purchases/store', [\App\Http\Controllers\InventoryController::class, 'purchaseStore'])->name('inventory.purchases.store');
            Route::get('/inventory/kardex/{itemId?}', [\App\Http\Controllers\InventoryController::class, 'kardex'])->name('inventory.kardex');
            Route::post('/inventory/stock-adjust', [\App\Http\Controllers\InventoryController::class, 'stockAdjust'])->name('inventory.stock-adjust');

            // ── Recetas (Cocina + Bar) ────────────────────────────────────────
            Route::get('/inventory/recipes', [\App\Http\Controllers\InventoryController::class, 'recipes'])->name('inventory.recipes');
            Route::post('/inventory/recipes/store', [\App\Http\Controllers\InventoryController::class, 'recipeStore'])->name('inventory.recipes.store');
            Route::post('/inventory/recipes/{id}/delete', [\App\Http\Controllers\InventoryController::class, 'recipeDelete'])->name('inventory.recipes.delete');
            Route::post('/inventory/productions/store', [\App\Http\Controllers\InventoryController::class, 'recipeProduction'])->name('inventory.productions.store');
            Route::post('/inventory/productions/{id}/void', [\App\Http\Controllers\InventoryController::class, 'recipeProductionVoid'])->name('inventory.productions.void');

            // ── Alertas de Stock ────────────────────────────────────────────────
            Route::get('/inventory/alerts', [\App\Http\Controllers\InventoryController::class, 'stockAlerts'])->name('inventory.alerts');

            // ── Mermas ───────────────────────────────────────────────────────
            Route::get('/inventory/wastes', [\App\Http\Controllers\InventoryController::class, 'wastes'])->name('inventory.wastes');
            Route::post('/inventory/wastes/store', [\App\Http\Controllers\InventoryController::class, 'wasteStore'])->name('inventory.wastes.store');
            Route::post('/inventory/wastes/{id}/void', [\App\Http\Controllers\InventoryController::class, 'wasteVoid'])->name('inventory.wastes.void');

            // ── Ventas Directas (Tienda) ──────────────────────────────────────
            Route::get('/inventory/sales', [\App\Http\Controllers\InventoryController::class, 'sales'])->name('inventory.sales');
            Route::post('/inventory/sales/store', [\App\Http\Controllers\InventoryController::class, 'saleStore'])->name('inventory.sales.store');
            Route::post('/inventory/sales/{id}/void', [\App\Http\Controllers\InventoryController::class, 'saleVoid'])->name('inventory.sales.void');

            // ── Reporte Tributario ────────────────────────────────────────────
            Route::get('/inventory/tax-report', [\App\Http\Controllers\InventoryController::class, 'taxReport'])->name('inventory.tax-report');

            // ── MÓDULO DE LOGÍSTICA ERP ───────────────────────────────────────
            Route::get('/logistics/suppliers', [\App\Http\Controllers\LogisticsController::class, 'suppliers'])->name('logistics.suppliers');
            Route::post('/logistics/suppliers/store', [\App\Http\Controllers\LogisticsController::class, 'supplierStore'])->name('logistics.suppliers.store');
            Route::post('/logistics/suppliers/{id}/update', [\App\Http\Controllers\LogisticsController::class, 'supplierUpdate'])->name('logistics.suppliers.update');
            Route::post('/logistics/suppliers/{id}/delete', [\App\Http\Controllers\LogisticsController::class, 'supplierDelete'])->name('logistics.suppliers.delete');

            Route::get('/logistics/warehouses', [\App\Http\Controllers\LogisticsController::class, 'warehouses'])->name('logistics.warehouses');
            Route::post('/logistics/warehouses/store', [\App\Http\Controllers\LogisticsController::class, 'warehouseStore'])->name('logistics.warehouses.store');
            Route::post('/logistics/warehouses/{id}/update', [\App\Http\Controllers\LogisticsController::class, 'warehouseUpdate'])->name('logistics.warehouses.update');
            Route::post('/logistics/warehouses/{id}/delete', [\App\Http\Controllers\LogisticsController::class, 'warehouseDelete'])->name('logistics.warehouses.delete');

            Route::get('/logistics/purchase-orders', [\App\Http\Controllers\LogisticsController::class, 'purchaseOrders'])->name('logistics.purchase_orders');
            Route::post('/logistics/purchase-orders/store', [\App\Http\Controllers\LogisticsController::class, 'purchaseOrderStore'])->name('logistics.purchase_orders.store');
            Route::post('/logistics/purchase-orders/{id}/status', [\App\Http\Controllers\LogisticsController::class, 'purchaseOrderStatus'])->name('logistics.purchase_orders.status');
            Route::get('/logistics/purchase-orders/{id}/pdf', [\App\Http\Controllers\LogisticsController::class, 'purchaseOrderPdf'])->name('logistics.purchase_orders.pdf');

            Route::get('/logistics/receptions', [\App\Http\Controllers\LogisticsController::class, 'receptions'])->name('logistics.receptions');
            Route::get('/logistics/receptions/create/{orderId?}', [\App\Http\Controllers\LogisticsController::class, 'receptionCreate'])->name('logistics.receptions.create');
            Route::post('/logistics/receptions/store', [\App\Http\Controllers\LogisticsController::class, 'receptionStore'])->name('logistics.receptions.store');

            Route::get('/logistics/transfers', [\App\Http\Controllers\LogisticsController::class, 'transfers'])->name('logistics.transfers');
            Route::post('/logistics/transfers/store', [\App\Http\Controllers\LogisticsController::class, 'transferStore'])->name('logistics.transfers.store');
            Route::post('/logistics/transfers/{id}/status', [\App\Http\Controllers\LogisticsController::class, 'transferStatus'])->name('logistics.transfers.status');

            Route::get('/logistics/reports', [\App\Http\Controllers\LogisticsController::class, 'reports'])->name('logistics.reports');
        });
        Route::get('/pos/billing', [\App\Http\Controllers\SellerPosController::class, 'billing'])->name('pos.billing');
        Route::post('/pos/order/{id}/pay', [\App\Http\Controllers\SellerPosController::class, 'payOrder'])->name('pos.order.pay');
        Route::post('/pos/order/{id}/invoice', [\App\Http\Controllers\SellerPosController::class, 'generateInvoice'])->name('pos.order.invoice');
        Route::get('/pos/order/{id}/ticket', [\App\Http\Controllers\SellerPosController::class, 'preCheckTicket'])->name('pos.order.ticket');
        Route::post('/pos/order/create', [\App\Http\Controllers\SellerPosController::class, 'orderCreate'])->name('pos.order.create');
        Route::get('/pos/kitchen', [\App\Http\Controllers\SellerPosController::class, 'kitchen'])->name('pos.kitchen');
        Route::post('/pos/kitchen/{id}/status', [\App\Http\Controllers\SellerPosController::class, 'kitchenUpdateStatus'])->name('pos.kitchen.status');
        Route::get('/pos/fee-estimate', [\App\Http\Controllers\SellerPosController::class, 'feeEstimate'])->name('pos.fee');
        Route::get('/pos/orders/active', [\App\Http\Controllers\SellerPosController::class, 'activeOrders'])->name('pos.orders.active');
        Route::get('/pos/table/{tableId}/active-order', [\App\Http\Controllers\SellerPosController::class, 'getActiveTableOrder'])->name('pos.table.active-order');
        Route::post('/pos/table/transfer', [\App\Http\Controllers\SellerPosController::class, 'transferTable'])->name('pos.table.transfer');
        Route::post('/pos/table/group', [\App\Http\Controllers\SellerPosController::class, 'groupTables'])->name('pos.table.group');
        Route::post('/pos/table/ungroup', [\App\Http\Controllers\SellerPosController::class, 'ungroupTable'])->name('pos.table.ungroup');
        Route::post('/pos/table/ungroup-all', [\App\Http\Controllers\SellerPosController::class, 'ungroupAll'])->name('pos.table.ungroup-all');
        Route::get('/pos/reservations', [\App\Http\Controllers\SellerPosController::class, 'reservationsList'])->name('pos.reservations.list');
        Route::post('/pos/reservations/save', [\App\Http\Controllers\SellerPosController::class, 'reservationSave'])->name('pos.reservations.save');
        Route::post('/pos/reservations/{id}/status', [\App\Http\Controllers\SellerPosController::class, 'reservationStatus'])->name('pos.reservations.status');

        // CRUD de Personal/Meseros
        Route::get('/pos/staff', [\App\Http\Controllers\SellerPosController::class, 'staff'])->name('pos.staff');
        Route::post('/pos/staff/store', [\App\Http\Controllers\SellerPosController::class, 'staffStore'])->name('pos.staff.store');
        Route::post('/pos/staff/{id}/update', [\App\Http\Controllers\SellerPosController::class, 'staffUpdate'])->name('pos.staff.update');
        Route::post('/pos/staff/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'staffDelete'])->name('pos.staff.delete');
        
        // CRUD de Cuentas Bancarias / Caja y Bancos
        Route::get('/pos/bank-accounts', [\App\Http\Controllers\SellerPosController::class, 'bankAccounts'])->name('pos.bank_accounts');
        Route::post('/pos/bank-accounts/store', [\App\Http\Controllers\SellerPosController::class, 'bankAccountStore'])->name('pos.bank_accounts.store');
        Route::post('/pos/bank-accounts/{id}/update', [\App\Http\Controllers\SellerPosController::class, 'bankAccountUpdate'])->name('pos.bank_accounts.update');
        Route::post('/pos/bank-accounts/{id}/delete', [\App\Http\Controllers\SellerPosController::class, 'bankAccountDelete'])->name('pos.bank_accounts.delete');
        Route::post('/pos/bank-accounts/transaction', [\App\Http\Controllers\SellerPosController::class, 'bankAccountTransaction'])->name('pos.bank_accounts.transaction');
        
        // Reporte de Comisiones
        Route::get('/pos/staff/reports', [\App\Http\Controllers\SellerPosController::class, 'staffReports'])->name('pos.staff.reports');

        // Recursos Humanos (RR.HH) - Asistencia
        Route::get('/pos/hr/attendance', [\App\Http\Controllers\SellerPosController::class, 'attendance'])->name('pos.hr.attendance');
        Route::post('/pos/hr/attendance/store', [\App\Http\Controllers\SellerPosController::class, 'attendanceStore'])->name('pos.hr.attendance.store');
        Route::post('/pos/hr/attendance/clock-in-out', [\App\Http\Controllers\SellerPosController::class, 'attendanceClock'])->name('pos.hr.attendance.clock');

        // Recursos Humanos (RR.HH) - Planilla
        Route::get('/pos/hr/payroll', [\App\Http\Controllers\SellerPosController::class, 'payroll'])->name('pos.hr.payroll');
        Route::post('/pos/hr/payroll/calculate', [\App\Http\Controllers\SellerPosController::class, 'payrollCalculate'])->name('pos.hr.payroll.calculate');
        Route::post('/pos/hr/payroll/{id}/pay', [\App\Http\Controllers\SellerPosController::class, 'payrollPay'])->name('pos.hr.payroll.pay');
        Route::get('/pos/hr/payroll/{id}/pdf', [\App\Http\Controllers\SellerPosController::class, 'payrollPdf'])->name('pos.hr.payroll.pdf');

        // Canal de notificaciones en tiempo real (Reverb/WebSockets)
        Route::post('/broadcasting/auth', [\App\Http\Controllers\SellerPosController::class, 'broadcastingAuth'])->name('broadcasting.auth');
    });

    // Libro de Reclamaciones
    Route::controller(\App\Http\Controllers\ComplaintController::class)->name('complaints.')->group(function () {
        Route::get('/libro-de-reclamaciones', 'showForm')->name('form');
        Route::post('/libro-de-reclamaciones', 'submitForm')->name('submit');
        Route::get('/libro-de-reclamaciones/exito/{ticket_number}', 'success')->name('success');
        Route::get('/libro-de-reclamaciones/pdf/{ticket_number}', 'downloadPdf')->name('pdf');
    });

    // SEO - Sitemap
    Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

    // JSoft AI Chat
    Route::post('/jsoft-ai/chat', [\App\Http\Controllers\JSoftAiController::class, 'chat'])->name('jsoft.ai.chat');

    Route::get('/{slug}', 'pages')->name('pages');
});
