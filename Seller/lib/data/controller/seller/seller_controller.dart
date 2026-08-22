import 'dart:io';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/helper/string_format_helper.dart';
import 'package:lizto_store/core/route/route.dart';
import 'package:lizto_store/data/controller/seller/seller_notification_service.dart';
import 'package:lizto_store/data/repo/seller/seller_repo.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../model/delivery/delivery_models.dart';

class SellerController extends GetxController {
  SellerRepo sellerRepo;
  SellerController({required this.sellerRepo});

  bool isLoading = false;
  bool isLoggedIn = false;
  Map<String, dynamic>? sellerData;
  List<dynamic> stores = [];
  List<StoreModel> storeModels = [];
  List<ProductModel> products = [];
  List<dynamic> storeCategories = [];
  List<dynamic> menuCategories = [];
  List<dynamic> subCategories = [];
  String menuCategoryImagePath = '';
  String storeImagePath = '';
  String productImagePath = '';

  List<DeliveryOrderModel> orders = [];
  double walletBalance = 0;
  double get receivableBalance {
    final value = sellerData?['receivable_balance'] ?? sellerData?['seller']?['receivable_balance'];
    return value is num ? value.toDouble() : double.tryParse(value?.toString() ?? '') ?? 0;
  }

  bool isLoadingOrders = false;
  bool isLoadingWallet = false;

  String email = '';
  String password = '';
  String? errorMessage;

  // ── Staff / Mozo session ──
  bool isStaff = false;
  int? staffId;
  String? staffName;
  String? staffPosition;
  List<String> staffPermissions = [];

  bool hasPermission(String permission) {
    if (!isStaff) return true;
    return staffPermissions.contains(permission);
  }

  bool get hasSellerSession => sellerRepo.isLoggedIn;

