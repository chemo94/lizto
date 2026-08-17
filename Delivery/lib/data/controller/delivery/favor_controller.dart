import 'dart:convert';
import 'dart:io';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/helper/string_format_helper.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';
import 'package:lizto_delivery/data/model/delivery/favor_models.dart';
import 'package:lizto_delivery/data/model/global/response_model/response_model.dart';
import 'package:lizto_delivery/data/repo/delivery/favor_repo.dart';
import 'package:lizto_delivery/data/services/api_client.dart';
import 'package:lizto_delivery/data/services/delivery_cache_service.dart';
import 'package:lizto_delivery/data/services/pusher_service.dart';

class FavorController extends GetxController {
  final FavorRepo favorRepo;
  FavorController({required this.favorRepo});

  List<FavorModel> favors = [];
  FavorModel? selectedFavor;
  List<FavorMessageModel> messages = [];
  List<FavorBidModel> bids = [];
  String? courierImagePath;

  bool isLoading = false;
  bool loadingMessages = false;
  bool loadingBids = false;
  bool sending = false;

  bool get hasActiveFavor => favors.any((f) => f.isActive);
  FavorModel? get activeFavor {
    try {
      return favors.firstWhere((f) => f.isActive);
    } catch (_) {
      return null;
    }
  }

  // ── CRUD ──

