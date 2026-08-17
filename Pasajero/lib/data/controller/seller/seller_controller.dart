import 'dart:io';
import 'package:get/get.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/data/controller/delivery/seller_notification_service.dart';
import 'package:liztogo/data/repo/seller/seller_repo.dart';

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
  List<dynamic> subCategories = []; // subcategories for stores list
  String menuCategoryImagePath = '';
  String storeImagePath = '';
  String productImagePath = '';

  String email = '';
  String password = '';
  String? errorMessage;

  bool get hasSellerSession => sellerRepo.isLoggedIn;

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
          isLoading = false;
          update();
          await loadDashboard();

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
    sellerRepo.removeToken();
    isLoggedIn = false;
    sellerData = null;
    stores = [];
    products = [];
    update();
  }

  void restoreSession() {
    if (sellerRepo.isLoggedIn && !isLoggedIn) {
      isLoggedIn = true;
      loadDashboard();
    }
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
