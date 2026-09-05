<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::namespace("Api")->group(function () {
    Route::controller('AppController')->group(function () {
        Route::any('general-setting', 'generalSetting');
        Route::get('app-release', 'appRelease');
        Route::get('get-countries', 'getCountries');
        Route::get('language/{key}', 'getLanguage');
        Route::get('policies', 'policies');
        Route::get('faq', 'faq');
        Route::get('zones', 'zone');
        Route::get('banners', 'banners');
        Route::post('broadcasting/auth', 'broadcastingAuth');
        Route::post('save-admin-device-token', 'saveAdminDeviceToken');
    });

    Route::get('test-broadcast', 'AppController@testBroadcast');

    // IPN / Webhooks (public, no auth)
    Route::post('ipn/wallet-mercadopago', 'WalletPaymentController@mercadoPagoIpn');

    Route::controller('DeliveryController')->prefix('delivery')->name('delivery.')->group(function () {
        Route::get('categories', 'generalCategories');
        Route::get('categories/{id}/home', 'categoryHome');
        Route::get('categories/{id}/stores', 'categoryStores');
        Route::get('categories/{id}/subcategories', 'subCategories');
        Route::get('subcategories/{id}/stores', 'stores');
        Route::get('store/{id}', 'storeDetail');
        Route::get('nearby-stores', 'nearbyStores');
        Route::post('fee-estimate', 'feeEstimate');
    });

    // Stories (customer view)
    Route::controller('StoreStoryController')->prefix('delivery/stories')->group(function () {
        Route::get('/', 'index');
        Route::get('{storeId}', 'byStore');
        Route::post('view/{storyId}', 'view');
    });
});

