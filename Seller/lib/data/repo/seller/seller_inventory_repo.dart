import 'package:lizto_store/core/utils/method.dart';
import 'package:lizto_store/core/utils/url_container.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/services/api_client.dart';

class SellerInventoryRepo {
  final ApiClient apiClient;
  SellerInventoryRepo({required this.apiClient});

  Future<ResponseModel> _get(String url) async {
    return await apiClient.request('${UrlContainer.baseUrl}$url', Method.getMethod, null, passHeader: true);
  }

  Future<ResponseModel> _post(String url, [Map<String, dynamic>? data]) async {
    return await apiClient.request('${UrlContainer.baseUrl}$url', Method.postMethod, data, passHeader: true);
  }

  // ── Metadata / Master Catalogs ──
  Future<ResponseModel> metadata() => _get(UrlContainer.inventoryMetadata);

  // ── Items / Insumos ──
  Future<ResponseModel> items({String? search, String? category, bool? lowStock}) {
    final params = <String>[];
    if (search != null && search.isNotEmpty) params.add('search=${Uri.encodeComponent(search)}');
    if (category != null && category.isNotEmpty) params.add('category=${Uri.encodeComponent(category)}');
    if (lowStock == true) params.add('low_stock=1');
    final query = params.isNotEmpty ? '?${params.join('&')}' : '';
    return _get('${UrlContainer.inventoryItems}$query');
  }

  Future<ResponseModel> createItem(Map<String, dynamic> data) =>
      _post(UrlContainer.inventoryItemStore, data);

  Future<ResponseModel> updateItem(int id, Map<String, dynamic> data) =>
      _post(UrlContainer.inventoryItemUpdate(id), data);

  Future<ResponseModel> deleteItem(int id) =>
      _post(UrlContainer.inventoryItemDelete(id));

  Future<ResponseModel> adjustStock(int itemId, String type, double quantity, {String? description}) {
    return _post(UrlContainer.inventoryStockAdjust, {
      'item_id': itemId,
      'type': type,
      'quantity': quantity,
      if (description != null && description.isNotEmpty) 'description': description,
    });
  }

  // ── Recipes & Production ──
  Future<ResponseModel> recipes({String? type}) {
    final query = (type != null && type.isNotEmpty) ? '?type=$type' : '';
    return _get('${UrlContainer.inventoryRecipes}$query');
  }

  Future<ResponseModel> createRecipe(Map<String, dynamic> data) =>
      _post(UrlContainer.inventoryRecipeStore, data);

  Future<ResponseModel> deleteRecipe(int id) =>
      _post(UrlContainer.inventoryRecipeDelete(id));

  Future<ResponseModel> produceRecipe(int recipeId, double portionsProduced, {String? notes}) {
    return _post(UrlContainer.inventoryRecipeProduction, {
      'recipe_id': recipeId,
      'portions_produced': portionsProduced,
      if (notes != null && notes.isNotEmpty) 'notes': notes,
    });
  }

  Future<ResponseModel> voidProduction(int id) =>
      _post(UrlContainer.inventoryRecipeProductionVoid(id));

  // ── Purchases ──
  Future<ResponseModel> purchases({int page = 1}) =>
      _get('${UrlContainer.inventoryPurchases}?page=$page');

  Future<ResponseModel> createPurchase(Map<String, dynamic> data) =>
      _post(UrlContainer.inventoryPurchaseStore, data);

  // ── Wastes ──
  Future<ResponseModel> wastes({int page = 1}) =>
      _get('${UrlContainer.inventoryWastes}?page=$page');

  Future<ResponseModel> createWaste(Map<String, dynamic> data) =>
      _post(UrlContainer.inventoryWasteStore, data);

  // ── Kardex ──
  Future<ResponseModel> kardex({int? itemId, String? type, String? startDate, String? endDate, int page = 1}) {
    final params = <String>['page=$page'];
    if (itemId != null) params.add('item_id=$itemId');
    if (type != null && type.isNotEmpty) params.add('type=$type');
    if (startDate != null && startDate.isNotEmpty) params.add('start_date=$startDate');
    if (endDate != null && endDate.isNotEmpty) params.add('end_date=$endDate');
    return _get('${UrlContainer.inventoryKardex}?${params.join('&')}');
  }

  // ── Suppliers ──
  Future<ResponseModel> suppliers({String? search}) {
    final query = (search != null && search.isNotEmpty) ? '?search=${Uri.encodeComponent(search)}' : '';
    return _get('${UrlContainer.inventorySuppliers}$query');
  }

  Future<ResponseModel> createSupplier(Map<String, dynamic> data) =>
      _post(UrlContainer.inventorySupplierStore, data);

  Future<ResponseModel> deleteSupplier(int id) =>
      _post(UrlContainer.inventorySupplierDelete(id));
}
