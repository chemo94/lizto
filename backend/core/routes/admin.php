<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Auth')->group(function () {
    Route::middleware('admin.guest')->group(function () {
        Route::controller('LoginController')->group(function () {
            Route::get('/', 'showLoginForm')->name('login');
            Route::post('/', 'login')->name('login.submit');
            Route::get('logout', 'logout')->middleware('admin')->withoutMiddleware('admin.guest')->name('logout');
        });
        // Admin Password Reset
        Route::controller('ForgotPasswordController')->prefix('password')->name('password.')->group(function () {
            Route::get('reset', 'showLinkRequestForm')->name('reset');
            Route::post('reset', 'sendResetCodeEmail');
            Route::get('code-verify', 'codeVerify')->name('code.verify');
            Route::post('verify-code', 'verifyCode')->name('verify.code');
        });

        Route::controller('ResetPasswordController')->group(function () {
            Route::get('password/reset/{token}', 'showResetForm')->name('password.reset.form');
            Route::post('password/reset/change', 'reset')->name('password.change');
        });
    });
});

Route::middleware('admin')->group(function () {

    //zone
    Route::controller('ZoneController')->prefix('zone')->name('zone.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view zone,admin');
        Route::get('create', 'create')->name('create')->middleware('permission:add zone,admin');
        Route::get('edit/{id}', 'edit')->name('edit')->middleware('permission:edit zone,admin');
        Route::post('save/{id}', 'save')->name('save')->middleware('permission:edit zone,admin');
        Route::post('change-status/{id}', 'changeStatus')->name('status')->middleware('permission:edit zone,admin');
    });

    //Service
    Route::controller('ServiceController')->prefix('services')->name('service.')->group(function () {
        Route::get('index', 'index')->name('index')->middleware('permission:view service,admin');
        Route::post('store', 'store')->name('store')->middleware('permission:add service,admin');
        Route::post('update/{id}', 'store')->name('update')->middleware('permission:edit service,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:edit service,admin');
    });

    //Brands
    Route::controller('BrandController')->prefix('brands')->name('brand.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view brand,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:edit brand,admin');
        Route::post('store', 'store')->name('store')->middleware('permission:add brand,admin');
        Route::post('update/{id}', 'store')->name('update')->middleware('permission:edit brand,admin');
    });
    //Banners
    Route::controller('BannerController')->prefix('banners')->name('banner.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view banner,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:edit banner,admin');
        Route::post('store', 'store')->name('store')->middleware('permission:add banner,admin');
        Route::post('update/{id}', 'store')->name('update')->middleware('permission:edit banner,admin');
    });
    //Sellers
    Route::controller('SellerController')->prefix('sellers')->name('seller.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view seller,admin');
        Route::get('pending', 'pending')->name('pending')->middleware('permission:view seller,admin');
        Route::get('approved', 'approved')->name('approved')->middleware('permission:view seller,admin');
        Route::get('banned', 'banned')->name('banned')->middleware('permission:view seller,admin');
        Route::get('detail/{id}', 'detail')->name('detail')->middleware('permission:view seller,admin');
        Route::post('store/{storeId}/qr', 'saveStoreQr')->name('store.qr.save')->middleware('permission:edit seller,admin');
        Route::post('{id}/receivable/settle', 'settleReceivable')->name('receivable.settle')->middleware('permission:edit seller,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:edit seller,admin');
        Route::post('verification/{id}', 'verification')->name('verification')->middleware('permission:edit seller,admin');
    });
    //Fleets
    Route::controller('ManageFleetsController')->prefix('fleets')->name('fleet.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('detail/{id}', 'detail')->name('detail');
        Route::post('update/{id}', 'update')->name('update');
        Route::post('adjust-balance/{id}', 'adjustBalance')->name('adjust.balance');
    });
    //model
    Route::controller('VehicleModelController')->prefix('vehicle-model')->name('model.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view vehicle model,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:edit vehicle model,admin');
        Route::post('store', 'store')->name('store')->middleware('permission:add vehicle model,admin');
        Route::post('update/{id}', 'store')->name('update')->middleware('permission:edit vehicle model,admin');
    });
    //color
    Route::controller('VehicleColorController')->prefix('vehicle-color')->name('color.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view vehicle color,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:edit vehicle color,admin');
        Route::post('store', 'store')->name('store')->middleware('permission:add vehicle color,admin');
        Route::post('update/{id}', 'store')->name('update')->middleware('permission:edit vehicle color,admin');
    });

    //year
    Route::controller('VehicleYearController')->prefix('vehicle-year')->name('year.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view vehicle year,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:edit vehicle year,admin');
        Route::post('store', 'store')->name('store')->middleware('permission:add vehicle year,admin');
        Route::post('update/{id}', 'store')->name('update')->middleware('permission:edit vehicle year,admin');
    });

    //Coupons
    Route::controller('CouponController')->prefix('coupon')->name('coupon.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view coupon,admin');
        Route::post('store', 'store')->name('store')->middleware('permission:add coupon,admin');
        Route::post('update/{id}', 'store')->name('update')->middleware('permission:edit coupon,admin');
        Route::post('change-status/{id}', 'changeStatus')->name('status.change')->middleware('permission:edit coupon,admin');
    });

    Route::controller('ManageReviewController')->name('reviews.')->prefix('reviews')->group(function () {
        Route::get('/', 'reviews')->name('all')->middleware('permission:all reviews,admin');
        Route::get('rider/all', 'allRiderReviews')->name('all.rider')->middleware('permission:all reviews,admin');
        Route::get('driver/all', 'allDriverReviews')->name('all.driver')->middleware('permission:all reviews,admin');
        Route::get('rider/{id}', 'riderReviews')->name('rider')->middleware('permission:all reviews,admin');
        Route::get('driver/{id}', 'driverReviews')->name('driver')->middleware('permission:all reviews,admin');
    });

    Route::controller('ManageRideController')->middleware('permission:view rides,admin')->name('rides.')->prefix('rides')->group(function () {
        Route::get('/', 'allRides')->name('all');
        Route::get('/rider', 'riderRides')->name('all.rider');
        Route::get('/rider/completed', 'riderCompletedRides')->name('all.rider.completed');
        Route::get('/rider/canceled', 'riderCanceledRides')->name('all.rider.canceled');
        Route::get('/driver', 'driverRides')->name('all.driver');
        Route::get('/driver/completed', 'driverCompletedRides')->name('all.driver.completed');
        Route::get('/driver/canceled', 'driverCanceledRides')->name('all.driver.canceled');
        Route::get('new', 'new')->name('new');
        Route::get('location/{id}', 'location')->name('location');
        Route::get('live-location/{id}', 'liveLocation')->name('live.location');
        Route::get('running', 'running')->name('running');
        Route::get('completed', 'completed')->name('completed');
        Route::get('canceled', 'canceled')->name('canceled');
        Route::get('detail/{id}', 'detail')->name('detail');
        Route::get('bids/{id}', 'bid')->name('bids');
        Route::get('tips', 'tips')->name('tips');
    });

    // SOS Alerts
    Route::controller('SosController')->middleware('permission:view rides,admin')->name('sos.')->prefix('sos')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('location/{id}', 'location')->name('location');
        Route::post('resolve/{id}', 'resolve')->name('resolve');
    });

    // Users Manager
    Route::controller('ManageRiderController')->name('rider.')->prefix('rider')->group(function () {
        Route::get('/', 'allUsers')->name('all')->middleware('permission:view riders,admin');
        Route::get('active', 'activeUsers')->name('active')->middleware('permission:view riders,admin');
        Route::get('banned', 'bannedUsers')->name('banned')->middleware('permission:view riders,admin');
        Route::get('email-verified', 'emailVerifiedUsers')->name('email.verified')->middleware('permission:view riders,admin');
        Route::get('email-unverified', 'emailUnverifiedUsers')->name('email.unverified')->middleware('permission:view riders,admin');
        Route::get('mobile-unverified', 'mobileUnverifiedUsers')->name('mobile.unverified')->middleware('permission:view riders,admin');
        Route::get('mobile-verified', 'mobileVerifiedUsers')->name('mobile.verified')->middleware('permission:view riders,admin');

        Route::get('detail/{id}', 'detail')->name('detail')->middleware('permission:view riders,admin');
        Route::post('update/{id}', 'update')->name('update')->middleware('permission:update riders,admin');
        Route::post('add-sub-balance/{id}', 'addSubBalance')->name('add.sub.balance')->middleware('permission:update riders,admin');
        Route::get('send-notification/{id}', 'showNotificationSingleForm')->name('notification.single')->middleware('permission:send notification to riders,admin');
        Route::post('send-notification/{id}', 'sendNotificationSingle')->name('notification.single.send')->middleware('permission:send notification to riders,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:update riders,admin');

        Route::get('send-notification', 'showNotificationAllForm')->name('notification.all')->middleware('permission:send notification to riders,admin');
        Route::post('send-notification', 'sendNotificationAll')->name('notification.all.send')->middleware('permission:send notification to riders,admin');
        Route::get('list', 'list')->name('list');
        Route::get('count-by-segment/{methodName}', 'countBySegment')->name('segment.count');
        Route::get('notification-log/{id}', 'notificationLog')->name('notification.log')->middleware('permission:view rider notifications,admin');
    });

    //Rider Rules
    Route::controller('RiderRuleController')->prefix('rider/rules')->name('rider.rule.')->group(function () {
        Route::get('index', 'index')->name('index')->middleware('permission:view rider rule,admin');
        Route::post('store', 'store')->name('store')->middleware('permission:add rider rule,admin');
        Route::post('update/{id?}', 'store')->name('update')->middleware('permission:edit rider rule,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:edit rider rule,admin');
    });

    // Driver Manager
    Route::controller('ManageDriversController')->name('driver.')->prefix('drivers')->group(function () {
        Route::get('tracking', 'tracking')->name('tracking')->middleware('permission:view drivers,admin');
        Route::get('/', 'allDrivers')->name('all')->middleware('permission:view drivers,admin');
        Route::get('active', 'activeDrivers')->name('active')->middleware('permission:view drivers,admin');
        Route::get('banned', 'bannedDrivers')->name('banned')->middleware('permission:view drivers,admin');
        Route::get('email-verified', 'emailVerifiedDrivers')->name('email.verified')->middleware('permission:view drivers,admin');
        Route::get('email-unverified', 'emailUnverifiedDrivers')->name('email.unverified')->middleware('permission:view drivers,admin');
        Route::get('mobile-unverified', 'mobileUnverifiedDrivers')->name('mobile.unverified')->middleware('permission:view drivers,admin');
        Route::get('unverified', 'unverifiedDrivers')->name('unverified')->middleware('permission:view drivers,admin');
        Route::get('verify-pending', 'verifyPendingDrivers')->name('verify.pending')->middleware('permission:view drivers,admin');

        Route::get('vehicle-unverified', 'vehicleUnverifiedDrivers')->name('vehicle.unverified')->middleware('permission:view drivers,admin');
        Route::get('vehicle-verify-pending', 'vehiclePendingDrivers')->name('vehicle.verify.pending')->middleware('permission:view drivers,admin');

        Route::get('mobile-verified', 'mobileVerifiedDrivers')->name('mobile.verified')->middleware('permission:view drivers,admin');

        Route::get('detail/{id}', 'detail')->name('detail')->middleware('permission:view drivers,admin');
        Route::get('location/{id}', 'location')->name('location')->middleware('permission:view drivers,admin');
        Route::get('live-location/{id}', 'liveLocation')->name('live.location')->middleware('permission:view drivers,admin');
        Route::get('verification/details/{id}', 'verificationDetails')->name('verification.details');
        Route::post('verification-approve/{id}', 'verificationApprove')->name('verification.approve');
        Route::post('verification-reject/{id}', 'verificationReject')->name('verification.reject');
        Route::post('vehicle-approve/{id}', 'vehicleApprove')->name('vehicle.approve');
        Route::post('vehicle-reject/{id}', 'vehicleReject')->name('vehicle.reject');
        Route::get('rides/rules/{id}', 'rideRules')->name('rides.rules');

        Route::post('update/{id}', 'update')->name('update');
        Route::post('change-zone/{id}', 'changeZone')->name('change.zone')->middleware('permission:update drivers,admin');
        Route::post('add-sub-balance/{id}', 'addSubBalance')->name('add.sub.balance')->middleware('permission:update driver balance,admin');
        Route::get('send-notification/{id}', 'showNotificationSingleForm')->name('notification.single')->middleware('permission:notification to all drivers,admin');
        Route::post('send-notification/{id}', 'sendNotificationSingle')->name('notification.single.send')->middleware('permission:notification to all drivers,admin');
        Route::post('status/{id}', 'status')->name('status');
        Route::post('account/delete/{id}', 'deleteAccount')->name('delete.account');

        Route::get('send-notification', 'showNotificationAllForm')->name('notification.all')->middleware('permission:notification to all drivers,admin');
        Route::post('send-notification', 'sendNotificationAll')->name('notification.all.send')->middleware('permission:notification to all drivers,admin');
        Route::get('list', 'list')->name('list');
        Route::get('count-by-segment/{methodName}', 'countBySegment')->name('segment.count');
        Route::get('notification-log/{id}', 'notificationLog')->name('notification.log')->middleware('permission:view driver notifications,admin');
        Route::get('ranking', 'driversRanking')->name('ranking')->middleware('permission:view drivers ranking,admin');
    });

    Route::controller('RiderAnalysisController')->name('analysis.rider.')->prefix('rider')->group(function () {
        Route::get('all/analysis', 'allRiderAnalysis')->name('all')->middleware('permission:rider analysis,admin');
        Route::get('all/chart/spent', 'allPaymentSpentReport')->name('all.chart.spent')->middleware('permission:rider analysis,admin');
        Route::get('analysis/{id}', 'riderAnalysis')->name('analysis')->middleware('permission:view rider analysis,admin');
        Route::get('chart/spent/{id}', 'paymentSpentReport')->name('chart.spent')->middleware('permission:view rider analysis,admin');
    });

    Route::controller('DriverAnalysisController')->name('analysis.driver.')->prefix('driver')->group(function () {
        Route::get('all/analysis', 'allDriverAnalysis')->name('all')->middleware('permission:driver analysis,admin');
        Route::get('all/chart/spent', 'allPaymentSpentReport')->name('all.chart.spent')->middleware('permission:driver analysis,admin');
        Route::get('analysis/{id}', 'driverAnalysis')->name('analysis')->middleware('permission:view driver analysis,admin');
        Route::get('chart/spent/{id}', 'paymentSpentReport')->name('chart.spent')->middleware('permission:view driver analysis,admin');
    });
    Route::controller('DriverEarningController')->name('driver.')->prefix('driver')->group(function () {
        Route::get('earning/{id}', 'driverEarning')->name('earning')->middleware('permission:view driver earning,admin');
        Route::get('earning/{id}/chart', 'chart')->name('earning.chart')->middleware('permission:view driver earning,admin');
        Route::post('earning/{id}/cash-remittance', 'remitCash')->name('earning.cash.remit')->middleware('permission:update driver balance,admin');
        Route::post('earning/{id}/earning-settlement', 'settleEarning')->name('earning.settle')->middleware('permission:update driver balance,admin');
    });

    // role & permission
    Route::controller('RoleController')->name('role.')->prefix('role')->group(function () {
        Route::get('list', 'list')->name('list')->middleware('permission:view roles,admin');
        Route::post('create', 'save')->name('create')->middleware('permission:add role,admin');
        Route::post('update/{id}', 'save')->name('update')->middleware('permission:edit role,admin');
        Route::post('status-change/{id}', 'status')->name('status.change')->middleware('permission:edit role,admin');
        Route::get('permission/{id}', 'permission')->name('permission')->middleware('permission:assign permissions,admin');
        Route::post('permission/update/{id}', 'permissionUpdate')->name('permission.update')->middleware('permission:assign permissions,admin');
    });

    // Subscriber
    Route::controller('SubscriberController')->middleware('permission:manage subscribers,admin')->prefix('subscriber')->name('subscriber.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('send-email', 'sendEmailForm')->name('send.email');
        Route::post('remove/{id}', 'remove')->name('remove');
        Route::post('send-email', 'sendEmail')->name('send.email.submit');
    });

    // Report
    Route::controller('ReportController')->name('report.')->prefix('report')->group(function () {
        Route::prefix('rider')->name('rider.')->group(function () {
            Route::get('rider-payment', 'riderPayment')->name('payment')->middleware('permission:view rider payment report,admin');
            Route::get('rider-payment/today', 'riderPaymentPerToday')->name('payment.today')->middleware('permission:view rider payment report,admin');
            Route::get('rider-payment/week', 'riderPaymentPerWeek')->name('payment.week')->middleware('permission:view rider payment report,admin');
            Route::get('rider-payment/month', 'riderPaymentPerMonth')->name('payment.month')->middleware('permission:view rider payment report,admin');
            Route::get('rider-payment/year', 'riderPaymentPerYear')->name('payment.year')->middleware('permission:view rider payment report,admin');
            Route::get('login/history', 'loginHistory')->name('login.history')->middleware('permission:view rider login history,admin');
            Route::get('login/ipHistory/{ip}', 'loginIpHistory')->name('login.ipHistory')->middleware('permission:view rider login history,admin');
            Route::get('notification/history', 'notificationHistory')->name('notification.history')->middleware('permission:view rider notification history,admin');
            Route::get('email/detail/{id}', 'emailDetails')->name('email.details');
        });
        Route::prefix('driver')->name('driver.')->group(function () {
            Route::get('transaction', 'transaction')->name('transaction')->middleware('permission:view driver transaction history,admin');
            Route::get('commission', 'commission')->name('commission')->middleware('permission:view driver commission history,admin');
            Route::get('login/history', 'loginHistoryDriver')->name('login.history')->middleware('permission:view driver login history,admin');
            Route::get('login/ipHistory/{ip}', 'loginIpHistory')->name('login.ipHistory')->middleware('permission:view driver login history,admin');
            Route::get('notification/history', 'notificationHistoryDriver')->name('notification.history')->middleware('permission:view driver notification history,admin');
            Route::get('email/detail/{id}', 'emailDetails')->name('email.details');
        });
    });

    // Admin Support
    Route::controller('SupportTicketController')->prefix('ticket')->name('ticket.')->group(function () {
        Route::get('/', 'tickets')->name('index')->middleware('permission:view user tickets,admin');
        Route::get('pending', 'pendingTicket')->name('pending')->middleware('permission:view user tickets,admin');
        Route::get('closed', 'closedTicket')->name('closed')->middleware('permission:view user tickets,admin');
        Route::get('answered', 'answeredTicket')->name('answered')->middleware('permission:view user tickets,admin');
        Route::get('view/{id}', 'ticketReply')->name('view')->middleware('permission:view user tickets,admin');
        Route::post('reply/{id}', 'replyTicket')->name('reply')->middleware('permission:answer tickets,admin');
        Route::post('close/{id}', 'closeTicket')->name('close')->middleware('permission:close tickets,admin');
        Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
        Route::post('delete/{id}', 'ticketDelete')->name('delete');
    });

    Route::controller('AdminController')->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard')->middleware('permission:view dashboard,admin');
        Route::get('chart/deposit-withdraw', 'depositAndWithdrawReport')->name('chart.deposit.withdraw')->middleware('permission:view dashboard,admin');
        Route::get('chart/transaction', 'transactionReport')->name('chart.transaction')->middleware('permission:view dashboard,admin');
        Route::get('profile', 'profile')->name('profile');
        Route::post('profile', 'profileUpdate')->name('profile.update');
        Route::get('password', 'password')->name('password');
        Route::post('password', 'passwordUpdate')->name('password.update');
        Route::post('save-token', 'saveDeviceToken')->name('save-token');

        //Notification
        Route::get('notifications', 'notifications')->name('notifications')->middleware('permission:notification settings,admin');
        Route::get('notification/read/{id}', 'notificationRead')->name('notification.read')->middleware('permission:notification settings,admin');
        Route::get('notifications/read-all', 'readAllNotification')->name('notifications.read.all')->middleware('permission:notification settings,admin');
        Route::post('notifications/delete-all', 'deleteAllNotification')->name('notifications.delete.all')->middleware('permission:notification settings,admin');
        Route::post('notifications/delete-single/{id}', 'deleteSingleNotification')->name('notifications.delete.single')->middleware('permission:notification settings,admin');

        //Report Bugs
        Route::get('download-attachments/{file_hash}', 'downloadAttachment')->name('download.attachment');
        Route::get('send/promotional-notification', 'showPromotionalNotificationForm')->name('promotional.notification.all')->middleware('permission:promotional notify,admin');
        Route::post('send-promotional-notification', 'sendPromotionalNotificationAll')->name('promotional.notification.all.send');

        // Assign role
        Route::get('list', 'list')->name('list')->middleware('permission:view admin,admin');
        Route::post('store', 'save')->name('store')->middleware('permission:add admin,admin');
        Route::post('update/{id}', 'save')->name('update')->middleware('permission:edit admin,admin');
        Route::post('status-change/{id}', 'status')->name('status.change')->middleware('permission:edit admin,admin');
    });

    // extensions
    Route::controller('ExtensionController')->prefix('extensions')->name('extensions.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view extension,admin');
        Route::post('update/{id}', 'update')->name('update')->middleware('permission:update extension,admin');
        Route::post('status/{id}', 'status')->name('status')->middleware('permission:update extension,admin');
    });

    // Language Manager
    Route::controller('LanguageController')->prefix('language')->name('language.')->group(function () {
        Route::get('/', 'langManage')->name('manage')->middleware('permission:view language,admin');
        Route::post('/', 'langStore')->name('manage.store')->middleware('permission:update language,admin');
        Route::post('delete/{id}', 'langDelete')->name('manage.delete')->middleware('permission:update language,admin');
        Route::post('update/{id}', 'langUpdate')->name('manage.update')->middleware('permission:update language,admin');
        Route::get('edit/{id}', 'langEdit')->name('key')->middleware('permission:update language,admin');
        Route::post('import', 'langImport')->name('import.lang')->middleware('permission:update language,admin');
        Route::post('store/key/{id}', 'storeLanguageJson')->name('store.key')->middleware('permission:update language,admin');
        Route::post('delete/key/{id}/{key}', 'deleteLanguageJson')->name('delete.key')->middleware('permission:update language,admin');
        Route::post('update/key/{id}', 'updateLanguageJson')->name('update.key')->middleware('permission:update language,admin');
        Route::get('get-keys', 'getKeys')->name('get.key')->middleware('permission:update language,admin');
    });

    //Notification Setting
    Route::name('setting.notification.')->middleware('permission:notification settings,admin')->controller('NotificationController')->prefix('notification')->group(function () {
        //Template Setting
        Route::get('global/email', 'globalEmail')->name('global.email');
        Route::post('global/email/update', 'globalEmailUpdate')->name('global.email.update');

        Route::get('global/sms', 'globalSms')->name('global.sms');
        Route::post('global/sms/update', 'globalSmsUpdate')->name('global.sms.update');

        Route::get('global/push', 'globalPush')->name('global.push');
        Route::post('global/push/update', 'globalPushUpdate')->name('global.push.update');

        Route::get('templates', 'templates')->name('templates');
        Route::get('template/edit/{type}/{id}', 'templateEdit')->name('template.edit');
        Route::post('template/update/{type}/{id}', 'templateUpdate')->name('template.update');

        //Email Setting
        Route::get('email/setting', 'emailSetting')->name('email');
        Route::post('email/setting', 'emailSettingUpdate')->name('email.update');
        Route::post('email/test', 'emailTest')->name('email.test');

        //SMS Setting
        Route::get('sms/setting', 'smsSetting')->name('sms');
        Route::post('sms/setting', 'smsSettingUpdate')->name('sms.update');
        Route::post('sms/test', 'smsTest')->name('sms.test');

        Route::get('notification/push/setting', 'pushSetting')->name('push');
        Route::post('notification/push/setting', 'pushSettingUpdate')->name('push.update');
        Route::post('notification/push/setting/upload', 'pushSettingUpload')->name('push.upload');
        Route::get('notification/push/setting/download', 'pushSettingDownload')->name('push.download');
    });

    //KYC setting
    Route::controller('KycController')->group(function () {
        Route::get('driver-verification', 'driverVerification')->name('verification.driver.form')->middleware('permission:view driver verification form,admin');
        Route::post('driver-verification', 'driverVerificationUpdate')->name('verification.driver.update')->middleware('permission:view driver verification form,admin');

        Route::get('vehicle-verification', 'vehicleVerification')->name('vehicle.verification.form')->middleware('permission:view vehicle verification form,admin');
        Route::post('vehicle-verification', 'vehicleVerificationUpdate')->name('vehicle.verification.update')->middleware('permission:view vehicle verification form,admin');
    });

    // DEPOSIT SYSTEM
    Route::controller('DepositController')->prefix('deposit')->name('deposit.')->group(function () {
        Route::get('all', 'deposit')->name('list')->middleware('permission:view driver deposits,admin');
        Route::get('pending', 'pending')->name('pending')->middleware('permission:view driver deposits,admin');
        Route::get('rejected', 'rejected')->name('rejected')->middleware('permission:view driver deposits,admin');
        Route::get('approved', 'approved')->name('approved')->middleware('permission:view driver deposits,admin');
        Route::get('successful', 'successful')->name('successful')->middleware('permission:view driver deposits,admin');
        Route::get('initiated', 'initiated')->name('initiated')->middleware('permission:view driver deposits,admin');
        Route::get('details/{id}', 'details')->name('details')->middleware('permission:view driver deposits,admin');
        Route::post('reject', 'reject')->name('reject')->middleware('permission:reject driver deposits,admin');
        Route::post('approve/{id}', 'approve')->name('approve')->middleware('permission:approve driver deposits,admin');
    });

    // WITHDRAW SYSTEM
    Route::name('withdraw.')->prefix('withdraw')->group(function () {

        Route::controller('WithdrawalController')->name('data.')->group(function () {
            Route::get('pending/{user_id?}', 'pending')->name('pending')->middleware('permission:view driver withdrawals,admin');
            Route::get('approved/{user_id?}', 'approved')->name('approved')->middleware('permission:view driver withdrawals,admin');
            Route::get('rejected/{user_id?}', 'rejected')->name('rejected')->middleware('permission:view driver withdrawals,admin');
            Route::get('all/{user_id?}', 'all')->name('all')->middleware('permission:view driver withdrawals,admin');
            Route::get('details/{id}', 'details')->name('details')->middleware('permission:view driver withdrawals,admin');
            Route::post('approve', 'approve')->name('approve')->middleware('permission:approve driver withdrawals,admin');
            Route::post('reject', 'reject')->name('reject')->middleware('permission:reject driver withdrawals,admin');
        });

        // Withdraw Method
        Route::controller('WithdrawMethodController')->prefix('method')->name('method.')->group(function () {
            Route::get('/', 'methods')->name('index')->middleware('permission:view withdrawals methods,admin');
            Route::get('create', 'create')->name('create')->middleware('permission:update withdrawals methods,admin');
            Route::post('create', 'store')->name('store')->middleware('permission:update withdrawals methods,admin');
            Route::get('edit/{id}', 'edit')->name('edit')->middleware('permission:update withdrawals methods,admin');
            Route::post('edit/{id}', 'update')->name('update')->middleware('permission:update withdrawals methods,admin');
            Route::post('status/{id}', 'status')->name('status')->middleware('permission:update withdrawals methods,admin');
        });
    });

    // SEO
    Route::get('seo', 'FrontendController@seoEdit')->name('seo')->middleware(('permission:view seo,admin'));

    // Frontend
    Route::name('frontend.')->prefix('frontend')->group(function () {

        Route::controller('FrontendController')->middleware('permission:manage sections,admin')->group(function () {
            Route::get('index', 'index')->name('index');
            Route::get('templates', 'templates')->name('templates');
            Route::post('templates', 'templatesActive')->name('templates.active');
            Route::get('frontend-sections/{key?}', 'frontendSections')->name('sections');
            Route::post('frontend-content/{key}', 'frontendContent')->name('sections.content');
            Route::get('frontend-element/{key}/{id?}', 'frontendElement')->name('sections.element');
            Route::get('frontend-slug-check/{key}/{id?}', 'frontendElementSlugCheck')->name('sections.element.slug.check');
            Route::get('frontend-element-seo/{key}/{id}', 'frontendSeo')->name('sections.element.seo');
            Route::post('frontend-element-seo/{key}/{id}', 'frontendSeoUpdate')->name('sections.element.seo.update');
            Route::post('remove/{id}', 'remove')->name('remove');
        });

        // Page Builder
        Route::controller('PageBuilderController')->middleware('permission:manage pages,admin')->group(function () {
            Route::get('manage-pages', 'managePages')->name('manage.pages');
            Route::get('manage-pages/check-slug/{id?}', 'checkSlug')->name('manage.pages.check.slug');
            Route::post('manage-pages', 'managePagesSave')->name('manage.pages.save');
            Route::post('manage-pages/update', 'managePagesUpdate')->name('manage.pages.update');
            Route::post('manage-pages/delete/{id}', 'managePagesDelete')->name('manage.pages.delete');
            Route::get('manage-section/{id}', 'manageSection')->name('manage.section');
            Route::post('manage-section/{id}', 'manageSectionUpdate')->name('manage.section.update');

            Route::get('manage-seo/{id}', 'manageSeo')->name('manage.pages.seo');
            Route::post('manage-seo/{id}', 'manageSeoStore')->name('manage.pages.seo.store');
        });
    });

    //System Information
    Route::controller('SystemController')->name('system.')->prefix('system')->group(function () {
        Route::get('info', 'systemInfo')->name('info')->middleware('permission:view application info,admin');
        Route::get('optimize-clear', 'optimizeClear')->name('optimize.clear');
    });

    Route::controller('GeneralSettingController')->group(function () {

        // General Setting
        Route::get('general-setting', 'general')->name('setting.general')->middleware('permission:general settings,admin');
        Route::post('general-setting', 'generalUpdate')->name('setting.general.update')->middleware('permission:general settings,admin');

        // ride Setting
        Route::get('ride-setting', 'rideSetting')->name('setting.ride')->middleware('permission:general settings,admin');
        Route::post('ride-setting', 'rideSettingUpdate')->name('setting.ride.update')->middleware('permission:general settings,admin');

        // Pusher Configuration
        Route::get('pusher-configuration', 'pusherConfiguration')->name('setting.pusher.configuration')->middleware('permission:pusher configuration,admin');
        Route::post('pusher-configuration', 'pusherConfigurationUpdate')->name('setting.pusher.configuration.update')->middleware('permission:pusher configuration,admin');

        // Google maps Configuration
        Route::get('google-maps-setting', 'googleMaps')->name('setting.google.maps')->middleware('permission:google maps setting,admin');
        Route::post('google-maps-setting', 'googleMapsUpdate')->name('setting.google.maps.update')->middleware('permission:google maps setting,admin');

        //configuration
        Route::get('setting/system-configuration', 'systemConfiguration')->name('setting.system.configuration')->middleware('permission:system configuration,admin');
        Route::get('setting/system-configuration/{key}', 'systemConfigurationUpdate')->name("setting.system.configuration.update")->middleware('permission:system configuration,admin');

        // Logo-Icon
        Route::get('setting/brand', 'logoIcon')->name('setting.brand')->middleware('permission:brand settings,admin');
        Route::post('setting/brand', 'logoIconUpdate')->name('setting.brand.update')->middleware('permission:brand settings,admin');

        //Custom CSS
        Route::get('custom-css', 'customCss')->name('setting.custom.css')->middleware('permission:custom css,admin');
        Route::post('custom-css', 'customCssSubmit')->name('setting.custom.css.update')->middleware('permission:custom css,admin');

        Route::get('sitemap', 'sitemap')->name('setting.sitemap')->middleware('permission:sitemap,admin');
        Route::post('sitemap', 'sitemapSubmit')->name('setting.sitemap.update')->middleware('permission:sitemap,admin');

        Route::get('robot', 'robot')->name('setting.robot')->middleware('permission:robot,admin');
        Route::post('robot', 'robotSubmit')->name('setting.robot.update')->middleware('permission:robot,admin');

        //Cookie
        Route::get('cookie', 'cookie')->name('setting.cookie')->middleware('permission:gdpr cookie,admin');
        Route::post('cookie', 'cookieSubmit')->name('setting.cookie.update')->middleware('permission:gdpr cookie,admin');

        //maintenance_mode
        Route::get('maintenance-mode', 'maintenanceMode')->name('maintenance.mode')->middleware('permission:maintenance mode,admin');
        Route::post('maintenance-mode', 'maintenanceModeSubmit')->name('maintenance.mode.update')->middleware('permission:maintenance mode,admin');
    });
    // Deposit Gateway
    Route::name('gateway.')->prefix('gateway')->group(function () {

        // Manual Methods
        Route::controller('ManualGatewayController')->middleware('permission:update payment gateway,admin')->prefix('manual')->name('manual.')->group(function () {
            Route::get('new', 'create')->name('create');
            Route::post('new', 'store')->name('store');
            Route::get('edit/{alias}', 'edit')->name('edit');
            Route::post('update/{id}', 'update')->name('update');
            Route::post('status/{id}', 'status')->name('status');
        });

        // Automatic Gateway
        Route::controller('AutomaticGatewayController')->name('automatic.')->group(function () {
            Route::get('/', 'index')->name('index')->middleware('permission:view payment gateway,admin');
            Route::get('edit/{alias}', 'edit')->name('edit')->middleware('permission:update payment gateway,admin');
            Route::post('update/{code}', 'update')->name('update')->middleware('permission:update payment gateway,admin');
            Route::post('remove/{id}', 'remove')->name('remove')->middleware('permission:update payment gateway,admin');
            Route::post('status/{id}', 'status')->name('status')->middleware('permission:update payment gateway,admin');
        });
    });

    //cron
    Route::controller('CronConfigurationController')->middleware('permission:cron job settings,admin')->name('cron.')->prefix('cron')->group(function () {
        Route::get('index', 'cronJobs')->name('index');
        Route::post('store', 'cronJobStore')->name('store');
        Route::post('update/{id}', 'cronJobUpdate')->name('update');
        Route::post('delete/{id}', 'cronJobDelete')->name('delete');
        Route::get('schedule', 'schedule')->name('schedule');
        Route::post('schedule/store/{id?}', 'scheduleStore')->name('schedule.store');
        Route::post('schedule/status/{id}', 'scheduleStatus')->name('schedule.status');
        Route::get('schedule/pause/{id}', 'schedulePause')->name('schedule.pause');
        Route::get('schedule/logs/{id}', 'scheduleLogs')->name('schedule.logs');
        Route::post('schedule/log/resolved/{id}', 'scheduleLogResolved')->name('schedule.log.resolved');
        Route::post('schedule/log/flush/{id}', 'logFlush')->name('log.flush');
    });

    // ── Delivery Management ──
    Route::controller('DeliveryManagerController')->prefix('delivery')->name('delivery.')->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('app-releases', 'appReleases')->name('app.releases');
        Route::post('app-releases/{id?}', 'appReleaseSave')->name('app.releases.save');
        Route::get('orders', 'orders')->name('orders');
        Route::get('orders/{id}', 'orderDetail')->name('order.detail');
        Route::post('orders/{id}/status', 'orderStatus')->name('order.status');
        Route::post('orders/{id}/assign', 'assignDriver')->name('order.assign');
        Route::get('favors', 'favors')->name('favors');
        Route::get('favors/{id}', 'favorDetail')->name('favor.detail');
        Route::get('favors/{id}/live-location', 'favorLiveLocation')->name('favors.live.location');
        Route::post('favors/{id}/share-tracking', 'createFavorTrackingShare')->name('favors.share.tracking');
        Route::post('favors/{id}/status', 'favorStatus')->name('favor.status');
        Route::post('favors/{id}/courier', 'reassignFavorCourier')->name('favor.courier');
        Route::post('favors/{id}/destination', 'updateFavorDestination')->name('favor.destination');
        Route::post('broadcasting/auth', 'broadcastingAuth')->name('broadcasting.auth');
        Route::get('stores', 'stores')->name('stores');
        Route::get('stores/create', 'storeCreate')->name('store.create');
        Route::get('stores/{id}/edit', 'storeEdit')->name('store.edit');
        Route::post('stores/save/{id?}', 'storeSave')->name('store.save');
        Route::post('stores/{id}/subscription/renew', 'subscriptionRenew')->name('store.subscription.renew');
        Route::post('stores/{id}/notification', 'storeNotification')->name('store.notification');
        Route::get('stores/{id}', 'storeDetail')->name('store.detail');

        // Business packages
        Route::get('packages', 'packages')->name('packages');
        Route::post('packages/save/{id?}', 'packageSave')->name('packages.save');
        Route::get('packages/status/{id}', 'packageStatus')->name('packages.status');
        Route::post('packages/delete/{id}', 'packageDelete')->name('packages.delete');
        Route::post('subscription/{id}/approve', 'subscriptionApprove')->name('subscription.approve');
        Route::post('subscription/{id}/reject', 'subscriptionReject')->name('subscription.reject');

        // Coupons & Free Delivery
        Route::get('coupons', 'coupons')->name('coupons');
        Route::post('coupons/store', 'couponStore')->name('coupons.store');
        Route::post('coupons/{id}/update', 'couponUpdate')->name('coupons.update');
        Route::post('coupons/{id}/delete', 'couponDelete')->name('coupons.delete');

        Route::get('free-deliveries', 'freeDeliveries')->name('free.deliveries');
        Route::post('free-deliveries/store', 'freeDeliveryStore')->name('free.deliveries.store');
        Route::post('free-deliveries/{id}/delete', 'freeDeliveryDelete')->name('free.deliveries.delete');

    // Store categories
    Route::post('store/{storeId}/category/save', 'storeCategorySave')->name('store.category.save');
    Route::post('store/category/{id}/update', 'storeCategoryUpdate')->name('store.category.update');
    Route::get('store/category/{id}/toggle', 'storeCategoryToggle')->name('store.category.toggle');
    Route::post('store/category/{id}/delete', 'storeCategoryDelete')->name('store.category.delete');
        Route::get('categories', 'categories')->name('categories');
        Route::post('categories/store', 'categoryStore')->name('category.store');
        Route::post('categories/update/{id}', 'categoryUpdate')->name('category.update');
        Route::post('categories/delete/{id}', 'categoryDelete')->name('category.delete');
        Route::get('categories/{id}/sub', 'subCategories')->name('subcategories');
        Route::post('categories/{id}/sub/store', 'subCategoryStore')->name('subcategory.store');
        Route::post('categories/sub/update/{id}', 'subCategoryUpdate')->name('subcategory.update');
        Route::post('categories/sub/delete/{id}', 'subCategoryDelete')->name('subcategory.delete');

        // Bulk product upload
        Route::get('products/bulk', 'bulkUpload')->name('products.bulk');
        Route::get('products/bulk-template', 'downloadTemplate')->name('products.bulk.template');
        Route::post('products/bulk-store', 'bulkStore')->name('products.bulk.store');
        Route::post('products/ocr-parse', 'ocrParse')->name('products.ocr-parse');

        Route::get('products/create/{storeId}', 'productCreate')->name('product.create');
        Route::get('products/edit/{id}', 'productEdit')->name('product.edit');
        Route::post('products/save/{id?}', 'productSave')->name('product.save');
        Route::post('products/delete/{id}', 'productDelete')->name('product.delete');
        Route::get('refunds', 'refunds')->name('refunds');
        Route::post('refunds/{id}', 'refundAction')->name('refund.action');
    Route::get('commission', 'commission')->name('commission');
    Route::post('commission', 'commissionUpdate')->name('commission.update');

    // Request delivery
    Route::get('request', 'requestForm')->name('request');
    Route::post('request/submit', 'requestSubmit')->name('request.submit');
    Route::post('request/fee-calculate', 'requestFeeCalculate')->name('request.fee.calculate');
    Route::get('request/status/{id}', 'requestStatus')->name('request.status.show');
        Route::get('wallets', 'wallets')->name('wallets');
        Route::get('wallets/{id}', 'walletDetail')->name('wallet.detail');
        Route::get('wallets/{id}/transactions', 'walletTransactions')->name('wallet.transactions');
        Route::post('wallet/add', 'addBalance')->name('wallet.add');
        Route::post('wallet/deduct', 'deductBalance')->name('wallet.deduct');
        Route::get('holders-by-type', 'getHoldersByType')->name('holders.by.type');
        Route::get('withdrawals', 'withdrawals')->name('withdrawals');
        Route::post('withdrawals/approve', 'withdrawalApprove')->name('withdrawal.approve');
        Route::post('withdrawals/reject', 'withdrawalReject')->name('withdrawal.reject');
        Route::get('sections', 'sectionsConfig')->name('sections.config');
        Route::post('sections', 'sectionsConfigUpdate')->name('sections.config.update');
    });

    // Store Stories Admin
    Route::controller('StoreStoryController')->prefix('delivery/stories')->name('delivery.stories.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('{id}/approve', 'approve')->name('approve');
        Route::post('{id}/reject', 'reject')->name('reject');
        Route::delete('{id}', 'destroy')->name('delete');
    });

    // Libro de Reclamaciones
    Route::controller('ComplaintController')->prefix('complaints')->name('complaints.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:view complaints,admin');
        Route::get('/{id}', 'details')->name('details')->middleware('permission:view complaints,admin');
        Route::post('/{id}/update', 'update')->name('update')->middleware('permission:view complaints,admin');
    });

    // ── Kitchen Display (Pantalla Cocina) ──
    Route::controller('KitchenController')->prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/orders', 'orders')->name('orders');
    });
});
