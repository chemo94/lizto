import 'dart:convert';
import 'dart:math';
import 'dart:ui';
import 'package:geolocator/geolocator.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/helper/string_format_helper.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_models.dart';
import 'package:lizto_delivery/data/model/global/response_model/response_model.dart';
import 'package:lizto_delivery/data/repo/delivery/delivery_repo.dart';
import 'package:lizto_delivery/data/services/delivery_cache_service.dart';
import 'package:lizto_delivery/data/services/pusher_service.dart';
import 'package:geocoding/geocoding.dart';
import 'package:lizto_delivery/data/repo/location/location_search_repo.dart';
import 'package:lizto_delivery/presentation/components/snack_bar/show_custom_snackbar.dart';

import '../../../core/utils/my_color.dart';
import '../../services/api_client.dart';

class DeliveryController extends GetxController {
  DeliveryRepo deliveryRepo;
  DeliveryController({required this.deliveryRepo});

  @override
  void onInit() {
    super.onInit();
    loadCartFromLocal();
    loadSelectedLocationFromLocal();
  }

  void loadSelectedLocationFromLocal() {
    try {
      final prefs = deliveryRepo.apiClient.sharedPreferences;
      final savedAddr = prefs.getString('delivery_user_selected_address');
      final savedLat = prefs.getDouble('delivery_user_selected_lat');
      final savedLng = prefs.getDouble('delivery_user_selected_lng');
      if (savedAddr != null && savedAddr.isNotEmpty) {
        currentDeliveryAddress = savedAddr;
      }
      if (savedLat != null && savedLng != null) {
        userLat = savedLat;
        userLng = savedLng;
      }
    } catch (e) {
      printX('Error loading selected location: $e');
    }
  }

  // Catalog state
  List<GeneralCategoryModel> generalCategories = [];
  List<SubCategoryModel> subCategories = [];
  List<StoreModel> stores = [];
  List<StoreModel> nearbyStores = [];
  List<dynamic> premiumSections = [];
  List<dynamic> stories = [];
  String storyBasePath = '';
  bool isLoadingStories = false;
  StoreModel? selectedStore;
  List<StoreCategoryModel> storeCategories = [];
  String categoryImagePath = '';
  String subCategoryImagePath = '';
  String storeImagePath = '';
  String storeCoverPath = '';
  String productImagePath = '';
  String? paymentRedirectUrl;
  Map<String, dynamic>? mpCheckoutData;
  int? pendingOrderId;

  bool isLoading = false;
  bool isLoadingMore = false;
  final int _currentPage = 1;
  final int _lastPage = 1;
  bool get hasMorePages => _currentPage < _lastPage;

  double? userLat;
  double? userLng;
  String currentDeliveryAddress = 'Ubicación actual detectada';
  String deliverySearchQuery = '';

  // ── Service Home (per-category screen) state ──
  List<dynamic> categoryHomeSections = [];
  List<SubCategoryModel> categoryHomeSubCategories = [];
  List<StoreModel> categoryHomeStores = [];
  bool categoryHomeLoading = false;
  String categoryHomeSearchQuery = '';
  String categoryHomeStoreImagePath = '';
  String categoryHomeSubCategoryImagePath = '';
  String categoryHomeProductImagePath = '';

  // Cart state
  List<CartItemModel> cartItems = [];

  // Order state
  List<DeliveryOrderModel> orders = [];
  DeliveryOrderModel? selectedOrder;
  String orderProductImagePath = '';
  String orderStoreImagePath = '';
  String orderDriverImagePath = '';

  // Gateways
  List<GatewayModel> gateways = [];
  String gatewayImagePath = '';

  // Favorites
  List<StoreModel> favoriteStores = [];
  final Set<int> _favoriteStoreIds = {};
  bool isLoadingFavorites = false;
  GatewayModel? selectedGateway;

  // Refunds
  List<RefundModel> refunds = [];
  bool loadingRefunds = false;

  // Payment history
  List<DeliveryPaymentModel> deliveryPayments = [];
  bool loadingPayments = false;

  double couponDiscount = 0;

  int get cartCount => cartItems.fold(0, (sum, item) => sum + item.quantity);
  double get cartSubtotal => cartItems.fold(0.0, (sum, item) => sum + item.totalPrice);
  bool get hasItemsInCart => cartItems.isNotEmpty;
  bool get checkoutLoading => _checkoutLoading;
  bool _checkoutLoading = false;
  double estimatedDeliveryFee = 0;
  double? estimatedDeliveryDistance;
  bool deliveryFeeLoading = false;
  String? deliveryCoverageError;

  // ── Coverage Zone ──
  bool isInCoverageZone = true;
  bool coverageLoading = false;
  String? coverageMessage;

  // ── Catalog ──

