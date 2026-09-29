class UrlContainer {
  static const String domainUrl = 'https://www.liztodelivery.com'; //YOUR WEBSITE DOMAIN URL HERE

  static const String baseUrl = '$domainUrl/api/';
  static const String wsUrl = 'wss://liztodelivery.com/ws';
  static const String dashBoardEndPoint = 'dashboard';
  static const String depositHistoryUrl = 'deposit/history';
  static const String depositMethodUrl = 'deposit/methods';
  static const String addMoneyUrl = 'add-money';

  static const String registrationEndPoint = 'seller/panel/register';
  static const String loginEndPoint = 'seller/login';
  static const String socialLoginEndPoint = 'seller/social-login';
  static const String whatsappSendOtp = 'seller/auth/whatsapp/send-otp';
  static const String whatsappVerifyOtp = 'seller/auth/whatsapp/verify-otp';
  static const String logoutUrl = 'logout';

  static const String forgetPasswordEndPoint = 'password/email';
  static const String passwordVerifyEndPoint = 'password/verify-code';
  static const String resetPasswordEndPoint = 'password/reset';

  static const String verify2FAUrl = 'verify-g2fa';
  static const String otpVerify = 'otp-verify';
  static const String otpResend = 'otp-resend';

  static const String verifyEmailEndPoint = 'verify-email';
  static const String verifySmsEndPoint = 'seller/verify-mobile';
  static const String resendVerifyCodeEndPoint = 'seller/resend-verify/';
  static const String authorizationCodeEndPoint = 'seller/authorization';
  static const String dashBoardUrl = 'dashboard';
  static const String paymentHistoryEndpoint = 'payment/history';

  static const String addWithdrawRequestUrl = 'withdraw-request';
  static const String withdrawMethodUrl = 'withdraw-method';
  static const String withdrawRequestConfirm = 'withdraw-request/confirm';
  static const String withdrawHistoryUrl = 'withdraw/history';
  static const String withdrawStoreUrl = 'withdraw/store/';
  static const String withdrawConfirmScreenUrl = 'withdraw/preview/';
  static const String kycFormUrl = 'kyc-form';
  static const String kycSubmitUrl = 'kyc-submit';

  static const String generalSettingEndPoint = 'general-setting';
  static const String userDeleteEndPoint = 'delete-account';
  static const String privacyPolicyEndPoint = 'policies';
  static const String getProfileEndPoint = 'user-info';
  static const String updateProfileEndPoint = 'profile-setting';
  static const String profileCompleteEndPoint = 'user-data-submit';
  static const String faq = "faq";

  static const String changePasswordEndPoint = 'change-password';
  static const String countryEndPoint = 'get-countries';
  static const String deviceTokenEndPoint = 'seller/save-device-token';
  static const String languageUrl = 'language/';

  static const String ride = 'ride';
  static const String rideDetails = '$ride/details';
  static const String ridePayment = '$ride/payment';
  static const String rideFareAndDistance = '$ride/fare-and-distance';
  static const String createRide = '$ride/create';

  static const String sosRide = '$ride/sos';
  static const String reviewRide = 'review';
  static const String getDriverReview = 'get-driver-review';
  static const String rideList = '$ride/list';

  static const String activeRide = '$ride/active';
  static const String completedRide = '$ride/completed';
  static const String canceledRide = '$ride/canceled';

  static const String rideMessageList = '$ride/messages';
  static const String sendMessage = '$ride/send/message';
  static const String rideBidList = '$ride/bids';
  static const String acceptBid = '$ride/accept';
  static const String rejectBid = '$ride/reject';
  static const String cancelBid = '$ride/cancel';

  // coupon

  static const String reference = 'reference';
  static const String couponList = 'coupons';
  static const String applyCoupon = 'apply-coupon';
  static const String removeCoupon = 'remove-coupon';

  static const String paymentGateways = 'payment-gateways';
  static const String submitPayment = 'payment';
  static const String paymentHistory = 'payment/history';
  static const String pusherAuthenticate = 'pusher/auth/';

  //support ticket
  static const String supportMethodsEndPoint = 'support/method';
  static const String supportListEndPoint = 'ticket';
  static const String storeSupportEndPoint = 'ticket/create';
  static const String supportViewEndPoint = 'ticket/view';
  static const String supportReplyEndPoint = 'ticket/reply';
  static const String supportCloseEndPoint = 'ticket/close';
  static const String supportDownloadEndPoint = 'ticket/download';

  static const String nearbyDrivers = 'drivers/nearby';
  static const String rideReceipt = "${baseUrl}ride/receipt";
  static const String supportImagePath = '$domainUrl/assets/support/';
  static const String userImagePath = '$domainUrl/assets/support/';
  static const String serviceImagePath = '$domainUrl/assets/images/service/';
  // others url
  static const String countryFlagImageLink = 'https://flagpedia.net/data/flags/h24/{countryCode}.webp';
  static const String googleMapLocationSearch = 'https://maps.googleapis.com/maps/api';

  // ── Seller Packages ──
  static const String sellerPackagesEndpoint = 'seller/packages';
  static const String sellerPurchasePackageEndpoint = 'seller/packages/purchase';
  static const String sellerMyPackagesEndpoint = 'seller/packages/my';
  static String sellerStoreAnalytics(int storeId) => 'seller/packages/$storeId/analytics';
  static String sellerNotifyCustomers(int storeId) => 'seller/packages/$storeId/notify';
  static String sellerStoreQr(int storeId) => 'seller/packages/$storeId/qr';

  // ── Seller Panel ──
  static const String panelDashboard = 'seller/panel/dashboard';
  static const String panelTables = 'seller/panel/tables';
  static const String panelTablesStore = 'seller/panel/tables/store';
  static String panelTableUpdate(int id) => 'seller/panel/tables/$id/update';
  static String panelTableDelete(int id) => 'seller/panel/tables/$id/delete';
  static const String panelTablesPosition = 'seller/panel/tables/position';
  static const String panelAreas = 'seller/panel/areas';
  static const String panelAreasStore = 'seller/panel/areas/store';
  static String panelAreaDelete(int id) => 'seller/panel/areas/$id/delete';
  static const String panelKitchen = 'seller/panel/kitchen';
  static String panelKitchenStatus(int id) => 'seller/panel/kitchen/$id/status';
  static const String panelOrders = 'seller/panel/orders';
  static const String panelCustomers = 'seller/panel/customers';
  static const String panelCash = 'seller/panel/cash';
  static const String panelCashOpen = 'seller/panel/cash/open';
  static const String panelCashClose = 'seller/panel/cash/close';
  static const String panelCashTransaction = 'seller/panel/cash/transaction';
  static const String panelExpenses = 'seller/panel/expenses';
  static const String panelExpensesStore = 'seller/panel/expenses/store';
  static String panelExpenseDelete(int id) => 'seller/panel/expenses/$id/delete';
  static const String panelBilling = 'seller/panel/billing';
  static String panelBillingPay(int id) => 'seller/panel/billing/$id/pay';
  static String panelBillingInvoice(int id) => 'seller/panel/billing/$id/invoice';
  static const String panelInvoicing = 'seller/panel/invoicing';
  static const String panelInvoicingSeriesStore = 'seller/panel/invoicing/series/store';
  static const String panelNotificationsSend = 'seller/panel/notifications/send';
  static const String panelQrMenu = 'seller/panel/qrmenu';
  static const String panelReports = 'seller/panel/reports';
  static const String sunatLookup = 'seller/sunat-lookup';

  // ── Mozo Ordering ──
  static const String panelProducts = 'seller/panel/products';
  static const String panelOrderCreate = 'seller/panel/order/create';
  static String panelTableActiveOrder(int id) => 'seller/panel/table/$id/active-order';

  // ── Seller Inventory & Gastronomy ──
  static const String inventoryMetadata = 'seller/inventory/metadata';
  static const String inventoryItems = 'seller/inventory/items';
  static const String inventoryItemStore = 'seller/inventory/items/store';
  static String inventoryItemUpdate(int id) => 'seller/inventory/items/update/$id';
  static String inventoryItemDelete(int id) => 'seller/inventory/items/delete/$id';
  static const String inventoryStockAdjust = 'seller/inventory/stock-adjust';

  static const String inventoryRecipes = 'seller/inventory/recipes';
  static const String inventoryRecipeStore = 'seller/inventory/recipes/store';
  static String inventoryRecipeDelete(int id) => 'seller/inventory/recipes/delete/$id';
  static const String inventoryRecipeProduction = 'seller/inventory/recipes/production';
  static String inventoryRecipeProductionVoid(int id) => 'seller/inventory/recipes/production/void/$id';

  static const String inventoryPurchases = 'seller/inventory/purchases';
  static const String inventoryPurchaseStore = 'seller/inventory/purchases/store';

  static const String inventoryWastes = 'seller/inventory/wastes';
  static const String inventoryWasteStore = 'seller/inventory/wastes/store';

  static const String inventoryKardex = 'seller/inventory/kardex';

  static const String inventorySuppliers = 'seller/inventory/suppliers';
  static const String inventorySupplierStore = 'seller/inventory/suppliers/store';
  static String inventorySupplierDelete(int id) => 'seller/inventory/suppliers/delete/$id';
}

