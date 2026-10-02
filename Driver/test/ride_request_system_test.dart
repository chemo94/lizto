import 'package:flutter_test/flutter_test.dart';
import 'package:liztogo_pro/data/model/global/ride/ride_model.dart';
import 'package:liztogo_pro/data/model/ride/ride_opportunity_model.dart';

void main() {
  group('Lizto Conductor - Mobility Ride Request System Tests', () {
    test('RideOpportunity parses from WebSocket RideModel correctly', () {
      final ride = RideModel(
        id: '12345',
        uid: 'RIDE-12345',
        pickupLocation: 'Jr. Cahuide 906',
        destination: 'Morales',
        amount: '8.50',
        recommendAmount: '8.50',
        minAmount: '7.00',
        maxAmount: '12.00',
        distance: '2.4',
        duration: '10 min',
        note: 'Esperar frente a la puerta',
      );

      final opportunity = RideOpportunity.fromRideModel(ride, source: 'WEBSOCKET');

      expect(opportunity.id, '12345');
      expect(opportunity.uid, 'RIDE-12345');
      expect(opportunity.pickupLocation, 'Jr. Cahuide 906');
      expect(opportunity.destination, 'Morales');
      expect(opportunity.fare, 8.50);
      expect(opportunity.minFare, 7.00);
      expect(opportunity.maxFare, 12.00);
      expect(opportunity.distance, '2.4');
      expect(opportunity.duration, '10 min');
      expect(opportunity.riderNote, 'Esperar frente a la puerta');
      expect(opportunity.source, 'WEBSOCKET');
      expect(opportunity.isExpired, isFalse);
      expect(opportunity.remainingSeconds, greaterThan(0));
    });

    test('RideOpportunity parses from FCM background / Full-Screen Intent payload', () {
      final fcmPayload = {
        'type': 'new_ride',
        'ride_id': '9876',
        'pickup_location': 'Plaza Mayor de Tarapoto',
        'pickup_latitude': '-6.4850',
        'pickup_longitude': '-76.3650',
        'destination': 'Aeropuerto Guillermo del Castillo',
        'destination_latitude': '-6.5050',
        'destination_longitude': '-76.3750',
        'amount': '15.00',
        'min_amount': '12.00',
        'max_amount': '18.00',
        'distance': '4.5',
        'duration': '15 min',
        'rider_name': 'Carlos Perez',
        'service': 'Taxi Express',
        'seconds_remaining': '30',
        'template_name': 'NEW_RIDE',
      };

      final opportunity = RideOpportunity.fromMap(fcmPayload, source: 'FCM_FULL_SCREEN');

      expect(opportunity.id, '9876');
      expect(opportunity.pickupLocation, 'Plaza Mayor de Tarapoto');
      expect(opportunity.pickupLat, -6.4850);
      expect(opportunity.destination, 'Aeropuerto Guillermo del Castillo');
      expect(opportunity.fare, 15.00);
      expect(opportunity.distance, '4.5');
      expect(opportunity.duration, '15 min');
      expect(opportunity.riderName, 'Carlos Perez');
      expect(opportunity.serviceName, 'Taxi Express');
      expect(opportunity.source, 'FCM_FULL_SCREEN');
      expect(opportunity.isExpired, isFalse);
    });

    test('RideOpportunity correctly detects expiration after time window elapses', () {
      final pastOpportunity = RideOpportunity(
        id: '555',
        uid: 'RIDE-555',
        pickupLocation: 'Punto A',
        destination: 'Punto B',
        fare: 10.0,
        distance: '3.0',
        duration: '8 min',
        expiresAt: DateTime.now().subtract(const Duration(seconds: 5)),
        totalSeconds: 30,
      );

      expect(pastOpportunity.isExpired, isTrue);
      expect(pastOpportunity.remainingSeconds, 0);
    });

    test('Idempotency logic prevents duplicate displays for identical ride ID within 60s', () {
      final Map<String, DateTime> processedMap = {};
      const String rideId = 'RIDE-UNIQUE-777';

      bool shouldProcess(String id) {
        final now = DateTime.now();
        if (processedMap.containsKey(id)) {
          final diff = now.difference(processedMap[id]!).inSeconds;
          if (diff < 60) return false;
        }
        processedMap[id] = now;
        return true;
      }

      // First arrival via WebSocket
      final firstCheck = shouldProcess(rideId);
      expect(firstCheck, isTrue, reason: 'First occurrence must be processed');

      // Second arrival via FCM milliseconds later
      final duplicateCheck = shouldProcess(rideId);
      expect(duplicateCheck, isFalse, reason: 'Duplicate occurrence must be ignored');

      // Different ride arrival
      final differentRideCheck = shouldProcess('RIDE-OTHER-888');
      expect(differentRideCheck, isTrue, reason: 'Distinct ride ID must be processed');
    });

    test('RideOpportunity serialization to Map preserves all required mobility fields', () {
      final opp = RideOpportunity(
        id: '4321',
        uid: 'UID-4321',
        pickupLocation: 'Av. Salaverry 123',
        destination: 'Av. Larco 456',
        fare: 14.50,
        distance: '5.2',
        duration: '18 min',
        serviceName: 'Taxi Premium',
        riderName: 'Maria Rodriguez',
        expiresAt: DateTime.now().add(const Duration(seconds: 30)),
      );

      final map = opp.toMap();
      expect(map['id'], '4321');
      expect(map['amount'], 14.50);
      expect(map['distance'], '5.2');
      expect(map['duration'], '18 min');
      expect(map['rider_name'], 'Maria Rodriguez');
      expect(map['service'], 'Taxi Premium');
      expect(map['seconds_remaining'], greaterThan(0));
    });
  });
}