Route::namespace('Api\User')->group(function () {

    Route::namespace('Auth')->group(function () {
        Route::controller('LoginController')->group(function () {
            Route::post('login', 'login');
            Route::post('check-token', 'checkToken');
            Route::post('social-login', 'socialLogin');
        });
        Route::post('register', 'RegisterController@register');
        Route::controller('ForgotPasswordController')->group(function () {
            Route::post('password/email', 'sendResetCodeEmail');
            Route::post('password/verify-code', 'verifyCode');
            Route::post('password/reset', 'reset');
        });
    });

    Route::middleware(['auth:sanctum', 'token.permission:auth_token'])->group(function () {

        Route::post('user-data-submit', 'UserController@userDataSubmit');

        //authorization
        Route::middleware('registration.complete')->group(function () {
            Route::controller('AuthorizationController')->group(function () {
                Route::get('authorization', 'authorization');
                Route::get('resend-verify/{type}', 'sendVerifyCode');
                Route::post('verify-email', 'emailVerification');
                Route::post('verify-mobile', 'mobileVerification');
            });

            Route::middleware(['registration.complete', 'check.status'])->group(function () {

                Route::controller('UserController')->group(function () {

                    Route::get('dashboard', 'dashboard');
                    Route::post('profile-setting', 'submitProfile');
                    Route::post('change-password', 'submitPassword');

                    Route::get('user-info', 'userInfo');

                    //Report

                    Route::any('payment/history', 'paymentHistory');

                    Route::post('save-device-token', 'addDeviceToken');
                    Route::get('push-notifications', 'pushNotifications');
                    Route::post('push-notifications/read/{id}', 'pushNotificationsRead');

                    Route::post('delete-account', 'deleteAccount');

                    Route::post('pusher/auth/{socketId}/{channelName}', 'pusher');

                    Route::get('drivers/nearby', 'nearbyDrivers');
                });

                Route::prefix('ride')->controller('RideController')->group(function () {
                    Route::post('fare-and-distance', 'findFareAndDistance');
                    Route::post('create', 'create');
                    Route::get('bids/{id}', 'bids');
                    Route::post('reject/{id}', 'reject');
                    Route::post('accept/{bidId}', 'accept');
                    Route::get('list', 'list');
                    Route::post('cancel/{id}', 'cancel');
                    Route::post('sos/{id}', 'sos');
                    Route::get('details/{id}', 'details');
                    Route::get('payment/{id}', 'payment');
                    Route::post('payment/{id}', 'paymentSave');
                    Route::post('payment/{id}/mp-process', 'mpCheckoutProcess');
                    Route::get('receipt/{id}', 'receipt');
                });

                //review
                Route::controller('ReviewController')->prefix('review')->group(function () {
                    Route::get('/', 'review');
                    Route::post('/{rideId}', 'reviewStore');
                    Route::get('/driver/{driverId}', 'driverReview');
                });

                Route::controller('TicketController')->prefix('ticket')->group(function () {
                    Route::get('/', 'supportTicket');
                    Route::post('create', 'storeSupportTicket');
                    Route::get('view/{ticket}', 'viewTicket');
                    Route::post('reply/{id}', 'replyTicket');
                    Route::post('close/{id}', 'closeTicket');
                    Route::get('download/{attachment_id}', 'ticketDownload');
                });
                //message
                Route::controller('MessageController')->prefix('ride')->group(function () {
                    Route::get('messages/{id}', 'messages');
                    Route::post('send/message/{id}', 'messageSave');
                });

                //delivery orders
                Route::controller('DeliveryOrderController')->prefix('delivery/orders')->group(function () {
                    Route::post('create', 'create');
                    Route::get('/', 'orders');
                    Route::get('{id}', 'detail');
                    Route::post('{id}/pay', 'pay');
                    Route::post('{id}/mp-process', 'mpCheckoutProcess');
                    Route::post('cancel/{id}', 'cancel');
                    Route::post('delete-pending/{id}', 'deletePendingOrder');
                    Route::post('tip/{id}', 'addTip');
                    Route::post('{id}/review', 'review');
                    Route::post('{id}/report', 'TicketController@storeOrderTicket');
                });

                //delivery refunds
                Route::controller('RefundController')->prefix('delivery/refunds')->group(function () {
                    Route::get('/', 'index');
                });
                Route::post('delivery/orders/refund/{id}', 'RefundController@requestRefund');

                //delivery coupon
                Route::post('delivery/coupon/apply', 'CouponController@applyDeliveryCoupon');

                //delivery payments history
                Route::get('delivery/payments', 'DeliveryOrderController@paymentHistory');

                //payment gateways for delivery
                Route::get('delivery/gateways', 'DeliveryOrderController@gateways');

                // Favorite stores
                Route::post('delivery/favorites/{storeId}', '\App\Http\Controllers\Api\DeliveryController@toggleFavorite');
                Route::get('delivery/favorites', '\App\Http\Controllers\Api\DeliveryController@favoriteStores');

                // Favors
                Route::controller('FavorController')->prefix('delivery/favors')->group(function () {
                    Route::post('create', 'create');
                    Route::get('/', 'list');
                    Route::get('{id}', 'detail');
                    Route::post('cancel/{id}', 'cancel');
                    Route::get('{id}/bids', 'bids');
                    Route::post('{favorId}/bids/{bidId}/accept', 'acceptBid');
                    Route::get('{id}/messages', 'messages');
                    Route::post('{id}/send-message', 'sendMessage');
                });
                Route::post('delivery/favors/fee-estimate', 'FavorController@feeEstimate');

                //payment gateways for favors
                Route::get('delivery/favors/gateways', 'FavorController@gateways');

                // Shopping List (buy-in-store)
                Route::controller('ShoppingController')->prefix('delivery/favors/{favorId}/shopping')->group(function () {
                    Route::post('items', 'addItem');
                    Route::put('items/{itemId}', 'updateItem');
                    Route::delete('items/{itemId}', 'removeItem');
                    Route::post('items/{itemId}/image', 'uploadItemImage');
                    Route::get('items', 'getShoppingList');
                    Route::post('budget', 'setBudget');
                    Route::post('submit', 'submitShopping');
                    Route::post('items/{itemId}/approve-substitution', 'approveSubstitution');
                    Route::post('confirm-purchase', 'confirmPurchase');
                    Route::get('status', 'getShoppingStatus');
                });

                // Wallet
                Route::controller('WalletController')->prefix('wallet')->group(function () {
                    Route::get('balance', 'getBalance');
                    Route::get('transactions', 'getTransactions');
                    Route::post('add-funds', 'addFunds');
                    Route::post('verify-payment', 'verifyPayment');
                    Route::post('withdraw', 'withdrawFunds');
                });
            });
        });
        Route::get('logout', 'Auth\LoginController@logout');
    });
});

