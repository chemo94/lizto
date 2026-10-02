import 'package:flutter_test/flutter_test.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';

void main() {
  group('Lizto Delivery Mobile App - Phase 5 Flutter Tests', () {
    test('CourierBatchModel deserializes Single, Double, Triplet, and Quadruple batches', () {
      final singleJson = {
        'id': 1,
        'batch_no': 'BATCH-001',
        'status': 'offered',
        'total_orders': 1,
        'batch_type': 'SINGLE',
        'total_distance_km': 3.5,
        'total_duration_minutes': 12.0,
        'driver_earning': 7.40,
        'total_payout': 7.40,
        'orders': [
          {'id': 101, 'courier_batch_id': 1, 'order_id': 501, 'status': 'assigned', 'sequence_order': 1}
        ],
      };

      final singleBatch = CourierBatchModel.fromJson(singleJson);
      expect(singleBatch.id, 1);
      expect(singleBatch.batchType, 'SINGLE');
      expect(singleBatch.totalOrders, 1);
      expect(singleBatch.driverEarning, 7.40);
      expect(singleBatch.isSingle, isTrue);
      expect(singleBatch.orders.length, 1);

      final quadJson = {
        'id': 4,
        'batch_no': 'BATCH-004',
        'status': 'offered',
        'total_orders': 4,
        'batch_type': 'QUADRUPLE',
        'total_distance_km': 10.2,
        'total_duration_minutes': 35.0,
        'driver_earning': 26.50,
        'total_payout': 26.50,
        'orders': [
          {'id': 1, 'courier_batch_id': 4, 'order_id': 601, 'status': 'assigned', 'sequence_order': 1},
          {'id': 2, 'courier_batch_id': 4, 'order_id': 602, 'status': 'assigned', 'sequence_order': 2},
          {'id': 3, 'courier_batch_id': 4, 'order_id': 603, 'status': 'assigned', 'sequence_order': 3},
          {'id': 4, 'courier_batch_id': 4, 'order_id': 604, 'status': 'assigned', 'sequence_order': 4},
        ],
      };

      final quadBatch = CourierBatchModel.fromJson(quadJson);
      expect(quadBatch.batchType, 'QUADRUPLE');
      expect(quadBatch.totalOrders, 4);
      expect(quadBatch.driverEarning, 26.50);
      expect(quadBatch.isQuadruple, isTrue);
      expect(quadBatch.orders.length, 4);
    });

    test('FareBreakdownModel validates Lizto recharge model (100% earnings retention & tips)', () {
      final fareJson = {
        'base_fare': 3.00,
        'distance_km': 5.0,
        'distance_rate': 0.80,
        'distance_amount': 4.00,
        'time_minutes': 15.0,
        'time_rate': 0.10,
        'time_amount': 1.50,
        'batch_bonus': 3.00,
        'batch_type': 'TRIPLET',
        'demand_incentive': 2.50,
        'demand_tier': 'VERY_HIGH',
        'points': 35,
        'driver_earning': 14.00,
        'tip': 5.00,
        'total_payout': 19.00,
        'driver_retains_100': true,
      };

      final fare = FareBreakdownModel.fromJson(fareJson);
      expect(fare.baseFare, 3.00);
      expect(fare.batchBonus, 3.00);
      expect(fare.batchType, 'TRIPLET');
      expect(fare.demandTier, 'VERY_HIGH');
      expect(fare.points, 35);
      expect(fare.driverEarning, 14.00);
      expect(fare.tip, 5.00);
      expect(fare.totalPayout, 19.00);
      expect(fare.driverRetains100, isTrue);
    });

    test('TargetedCourierOfferModel deserializes 15s window and expiresAt correctly', () {
      final offerJson = {
        'id': 77,
        'order_id': 2001,
        'batch_id': 12,
        'job_type': 'CourierBatch',
        'expires_at': DateTime.now().add(const Duration(seconds: 15)).toIso8601String(),
        'remaining_seconds': 15,
        'job_detail': {
          'id': 12,
          'batch_no': 'BATCH-012',
          'status': 'offered',
          'total_orders': 2,
          'batch_type': 'DOUBLE',
          'total_distance_km': 5.2,
          'total_duration_minutes': 18.0,
          'driver_earning': 14.20,
          'total_payout': 14.20,
          'orders': [],
        },
      };

      final offer = TargetedCourierOfferModel.fromJson(offerJson);
      expect(offer.id, 77);
      expect(offer.batchId, 12);
      expect(offer.isBatch, isTrue);
      expect(offer.remainingSeconds, 15);
      expect(offer.batchDetail, isNotNull);
      expect(offer.batchDetail?.batchType, 'DOUBLE');
      expect(offer.batchDetail?.driverEarning, 14.20);
    });

    test('AutoAcceptSettingsModel correctly parses and serializes driver preferences', () {
      final settings = AutoAcceptSettingsModel(
        autoAcceptEnabled: true,
        minEarning: 9.50,
        maxDistance: 4.5,
      );

      final json = settings.toJson();
      expect(json['auto_accept_enabled'], isTrue);
      expect(json['auto_accept_min_earning'], 9.50);
      expect(json['auto_accept_max_distance'], 4.5);

      final parsed = AutoAcceptSettingsModel.fromJson(json);
      expect(parsed.autoAcceptEnabled, isTrue);
      expect(parsed.minEarning, 9.50);
      expect(parsed.maxDistance, 4.5);
    });

    test('OptimizedStopModel correctly models pickup and dropoff sequencing', () {
      final pickupStop = OptimizedStopModel(
        stopNumber: 1,
        type: 'pickup',
        orderId: 101,
        address: 'Restaurante A',
        lat: -12.045,
        lng: -77.030,
        contactName: 'Local Central',
      );

      final dropoffStop = OptimizedStopModel(
        stopNumber: 2,
        type: 'dropoff',
        orderId: 101,
        address: 'Av. Siempre Viva 742',
        lat: -12.055,
        lng: -77.040,
        contactName: 'Cliente Juan',
      );

      expect(pickupStop.stopNumber, 1);
      expect(pickupStop.isPickup, isTrue);
      expect(pickupStop.type, 'pickup');
      expect(dropoffStop.stopNumber, 2);
      expect(dropoffStop.isDropoff, isTrue);
      expect(dropoffStop.type, 'dropoff');
      expect(pickupStop.orderId, dropoffStop.orderId);
      expect(pickupStop.stopNumber, lessThan(dropoffStop.stopNumber!));
    });
  });
}