  Future<bool> register(String name, String email, String phone, String password) async {
    isLoading = true;
    errorMessage = null;
    update();

    try {
      var response = await sellerRepo.register(name, email, phone, password);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          sellerRepo.saveToken(json['data']['token']);
          isLoggedIn = true;
          isLoading = false;
          update();
          await loadDashboard();
          _saveDeviceToken();
          if (Get.isRegistered<SellerNotificationService>()) {
            int sellerId = sellerData?['id'] ?? sellerData?['seller']?['id'] ?? 0;
            Get.find<SellerNotificationService>().subscribe(sellerId);
          }
          return true;
        } else {
          errorMessage = json['message']?.toString() ?? 'Error al crear la cuenta';
        }
      } else {
        errorMessage = 'Error del servidor';
      }
    } catch (e) {
      printX(e);
      errorMessage = 'Error de conexión';
    }

    isLoading = false;
    update();
    return false;
  }

  Future<bool> login(String email, String password) async {
    isLoading = true;
    errorMessage = null;
    update();

    try {
      var response = await sellerRepo.login(email, password);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          sellerRepo.saveToken(json['data']['token']);
          isLoggedIn = true;

          // Handle staff/mozo login
          if (json['data']['is_staff'] == true) {
            isStaff = true;
            staffId = json['data']['staff']?['id'];
            staffName = json['data']['staff']?['name'];
            staffPosition = json['data']['staff']?['position'];
            staffPermissions = (json['data']['staff']?['permissions'] as List?)?.map((e) => e.toString()).toList() ?? [];
            _saveStaffSession();
          } else {
            isStaff = false;
            staffId = null;
            staffName = null;
            staffPosition = null;
            staffPermissions = [];
            _clearStaffSession();
          }

          isLoading = false;
          update();
          await loadDashboard();
          _saveDeviceToken();

          if (Get.isRegistered<SellerNotificationService>()) {
            int sellerId = sellerData?['id'] ?? sellerData?['seller']?['id'] ?? 0;
            Get.find<SellerNotificationService>().subscribe(sellerId);
          }

          return true;
        } else {
          errorMessage = json['message']?.toString() ?? 'Error al iniciar sesión';
        }
      } else {
        errorMessage = 'Error del servidor';
      }
    } catch (e) {
      printX(e);
      errorMessage = 'Error de conexión';
    }

    isLoading = false;
    update();
    return false;
  }

  Future<void> loadDashboard() async {
    isLoading = true;
    update();
    try {
      var response = await sellerRepo.dashboard();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          var data = json['data'];
          sellerData = data;
          stores = data['stores'] ?? [];
          storeImagePath = data['store_image_path'] ?? '';
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<void> loadProducts(int storeId) async {
    isLoading = true;
    update();
    try {
      var response = await sellerRepo.getProducts(storeId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          var data = json['data'];
          var rawProducts = data['products'];
          if (rawProducts is Map && rawProducts.containsKey('data')) {
            products = (rawProducts['data'] as List).map((x) => ProductModel.fromJson(x)).toList();
          } else if (rawProducts is List) {
            products = rawProducts.map((x) => ProductModel.fromJson(x)).toList();
          }
          storeCategories = data['store_categories'] ?? [];
          productImagePath = data['product_image_path'] ?? '';
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<bool> createProduct(int storeId, Map<String, dynamic> data, {File? image}) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.createProduct(storeId, data, image: image);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadProducts(storeId);
      } else {
        errorMessage = response.responseJson['message']?.toString() ?? 'Error al crear producto';
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }

  Future<bool> updateProduct(int productId, int storeId, Map<String, dynamic> data, {File? image}) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.updateProduct(productId, data, image: image);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadProducts(storeId);
      } else {
        errorMessage = response.responseJson['message']?.toString() ?? 'Error al actualizar producto';
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }

  Future<bool> deleteProduct(int productId, int storeId) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.deleteProduct(productId);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadProducts(storeId);
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }

  Future<bool> toggleProductStatus(int productId, int storeId) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.toggleProductStatus(productId);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadProducts(storeId);
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }

  void logout() {
    if (Get.isRegistered<SellerNotificationService>()) {
      Get.find<SellerNotificationService>().unsubscribe();
    }
    sellerRepo.removeToken();
    _clearStaffSession();
    isLoggedIn = false;
    isStaff = false;
    staffId = null;
    staffName = null;
    staffPosition = null;
    staffPermissions = [];
    sellerData = null;
    stores = [];
    products = [];
    orders = [];
    walletBalance = 0;
    update();
    Get.offAllNamed(RouteHelper.loginScreen);
  }

  Future<void> _saveDeviceToken() async {
    try {
      final token = await FirebaseMessaging.instance.getToken();
      if (token != null && token.isNotEmpty) {
        await sellerRepo.saveDeviceToken(token);
      }
    } catch (_) {}
  }

  void _saveStaffSession() async {
    final prefs = await SharedPreferences.getInstance();
    prefs.setBool('is_staff', isStaff);
    prefs.setInt('staff_id', staffId ?? 0);
    prefs.setString('staff_name', staffName ?? '');
    prefs.setString('staff_position', staffPosition ?? '');
    prefs.setStringList('staff_permissions', staffPermissions);
  }

  void _clearStaffSession() async {
    final prefs = await SharedPreferences.getInstance();
    prefs.remove('is_staff');
    prefs.remove('staff_id');
    prefs.remove('staff_name');
    prefs.remove('staff_position');
    prefs.remove('staff_permissions');
  }

  void restoreSession() async {
    if (sellerRepo.isLoggedIn && !isLoggedIn) {
      final prefs = await SharedPreferences.getInstance();
      isStaff = prefs.getBool('is_staff') ?? false;
      staffId = prefs.getInt('staff_id');
      staffName = prefs.getString('staff_name');
      staffPosition = prefs.getString('staff_position');
      staffPermissions = prefs.getStringList('staff_permissions') ?? [];
      isLoggedIn = true;
      loadDashboard();
    }
  }

  // ─── Orders ──────────────────────────────────────────────────────────────

  Future<void> loadOrders([String status = '']) async {
    isLoadingOrders = true;
    update();
    try {
      final response = await sellerRepo.getOrders(status);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          var raw = json['data']['orders'];
          if (raw is Map && raw.containsKey('data')) {
            orders = (raw['data'] as List).map((x) => DeliveryOrderModel.fromJson(x)).toList();
          } else if (raw is List) {
            orders = raw.map((x) => DeliveryOrderModel.fromJson(x)).toList();
          }
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoadingOrders = false;
    update();
  }

  Future<bool> updateOrderStatus(int orderId, String status) async {
    try {
      final response = await sellerRepo.updateOrderStatus(orderId, status);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        await loadOrders();
        return true;
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  // ─── Wallet ──────────────────────────────────────────────────────────────

  Future<void> loadWalletBalance() async {
    isLoadingWallet = true;
    update();
    try {
      final response = await sellerRepo.getWalletBalance();
      if (response.statusCode == 200) {
        final json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          final data = json['data'];
          final balance = data['balance'] ?? data['available_balance'] ?? data['amount'] ?? 0;
          walletBalance = (balance is num) ? balance.toDouble() : double.tryParse(balance.toString()) ?? 0;
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoadingWallet = false;
    update();
  }

  // ─── Dashboard computed helpers ──────────────────────────────────────────

  int get pendingOrderCount => orders.where((o) => o.status == 'pending').length;
  int get confirmedOrderCount => orders.where((o) => o.status == 'confirmed').length;
  int get preparingOrderCount => orders.where((o) => o.status == 'preparing').length;
  double get todayTotalSales {
    final today = DateTime.now();
    return orders.where((o) {
      if (o.createdAt == null) return false;
      try {
        final date = DateTime.parse(o.createdAt!);
        return date.year == today.year && date.month == today.month && date.day == today.day;
      } catch (_) {
        return false;
      }
    }).fold(0.0, (sum, o) => sum + (o.total ?? 0));
  }

  // ─── Menu Category CRUD ──────────────────────────────────────────────────

  Future<void> loadMenuCategories(int storeId) async {
    isLoading = true;
    update();
    try {
      final response = await sellerRepo.getMenuCategories(storeId);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        final data = response.responseJson['data'];
        menuCategories = data['categories'] ?? [];
        menuCategoryImagePath = data['image_path'] ?? '';
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<bool> createMenuCategory(int storeId, String name, {File? image}) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.createMenuCategory(storeId, name, image: image);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadMenuCategories(storeId);
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }

  Future<bool> updateMenuCategory(int categoryId, int storeId, String name, {File? image}) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.updateMenuCategory(categoryId, name, image: image);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadMenuCategories(storeId);
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }

  Future<bool> deleteMenuCategory(int categoryId, int storeId) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.deleteMenuCategory(categoryId);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadMenuCategories(storeId);
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }

  Future<void> loadSubCategories() async {
    isLoading = true;
    update();
    try {
      final response = await sellerRepo.getSubCategories();
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        subCategories = response.responseJson['data']['subcategories'] ?? [];
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<bool> createStoreFavor(Map<String, dynamic> data) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.createStoreFavor(data);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
      } else {
        errorMessage = response.responseJson['message']?.toString() ?? 'Error al crear la solicitud';
      }
    } catch (e) {
      printX(e);
      errorMessage = 'Error de conexión';
    }
    isLoading = false;
    update();
    return success;
  }

  Future<Map<String, dynamic>?> estimateStoreFavorFee(Map<String, dynamic> data) async {
    try {
      final response = await sellerRepo.estimateStoreFavorFee(data);
      final body = response.responseJson;
      if (response.statusCode >= 200 && response.statusCode < 300 && body is Map && body['status'] == 'success') {
        final payload = body['data'];
        if (payload is Map && payload['estimate'] is Map) {
          return Map<String, dynamic>.from(payload['estimate']);
        }
      }
      final message = body is Map ? body['message'] : null;
      errorMessage = message is List ? message.map((item) => item.toString()).join('\n') : message?.toString() ?? 'No se pudo calcular la tarifa';
    } catch (e) {
      printX(e);
      errorMessage = 'No se pudo calcular la tarifa';
    }
    return null;
  }

  Future<Map<String, dynamic>?> createStoreFavorAndStartSearch(Map<String, dynamic> data) async {
    errorMessage = null;
    try {
      final response = await sellerRepo.createStoreFavor(data);
      final body = response.responseJson;
      if (response.isSuccess && body is Map && body['status'] == 'success') {
        final payload = body['data'];
        if (payload is Map) return Map<String, dynamic>.from(payload);
        errorMessage = 'El servidor no devolvió los datos de la solicitud';
        return null;
      }
      errorMessage = _apiErrorMessage(body, response.message, 'No se pudo iniciar la búsqueda');
    } catch (e) {
      printX(e);
      errorMessage = 'Error de conexión. Inténtalo nuevamente';
    }
    return null;
  }

  String _apiErrorMessage(dynamic body, String responseMessage, String fallback) {
    final message = body is Map ? body['message'] : null;
    if (message is List && message.isNotEmpty) {
      return message.map((item) => item.toString()).join('\n');
    }
    if (message != null && message.toString().trim().isNotEmpty) {
      return message.toString();
    }
    return responseMessage.trim().isNotEmpty ? responseMessage : fallback;
  }

  Future<Map<String, dynamic>?> getStoreFavorSearchStatus(int favorId) async {
    final response = await sellerRepo.storeFavorSearchStatus(favorId);
    if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
      return Map<String, dynamic>.from(response.responseJson['data'] ?? {});
    }
    errorMessage = response.responseJson?['message']?.toString() ?? 'No se pudo actualizar la búsqueda';
    return null;
  }

  Future<Map<String, dynamic>?> getStoreFavorDetail(int favorId) async {
    final response = await sellerRepo.storeFavorDetail(favorId);
    if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
      return Map<String, dynamic>.from(response.responseJson['data'] ?? {});
    }
    errorMessage = response.responseJson?['message']?.toString() ?? 'No se pudo actualizar el seguimiento';
    return null;
  }

  Future<Map<String, dynamic>?> retryStoreFavorSearch(int favorId) async {
    final response = await sellerRepo.retryStoreFavorSearch(favorId);
    if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
      return Map<String, dynamic>.from(response.responseJson['data'] ?? {});
    }
    errorMessage = response.responseJson?['message']?.toString() ?? 'No se pudo reiniciar la búsqueda';
    return null;
  }

  Future<bool> createStore(Map<String, dynamic> data, {File? image, File? coverImage}) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.createStore(data, image: image, coverImage: coverImage);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadDashboard();
      } else {
        errorMessage = response.responseJson['message']?.toString() ?? 'Error al crear la tienda';
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }

  Future<bool> updateStore(int storeId, Map<String, dynamic> data, {File? image, File? coverImage}) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.updateStore(storeId, data, image: image, coverImage: coverImage);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadDashboard();
      } else {
        errorMessage = response.responseJson['message']?.toString() ?? 'Error al actualizar la tienda';
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }

  Future<bool> deleteStore(int storeId) async {
    isLoading = true;
    update();
    bool success = false;
    try {
      final response = await sellerRepo.deleteStore(storeId);
      if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
        success = true;
        await loadDashboard();
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
    return success;
  }
}