// ── External API v1 (store-to-store / platform integration) ──
Route::middleware(\App\Http\Middleware\ExternalApiAuth::class)->prefix('external/v1')->group(function () {
    Route::get('store', '\App\Http\Controllers\Api\ExternalApiController@storeInfo');
    Route::get('categories', '\App\Http\Controllers\Api\ExternalApiController@categories');
    Route::get('products', '\App\Http\Controllers\Api\ExternalApiController@products');
    Route::get('inventory', '\App\Http\Controllers\Api\ExternalApiController@inventory');
    Route::post('orders', '\App\Http\Controllers\Api\ExternalApiController@createOrder');
    Route::get('orders', '\App\Http\Controllers\Api\ExternalApiController@orders');
    Route::get('orders/{id}', '\App\Http\Controllers\Api\ExternalApiController@orderDetail');
    Route::post('categories/sync', '\App\Http\Controllers\Api\ExternalApiController@syncCategories');
    Route::post('products/sync', '\App\Http\Controllers\Api\ExternalApiController@syncProducts');
    Route::post('token/regenerate', '\App\Http\Controllers\Api\ExternalApiController@regenerateToken');
});

//start driver route
Route::namespace('Api\Driver')->prefix('driver')->group(function () {
    Route::namespace('Auth')->group(function () {
        Route::controller('LoginController')->group(function () {
            Route::post('login', 'login');
            Route::post('social-login', 'socialLogin');
        });
        Route::post('register', 'RegisterController@register');

        Route::controller('ForgotPasswordController')->group(function () {
            Route::post('password/email', 'sendResetCodeEmail');
            Route::post('password/verify-code', 'verifyCode');
            Route::post('password/reset', 'reset');
        });
    });

    Route::middleware(['auth:sanctum', 'token.permission:driver_token'])->group(function () {
        //authorization
        Route::post('driver-data-submit', 'DriverController@driverDataSubmit');
        Route::middleware('registration.complete')->group(function () {
            Route::controller('AuthorizationController')->group(function () {
                Route::get('authorization', 'authorization');
                Route::get('resend-verify/{type}', 'sendVerifyCode');
                Route::post('verify-email', 'emailVerification');
                Route::post('verify-mobile', 'mobileVerification');
                Route::post('verify-g2fa', 'g2faVerification');
            });

            Route::middleware(['check.status'])->group(function () {

                Route::controller('DriverController')->group(function () {
                    Route::get('dashboard', 'dashboard');
                    Route::get('driver-info', 'driverInfo');

                    Route::post('profile-setting', 'submitProfile');
                    Route::post('change-password', 'submitPassword');
                    Route::post('delete-account', 'accountDelete');

                    Route::post('pusher/auth/{socketId}/{channelName}', 'pusher');

                    //Driver Verification
                    Route::get('driver-verification', 'driverVerification');
                    Route::post('driver-verification', 'driverVerificationStore');

                    //vehicle verification
                    Route::get('vehicle-verification', 'vehicleVerification');
                    Route::post('vehicle-verification', 'vehicleVerificationStore');

                    //Report
                    Route::any('deposit/history', 'depositHistory');
                    Route::get('transactions', 'transactions');
                    Route::get('payment/history', 'paymentHistory');
                    Route::post('online-status', 'onlineStatus');

                    Route::post('save-device-token', 'addDeviceToken');

                    //2FA
                    Route::get('twofactor', 'show2faForm');
                    Route::post('twofactor/enable', 'create2fa');
                    Route::post('twofactor/disable', 'disable2fa');

                    Route::post('location-update', 'locationUpdate');
                });

                Route::controller('ReviewController')->group(function () {
                    Route::get('review', 'review');
                    Route::post('review/{rideId}', 'reviewStore');
                    Route::get('get-rider-review/{riderId}', 'riderReview');
                });
                //Withdraw
                Route::middleware('driver.verification')->group(function () {
                    Route::controller('WithdrawController')->group(function () {
                        Route::get('withdraw-method', 'withdrawMethod');
                        Route::post('withdraw-request', 'withdrawStore');
                        Route::post('withdraw-request/confirm', 'withdrawSubmit');
                        Route::get('withdraw/history', 'withdrawLog');
                    });
                    // Rides
                    Route::controller('RideController')->prefix('rides')->group(function () {
                        Route::get('/', 'rides');
                        Route::get('details/{id}', 'details');
                        Route::post('start/{id}', 'start');
                        Route::post('end/{id}', 'end');
                        Route::get('list', 'list');
                        Route::post('cancel/{id}', 'cancel');
                        Route::post('received-cash-payment/{id}', 'receivedCashPayment');
                        Route::get('receipt/{id}', 'receipt');
                    });
                    //Bid
                    Route::controller('BidController')->prefix('bid')->group(function () {
                        Route::post('create/{id}', 'create');
                        Route::get('list', 'list');
                        Route::get('cancel/{id}', 'cancel');
                    });
                    //message
                    Route::controller('MessageController')->prefix('ride')->group(function () {
                        Route::get('messages/{id}', 'messages');
                        Route::post('send/message/{id}', 'messageSave');
                    });
                });
                //payment
                Route::controller('PaymentController')->group(function () {
                    Route::get('deposit/methods', 'methods');
                    Route::post('deposit/insert', 'depositInsert');
                });
                //ticket
                Route::controller('TicketController')->prefix('ticket')->group(function () {
                    Route::get('/', 'supportTicket');
                    Route::post('create', 'storeSupportTicket');
                    Route::get('view/{ticket}', 'viewTicket');
                    Route::post('reply/{id}', 'replyTicket');
                    Route::post('close/{id}', 'closeTicket');
                    Route::get('download/{attachment_id}', 'ticketDownload');
                });

                // Courier delivery/favor job routes (under Driver auth)
                Route::prefix('courier')->group(function () {
                    Route::get('jobs/pending', '\App\Http\Controllers\Api\Driver\CourierJobController@pendingJobs');
                    Route::get('jobs/active', '\App\Http\Controllers\Api\Driver\CourierJobController@activeJobs');
                    Route::get('jobs/history', '\App\Http\Controllers\Api\Driver\CourierJobController@jobHistory');
                    Route::get('jobs/{id}', '\App\Http\Controllers\Api\Driver\CourierJobController@jobDetail');
                    Route::post('jobs/{id}/accept', '\App\Http\Controllers\Api\Driver\CourierJobController@acceptJob');
                    Route::post('jobs/{id}/reject', '\App\Http\Controllers\Api\Driver\CourierJobController@rejectJob');
                    Route::post('jobs/{id}/status', '\App\Http\Controllers\Api\Driver\CourierJobController@updateJobStatus');
                    Route::post('jobs/{id}/cancel', '\App\Http\Controllers\Api\Driver\CourierJobController@cancelJob');
                    Route::post('jobs/{id}/location', '\App\Http\Controllers\Api\Driver\CourierJobController@sendLocation');
                    Route::post('jobs/{id}/proof', '\App\Http\Controllers\Api\Driver\CourierJobController@uploadProof');
                    Route::post('jobs/{id}/verify-pin', '\App\Http\Controllers\Api\Driver\CourierJobController@verifyPin');
                    Route::get('jobs/{id}/confirmation-requirements', '\App\Http\Controllers\Api\Driver\CourierJobController@getConfirmationRequirements');
                    Route::post('jobs/{id}/accept-return', '\App\Http\Controllers\Api\Driver\CourierJobController@acceptReturn');
                    Route::post('jobs/{id}/pickup-return', '\App\Http\Controllers\Api\Driver\CourierJobController@pickupReturn');
                    Route::post('jobs/{id}/complete-return', '\App\Http\Controllers\Api\Driver\CourierJobController@completeReturn');
                    Route::get('jobs/{id}/messages', '\App\Http\Controllers\Api\Driver\CourierJobController@messages');
                    Route::post('jobs/{id}/messages/send', '\App\Http\Controllers\Api\Driver\CourierJobController@sendMessage');
                    Route::post('jobs/{id}/messages/send-image', '\App\Http\Controllers\Api\Driver\CourierJobController@sendImage');
                    Route::get('earnings', '\App\Http\Controllers\Api\Driver\CourierJobController@earnings');
                    Route::get('wallet/transactions', '\App\Http\Controllers\Api\Driver\CourierJobController@walletTransactions');
                    Route::get('heatmap', '\App\Http\Controllers\Api\Driver\CourierJobController@heatmapData');
                });

                // Shopping (driver side - buy in store)
                Route::controller('\App\Http\Controllers\Api\User\DriverShoppingController')->prefix('shopping')->group(function () {
                    Route::post('favor/{favorId}/store-confirm', 'storeConfirm');
                    Route::post('favor/{favorId}/items/{itemId}/confirm', 'confirmItem');
                    Route::post('favor/{favorId}/items/{itemId}/not-found', 'notFoundItem');
                    Route::post('favor/{favorId}/items/{itemId}/substitute', 'proposeSubstitute');
                    Route::post('favor/{favorId}/receipt', 'uploadReceipt');
                    Route::get('favor/{favorId}/checklist', 'getChecklist');
                });
                Route::post('favor/{favorId}/bid', '\App\Http\Controllers\Api\Driver\CourierJobController@acceptJob');
            });
        });
        Route::get('logout', 'Auth\LoginController@logout');
    });
});

