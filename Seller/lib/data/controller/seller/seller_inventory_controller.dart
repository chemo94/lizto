import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/helper/string_format_helper.dart';
import 'package:lizto_store/core/utils/my_strings.dart';
import 'package:lizto_store/data/model/global/response_model/response_model.dart';
import 'package:lizto_store/data/model/seller/inventory_models.dart';
import 'package:lizto_store/data/repo/seller/seller_inventory_repo.dart';
import 'package:lizto_store/presentation/components/snack_bar/show_custom_snackbar.dart';

class SellerInventoryController extends GetxController {
  final SellerInventoryRepo repo;
  SellerInventoryController({required this.repo}) {
    try {
      final sym = repo.apiClient.getCurrency(isSymbol: true);
      if (sym.isNotEmpty) {
        currencySymbol = sym;
      }
    } catch (_) {}
  }

  // ── Items / Insumos State ──
  List<InvItemModel> items = [];
  List<String> categories = [];
  String selectedCategory = 'Todos';
  bool showOnlyLowStock = false;
  String searchQuery = '';
  int totalItems = 0;
  double totalValuation = 0.0;
  int lowStockCount = 0;
  int outOfStockCount = 0;
  bool loadingItems = false;

  // ── Recipes & Production State ──
  List<InvRecipeModel> recipes = [];
  List<InvItemModel> recipeInsumos = [];
  List<Map<String, dynamic>> availableProducts = [];
  List<InvProductionModel> recentProductions = [];
  bool loadingRecipes = false;

  // ── Purchases State ──
  List<InvPurchaseModel> purchasesList = [];
  List<InvSupplierModel> purchaseSuppliers = [];
  bool loadingPurchases = false;

  // ── Wastes State ──
  List<InvWasteModel> wastesList = [];
  bool loadingWastes = false;

  // ── Kardex State ──
  List<InvKardexModel> kardexList = [];
  int? kardexItemId;
  String? kardexType; // 'entrada', 'salida' or null
  bool loadingKardex = false;

  // ── Suppliers State ──
  List<InvSupplierModel> suppliersList = [];
  bool loadingSuppliers = false;

  // ── Dynamic Metadata & Catalogs ──
  InventoryMetadataModel? metadata;
  String currencySymbol = 'S/';
  double taxRatePercent = 18.0;
  double targetMarginPercent = 35.0;

  List<InventoryOptionModel> units = [];
  List<InventoryOptionModel> taxTypes = [];
  List<InventoryOptionModel> documentTypes = [];
  List<InventoryOptionModel> paymentMethods = [];
  List<String> wasteReasons = [];

  String formatCurrency(num? amount) {
    final sym = currencySymbol.isNotEmpty ? currencySymbol : 'S/';
    return '$sym ${(amount ?? 0).toStringAsFixed(2)}';
  }

  void _applyMetadata(dynamic rawMeta) {
    if (rawMeta != null && rawMeta is Map) {
      metadata = InventoryMetadataModel.fromJson(Map<String, dynamic>.from(rawMeta));
      if (metadata != null) {
        if (metadata!.units.isNotEmpty) units = metadata!.units;
        if (metadata!.taxTypes.isNotEmpty) taxTypes = metadata!.taxTypes;
        if (metadata!.documentTypes.isNotEmpty) documentTypes = metadata!.documentTypes;
        if (metadata!.paymentMethods.isNotEmpty) paymentMethods = metadata!.paymentMethods;
        if (metadata!.wasteReasons.isNotEmpty) wasteReasons = metadata!.wasteReasons;
        taxRatePercent = metadata!.taxRatePercent;
        targetMarginPercent = metadata!.targetMarginPercent;
      }
    }
    try {
      final cur = repo.apiClient.getCurrency(isSymbol: true);
      if (cur.isNotEmpty) {
        currencySymbol = cur;
      } else if (metadata != null && metadata!.currencySymbol.isNotEmpty) {
        currencySymbol = metadata!.currencySymbol;
      }
    } catch (_) {}
  }

