import 'package:flutter_test/flutter_test.dart';
import 'package:lizto_store/data/model/seller/inventory_models.dart';

void main() {
  group('Inventory & Gastronomy Models Tests', () {
    test('InvItemModel calculations & flags', () {
      final item = InvItemModel.fromJson({
        'id': 1,
        'name': 'Pechuga de Pollo',
        'category': 'Carnes',
        'unit': 'KG',
        'min_stock': '5.0',
        'cost': '14.50',
        'stock': '3.20',
        'tax_type': 'gravado',
        'is_bar_item': 0,
      });

      expect(item.name, 'Pechuga de Pollo');
      expect(item.unit, 'KG');
      expect(item.stock, 3.20);
      expect(item.cost, 14.50);
      expect(item.isLowStock, true);
      expect(item.isOutOfStock, false);
      expect(item.totalValuation, closeTo(3.20 * 14.50, 0.001));
    });

    test('InvItemModel out of stock flag', () {
      final item = InvItemModel.fromJson({
        'id': 2,
        'name': 'Pisco Quebranta',
        'unit': 'BOTELLA',
        'min_stock': 2,
        'cost': 35.00,
        'stock': 0,
        'is_bar_item': 1,
      });

      expect(item.isOutOfStock, true);
      expect(item.isLowStock, true);
      expect(item.isBarItem, true);
      expect(item.totalValuation, 0.0);
    });

    test('InvRecipeItemModel ingredient cost calculation', () {
      final ing = InvRecipeItemModel.fromJson({
        'id': 10,
        'recipe_id': 5,
        'item_id': 1,
        'quantity_gross': '0.350',
        'waste_pct': '10.0',
        'quantity_net': '0.315',
        'unit': 'KG',
        'item': {
          'id': 1,
          'name': 'Pechuga de Pollo',
          'cost': '16.00',
        },
      });

      expect(ing.quantityNet, 0.315);
      expect(ing.ingredientCost, closeTo(0.315 * 16.00, 0.001));
    });

    test('InvRecipeModel fromJson and food cost margin', () {
      final recipe = InvRecipeModel.fromJson({
        'id': 5,
        'name': 'Arroz Chaufa de Pollo',
        'recipe_type': 'kitchen',
        'portions': '1.0',
        'unit_produced': 'plato',
        'total_cost': '6.50',
        'cost_per_portion': '6.50',
        'product_price': '22.00',
        'margin': '70.5',
        'suggested_price': '10.00',
        'product': {'name': 'Chaufa Especial'},
        'items': [
          {
            'id': 1,
            'quantity_net': 0.2,
            'unit': 'KG',
            'item': {'name': 'Pollo', 'cost': 15.0},
          },
          {
            'id': 2,
            'quantity_net': 0.25,
            'unit': 'KG',
            'item': {'name': 'Arroz', 'cost': 3.5},
          },
        ],
      });

      expect(recipe.name, 'Arroz Chaufa de Pollo');
      expect(recipe.isKitchen, true);
      expect(recipe.isBar, false);
      expect(recipe.costPerPortion, 6.50);
      expect(recipe.margin, 70.5);
      expect(recipe.productName, 'Chaufa Especial');
      expect(recipe.items.length, 2);
    });

    test('InvProductionModel statuses', () {
      final prod = InvProductionModel.fromJson({
        'id': 12,
        'portions_produced': '20',
        'status': 'completed',
        'recipe': {'name': 'Salsa Huancaína', 'unit_produced': 'litro'},
      });

      expect(prod.isCompleted, true);
      expect(prod.isVoided, false);
      expect(prod.recipeName, 'Salsa Huancaína');
      expect(prod.portionsProduced, 20.0);
    });

    test('InvPurchaseModel calculations & doc number format', () {
      final purchase = InvPurchaseModel.fromJson({
        'id': 8,
        'document_type': '01',
        'document_series': 'F001',
        'document_number': '000452',
        'subtotal': '100.00',
        'igv': '18.00',
        'total': '118.00',
        'supplier': {
          'id': 2,
          'name': 'Makro Supermayorista',
          'document_number': '20100055551',
        },
        'items': [
          {'id': 1, 'quantity': 10, 'unit_cost': 10.0, 'total': 100.0}
        ],
      });

      expect(purchase.fullDocNumber, 'F001-000452');
      expect(purchase.total, 118.00);
      expect(purchase.supplier?.name, 'Makro Supermayorista');
      expect(purchase.items.length, 1);
    });

    test('InvWasteModel estimated loss', () {
      final waste = InvWasteModel.fromJson({
        'id': 4,
        'quantity': '1.5',
        'reason': 'Caducado',
        'item': {'id': 1, 'name': 'Leche Fresca', 'cost': '4.80'},
      });

      expect(waste.quantity, 1.5);
      expect(waste.estimatedCostLoss, closeTo(1.5 * 4.80, 0.001));
    });

    test('InvKardexModel entrada & salida checks', () {
      final entrada = InvKardexModel.fromJson({
        'id': 1,
        'type': 'entrada',
        'quantity': 10.0,
        'unit_cost': 5.0,
        'balance_stock': 25.0,
      });

      final salida = InvKardexModel.fromJson({
        'id': 2,
        'type': 'salida',
        'quantity': -2.5,
        'unit_cost': 5.0,
        'balance_stock': 22.5,
      });

      expect(entrada.isEntrada, true);
      expect(entrada.isSalida, false);
      expect(salida.isSalida, true);
      expect(salida.isEntrada, false);
    });

    test('InvSupplierModel serialization', () {
      final supplier = InvSupplierModel.fromJson({
        'id': 3,
        'name': 'Carnes del Sur S.A.C.',
        'document_number': '20501234567',
        'phone': '999888777',
      });

      expect(supplier.name, 'Carnes del Sur S.A.C.');
      expect(supplier.documentNumber, '20501234567');
      final json = supplier.toJson();
      expect(json['name'], 'Carnes del Sur S.A.C.');
      expect(json['phone'], '999888777');
    });

    test('InventoryMetadataModel dynamic catalogs and settings', () {
      final meta = InventoryMetadataModel.fromJson({
        'currency_symbol': '\$',
        'currency_text': 'USD',
        'tax_rate_percent': 16.0,
        'target_margin_percent': 40.0,
        'default_tax_type': 'gravado',
        'units': [
          {'code': 'KG', 'name': 'Kilogramo (KG)'},
          {'code': 'L', 'name': 'Litro (L)'},
        ],
        'tax_types': [
          {'code': 'gravado', 'name': 'Gravado (IVA)'},
          {'code': 'exento', 'name': 'Exento'},
        ],
        'document_types': [
          {'code': '01', 'name': 'Factura Fiscal'},
        ],
        'payment_methods': [
          {'code': 'cash', 'name': 'Efectivo'},
        ],
        'waste_reasons': ['Vencido', 'Mermado'],
      });

      expect(meta.currencySymbol, '\$');
      expect(meta.taxRatePercent, 16.0);
      expect(meta.targetMarginPercent, 40.0);
      expect(meta.units.length, 2);
      expect(meta.units.first.code, 'KG');
      expect(meta.taxTypes.length, 2);
      expect(meta.wasteReasons, ['Vencido', 'Mermado']);
    });

    test('InvRecipeModel dynamic margin and suggested price calculation', () {
      final recipe = InvRecipeModel(
        costPerPortion: 10.0,
        productPrice: 20.0,
      );

      // Margin with price 20: ((20 - 10) / 20) * 100 = 50.0%
      expect(recipe.calculatedMargin(), 50.0);

      // Suggested price with target margin 35%: 10 / (1 - 0.35) = 10 / 0.65 = 15.38
      expect(recipe.calculatedSuggestedPrice(35.0), closeTo(15.38, 0.01));

      // Suggested price with target margin 50%: 10 / (1 - 0.50) = 20.00
      expect(recipe.calculatedSuggestedPrice(50.0), closeTo(20.00, 0.01));
    });
  });
}