  Future<void> loadGeneralCategories() async {
    isLoading = true;
    update();
    try {
      await detectUserLocation();
      ResponseModel response = await deliveryRepo.getGeneralCategories(lat: userLat, lng: userLng);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var data = json['data'];
          generalCategories = (data['general_categories'] as List).map((x) => GeneralCategoryModel.fromJson(x)).toList();
          categoryImagePath = data['general_category_image_path'] ?? '';
          premiumSections = data['sections'] ?? [];
          storeImagePath = data['store_image_path'] ?? storeImagePath;
          storeCoverPath = data['store_cover_path'] ?? storeCoverPath;
          productImagePath = data['product_image_path'] ?? productImagePath;
        }
      }
      loadNearbyStores();
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<void> loadStories() async {
    isLoadingStories = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.getStories();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var data = json['data'];
          stories = data['stores'] ?? [];
          storyBasePath = data['story_base_path'] ?? '';
        }
      }
    } catch (e) {
      printX('Error loading stories: $e');
    }
    isLoadingStories = false;
    update();
  }

  // ── Service Home (per category) ──

  /// Loads the complete home page for a specific service (general category).
  /// Tries the dedicated `/home` endpoint first; falls back to combining
  /// subcategories + nearby stores filtered by subcategory IDs.
  Future<void> loadCategoryHome(int categoryId, {bool top = false, bool fast = false, bool highRating = false, String sort = 'relevance'}) async {
    categoryHomeLoading = true;
    categoryHomeSections = [];
    categoryHomeSubCategories = [];
    categoryHomeStores = [];
    categoryHomeSearchQuery = '';
    update();

    if (userLat == null || userLng == null) {
      await detectUserLocation();
    }

    try {
      // 1. Try the dedicated /home endpoint
      ResponseModel response = await deliveryRepo.getCategoryHome(
        categoryId,
        lat: userLat,
        lng: userLng,
        top: top,
        fast: fast,
        highRating: highRating,
        sort: sort,
      );

      if (response.statusCode == 200) {
        final json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final data = json['data'];
          categoryHomeSections = data['sections'] ?? [];
          categoryHomeSubCategories = data['sub_categories'] != null ? (data['sub_categories'] as List).map((x) => SubCategoryModel.fromJson(x)).toList() : [];
          categoryHomeStoreImagePath = data['store_image_path'] ?? storeImagePath;
          categoryHomeSubCategoryImagePath = data['sub_category_image_path'] ?? subCategoryImagePath;
          categoryHomeProductImagePath = data['product_image_path'] ?? productImagePath;

          // Also grab flat store list if backend provides one
          if (data['stores'] != null) {
            final raw = data['stores'];
            if (raw is Map && raw.containsKey('data')) {
              categoryHomeStores = (raw['data'] as List).map((x) => StoreModel.fromJson(x)).toList();
            } else if (raw is List) {
              categoryHomeStores = raw.map((x) => StoreModel.fromJson(x)).toList();
            }
          }
          if (categoryHomeSections.isNotEmpty) {
            categoryHomeLoading = false;
            update();
            return;
          } else if (categoryHomeStores.isNotEmpty) {
            _buildFallbackSections(categoryHomeStores);
            categoryHomeLoading = false;
            update();
            return;
          }
        }
      }
    } catch (e) {
      printX('loadCategoryHome /home endpoint error: $e');
    }

    // 2. Fallback: load subcategories + build sections locally
    try {
      ResponseModel subResp = await deliveryRepo.getSubCategories(categoryId);
      if (subResp.statusCode == 200) {
        final json = subResp.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final data = json['data'];
          categoryHomeSubCategories = (data['sub_categories'] as List).map((x) => SubCategoryModel.fromJson(x)).toList();
          categoryHomeSubCategoryImagePath = data['sub_category_image_path'] ?? subCategoryImagePath;
        }
      }

      // Load nearby stores as the "all stores" section
      ResponseModel nearbyResp = await deliveryRepo.getNearbyStores(lat: userLat, lng: userLng, perPage: 60);
      if (nearbyResp.statusCode == 200) {
        final json = nearbyResp.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final raw = json['data']['stores'];
          List<StoreModel> allNearby = [];
          if (raw is Map && raw.containsKey('data')) {
            allNearby = (raw['data'] as List).map((x) => StoreModel.fromJson(x)).toList();
          } else if (raw is List) {
            allNearby = raw.map((x) => StoreModel.fromJson(x)).toList();
          }
          categoryHomeStoreImagePath = json['data']['store_image_path'] ?? categoryHomeStoreImagePath;

          // Filter stores to only those that belong to the subcategories of this general category
          final subCategoryIds = categoryHomeSubCategories.map((s) => s.id).toSet();
          final filteredStores = allNearby.where((store) => subCategoryIds.contains(store.subCategoryId)).toList();

          categoryHomeStores = filteredStores;

          // Build synthetic premium sections from the filtered store list
          if (filteredStores.isNotEmpty) {
            _buildFallbackSections(filteredStores);
          }
        }
      }
    } catch (e) {
      printX('loadCategoryHome fallback error: $e');
    }

    categoryHomeLoading = false;
    update();
  }

  /// Builds local premium sections (top nearby, best rated, etc.) from a store list.
  void _buildFallbackSections(List<StoreModel> stores) {
    final storeJsonList = stores
        .map((s) => {
              'id': s.id,
              'name': s.name,
              'image': s.image,
              'cover_image': s.coverImage,
              'description': s.description,
              'address': s.address,
              'delivery_fee': s.deliveryFee,
              'min_order_amount': s.minOrderAmount,
              'is_open': s.isOpenNow,
              'preparation_time': s.preparationTime,
              'distance': s.distance,
              'latitude': s.latitude,
              'longitude': s.longitude,
            })
        .toList();

    // Sort copies for each section
    final byDistance = List<Map<String, dynamic>>.from(storeJsonList)..sort((a, b) => ((a['distance'] as double?) ?? 999).compareTo((b['distance'] as double?) ?? 999));

    categoryHomeSections = [
      if (byDistance.isNotEmpty) {'key': 'nearby', 'title': 'Más cerca de ti', 'subtitle': 'Comercios ordenados por distancia', 'type': 'store', 'data': byDistance.take(12).toList()},
    ];
  }

  /// Search stores within a specific service category.
  Future<void> searchWithinCategory(int categoryId, String query, {bool top = false, bool fast = false, bool highRating = false, String sort = 'relevance'}) async {
    categoryHomeSearchQuery = query;
    update();
    if (query.trim().isEmpty) return;
    try {
      final resp = await deliveryRepo.searchCategoryStores(categoryId, query, lat: userLat, lng: userLng, top: top, fast: fast, highRating: highRating, sort: sort);
      if (resp.statusCode == 200) {
        final json = resp.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final raw = json['data']['stores'];
          if (raw is Map && raw.containsKey('data')) {
            categoryHomeStores = (raw['data'] as List).map((x) => StoreModel.fromJson(x)).toList();
          } else if (raw is List) {
            categoryHomeStores = raw.map((x) => StoreModel.fromJson(x)).toList();
          }
          update();
        }
      }
    } catch (e) {
      printX('searchWithinCategory error: $e');
    }
  }

  Future<void> loadSubCategories(int categoryId) async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.getSubCategories(categoryId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var data = json['data'];
          subCategories = (data['sub_categories'] as List).map((x) => SubCategoryModel.fromJson(x)).toList();
          subCategoryImagePath = data['sub_category_image_path'] ?? '';
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<void> loadStores(int subCategoryId) async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.getStores(subCategoryId, lat: userLat, lng: userLng);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var data = json['data'];
          var rawStores = data['stores'];
          if (rawStores is Map && rawStores.containsKey('data')) {
            stores = (rawStores['data'] as List).map((x) => StoreModel.fromJson(x)).toList();
          } else if (rawStores is List) {
            stores = rawStores.map((x) => StoreModel.fromJson(x)).toList();
          }
          storeImagePath = data['store_image_path'] ?? '';
          storeCoverPath = data['store_cover_path'] ?? storeCoverPath;
          _calculateDistances();
          DeliveryCacheService.cacheStores(stores);
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<void> loadNearbyStores({String? query}) async {
    if (query != null) deliverySearchQuery = query;
    try {
      ResponseModel response = await deliveryRepo.getNearbyStores(
        lat: userLat,
        lng: userLng,
        query: deliverySearchQuery,
      );
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['stores'];
          if (raw is Map && raw.containsKey('data')) {
            nearbyStores = (raw['data'] as List).map((x) => StoreModel.fromJson(x)).toList();
          } else if (raw is List) {
            nearbyStores = raw.map((x) => StoreModel.fromJson(x)).toList();
          }
          storeImagePath = json['data']['store_image_path'] ?? storeImagePath;
          storeCoverPath = json['data']['store_cover_path'] ?? storeCoverPath;
          nearbyStores.sort((a, b) {
            if (a.isFeatured == true && b.isFeatured != true) return -1;
            if (a.isFeatured != true && b.isFeatured == true) return 1;
            if (a.isPremium == true && b.isPremium != true) return -1;
            if (a.isPremium != true && b.isPremium == true) return 1;
            return (a.distance ?? 999).compareTo(b.distance ?? 999);
          });
          _calculateNearbyDistances();
          update();
        }
      }
    } catch (e) {
      printX(e);
    }
  }

  /// Checks if the current user location is within the delivery coverage zone.
  Future<void> checkCoverageZone() async {
    if (userLat == null || userLng == null) return;
    coverageLoading = true;
    coverageMessage = null;
    update();

    try {
      if (nearbyStores.isNotEmpty) {
        final store = nearbyStores.first;
        if (store.id != null && store.latitude != null && store.longitude != null) {
          ResponseModel response = await deliveryRepo.estimateDeliveryFee(
            storeId: store.id!,
            deliveryLat: userLat!,
            deliveryLng: userLng!,
          );
          if (response.statusCode == 200) {
            var json = response.responseJson;
            if (json['status'] == MyStrings.success && json['data'] != null) {
              final estimate = json['data']['estimate'];
              if (estimate is Map && estimate['in_coverage'] == false) {
                isInCoverageZone = false;
                coverageMessage = 'Tu ubicación está fuera de la zona de cobertura';
              } else {
                isInCoverageZone = true;
                coverageMessage = null;
              }
              coverageLoading = false;
              update();
              return;
            }
          }
        }
      }

      if (nearbyStores.isEmpty && !isLoading) {
        isInCoverageZone = false;
        coverageMessage = 'No hay tiendas disponibles en tu zona';
      } else {
        isInCoverageZone = true;
        coverageMessage = null;
      }
    } catch (e) {
      printX('checkCoverageZone error: $e');
      isInCoverageZone = true;
      coverageMessage = null;
    }
    coverageLoading = false;
    update();
  }

  void setUserLocation(double lat, double lng) {
    userLat = lat;
    userLng = lng;
  }

  void setDeliveryAddress(String address, {double? lat, double? lng}) {
    if (address.trim().isNotEmpty) {
      currentDeliveryAddress = address.trim();
      try {
        final prefs = deliveryRepo.apiClient.sharedPreferences;
        prefs.setString('delivery_user_selected_address', address.trim());
      } catch (_) {}
    }
    if (lat != null && lng != null) {
      setUserLocation(lat, lng);
      try {
        final prefs = deliveryRepo.apiClient.sharedPreferences;
        prefs.setDouble('delivery_user_selected_lat', lat);
        prefs.setDouble('delivery_user_selected_lng', lng);
      } catch (_) {}
    }
    update();
  }

  Future<void> searchNearbyStores(String value) async {
    deliverySearchQuery = value.trim();
    update();
    await loadNearbyStores(query: deliverySearchQuery);
  }

  Future<void> detectUserLocation({bool useSavedLocation = true}) async {
    if (useSavedLocation) {
      try {
        final prefs = deliveryRepo.apiClient.sharedPreferences;
        final savedLat = prefs.getDouble('delivery_user_selected_lat');
        final savedLng = prefs.getDouble('delivery_user_selected_lng');
        if (savedLat != null && savedLng != null) {
          userLat = savedLat;
          userLng = savedLng;
          final savedAddr = prefs.getString('delivery_user_selected_address');
          if (savedAddr != null && savedAddr.isNotEmpty) {
            currentDeliveryAddress = savedAddr;
          }
          return;
        }
      } catch (_) {}
    }

    try {
      bool serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) return;
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) return;
      }
      if (permission == LocationPermission.deniedForever) return;
      Position position = await Geolocator.getCurrentPosition(
        // ignore: deprecated_member_use
        desiredAccuracy: LocationAccuracy.high,
      );
      setUserLocation(position.latitude, position.longitude);

      try {
        final searchRepo = Get.find<LocationSearchRepo>();
        final address = await searchRepo.getActualAddress(position.latitude, position.longitude);
        if (address != null && address.trim().isNotEmpty) {
          setDeliveryAddress(address.trim(), lat: position.latitude, lng: position.longitude);
        } else {
          final placemarks = await placemarkFromCoordinates(position.latitude, position.longitude);
          if (placemarks.isNotEmpty) {
            final placemark = placemarks.first;
            final streetFull = (placemark.subThoroughfare != null && placemark.subThoroughfare!.isNotEmpty && !(placemark.street ?? '').contains(placemark.subThoroughfare!)) ? '${placemark.street ?? ''} ${placemark.subThoroughfare}'.trim() : (placemark.street ?? '');
            final resolvedAddress = [streetFull, placemark.subLocality, placemark.locality, placemark.country].where((p) => p != null && p.isNotEmpty).join(', ');
            setDeliveryAddress(resolvedAddress, lat: position.latitude, lng: position.longitude);
          }
        }
      } catch (e) {
        printX("Error reverse-geocoding in detectUserLocation: $e");
        setDeliveryAddress(currentDeliveryAddress, lat: position.latitude, lng: position.longitude);
      }
    } catch (_) {}
  }

  void _calculateDistances() {
    if (userLat == null || userLng == null) return;
    for (var store in stores) {
      if (store.distance == null && store.latitude != null && store.longitude != null) {
        store.distance = _haversineDistance(userLat!, userLng!, store.latitude!, store.longitude!);
      }
    }
    stores.sort((a, b) {
      double da = a.distance ?? double.infinity;
      double db = b.distance ?? double.infinity;
      return da.compareTo(db);
    });
  }

  void _calculateNearbyDistances() {
    if (userLat == null || userLng == null) return;
    for (var store in nearbyStores) {
      if (store.distance == null && store.latitude != null && store.longitude != null) {
        store.distance = _haversineDistance(userLat!, userLng!, store.latitude!, store.longitude!);
      }
    }
    nearbyStores.sort((a, b) {
      double da = a.distance ?? double.infinity;
      double db = b.distance ?? double.infinity;
      return da.compareTo(db);
    });
  }

  double _haversineDistance(double lat1, double lon1, double lat2, double lon2) {
    const R = 6371.0;
    double dLat = _toRadians(lat2 - lat1);
    double dLon = _toRadians(lon2 - lon1);
    double a = sin(dLat / 2) * sin(dLat / 2) + cos(_toRadians(lat1)) * cos(_toRadians(lat2)) * sin(dLon / 2) * sin(dLon / 2);
    double c = 2 * atan2(sqrt(a), sqrt(1 - a));
    return R * c;
  }

  double _toRadians(double degree) => degree * 3.14159265359 / 180;

  Future<void> loadStoreDetail(int storeId) async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.getStoreDetail(storeId, lat: userLat, lng: userLng);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var data = json['data'];
          selectedStore = StoreModel.fromJson(data['store']);
          storeCategories = (data['store']['categories'] as List).map((x) => StoreCategoryModel.fromJson(x)).toList();
          storeImagePath = data['store_image_path'] ?? '';
          productImagePath = data['product_image_path'] ?? '';
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  // ── Cart ──

  void saveCartToLocal() {
    try {
      final prefs = deliveryRepo.apiClient.sharedPreferences;
      final jsonString = jsonEncode(cartItems.map((item) => item.toJson()).toList());
      prefs.setString('delivery_cart_items', jsonString);
    } catch (e) {
      printX('Error saving cart: $e');
    }
  }

  void loadCartFromLocal() {
    try {
      final prefs = deliveryRepo.apiClient.sharedPreferences;
      final jsonString = prefs.getString('delivery_cart_items');
      if (jsonString != null && jsonString.isNotEmpty) {
        final List decoded = jsonDecode(jsonString);
        cartItems = decoded.map((item) => CartItemModel.fromJson(item)).toList();
        update();
      }
    } catch (e) {
      printX('Error loading cart: $e');
    }
  }

  void addToCart(ProductModel product, {StoreModel? store}) {
    addToCartWithOptions(product, store: store);
  }

  void addToCartWithOptions(ProductModel product, {ProductVariationModel? variation, List<ProductAddonModel> addons = const [], StoreModel? store}) {
    int index = _findCartItemIndex(product.id, variation: variation, addons: addons);
    if (index >= 0) {
      cartItems[index].quantity++;
    } else {
      cartItems.add(CartItemModel(product: product, selectedVariation: variation, selectedAddons: addons, store: store));
    }
    update();
    saveCartToLocal();

    // Surfacing feedback to the user
    CustomSnackBar.success(successList: ['Producto agregado al carrito']);
  }

  bool _addonListsEqual(List<ProductAddonModel> a, List<ProductAddonModel> b) {
    if (a.length != b.length) return false;
    var aIds = a.map((x) => x.id).toSet();
    var bIds = b.map((x) => x.id).toSet();
    return aIds.length == bIds.length && aIds.containsAll(bIds);
  }

  int _findCartItemIndex(int? productId, {ProductVariationModel? variation, List<ProductAddonModel> addons = const []}) {
    return cartItems.indexWhere((item) => item.product.id == productId && item.selectedVariation?.id == variation?.id && _addonListsEqual(item.selectedAddons, addons));
  }

  void increaseCartItem(CartItemModel item) {
    addToCartWithOptions(
      item.product,
      variation: item.selectedVariation,
      addons: List<ProductAddonModel>.from(item.selectedAddons),
      store: item.store,
    );
  }

  void decreaseCartItem(CartItemModel item) {
    int index = _findCartItemIndex(
      item.product.id,
      variation: item.selectedVariation,
      addons: item.selectedAddons,
    );
    if (index >= 0) {
      if (cartItems[index].quantity > 1) {
        cartItems[index].quantity--;
      } else {
        cartItems.removeAt(index);
      }
      update();
      saveCartToLocal();
    }
  }

  List<CartItemModel> cartItemsForProduct(int productId) {
    return cartItems.where((item) => item.product.id == productId).toList();
  }

  void removeFromCart(int productId) {
    cartItems.removeWhere((item) => item.product.id == productId);
    update();
    saveCartToLocal();
  }

  void decreaseQuantity(int productId) {
    int index = cartItems.indexWhere((item) => item.product.id == productId);
    if (index >= 0) {
      if (cartItems[index].quantity > 1) {
        cartItems[index].quantity--;
      } else {
        cartItems.removeAt(index);
      }
      update();
      saveCartToLocal();
    }
  }

  int cartQuantity(int productId) {
    return cartItems.fold(0, (sum, item) => item.product.id == productId ? sum + item.quantity : sum);
  }

  void clearCart() {
    cartItems.clear();
    update();
    saveCartToLocal();
  }

  // ── Orders ──

  // Returns null on success, or an error message string on failure.
  Future<String?> createOrder({
    required int storeId,
    required String deliveryAddress,
    double? deliveryLat,
    double? deliveryLng,
    required String contactPhone,
    required String contactName,
    String? notes,
    double? tip,
    int? gatewayCode,
    double? cashPayAmount,
    String? couponCode,
    String? scheduledTime,
  }) async {
    _checkoutLoading = true;
    update();

    try {
      var items = cartItems.map((item) => item.toOrderJson()).toList();

      var data = {
        'store_id': storeId,
        'items': items,
        'delivery_address': deliveryAddress,
        'contact_phone': contactPhone,
        'contact_name': contactName,
        if (deliveryLat != null) 'delivery_lat': deliveryLat,
        if (deliveryLng != null) 'delivery_lng': deliveryLng,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
        if (tip != null && tip > 0) 'tip': tip,
        if (gatewayCode != null) 'payment_method_code': gatewayCode,
        if (cashPayAmount != null) 'cash_pay_amount': cashPayAmount,
        if (couponCode != null && couponCode.isNotEmpty) 'coupon_code': couponCode,
        if (scheduledTime != null) 'scheduled_time': scheduledTime,
      };

      printX('createOrder payload: $data');

      ResponseModel response = await deliveryRepo.createOrder(data);

      printX('createOrder response [${response.statusCode}]: ${response.responseJson}');

      if (response.statusCode == 200) {
        var json = response.responseJson;

        final remark = json['remark']?.toString();
        final hasData = json['data'] != null;
        final hasRedirect = hasData && json['data'] is Map ? json['data']['redirect_url'] : null;
        final hasCheckoutApi = hasData && json['data'] is Map ? json['data']['checkout_api'] : null;
        final dataKeys = hasData && json['data'] is Map ? (json['data'] as Map).keys.join(', ') : 'N/A';
        printX('MercadoPago check: statusCode=${response.statusCode}, remark=$remark, hasData=$hasData, dataKeys=[$dataKeys], hasRedirect=$hasRedirect, hasCheckoutApi=$hasCheckoutApi');

        if (hasCheckoutApi == true || hasCheckoutApi?.toString() == 'true') {
          mpCheckoutData = Map<String, dynamic>.from(json['data']);
          pendingOrderId = hasData && json['data'] is Map ? (json['data'] as Map)['order_id'] : null;
          _checkoutLoading = false;
          update();
          return null;
        }

        if (hasRedirect != null && hasRedirect.toString().isNotEmpty) {
          paymentRedirectUrl = hasRedirect.toString();
          pendingOrderId = hasData && json['data'] is Map ? (json['data'] as Map)['order_id'] : null;
          printX('MercadoPago redirect URL found: $paymentRedirectUrl, orderId=$pendingOrderId');
          _checkoutLoading = false;
          update();
          return null;
        }

        if (json['status'] == MyStrings.success) {
          clearCart();
          _checkoutLoading = false;
          update();
          return null;
        }
        // Server returned 200 but status != success
        final msg = (json['message']?.toString() ?? json['error']?.toString() ?? json['msg']?.toString());
        _checkoutLoading = false;
        update();
        return (msg != null && msg.isNotEmpty) ? msg : 'No se pudo crear el pedido';
      } else {
        // Non-200 response — check if the order was actually created despite the error.
        // This happens when the server returns 500 due to a missing queue `jobs` table
        // (broadcast dispatch fails) but the order row itself was already persisted.
        String? serverMsg;
        bool orderCreatedDespiteError = false;
        try {
          final json = response.responseJson;
          if (json is Map) {
            serverMsg = json['message']?.toString() ?? json['error']?.toString() ?? json['msg']?.toString();
            // Detect the known "jobs table missing" pattern — order was saved, queue dispatch failed.
            if (serverMsg != null && (serverMsg.contains("jobs") && serverMsg.contains("doesn't exist") || serverMsg.contains("Table") && serverMsg.contains("jobs"))) {
              orderCreatedDespiteError = true;
            }
          }
        } catch (_) {}

        if (orderCreatedDespiteError) {
          // The order was created on the server; the 500 is from a backend queue misconfiguration.
          clearCart();
          _checkoutLoading = false;
          update();
          return null; // treat as success
        }

        _checkoutLoading = false;
        update();
        return serverMsg?.isNotEmpty == true ? serverMsg! : 'Error ${response.statusCode}: No se pudo crear el pedido';
      }
    } catch (e) {
      printX('createOrder exception: $e');
      _checkoutLoading = false;
      update();
      return 'Error inesperado al crear el pedido';
    }
  }

  Future<String?> payPendingOrder(int orderId, int gatewayCode) async {
    _checkoutLoading = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.payOrder(orderId, gatewayCode);
      printX('payOrder response [${response.statusCode}]: ${response.responseJson}');
      if (response.statusCode == 200) {
        var json = response.responseJson;
        final hasData = json['data'] != null;
        final hasRedirect = hasData && json['data'] is Map ? (json['data'] as Map)['redirect_url'] : null;
        final hasCheckoutApi = hasData && json['data'] is Map ? (json['data'] as Map)['checkout_api'] : null;
        printX('payOrder MercadoPago check: remark=${json['remark']}, hasRedirect=$hasRedirect, hasCheckoutApi=$hasCheckoutApi');
        if (hasCheckoutApi == true || hasCheckoutApi?.toString() == 'true') {
          mpCheckoutData = Map<String, dynamic>.from(json['data']);
          pendingOrderId = orderId;
          _checkoutLoading = false;
          update();
          return null;
        }
        if (hasRedirect != null && hasRedirect.toString().isNotEmpty) {
          paymentRedirectUrl = hasRedirect.toString();
          pendingOrderId = orderId;
          _checkoutLoading = false;
          update();
          return null;
        }
        if (json['status'] == MyStrings.success) {
          _checkoutLoading = false;
          update();
          return null;
        }
      }
      _checkoutLoading = false;
      update();
      return 'No se pudo iniciar el pago';
    } catch (e) {
      printX('payOrder exception: $e');
      _checkoutLoading = false;
      update();
      return 'Error al iniciar el pago';
    }
  }

  Future<void> deletePendingOrder(int orderId) async {
    try {
      await deliveryRepo.deletePendingOrder(orderId);
    } catch (e) {
      printX('deletePendingOrder exception: $e');
    }
  }

  Future<String?> submitMercadoPagoPayment({
    required int orderId,
    required String cardToken,
    required int installments,
    required String paymentMethodId,
    required String? issuerId,
    required String payerEmail,
    required String? docType,
    required String? docNumber,
  }) async {
    _checkoutLoading = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.processMercadoPagoPayment(
        orderId: orderId,
        cardToken: cardToken,
        installments: installments,
        paymentMethodId: paymentMethodId,
        issuerId: issuerId,
        payerEmail: payerEmail,
        docType: docType,
        docNumber: docNumber,
      );

      printX('processMercadoPagoPayment response [${response.statusCode}]: ${response.responseJson}');

      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          clearCart();
          _checkoutLoading = false;
          update();
          return null;
        }
        final msg = (json['message']?.toString() ?? json['error']?.toString() ?? json['msg']?.toString());
        _checkoutLoading = false;
        update();
        return (msg != null && msg.isNotEmpty) ? msg : 'Error al procesar el pago';
      } else {
        final json = response.responseJson;
        String? serverMsg;
        if (json is Map) {
          serverMsg = json['message']?.toString() ?? json['error']?.toString() ?? json['msg']?.toString();
        }
        _checkoutLoading = false;
        update();
        return serverMsg ?? 'Pago rechazado o fallido';
      }
    } catch (e) {
      printX('processMercadoPagoPayment exception: $e');
      _checkoutLoading = false;
      update();
      return 'Error de red al procesar el pago';
    }
  }

  Future<void> loadOrders() async {
    subscribeToOrderChannel();
    // Load from cache first for instant display
    var cached = await DeliveryCacheService.getCachedOrders();
    if (cached.isNotEmpty && orders.isEmpty) {
      orders = cached;
      update();
    }
    isLoading = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.getOrders();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['orders'];
          if (raw is Map && raw.containsKey('data')) {
            orders = (raw['data'] as List).map((x) => DeliveryOrderModel.fromJson(x)).toList();
          } else if (raw is List) {
            orders = raw.map((x) => DeliveryOrderModel.fromJson(x)).toList();
          }
          orderProductImagePath = json['data']['product_image_path'] ?? '';
          orderStoreImagePath = json['data']['store_image_path'] ?? '';
          DeliveryCacheService.cacheOrders(orders);
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<void> loadOrderDetail(int orderId) async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.getOrderDetail(orderId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          selectedOrder = DeliveryOrderModel.fromJson(json['data']['order']);
          orderProductImagePath = json['data']['product_image_path'] ?? '';
          orderStoreImagePath = json['data']['store_image_path'] ?? '';
          orderDriverImagePath = json['data']['driver_image_path'] ?? '';
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<bool> cancelOrder(int orderId, {String? reason}) async {
    try {
      ResponseModel response = await deliveryRepo.cancelOrder(orderId, reason: reason);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          await loadOrders();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  /// Local payment methods always offered alongside server gateways.
  static const List<Map<String, dynamic>> _localGateways = [
    {'id': 9001, 'code': 9001, 'name': 'Efectivo', 'currency': 'PEN', 'symbol': 'S/', 'is_cash': true},
    {'id': 9002, 'code': 119, 'name': 'Tarjeta de débito / crédito', 'currency': 'MercadoPago', 'symbol': 'S/', 'is_cash': false, 'percent_charge': 4.15, 'fixed_charge': 1.20},
  ];

  Future<void> loadGateways() async {
    try {
      ResponseModel response = await deliveryRepo.getGateways();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          gateways = (json['data']['gateways'] as List).map((x) => GatewayModel.fromJson(x)).toList();
          gatewayImagePath = json['data']['gateway_image_path'] ?? '';
        }
      }
    } catch (e) {
      printX(e);
    }

    // Always ensure Efectivo and MercadoPago are available, but avoid duplicates
    final hasCashGateway = gateways.any((g) => g.isCash == true);
    final existingCodes = gateways.map((g) => g.code).toSet();
    for (final local in _localGateways) {
      final isCash = local['is_cash'] == true;
      if (isCash && hasCashGateway) continue;
      if (!isCash && existingCodes.contains(local['code'])) continue;
      gateways.insert(0, GatewayModel.fromJson(local));
    }

    update();
  }

  Future<void> estimateDeliveryFee({
    required int storeId,
    required double deliveryLat,
    required double deliveryLng,
  }) async {
    deliveryFeeLoading = true;
    deliveryCoverageError = null;
    update();
    try {
      ResponseModel response = await deliveryRepo.estimateDeliveryFee(
        storeId: storeId,
        deliveryLat: deliveryLat,
        deliveryLng: deliveryLng,
      );
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final estimate = json['data']['estimate'];
          estimatedDeliveryFee = (estimate['delivery_fee'] is num) ? (estimate['delivery_fee'] as num).toDouble() : double.tryParse(estimate['delivery_fee']?.toString() ?? '0') ?? 0;
          estimatedDeliveryDistance = (estimate['distance_km'] is num) ? (estimate['distance_km'] as num).toDouble() : double.tryParse(estimate['distance_km']?.toString() ?? '');
          if (estimate['in_coverage'] == false) {
            deliveryCoverageError = 'Dirección fuera de cobertura';
          }
        } else {
          deliveryCoverageError = json['message']?.toString() ?? 'No se pudo calcular el delivery';
        }
      }
    } catch (e) {
      deliveryCoverageError = 'No se pudo calcular el delivery';
      printX(e);
    }
    deliveryFeeLoading = false;
    update();
  }

  Future<bool> addTip(int orderId, double tip) async {
    try {
      ResponseModel response = await deliveryRepo.addTip(orderId, tip);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['data'] != null && json['data']['redirect_url'] != null) {
          paymentRedirectUrl = json['data']['redirect_url']?.toString();
          _checkoutLoading = false;
          update();
          return false;
        }
        if (json['status'] == MyStrings.success) {
          selectedOrder = DeliveryOrderModel.fromJson(json['data']['order']);
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  Future<bool> requestRefund(int orderId, String reason) async {
    try {
      ResponseModel response = await deliveryRepo.requestRefund(orderId, reason);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['data'] != null && json['data']['redirect_url'] != null) {
          paymentRedirectUrl = json['data']['redirect_url']?.toString();
          _checkoutLoading = false;
          update();
          return false;
        }
        if (json['status'] == MyStrings.success) {
          Get.snackbar('Solicitud enviada', 'Tu solicitud de reembolso ha sido registrada.', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  Future<bool> reportOrderProblem(int orderId, String subject, String description) async {
    try {
      ResponseModel response = await deliveryRepo.reportOrderProblem(orderId, subject, description);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['data'] != null && json['data']['redirect_url'] != null) {
          paymentRedirectUrl = json['data']['redirect_url']?.toString();
          _checkoutLoading = false;
          update();
          return false;
        }
        if (json['status'] == MyStrings.success) {
          Get.snackbar('Reporte enviado', 'Tu reporte ha sido registrado.', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    Get.snackbar('Error', 'No se pudo enviar el reporte', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
    return false;
  }

  Future<void> loadRefunds() async {
    loadingRefunds = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.getRefunds();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['refunds'];
          refunds = (raw is List ? raw : raw['data'] ?? []).map<RefundModel>((x) => RefundModel.fromJson(x)).toList();
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingRefunds = false;
    update();
  }

  Future<void> loadDeliveryPayments() async {
    loadingPayments = true;
    update();
    try {
      ResponseModel response = await deliveryRepo.getDeliveryPayments();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['payments'];
          deliveryPayments = (raw is List ? raw : raw['data'] ?? []).map<DeliveryPaymentModel>((x) => DeliveryPaymentModel.fromJson(x)).toList();
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingPayments = false;
    update();
  }

  double? courierLat;
  double? courierLng;

  void subscribeToOrderChannel() {
    String userId = Get.find<ApiClient>().getUserID();
    if (userId.isEmpty) return;
    String channelName = 'private-delivery-order.$userId';
    PusherManager().checkAndInitIfNeeded(channelName);
    PusherManager().addListener((event) {
      if (event.channelName == channelName) {
        if (event.eventName == 'delivery_order_status_updated') {
          try {
            var data = jsonDecode(event.data);
            int? updatedOrderId = data['order_id'];
            if (updatedOrderId != null && selectedOrder?.id == updatedOrderId) {
              loadOrderDetail(updatedOrderId);
            }
            loadOrders();
          } catch (_) {}
        } else if (event.eventName == 'location_update' || event.eventName == 'courier_location_updated') {
          try {
            var data = jsonDecode(event.data);
            if (data['order_id'] == selectedOrder?.id || selectedOrder?.driverId != null) {
              courierLat = double.tryParse(data['latitude']?.toString() ?? '');
              courierLng = double.tryParse(data['longitude']?.toString() ?? '');
              update();
            }
          } catch (_) {}
        }
      }
    });
  }

  Future<bool> reviewDelivery(int orderId, {double rating = 5, String review = ''}) async {
    try {
      ResponseModel response = await deliveryRepo.reviewDelivery(orderId, rating: rating, review: review);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        return json['status'] == MyStrings.success;
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  Future<bool> applyDeliveryCoupon(String code, double orderAmount) async {
    try {
      ResponseModel response = await deliveryRepo.applyCoupon(code, orderAmount);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          couponDiscount = (json['data']['discount'] is num) ? (json['data']['discount'] as num).toDouble() : double.tryParse(json['data']['discount']?.toString() ?? '0') ?? 0;
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  Future<void> toggleFavoriteStore(int storeId) async {
    // Optimistic toggle
    final wasFav = _favoriteStoreIds.contains(storeId);
    if (wasFav) {
      _favoriteStoreIds.remove(storeId);
    } else {
      _favoriteStoreIds.add(storeId);
    }
    update();

    try {
      await deliveryRepo.toggleFavoriteStore(storeId);
    } catch (e) {
      // Revert on error
      if (wasFav) {
        _favoriteStoreIds.add(storeId);
      } else {
        _favoriteStoreIds.remove(storeId);
      }
      update();
    }
  }

  Future<void> loadFavoriteStores() async {
    isLoadingFavorites = true;
    update();
    try {
      final response = await deliveryRepo.getFavoriteStores();
      if (response.statusCode == 200) {
        final json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final raw = json['data']['stores'];
          if (raw is List) {
            favoriteStores = raw.map((x) => StoreModel.fromJson(x)).toList();
            _favoriteStoreIds.clear();
            _favoriteStoreIds.addAll(favoriteStores.map((s) => s.id!).where((id) => id != null));
          }
          storeImagePath = json['data']['store_image_path'] ?? storeImagePath;
          storeCoverPath = json['data']['store_cover_path'] ?? storeCoverPath;
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoadingFavorites = false;
    update();
  }

  bool isStoreFavorite(int storeId) {
    return _favoriteStoreIds.contains(storeId);
  }
}