  Future<bool> createFavor({
    required String type,
    required String description,
    String? storeName,
    String? storeAddress,
    double? estimatedAmount,
    required String pickupAddress,
    double? pickupLat,
    double? pickupLng,
    required String deliveryAddress,
    double? deliveryLat,
    double? deliveryLng,
    String? recipientName,
    String? recipientPhone,
    int? gatewayCode,
  }) async {
    sending = true;
    update();

    try {
      var data = {
        'type': type,
        'description': description,
        if (storeName != null && storeName.isNotEmpty) 'store_name': storeName,
        if (storeAddress != null && storeAddress.isNotEmpty) 'store_address': storeAddress,
        if (estimatedAmount != null && estimatedAmount > 0) 'estimated_amount': estimatedAmount,
        'pickup_address': pickupAddress,
        if (pickupLat != null) 'pickup_lat': pickupLat,
        if (pickupLng != null) 'pickup_lng': pickupLng,
        'delivery_address': deliveryAddress,
        if (deliveryLat != null) 'delivery_lat': deliveryLat,
        if (deliveryLng != null) 'delivery_lng': deliveryLng,
        if (recipientName != null && recipientName.isNotEmpty) 'recipient_name': recipientName,
        if (recipientPhone != null && recipientPhone.isNotEmpty) 'recipient_phone': recipientPhone,
        if (gatewayCode != null) 'payment_method_code': gatewayCode,
      };

      ResponseModel response = await favorRepo.createFavor(data);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          if (json['data'] != null && json['data']['favor'] != null) {
            selectedFavor = FavorModel.fromJson(json['data']['favor']);
            courierImagePath = json['data']['courier_image_path'] ?? '';
          }
          sending = false;
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    sending = false;
    update();
    return false;
  }

  Future<void> loadFavors() async {
    var cached = await DeliveryCacheService.getCachedFavors();
    if (cached.isNotEmpty && favors.isEmpty) {
      favors = cached;
      update();
    }
    isLoading = true;
    update();
    try {
      ResponseModel response = await favorRepo.getFavors();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['favors'];
          if (raw is Map && raw.containsKey('data')) {
            favors = (raw['data'] as List).map((x) => FavorModel.fromJson(x)).toList();
          } else if (raw is List) {
            favors = raw.map((x) => FavorModel.fromJson(x)).toList();
          }
          courierImagePath = json['data']['courier_image_path'] ?? '';
          DeliveryCacheService.cacheFavors(favors);
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<void> loadFavorDetail(int favorId) async {
    isLoading = true;
    update();
    try {
      ResponseModel response = await favorRepo.getFavorDetail(favorId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          selectedFavor = FavorModel.fromJson(json['data']['favor']);
          courierImagePath = json['data']['courier_image_path'] ?? '';
          subscribeToFavorChannel(favorId);
        }
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  Future<bool> cancelFavor(int favorId) async {
    try {
      ResponseModel response = await favorRepo.cancelFavor(favorId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          await loadFavors();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  // ── Chat ──

  void addEventMessage(Map<String, dynamic> data) {
    try {
      var msg = FavorMessageModel.fromJson(data);
      bool exists = messages.any((m) => m.id == msg.id);
      if (!exists) {
        messages.insert(0, msg);
        update();
      }
    } catch (_) {}
  }

  Future<void> loadMessages(int favorId) async {
    loadingMessages = true;
    update();
    try {
      ResponseModel response = await favorRepo.getFavorMessages(favorId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['messages'];
          if (raw is List) {
            messages = raw.map((x) => FavorMessageModel.fromJson(x)).toList();
          } else if (raw is Map && raw.containsKey('data')) {
            messages = (raw['data'] as List).map((x) => FavorMessageModel.fromJson(x)).toList();
          }
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingMessages = false;
    update();
  }

  Future<bool> sendMessage(int favorId, String text) async {
    try {
      ResponseModel response = await favorRepo.sendFavorMessage(favorId, text);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var msg = FavorMessageModel.fromJson(json['data']['message']);
          messages.insert(0, msg);
          update();
        }
        return true;
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  // ── Real-time ──

  void subscribeToFavorChannel(int favorId) {
    String userId = Get.find<ApiClient>().getUserID();
    if (userId.isEmpty) return;
    String channelName = 'private-favor.$favorId';
    PusherManager().checkAndInitIfNeeded(channelName);
    PusherManager().addListener((event) {
      if (event.channelName == channelName) {
        try {
          var data = jsonDecode(event.data);
          switch (event.eventName) {
            case 'favor_status_updated':
              if (selectedFavor != null) {
                if (data['favor'] != null) {
                  selectedFavor = FavorModel.fromJson(data['favor']);
                } else {
                  selectedFavor!.status = data['status'];
                }
                update();
              }
              break;
            case 'courier_location_updated':
              if (selectedFavor?.courier != null && data['latitude'] != null) {
                selectedFavor!.courier!.latitude = (data['latitude'] is num) ? (data['latitude'] as num).toDouble() : double.tryParse(data['latitude'].toString());
                selectedFavor!.courier!.longitude = (data['longitude'] is num) ? (data['longitude'] as num).toDouble() : double.tryParse(data['longitude'].toString());
                selectedFavor!.courier!.bearing = data['bearing'] != null ? ((data['bearing'] is num) ? (data['bearing'] as num).toDouble() : double.tryParse(data['bearing'].toString())) : null;
                update();
              }
              break;
            case 'favor_message_received':
            case 'job_message_received':
              addEventMessage(data);
              break;
          }
        } catch (_) {}
      }
    });
  }

  Future<bool> reviewFavor(int favorId, {double rating = 5, String review = ''}) async {
    try {
      ResponseModel response = await favorRepo.reviewFavor(favorId, rating: rating, review: review);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        return json['status'] == MyStrings.success;
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  Future<bool> sendImage(int favorId, File imageFile) async {
    try {
      ResponseModel response = await favorRepo.sendFavorImage(favorId, imageFile);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var msg = FavorMessageModel.fromJson(json['data']['message']);
          messages.insert(0, msg);
          update();
        }
        return true;
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }

  // ── Bidding ──

  Future<void> loadBids(int favorId) async {
    loadingBids = true;
    update();
    try {
      ResponseModel response = await favorRepo.getFavorBids(favorId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['bids'];
          bids = (raw is List ? raw : raw['data'] ?? []).map<FavorBidModel>((x) => FavorBidModel.fromJson(x)).toList();
          bids.sort((a, b) => (a.bidAmount ?? double.infinity).compareTo(b.bidAmount ?? double.infinity));
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingBids = false;
    update();
  }

  Future<bool> acceptBid(int favorId, int bidId) async {
    try {
      ResponseModel response = await favorRepo.acceptBid(favorId, bidId);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          if (json['data'] != null && json['data']['favor'] != null) {
            selectedFavor = FavorModel.fromJson(json['data']['favor']);
          }
          bids.clear();
          update();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }
}
