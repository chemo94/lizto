/// Enterprise delivery constants for the Repartidor app.
/// Matches backend enum values from DeliveryConfirmationService, AutoDispatchService, etc.

/// Delivery status flow
class DeliveryStatus {
  static const String pending = 'pending';
  static const String accepted = 'accepted';
  static const String onWayToPickup = 'on_way_to_pickup';
  static const String atPickup = 'at_pickup';
  static const String onWayToDelivery = 'on_way_to_delivery';
  static const String delivered = 'delivered';
  static const String cancelled = 'cancelled';

  // Delivery order statuses
  static const String confirmed = 'confirmed';
  static const String preparing = 'preparing';
  static const String ready = 'ready';
  static const String onWay = 'on_way';

  // Return statuses
  static const String returnRequested = 'return_requested';
  static const String returnAssigned = 'return_assigned';
  static const String returnInTransit = 'return_in_transit';
  static const String returnCompleted = 'return_completed';
}

/// Valid cancellation reasons (structured)
class CancelReasons {
  static const Map<String, String> reasons = {
    'no_courier_available': 'No hay repartidores disponibles',
    'wrong_address': 'Dirección incorrecta',
    'changed_mind': 'El cliente cambió de opinión',
    'too_expensive': 'Demasiado costoso',
    'courier_far': 'Repartidor muy lejos',
    'duplicate_order': 'Pedido duplicado',
    'other': 'Otro motivo',
  };
}

/// Valid return reasons
class ReturnReasons {
  static const Map<String, String> reasons = {
    'recipient_not_found': 'Destinatario no encontrado',
    'wrong_address': 'Dirección incorrecta',
    'recipient_refused': 'Destinatario rechazó el paquete',
    'damaged_in_transit': 'Dañado en tránsito',
    'incomplete_order': 'Pedido incompleto',
    'other': 'Otro motivo',
  };
}

/// Time slot labels
class TimeSlots {
  static const Map<String, String> slots = {
    'morning': 'Mañana (8:00 - 12:00)',
    'afternoon': 'Tarde (12:00 - 17:00)',
    'evening': 'Noche (17:00 - 21:00)',
  };
}

/// Payment method codes
class PaymentMethods {
  static const String cash = 'cash';
  static const String yape = 'yape';
  static const String plin = 'plin';
  static const String card = 'card';

  static String displayName(String? code) {
    switch (code) {
      case yape:
        return 'Yape';
      case plin:
        return 'Plin';
      case card:
        return 'Tarjeta';
      default:
        return 'Efectivo';
    }
  }
}

/// Payer types
class PayerType {
  static const String sender = 'sender';
  static const String recipient = 'recipient';

  static String displayName(String? type) {
    switch (type) {
      case recipient:
        return 'El cliente paga';
      default:
        return 'Envío pagado';
    }
  }
}

/// Shipment types
class ShipmentType {
  static const String document = 'document';
  static const String food = 'food';
  static const String package = 'package';
  static const String pharmacy = 'pharmacy';
  static const String grocery = 'grocery';
  static const String other = 'other';

  static String label(String? type) {
    switch (type) {
      case document:
        return 'Documento';
      case food:
        return 'Comida';
      case package:
        return 'Paquete';
      case pharmacy:
        return 'Farmacia';
      case grocery:
        return 'Supermercado';
      default:
        return 'Otro';
    }
  }

  static String icon(String? type) {
    switch (type) {
      case document:
        return '📄';
      case food:
        return '🍽️';
      case package:
        return '📦';
      case pharmacy:
        return '💊';
      case grocery:
        return '🛒';
      default:
        return '🏷️';
    }
  }
}

/// Evidence of delivery types
class EvidenceType {
  static const String photo = 'photo';
  static const String pin = 'pin';
  static const String both = 'both';

  static String label(String? type) {
    switch (type) {
      case photo:
        return 'Foto';
      case pin:
        return 'PIN';
      case both:
        return 'Foto + PIN';
      default:
        return 'Foto';
    }
  }

  static bool requiresPhoto(String? type) => type == photo || type == both;
  static bool requiresPin(String? type) => type == pin || type == both;
}
