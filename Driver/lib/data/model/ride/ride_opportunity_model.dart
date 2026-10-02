import 'package:liztogo_pro/data/model/global/ride/ride_model.dart';

class RideOpportunity {
  final String id;
  final String uid;
  final String pickupLocation;
  final double? pickupLat;
  final double? pickupLng;
  final String destination;
  final double? destinationLat;
  final double? destinationLng;
  final double fare;
  final double minFare;
  final double maxFare;
  final String distance;
  final String duration;
  final String serviceName;
  final String riderName;
  final String riderAvatar;
  final String riderPhone;
  final String riderNote;
  final DateTime expiresAt;
  final int totalSeconds;
  final String source; // 'WEBSOCKET', 'FCM', 'INTENT'

  RideOpportunity({
    required this.id,
    required this.uid,
    required this.pickupLocation,
    this.pickupLat,
    this.pickupLng,
    required this.destination,
    this.destinationLat,
    this.destinationLng,
    required this.fare,
    this.minFare = 0.0,
    this.maxFare = 0.0,
    required this.distance,
    required this.duration,
    this.serviceName = 'Taxi',
    this.riderName = 'Pasajero',
    this.riderAvatar = '',
    this.riderPhone = '',
    this.riderNote = '',
    required this.expiresAt,
    this.totalSeconds = 30,
    this.source = 'WEBSOCKET',
  });

  int get remainingSeconds {
    final diff = expiresAt.difference(DateTime.now()).inSeconds;
    return diff > 0 ? diff : 0;
  }

  bool get isExpired => remainingSeconds <= 0;

  factory RideOpportunity.fromRideModel(RideModel model, {String source = 'WEBSOCKET', int windowSeconds = 30}) {
    final fareVal = double.tryParse(model.amount ?? model.recommendAmount ?? '0') ?? 0.0;
    final minVal = double.tryParse(model.minAmount ?? '0') ?? fareVal;
    final maxVal = double.tryParse(model.maxAmount ?? '0') ?? fareVal;

    return RideOpportunity(
      id: model.id?.toString() ?? '-1',
      uid: model.uid?.toString() ?? model.id?.toString() ?? '',
      pickupLocation: model.pickupLocation ?? 'Ubicación de recojo',
      pickupLat: double.tryParse(model.pickupLatitude?.toString() ?? ''),
      pickupLng: double.tryParse(model.pickupLongitude?.toString() ?? ''),
      destination: model.destination ?? 'Destino',
      destinationLat: double.tryParse(model.destinationLatitude?.toString() ?? ''),
      destinationLng: double.tryParse(model.destinationLongitude?.toString() ?? ''),
      fare: fareVal,
      minFare: minVal,
      maxFare: maxVal,
      distance: model.distance?.toString() ?? '',
      duration: model.duration?.toString() ?? '',
      serviceName: model.service?.name ?? 'Taxi',
      riderName: '${model.user?.firstname ?? ''} ${model.user?.lastname ?? ''}'.trim().isNotEmpty
          ? '${model.user?.firstname ?? ''} ${model.user?.lastname ?? ''}'.trim()
          : (model.user?.username ?? 'Pasajero'),
      riderAvatar: model.user?.imageWithPath ?? '',
      riderPhone: model.user?.mobile ?? '',
      riderNote: model.note ?? '',
      expiresAt: DateTime.now().add(Duration(seconds: windowSeconds)),
      totalSeconds: windowSeconds,
      source: source,
    );
  }

  factory RideOpportunity.fromMap(Map<String, dynamic> map, {String source = 'FCM'}) {
    final idVal = (map['ride_id'] ?? map['id'] ?? map['uid'] ?? '-1').toString();
    final fareVal = double.tryParse((map['amount'] ?? map['fare'] ?? map['estimated_fare'] ?? '0').toString()) ?? 0.0;
    final minVal = double.tryParse((map['min_amount'] ?? '0').toString()) ?? fareVal;
    final maxVal = double.tryParse((map['max_amount'] ?? '0').toString()) ?? fareVal;

    int remaining = int.tryParse((map['seconds_remaining'] ?? '30').toString()) ?? 30;
    DateTime expiration;
    if (map['expires_at'] != null && map['expires_at'].toString().isNotEmpty) {
      try {
        expiration = DateTime.parse(map['expires_at'].toString());
      } catch (_) {
        expiration = DateTime.now().add(Duration(seconds: remaining));
      }
    } else {
      expiration = DateTime.now().add(Duration(seconds: remaining));
    }

    return RideOpportunity(
      id: idVal,
      uid: (map['uid'] ?? map['ride_id'] ?? idVal).toString(),
      pickupLocation: (map['pickup_location'] ?? map['origin'] ?? 'Ubicación de recojo').toString(),
      pickupLat: double.tryParse((map['pickup_latitude'] ?? map['pickup_lat'] ?? '').toString()),
      pickupLng: double.tryParse((map['pickup_longitude'] ?? map['pickup_lng'] ?? '').toString()),
      destination: (map['destination'] ?? 'Destino').toString(),
      destinationLat: double.tryParse((map['destination_latitude'] ?? map['destination_lat'] ?? '').toString()),
      destinationLng: double.tryParse((map['destination_longitude'] ?? map['destination_lng'] ?? '').toString()),
      fare: fareVal,
      minFare: minVal,
      maxFare: maxVal,
      distance: (map['distance'] ?? map['distance_km'] ?? '').toString(),
      duration: (map['duration'] ?? '').toString(),
      serviceName: (map['service'] ?? map['service_name'] ?? 'Taxi').toString(),
      riderName: (map['rider_name'] ?? 'Pasajero').toString(),
      riderAvatar: (map['rider_avatar'] ?? '').toString(),
      riderPhone: (map['rider_phone'] ?? '').toString(),
      riderNote: (map['note'] ?? '').toString(),
      expiresAt: expiration,
      totalSeconds: remaining > 0 ? remaining : 30,
      source: source,
    );
  }

  Map<String, dynamic> toMap() => {
        'id': id,
        'uid': uid,
        'pickup_location': pickupLocation,
        'pickup_latitude': pickupLat,
        'pickup_longitude': pickupLng,
        'destination': destination,
        'destination_latitude': destinationLat,
        'destination_longitude': destinationLng,
        'amount': fare,
        'min_amount': minFare,
        'max_amount': maxFare,
        'distance': distance,
        'duration': duration,
        'service': serviceName,
        'rider_name': riderName,
        'rider_avatar': riderAvatar,
        'rider_phone': riderPhone,
        'note': riderNote,
        'expires_at': expiresAt.toIso8601String(),
        'seconds_remaining': remainingSeconds,
        'source': source,
      };
}
