import 'dart:convert';
import 'package:liztogo/data/model/delivery/delivery_models.dart';
import 'package:liztogo/data/model/delivery/favor_models.dart';
import 'package:shared_preferences/shared_preferences.dart';

class DeliveryCacheService {
  static const _ordersKey = 'cached_delivery_orders';
  static const _favorsKey = 'cached_favors';
  static const _storesKey = 'cached_stores';

  static Future<void> cacheOrders(List<DeliveryOrderModel> orders) async {
    final prefs = await SharedPreferences.getInstance();
    final json = orders.map((o) => _orderToJson(o)).toList();
    await prefs.setString(_ordersKey, jsonEncode(json));
  }

  static Future<List<DeliveryOrderModel>> getCachedOrders() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_ordersKey);
    if (raw == null) return [];
    try {
      final list = jsonDecode(raw) as List;
      return list.map((x) => DeliveryOrderModel.fromJson(x)).toList();
    } catch (_) {
      return [];
    }
  }

  static Future<void> cacheFavors(List<FavorModel> favors) async {
    final prefs = await SharedPreferences.getInstance();
    final json = favors.map((o) => _favorToJson(o)).toList();
    await prefs.setString(_favorsKey, jsonEncode(json));
  }

  static Future<List<FavorModel>> getCachedFavors() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_favorsKey);
    if (raw == null) return [];
    try {
      final list = jsonDecode(raw) as List;
      return list.map((x) => FavorModel.fromJson(x)).toList();
    } catch (_) {
      return [];
    }
  }

  static Future<void> cacheStores(List<StoreModel> stores) async {
    final prefs = await SharedPreferences.getInstance();
    final json = stores.map((o) => _storeToJson(o)).toList();
    await prefs.setString(_storesKey, jsonEncode(json));
  }

  static Future<List<StoreModel>> getCachedStores() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_storesKey);
    if (raw == null) return [];
    try {
      final list = jsonDecode(raw) as List;
      return list.map((x) => StoreModel.fromJson(x)).toList();
    } catch (_) {
      return [];
    }
  }

  static Future<void> clearCache() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_ordersKey);
    await prefs.remove(_favorsKey);
    await prefs.remove(_storesKey);
  }

  static Map<String, dynamic> _orderToJson(DeliveryOrderModel o) => {
        'id': o.id, 'order_no': o.orderNo, 'subtotal': o.subtotal,
        'delivery_fee': o.deliveryFee, 'tip': o.tip, 'total': o.total,
        'status': o.status, 'delivery_address': o.deliveryAddress,
        'contact_phone': o.contactPhone, 'contact_name': o.contactName,
        'payment_status': o.paymentStatus, 'created_at': o.createdAt,
        'store': o.store != null ? {'name': o.store!.name ?? ''} : null,
      };

  static Map<String, dynamic> _favorToJson(FavorModel f) => {
        'id': f.id, 'order_no': f.orderNo, 'type': f.type,
        'description': f.description, 'pickup_address': f.pickupAddress,
        'delivery_address': f.deliveryAddress, 'status': f.status,
        'delivery_fee': f.deliveryFee, 'total': f.total, 'created_at': f.createdAt,
      };

  static Map<String, dynamic> _storeToJson(StoreModel s) => {
        'id': s.id, 'name': s.name, 'image': s.image,
        'cover_image': s.coverImage, 'description': s.description,
        'address': s.address, 'delivery_fee': s.deliveryFee,
        'opening_time': s.openingTime, 'closing_time': s.closingTime,
        'preparation_time': s.preparationTime, 'latitude': s.latitude,
        'longitude': s.longitude,
      };
}
