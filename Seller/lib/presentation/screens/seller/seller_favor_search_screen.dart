import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/presentation/screens/seller/seller_favor_tracking_screen.dart';

class SellerFavorSearchScreen extends StatefulWidget {
  final int favorId;
  final String orderNo;
  const SellerFavorSearchScreen({super.key, required this.favorId, required this.orderNo});
  @override
  State<SellerFavorSearchScreen> createState() => _SellerFavorSearchScreenState();
}

class _SellerFavorSearchScreenState extends State<SellerFavorSearchScreen> with SingleTickerProviderStateMixin {
  late final AnimationController _pulse;
  Timer? _poller;
  String _phase = 'nearby';
  String? _courierName;
  int? _seconds;
  bool _accepted = false, _exhausted = false, _retrying = false;
  bool _openingTracking = false;

  @override
  void initState() {
    super.initState();
    _pulse = AnimationController(vsync: this, duration: const Duration(milliseconds: 1200))..repeat(reverse: true);
    _refresh();
    _poller = Timer.periodic(const Duration(seconds: 3), (_) => _refresh());
  }

  @override
  void dispose() {
    _poller?.cancel();
    _pulse.dispose();
    super.dispose();
  }

  Future<void> _refresh() async {
    final data = await Get.find<SellerController>().getStoreFavorSearchStatus(widget.favorId);
    if (!mounted || data == null) return;
    final favor = data['favor'] as Map?;
    final search = data['search'] as Map? ?? {};
    setState(() {
      _accepted = ['accepted', 'on_way_to_pickup', 'at_pickup', 'on_way_to_delivery'].contains(favor?['status']);
      _exhausted = search['phase'] == 'exhausted';
      _phase = search['phase']?.toString() ?? 'nearby';
      _courierName = search['courier_name']?.toString();
      _seconds = search['seconds_remaining'] is num ? (search['seconds_remaining'] as num).ceil() : null;
    });
    if (_accepted || _exhausted) {
      _poller?.cancel();
      _poller = null;
    }
    if (_accepted && !_openingTracking) {
      _openingTracking = true;
      Get.off(() => SellerFavorTrackingScreen(favorId: widget.favorId, orderNo: widget.orderNo));
    }
  }

  Future<void> _retry() async {
    setState(() => _retrying = true);
    final data = await Get.find<SellerController>().retryStoreFavorSearch(widget.favorId);
    if (!mounted) return;
    if (data != null) {
      setState(() {
        _exhausted = false;
        _accepted = false;
        _retrying = false;
      });
      _poller?.cancel();
      _poller = null;
      _poller ??= Timer.periodic(const Duration(seconds: 3), (_) => _refresh());
      await _refresh();
    } else {
      setState(() => _retrying = false);
      Get.snackbar('Error', Get.find<SellerController>().errorMessage ?? 'No se pudo reiniciar la búsqueda');
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = _accepted
        ? '¡Repartidor encontrado!'
        : _exhausted
            ? 'No encontramos repartidores disponibles'
            : _phase == 'expanded'
                ? 'Ampliando rango de búsqueda'
                : _courierName != null
                    ? 'Esperando respuesta de $_courierName'
                    : 'Buscando repartidores cercanos';
    final subtitle = _accepted
        ? 'Tu solicitud ${widget.orderNo} ya fue aceptada.'
        : _exhausted
            ? '¿Deseas volver a buscar repartidores?'
            : _phase == 'expanded'
                ? 'Estamos notificando al resto de repartidores disponibles.'
                : _courierName != null
                    ? 'Tiene ${_seconds ?? 15} segundos para responder.'
                    : 'Estamos encontrando al repartidor más cercano.';
    return Scaffold(
        backgroundColor: const Color(0xFFF8FAFC),
        appBar: AppBar(backgroundColor: MyColor.primaryColor, title: Text('Buscando repartidor', style: boldLarge.copyWith(color: Colors.white))),
        body: Center(
            child: Padding(
                padding: const EdgeInsets.all(28),
                child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                  ScaleTransition(
                      scale: Tween(begin: .9, end: 1.12).animate(CurvedAnimation(parent: _pulse, curve: Curves.easeInOut)),
                      child: Container(
                          width: 116,
                          height: 116,
                          decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: _accepted
                                  ? const Color(0xFFDCFCE7)
                                  : _exhausted
                                      ? const Color(0xFFFEE2E2)
                                      : MyColor.primaryColor.withValues(alpha: .14)),
                          child: Icon(
                              _accepted
                                  ? Icons.check_circle_rounded
                                  : _exhausted
                                      ? Icons.search_off_rounded
                                      : Icons.two_wheeler_rounded,
                              size: 58,
                              color: _accepted
                                  ? const Color(0xFF16A34A)
                                  : _exhausted
                                      ? const Color(0xFFDC2626)
                                      : MyColor.primaryColor))),
                  const SizedBox(height: 28),
                  Text(title, textAlign: TextAlign.center, style: boldLarge.copyWith(fontSize: 21)),
                  const SizedBox(height: 10),
                  Text(subtitle, textAlign: TextAlign.center, style: regularDefault.copyWith(color: MyColor.bodyTextColor)),
                  const SizedBox(height: 14),
                  Text('Solicitud ${widget.orderNo}', style: regularSmall.copyWith(color: MyColor.primaryColor)),
                  if (_exhausted) ...[const SizedBox(height: 28), ElevatedButton.icon(onPressed: _retrying ? null : _retry, icon: _retrying ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : const Icon(Icons.refresh_rounded), label: const Text('Volver a buscar'))],
                ]))));
  }
}