Route::namespace('Api\Seller')->prefix('seller')->name('seller.')->group(function () {
    Route::post('login', 'AuthController@login');
    Route::post('social-login', 'AuthController@socialLogin');
    Route::get('onboarding/packages', 'PackageController@onboardingPlans');
    Route::post('register', [\App\Http\Controllers\Api\Seller\PanelController::class, 'register']);
    Route::middleware('auth:sanctum')->group(function () {
            Route::get('authorization', 'AuthController@authorization');
            Route::get('resend-verify/mobile', 'AuthController@authorization');
            Route::post('verify-mobile', 'AuthController@mobileVerification');
            Route::post('sunat-lookup', [\App\Http\Controllers\Api\Seller\PanelController::class, 'sunatLookup']);
            Route::post('save-device-token', 'AuthController@registerDeviceToken');
            Route::get('dashboard', 'AuthController@dashboard');
            Route::get('profile', 'AuthController@profile');
            Route::get('products/{storeId}', 'ProductController@products');
            Route::post('products/{storeId}/store', 'ProductController@storeProduct');
            Route::post('products/update/{id}', 'ProductController@updateProduct');
            Route::post('products/delete/{id}', 'ProductController@deleteProduct');
            Route::post('products/toggle-status/{id}', 'ProductController@toggleStatus');

            // Store CRUD for sellers
            Route::get('stores', 'StoreController@index');
            Route::post('stores/store', 'StoreController@storeStore');
            Route::post('stores/update/{id}', 'StoreController@updateStore');
            Route::post('stores/delete/{id}', 'StoreController@deleteStore');
            Route::get('subcategories', 'StoreController@subCategories');

            // menu categories (store menu sections)
            Route::get('menu-categories/{storeId}', 'ProductController@menuCategories');
            Route::post('menu-categories/{storeId}/store', 'ProductController@storeMenuCategory');
            Route::post('menu-categories/update/{id}', 'ProductController@updateMenuCategory');
            Route::post('menu-categories/delete/{id}', 'ProductController@deleteMenuCategory');

            //seller order management
            Route::controller('OrderController')->prefix('orders')->group(function () {
                Route::get('/', 'orders');
                Route::get('{id}', 'detail');
                Route::post('status/{id}', 'updateStatus');
            });

            // Seller wallet
            Route::controller('WalletController')->prefix('wallet')->group(function () {
                Route::get('balance', 'getBalance');
                Route::get('transactions', 'getTransactions');
                Route::post('withdraw', 'withdrawRequest');
            });

            // Seller stories
            Route::controller('StoryController')->prefix('stories')->group(function () {
                Route::get('/', 'index');
                Route::get('{id}', 'show');
                Route::post('store', 'store');
                Route::post('{id}/pause', 'pause');
                Route::post('{id}/resume', 'resume');
                Route::delete('{id}', 'destroy');
            });

            // Seller schedules
            Route::controller('ScheduleController')->prefix('schedules')->group(function () {
                Route::get('/', 'index');
                Route::post('store', 'store');
                Route::post('bulk', 'bulkStore');
                Route::delete('{id}', 'destroy');
                Route::post('clear-day', 'clearDay');
            });

            // SUNAT lookup endpoint
            Route::post('sunat-lookup', [\App\Http\Controllers\Api\Seller\PanelController::class, 'sunatLookup']);

            // Business packages
            Route::controller('PackageController')->prefix('packages')->group(function () {
                Route::get('/', 'list');
                Route::get('my', 'myPackages');
                Route::post('purchase', 'purchase');
                Route::get('{storeId}/analytics', 'analytics');
                Route::post('{storeId}/notify', 'notify');
                Route::get('{storeId}/qr', 'qrCode');
            });

            // Seller Panel (dashboard, tables, cash, billing, etc.) — requires active subscription
            Route::middleware(\App\Http\Middleware\CheckSubscription::class . ':restaurant_platform')->controller('\App\Http\Controllers\Api\Seller\PanelController')->prefix('panel')->group(function () {

                Route::get('dashboard', 'dashboard');
                Route::get('tables', 'tables');
                Route::post('tables/store', 'tableStore');
                Route::post('tables/{id}/update', 'tableUpdate');
                Route::post('tables/{id}/delete', 'tableDelete');
                Route::post('tables/position', 'tablePosition');
                Route::get('areas', 'areas');
                Route::post('areas/store', 'areaStore');
                Route::post('areas/{id}/delete', 'areaDelete');
                Route::get('kitchen', 'kitchen');
                Route::post('kitchen/{id}/status', 'kitchenStatus');
                Route::get('orders', 'orders');
                Route::get('customers', 'customers');
                Route::get('cash', 'cash');
                Route::post('cash/open', 'cashOpen');
                Route::post('cash/close', 'cashClose');
                Route::post('cash/transaction', 'cashTransaction');
                Route::get('expenses', 'expenses');
                Route::post('expenses/store', 'expenseStore');
                Route::post('expenses/{id}/delete', 'expenseDelete');
                Route::get('billing', 'billing');
                Route::post('billing/{id}/pay', 'payOrder');
                Route::post('billing/{id}/invoice', 'generateInvoice');
                Route::get('billing/{id}/ticket', 'orderPreCheckTicket');
                Route::get('invoicing', 'invoicing');
                Route::post('invoicing/series/store', 'invoiceSeriesStore');
                Route::get('invoicing/invoice/{id}/pdf/{format?}', 'invoicePdf');
                Route::get('invoicing/invoice/{id}/xml', 'invoiceXml');
                Route::get('invoicing/invoice/{id}/cdr', 'invoiceCdr');
                Route::post('notifications/send', 'sendNotification');
                Route::get('qrmenu', 'qrMenu');
                Route::get('reports', 'reports');
                // Mozo ordering endpoints
                Route::get('products', 'products');
                Route::post('order/create', 'orderCreate');
                Route::get('table/{tableId}/active-order', 'getActiveTableOrder');
            });

            // Store favors (solicitar repartidor para entregas propias)
            Route::middleware(\App\Http\Middleware\CheckSubscription::class . ':delivery_requests')->controller('\App\Http\Controllers\Api\StoreFavorController')->prefix('favors')->group(function () {
            Route::post('fee-estimate', 'feeEstimate');
            Route::post('create', 'create');
            Route::get('/', 'myFavors');
            Route::get('{id}', 'detail');
            Route::get('{id}/search-status', 'searchStatus');
            Route::post('{id}/retry-search', 'retrySearch');
            Route::post('cancel/{id}', 'cancel');
            Route::get('{id}/bids', 'bids');
            Route::post('{favorId}/bids/{bidId}/accept', 'acceptBid');
        });
    });
});