  Future<void> loadMetadata() async {
    try {
      ResponseModel r = await repo.metadata();
      if (r.statusCode == 200 && r.responseJson['status'] == MyStrings.success) {
        _applyMetadata(r.responseJson['data']);
        update();
      }
    } catch (e) {
      printX(e);
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 1. ITEMS / INSUMOS
  // ─────────────────────────────────────────────────────────────────────────

  Future<void> loadItems() async {
    loadingItems = true;
    update();
    try {
      final categoryParam = selectedCategory == 'Todos' ? null : selectedCategory;
      ResponseModel r = await repo.items(
        search: searchQuery.isNotEmpty ? searchQuery : null,
        category: categoryParam,
        lowStock: showOnlyLowStock ? true : null,
      );
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final data = json['data'];
          if (data['metadata'] != null) {
            _applyMetadata(data['metadata']);
          }
          items = (data['items'] as List?)
                  ?.map((e) => InvItemModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
          categories = ['Todos'] +
              ((data['categories'] as List?)?.map((e) => e.toString()).toList() ?? []);
          totalItems = int.tryParse(data['total_items']?.toString() ?? '') ?? items.length;
          totalValuation = double.tryParse(data['total_valuation']?.toString() ?? '') ?? 0.0;
          lowStockCount = int.tryParse(data['low_stock_count']?.toString() ?? '') ?? 0;
          outOfStockCount = int.tryParse(data['out_of_stock_count']?.toString() ?? '') ?? 0;
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
    } catch (e) {
      printX(e);
    }
    loadingItems = false;
    update();
  }

  void filterByCategory(String cat) {
    selectedCategory = cat;
    loadItems();
  }

  void toggleLowStockFilter() {
    showOnlyLowStock = !showOnlyLowStock;
    loadItems();
  }

  void searchItems(String query) {
    searchQuery = query;
    loadItems();
  }

  Future<bool> saveItem(Map<String, dynamic> data, {int? id}) async {
    try {
      ResponseModel r = id != null
          ? await repo.updateItem(id, data)
          : await repo.createItem(data);

      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(
              successList: [id != null ? 'Insumo actualizado' : 'Insumo creado']);
          await loadItems();
          return true;
        } else {
          List<String> errors = (json['message']?['error'] as List?)
                  ?.map((e) => e.toString())
                  .toList() ??
              ['Error al guardar insumo'];
          CustomSnackBar.error(errorList: errors);
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  Future<bool> deleteItem(int id) async {
    try {
      ResponseModel r = await repo.deleteItem(id);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(successList: ['Insumo eliminado']);
          await loadItems();
          return true;
        } else {
          List<String> errors = (json['message']?['error'] as List?)
                  ?.map((e) => e.toString())
                  .toList() ??
              ['Error al eliminar insumo'];
          CustomSnackBar.error(errorList: errors);
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  Future<bool> adjustStock(int itemId, String type, double quantity, {String? description}) async {
    try {
      ResponseModel r = await repo.adjustStock(itemId, type, quantity, description: description);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(successList: ['Stock ajustado exitosamente']);
          await loadItems();
          return true;
        } else {
          List<String> errors = (json['message']?['error'] as List?)
                  ?.map((e) => e.toString())
                  .toList() ??
              ['Error al ajustar stock'];
          CustomSnackBar.error(errorList: errors);
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 2. RECETAS & PRODUCCIÓN
  // ─────────────────────────────────────────────────────────────────────────

  Future<void> loadRecipes({String? type}) async {
    loadingRecipes = true;
    update();
    try {
      ResponseModel r = await repo.recipes(type: type);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final data = json['data'];
          if (data['metadata'] != null) {
            _applyMetadata(data['metadata']);
          }
          recipes = (data['recipes'] as List?)
                  ?.map((e) => InvRecipeModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
          recipeInsumos = (data['items'] as List?)
                  ?.map((e) => InvItemModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
          availableProducts = (data['products'] as List?)
                  ?.map((e) => Map<String, dynamic>.from(e as Map))
                  .toList() ??
              [];
          recentProductions = (data['productions'] as List?)
                  ?.map((e) => InvProductionModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
    } catch (e) {
      printX(e);
    }
    loadingRecipes = false;
    update();
  }

  Future<bool> saveRecipe(Map<String, dynamic> data) async {
    try {
      if (!data.containsKey('target_margin_percent')) {
        data['target_margin_percent'] = targetMarginPercent;
      }
      ResponseModel r = await repo.createRecipe(data);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(successList: ['Receta guardada exitosamente']);
          await loadRecipes();
          return true;
        } else {
          List<String> errors = (json['message']?['error'] as List?)
                  ?.map((e) => e.toString())
                  .toList() ??
              ['Error al guardar receta'];
          CustomSnackBar.error(errorList: errors);
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  Future<bool> deleteRecipe(int id) async {
    try {
      ResponseModel r = await repo.deleteRecipe(id);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(successList: ['Receta eliminada']);
          await loadRecipes();
          return true;
        }
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  Future<bool> produceRecipe(int recipeId, double portions, {String? notes}) async {
    try {
      ResponseModel r = await repo.produceRecipe(recipeId, portions, notes: notes);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(
              successList: ['Producción registrada. Stock de insumos descontado.']);
          await loadRecipes();
          return true;
        } else {
          List<String> errors = (json['message']?['error'] as List?)
                  ?.map((e) => e.toString())
                  .toList() ??
              ['Stock insuficiente de insumos para producir'];
          CustomSnackBar.error(errorList: errors);
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  Future<bool> voidProduction(int id) async {
    try {
      ResponseModel r = await repo.voidProduction(id);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(
              successList: ['Producción anulada. Insumos restaurados al stock.']);
          await loadRecipes();
          return true;
        }
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 3. COMPRAS / PURCHASES
  // ─────────────────────────────────────────────────────────────────────────

  Future<void> loadPurchases({int page = 1}) async {
    loadingPurchases = true;
    update();
    try {
      ResponseModel r = await repo.purchases(page: page);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final data = json['data'];
          if (data['metadata'] != null) {
            _applyMetadata(data['metadata']);
          }
          purchasesList = (data['purchases']?['data'] as List?)
                  ?.map((e) => InvPurchaseModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
          purchaseSuppliers = (data['suppliers'] as List?)
                  ?.map((e) => InvSupplierModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
          recipeInsumos = (data['items'] as List?)
                  ?.map((e) => InvItemModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingPurchases = false;
    update();
  }

  Future<bool> registerPurchase(Map<String, dynamic> data) async {
    try {
      if (!data.containsKey('tax_rate_percent')) {
        data['tax_rate_percent'] = taxRatePercent;
      }
      ResponseModel r = await repo.createPurchase(data);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(
              successList: ['Compra registrada. Stock y costos actualizados.']);
          await loadPurchases();
          return true;
        } else {
          List<String> errors = (json['message']?['error'] as List?)
                  ?.map((e) => e.toString())
                  .toList() ??
              ['Error al registrar compra'];
          CustomSnackBar.error(errorList: errors);
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 4. MERMAS / WASTES
  // ─────────────────────────────────────────────────────────────────────────

  Future<void> loadWastes({int page = 1}) async {
    loadingWastes = true;
    update();
    try {
      ResponseModel r = await repo.wastes(page: page);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final data = json['data'];
          if (data['metadata'] != null) {
            _applyMetadata(data['metadata']);
          }
          wastesList = (data['wastes']?['data'] as List?)
                  ?.map((e) => InvWasteModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
          recipeInsumos = (data['items'] as List?)
                  ?.map((e) => InvItemModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingWastes = false;
    update();
  }

  Future<bool> registerWaste(Map<String, dynamic> data) async {
    try {
      ResponseModel r = await repo.createWaste(data);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(successList: ['Merma registrada. Stock descontado.']);
          await loadWastes();
          return true;
        } else {
          List<String> errors = (json['message']?['error'] as List?)
                  ?.map((e) => e.toString())
                  .toList() ??
              ['Error al registrar merma'];
          CustomSnackBar.error(errorList: errors);
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 5. KARDEX MOVEMENTS
  // ─────────────────────────────────────────────────────────────────────────

  Future<void> loadKardex({int page = 1}) async {
    loadingKardex = true;
    update();
    try {
      ResponseModel r = await repo.kardex(
        itemId: kardexItemId,
        type: kardexType,
        page: page,
      );
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          final data = json['data'];
          kardexList = (data['movements']?['data'] as List?)
                  ?.map((e) => InvKardexModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
          recipeInsumos = (data['items'] as List?)
                  ?.map((e) => InvItemModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingKardex = false;
    update();
  }

  void filterKardexItem(int? id) {
    kardexItemId = id;
    loadKardex();
  }

  void filterKardexType(String? type) {
    kardexType = type;
    loadKardex();
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 6. PROVEEDORES / SUPPLIERS
  // ─────────────────────────────────────────────────────────────────────────

  Future<void> loadSuppliers({String? search}) async {
    loadingSuppliers = true;
    update();
    try {
      ResponseModel r = await repo.suppliers(search: search);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          suppliersList = (json['data']['suppliers'] as List?)
                  ?.map((e) => InvSupplierModel.fromJson(e as Map<String, dynamic>))
                  .toList() ??
              [];
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingSuppliers = false;
    update();
  }

  Future<bool> saveSupplier(Map<String, dynamic> data) async {
    try {
      ResponseModel r = await repo.createSupplier(data);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(successList: ['Proveedor guardado exitosamente']);
          await loadSuppliers();
          return true;
        } else {
          List<String> errors = (json['message']?['error'] as List?)
                  ?.map((e) => e.toString())
                  .toList() ??
              ['Error al guardar proveedor'];
          CustomSnackBar.error(errorList: errors);
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }

  Future<bool> deleteSupplier(int id) async {
    try {
      ResponseModel r = await repo.deleteSupplier(id);
      if (r.statusCode == 200) {
        var json = r.responseJson;
        if (json['status'] == MyStrings.success) {
          CustomSnackBar.success(successList: ['Proveedor eliminado']);
          await loadSuppliers();
          return true;
        } else {
          List<String> errors = (json['message']?['error'] as List?)
                  ?.map((e) => e.toString())
                  .toList() ??
              ['No se puede eliminar el proveedor'];
          CustomSnackBar.error(errorList: errors);
        }
      } else {
        CustomSnackBar.error(errorList: [r.message]);
      }
      return false;
    } catch (e) {
      printX(e);
      return false;
    }
  }
}
