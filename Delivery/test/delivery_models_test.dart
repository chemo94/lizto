import 'package:flutter_test/flutter_test.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_models.dart';
import 'package:lizto_delivery/data/model/delivery/favor_models.dart';
import 'package:lizto_delivery/data/model/delivery/courier_models.dart';
import 'package:lizto_delivery/data/model/delivery/delivery_pin_model.dart';
import 'package:lizto_delivery/data/model/delivery/user_address_model.dart';

void main() {
  group('StoreModel', () {
    test('fromJson parses correctly', () {
      final json = {
        'id': 1, 'name': 'Test Store', 'delivery_fee': '5.50',
        'opening_time': '08:00:00', 'closing_time': '22:00:00',
        'preparation_time': 15, 'latitude': -12.0464, 'longitude': -77.0428,
      };
      final store = StoreModel.fromJson(json);
      expect(store.id, 1);
      expect(store.name, 'Test Store');
      expect(store.deliveryFee, 5.50);
      expect(store.preparationTime, 15);
    });

    test('isOpenNow returns true during business hours', () {
      final store = StoreModel(openingTime: '00:00:00', closingTime: '23:59:59');
      expect(store.isOpenNow, true);
    });

    test('distanceFormatted returns m for < 1km', () {
      final store = StoreModel(distance: 0.5);
      expect(store.distanceFormatted, '500 m');
    });

    test('distanceFormatted returns km for >= 1km', () {
      final store = StoreModel(distance: 3.7);
      expect(store.distanceFormatted, '3.7 km');
    });
  });

  group('ProductModel', () {
    test('fromJson with variations and addons', () {
      final json = {
        'id': 1, 'name': 'Burger', 'price': '10.00', 'discount_price': '8.00',
        'variations': [{'id': 1, 'name': 'Grande', 'price': '12.00'}],
        'addons': [{'id': 1, 'name': 'Queso extra', 'price': '2.00'}],
      };
      final product = ProductModel.fromJson(json);
      expect(product.finalPrice, 8.00);
      expect(product.variations?.length, 1);
      expect(product.addons?.length, 1);
    });
  });

  group('CartItemModel', () {
    test('totalPrice calculates correctly', () {
      final product = ProductModel(id: 1, name: 'Test', price: 10);
      final item = CartItemModel(product: product);
      expect(item.totalPrice, 10.0);
      item.quantity = 3;
      expect(item.totalPrice, 30.0);
    });

    test('unitPrice with variation', () {
      final product = ProductModel(id: 1, name: 'Test', price: 10);
      final variation = ProductVariationModel(id: 1, name: 'XL', price: 15);
      final item = CartItemModel(product: product, selectedVariation: variation);
      expect(item.unitPrice, 15.0);
    });

    test('unitPrice with addons', () {
      final product = ProductModel(id: 1, name: 'Test', price: 10);
      final addons = [
        ProductAddonModel(id: 1, name: 'Extra', price: 3),
        ProductAddonModel(id: 2, name: 'Plus', price: 2),
      ];
      final item = CartItemModel(product: product, selectedAddons: addons);
      expect(item.unitPrice, 15.0);
    });

    test('toOrderJson produces correct format', () {
      final product = ProductModel(id: 1, name: 'Test', price: 10);
      final variation = ProductVariationModel(id: 1, name: 'XL', price: 15);
      final addons = [ProductAddonModel(id: 1, name: 'Extra', price: 3)];
      final item = CartItemModel(product: product, selectedVariation: variation, selectedAddons: addons);
      final json = item.toOrderJson();
      expect(json['product_id'], 1);
      expect(json['quantity'], 1);
      expect(json['variation_id'], 1);
      expect(json['addon_ids'], [1]);
    });
  });

  group('DeliveryOrderModel', () {
    test('statusLabel returns correct Spanish labels', () {
      expect(DeliveryOrderModel(status: 'pending').statusLabel, 'Pendiente');
      expect(DeliveryOrderModel(status: 'delivered').statusLabel, 'Entregado');
      expect(DeliveryOrderModel(status: 'on_way').statusLabel, 'En camino');
    });

    test('fromJson parses driver and store', () {
      final json = {
        'id': 1, 'order_no': 'ORD-001', 'status': 'pending',
        'subtotal': '25.00', 'delivery_fee': '5.00', 'total': '30.00',
        'driver': {'name': 'Juan', 'phone': '999888777'},
        'store': {'name': 'Tienda Test'},
      };
      final order = DeliveryOrderModel.fromJson(json);
      expect(order.orderNo, 'ORD-001');
      expect(order.statusLabel, 'Pendiente');
      expect(order.driver?['name'], 'Juan');
    });
  });

  group('FavorModel', () {
    test('typeLabel returns correct labels', () {
      expect(FavorModel(type: 'buy').typeLabel, 'Compra');
      expect(FavorModel(type: 'send').typeLabel, 'Envío');
    });

    test('isActive returns correct value', () {
      expect(FavorModel(status: 'pending').isActive, true);
      expect(FavorModel(status: 'delivered').isActive, false);
      expect(FavorModel(status: 'cancelled').isActive, false);
    });

    test('statusLabel returns Spanish labels', () {
      expect(FavorModel(status: 'searching_courier').statusLabel, 'Buscando repartidor...');
      expect(FavorModel(status: 'on_way_to_pickup').statusLabel, 'Camino a recoger');
      expect(FavorModel(status: 'delivered').statusLabel, 'Entregado');
    });
  });

  group('FavorMessageModel', () {
    test('isFromCourier check', () {
      expect(FavorMessageModel(senderRole: 'courier').isFromCourier, true);
      expect(FavorMessageModel(senderRole: 'customer').isFromCourier, false);
    });
  });

  group('CourierJobModel', () {
    test('nextStatus progression', () {
      expect(CourierJobModel(status: 'pending').nextStatus, 'accepted');
      expect(CourierJobModel(status: 'accepted').nextStatus, 'on_way_to_pickup');
      expect(CourierJobModel(status: 'on_way_to_pickup').nextStatus, 'at_pickup');
      expect(CourierJobModel(status: 'on_way_to_delivery').nextStatus, 'delivered');
      expect(CourierJobModel(status: 'delivered').nextStatus, null);
    });

    test('nextAction returns correct labels', () {
      expect(CourierJobModel(status: 'pending').nextAction, 'Aceptar');
      expect(CourierJobModel(status: 'at_pickup').nextAction, 'Iniciar entrega');
      expect(CourierJobModel(status: 'delivered').nextAction, '');
    });
  });

  group('DeliveryPinVerification', () {
    test('fromJson parses correctly', () {
      final json = {'order_id': 42, 'pin_code': '5678', 'courier_name': 'Carlos'};
      final pin = DeliveryPinVerification.fromJson(json);
      expect(pin.orderId, 42);
      expect(pin.pinCode, '5678');
      expect(pin.courierName, 'Carlos');
    });
  });

  group('UserAddressModel', () {
    test('toJson and fromJson roundtrip', () {
      final addr = UserAddressModel(
        id: '1', label: 'Casa', address: 'Av. Principal 123',
        latitude: -12.04, longitude: -77.04,
        isDefault: true,
      );
      final json = addr.toJson();
      expect(json['label'], 'Casa');
      expect(json['is_default'], 1);
      final restored = UserAddressModel.fromJson(json);
      expect(restored.label, 'Casa');
      expect(restored.isDefault, true);
    });
  });

  group('GeneralCategoryModel', () {
    test('isFavorCategory detects by slug', () {
      expect(GeneralCategoryModel(slug: 'servicio-de-favores').isFavorCategory, true);
      expect(GeneralCategoryModel(slug: 'comida').isFavorCategory, false);
    });

    test('isFavorCategory detects by name', () {
      expect(GeneralCategoryModel(name: 'Servicio de Favores').isFavorCategory, true);
      expect(GeneralCategoryModel(name: 'Favores Express').isFavorCategory, true);
      expect(GeneralCategoryModel(name: 'Restaurantes').isFavorCategory, false);
    });
  });
}
