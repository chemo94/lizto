import 'package:get/get.dart';
import 'package:lizto_store/core/helper/string_format_helper.dart';
import 'package:lizto_store/core/utils/my_strings.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/model/seller/package_models.dart';
import 'package:lizto_store/data/repo/seller/seller_package_repo.dart';

class SellerPackageController extends GetxController {
  final SellerPackageRepo repo;
  SellerPackageController({required this.repo});

  List<SellerPackageModel> packages = [];
  List<SellerSubscriptionModel> subscriptions = [];
  Map<int, List<SellerSubscriptionModel>> subscriptionsByStore = {};
  StoreAnalyticsModel? analytics;
  StoreQrModel? storeQr;
  NotifyResponseModel? notifyResult;

  bool loadingPackages = false;
  bool loadingSubs = false;
  bool loadingAnalytics = false;
  bool loadingQr = false;
  bool loadingNotify = false;
  bool purchasing = false;

  bool packagesFailed = false;
  bool subsLoadFailed = false;
  String packagesError = '';
  String purchaseError = '';

  Future<void> loadPackages() async {
    loadingPackages = true;
    packagesFailed = false;
    packagesError = '';
    update();
    try {
      ResponseModel r = await repo.getPackages();
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          final data = json['data'];
          final rawPackages = data is Map ? data['packages'] : data;
          if (rawPackages is List) {
            packages = rawPackages.map((x) => SellerPackageModel.fromJson(x)).toList();
          } else if (rawPackages == null) {
            packages = [];
          } else {
            packagesFailed = true;
            packagesError = 'Formato de respuesta inesperado al cargar los planes';
          }
        } else {
          packagesFailed = true;
          packagesError = _extractMessage(json);
        }
      } else {
        packagesFailed = true;
        packagesError = _describeStatus(r.statusCode);
      }
    } catch (e) {
      printX(e);
      packagesFailed = true;
      packagesError = 'Error al cargar los paquetes';
    }
    loadingPackages = false;
    update();
  }

  String _describeStatus(int? code) {
    switch (code) {
      case 401:
        return 'Sesión no autorizada. Vuelve a iniciar sesión (HTTP 401)';
      case 403:
        return 'No tienes permiso para ver los planes (HTTP 403)';
      case 500:
        return 'Error del servidor (HTTP 500). Verifica con el administrador que los planes estén activados';
      default:
        return 'No se pudieron cargar los planes (HTTP ${code ?? '?'})';
    }
  }

  String _extractMessage(dynamic json) {
    try {
      final m = json['message'];
      if (m is List && m.isNotEmpty) return m.first.toString();
      if (m != null) return m.toString();
    } catch (_) {}
    return 'No se pudieron cargar los paquetes';
  }

  Future<bool> purchasePackage({
    required int packageId,
    required int storeId,
    String? paymentMethod,
    String? paymentRef,
  }) async {
    purchasing = true;
    purchaseError = '';
    update();
    try {
      ResponseModel r = await repo.purchasePackage(
        packageId: packageId,
        storeId: storeId,
        paymentMethod: paymentMethod,
        paymentRef: paymentRef,
      );
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          purchasing = false;
          update();
          return true;
        } else {
          purchaseError = _extractMessage(json);
        }
      } else {
        purchaseError = r.message;
      }
    } catch (e) {
      printX(e);
      purchaseError = 'Error de conexión';
    }
    purchasing = false;
    update();
    return false;
  }

  Future<void> loadMySubscriptions(int storeId) async {
    loadingSubs = true;
    subsLoadFailed = false;
    update();
    try {
      ResponseModel r = await repo.getMySubscriptions(storeId);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          subscriptions = (json['data']['subscriptions'] as List)
              .map((x) => SellerSubscriptionModel.fromJson(x)).toList();
          subscriptionsByStore[storeId] = subscriptions;
        } else {
          subsLoadFailed = true;
        }
      } else {
        subsLoadFailed = true;
      }
    } catch (e) {
      printX(e);
      subsLoadFailed = true;
    }
    loadingSubs = false;
    update();
  }

  List<SellerSubscriptionModel>? subscriptionsFor(int storeId) =>
      subscriptionsByStore[storeId];

  bool hasActiveSubscriptionFor(int storeId) {
    final subs = subscriptionsByStore[storeId] ?? subscriptions;
    final now = DateTime.now();
    for (final s in subs) {
      if (s.status != 'active') continue;
      final exp = s.expiresAtDate;
      if (exp == null || exp.isAfter(now)) return true;
    }
    return false;
  }

  SellerSubscriptionModel? expiredSubscriptionFor(int storeId) {
    final subs = subscriptionsByStore[storeId] ?? subscriptions;
    final now = DateTime.now();
    SellerSubscriptionModel? latest;
    for (final s in subs) {
      final isExpired = s.status == 'expired' ||
          (s.status == 'active' && s.expiresAtDate != null && s.expiresAtDate!.isBefore(now));
      if (!isExpired) continue;
      final exp = s.expiresAtDate;
      if (exp == null) continue;
      if (latest == null || latest.expiresAtDate == null || exp.isAfter(latest.expiresAtDate!)) {
        latest = s;
      }
    }
    return latest;
  }

  Future<void> loadAllStoresSubscriptions(List<int> storeIds) async {
    loadingSubs = true;
    update();
    try {
      for (final id in storeIds) {
        ResponseModel r = await repo.getMySubscriptions(id);
        if (r.statusCode == 200) {
          var json = r.responseJson;
          if (json['status'] == MyStrings.success && json['data'] != null) {
            subscriptionsByStore[id] = (json['data']['subscriptions'] as List)
                .map((x) => SellerSubscriptionModel.fromJson(x)).toList();
          }
        }
      }
    } catch (e) { printX(e); }
    loadingSubs = false;
    update();
  }

  Future<void> loadAnalytics(int storeId) async {
    loadingAnalytics = true;
    update();
    try {
      ResponseModel r = await repo.getStoreAnalytics(storeId);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          analytics = StoreAnalyticsModel.fromJson(json['data']);
        }
      }
    } catch (e) { printX(e); }
    loadingAnalytics = false;
    update();
  }

  Future<void> loadQr(int storeId) async {
    loadingQr = true;
    update();
    try {
      ResponseModel r = await repo.getStoreQr(storeId);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          storeQr = StoreQrModel.fromJson(json['data']);
        }
      }
    } catch (e) { printX(e); }
    loadingQr = false;
    update();
  }

  Future<bool> sendNotification(int storeId, String title, String body) async {
    loadingNotify = true;
    update();
    try {
      ResponseModel r = await repo.notifyCustomers(storeId, title, body);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          notifyResult = NotifyResponseModel.fromJson(json['data']);
          loadingNotify = false;
          update();
          return true;
        }
      }
    } catch (e) { printX(e); }
    loadingNotify = false;
    update();
    return false;
  }
}
