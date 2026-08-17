import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/data/controller/delivery/user_address_controller.dart';
import 'package:liztogo/data/model/delivery/delivery_models.dart';
import 'package:liztogo/presentation/screens/delivery/order_confirmation_screen.dart';
import 'package:liztogo/data/model/webview/webview_model.dart';
import 'package:liztogo/presentation/screens/web_view/web_view_screen.dart';
import 'package:liztogo/presentation/screens/delivery/order_detail_screen.dart';
import 'package:liztogo/presentation/screens/delivery/mercadopago_checkout_screen.dart';

class CheckoutScreen extends StatefulWidget {
  final StoreModel store;
  const CheckoutScreen({super.key, required this.store});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final _addressCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _nameCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  final _couponCtrl = TextEditingController();
  final _cashPayAmountCtrl = TextEditingController();
  double _selectedTip = 0;
  GatewayModel? _selectedGateway;
  double _discount = 0;
  double _gatewayFee = 0;
  bool _applyingCoupon = false;
  bool _showCoupon = false;
  bool _showNotes = false;
  DateTime? _scheduledDate;
  TimeOfDay? _scheduledTime;
  double? _deliveryLat;
  double? _deliveryLng;

  final List<double> _tipOptions = [0, 2, 5, 10, 20];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadCheckoutDefaults());
  }

  @override
  void dispose() {
    _addressCtrl.dispose();
    _phoneCtrl.dispose();
    _nameCtrl.dispose();
    _notesCtrl.dispose();
    _couponCtrl.dispose();
    _cashPayAmountCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final store = widget.store;
    final top = MediaQuery.of(context).padding.top;

    return Scaffold(
      backgroundColor: const Color(0xFFF7F8FA),
      body: GetBuilder<DeliveryController>(
        builder: (c) {
          final deliveryFee = c.estimatedDeliveryFee;
          _gatewayFee = _calcGatewayFee(c.cartSubtotal + deliveryFee + _selectedTip - _discount);
          double total = c.cartSubtotal + deliveryFee + _selectedTip - _discount + _gatewayFee;
          if (total < 0) total = 0;

          return Column(
            children: [
              // ── Premium Header ──
              Container(
                padding: EdgeInsets.fromLTRB(Dimensions.space16, top + Dimensions.space12, Dimensions.space16, Dimensions.space20),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.vertical(bottom: Radius.circular(24)),
                ),
                child: Row(
                  children: [
                    GestureDetector(
                      onTap: Get.back,
                      child: Container(
                        height: 40,
                        width: 40,
                        decoration: BoxDecoration(
                          color: const Color(0xFFF2F4F7),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.arrow_back_ios_new_rounded, size: 18, color: Color(0xFF101828)),
                      ),
                    ),
                    const SizedBox(width: Dimensions.space12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(MyStrings.checkout.tr, style: boldLarge.copyWith(fontSize: 20, color: MyColor.primaryTextColor)),
                          Text(store.name ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                        ],
                      ),
                    ),
                    // Live total badge
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space6),
                      decoration: BoxDecoration(
                        color: MyColor.primaryColor.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        'S/ ${total.toStringAsFixed(2)}',
                        style: boldDefault.copyWith(color: MyColor.primaryColor, fontSize: 15),
                      ),
                    ),
                  ],
                ),
              ),

              // ── Scrollable content ──
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.all(Dimensions.space16),
                  physics: const BouncingScrollPhysics(),
                  children: [
                    // ── Dirección de entrega (arriba siempre) ──
                    _SectionCard(
                      icon: Icons.location_on_rounded,
                      title: 'Dirección de entrega',
                      color: const Color(0xFF00C9A7),
                      child: _PremiumTextField(
                        controller: _addressCtrl,
                        label: '¿Dónde entregamos?',
                        hint: 'Jr. San Martín 123, Piso 2',
                        icon: Icons.home_rounded,
                        maxLines: 2,
                      ),
                    ),
                    const SizedBox(height: Dimensions.space16),

                    // ── Order Items Card ──
                    _SectionCard(
                      icon: Icons.receipt_long_rounded,
                      title: 'Tu pedido',
                      color: MyColor.primaryColor,
                      child: Column(
                        children: [
                          ...c.cartItems.map((item) => _OrderItemRow(item: item)),
                          const Divider(height: 20),
                          _SummaryRow(label: 'Subtotal', value: 'S/ ${c.cartSubtotal.toStringAsFixed(2)}'),
                          const SizedBox(height: Dimensions.space4),
                          _SummaryRow(
                            label: 'Delivery',
                            value: c.deliveryFeeLoading ? 'Calculando...' : 'S/ ${deliveryFee.toStringAsFixed(2)}${c.estimatedDeliveryDistance != null ? '  ·  ${c.estimatedDeliveryDistance!.toStringAsFixed(1)} km' : ''}',
                            valueColor: c.deliveryFeeLoading ? MyColor.bodyMutedTextColor : null,
                          ),
                          if (c.deliveryCoverageError != null)
                            Padding(
                              padding: const EdgeInsets.only(top: 4),
                              child: Row(
                                children: [
                                  Icon(Icons.warning_rounded, size: 14, color: MyColor.redCancelTextColor),
                                  const SizedBox(width: 4),
                                  Expanded(child: Text(c.deliveryCoverageError!, style: regularSmall.copyWith(color: MyColor.redCancelTextColor))),
                                ],
                              ),
                            ),
                          if (_selectedTip > 0) ...[
                            const SizedBox(height: Dimensions.space4),
                            _SummaryRow(label: 'Propina', value: '+ S/ ${_selectedTip.toStringAsFixed(2)}', valueColor: const Color(0xFF10B981)),
                          ],
                          if (_discount > 0) ...[
                            const SizedBox(height: Dimensions.space4),
                            _SummaryRow(label: 'Descuento', value: '- S/ ${_discount.toStringAsFixed(2)}', valueColor: const Color(0xFF10B981)),
                          ],
                          if (_gatewayFee > 0) ...[
                            const SizedBox(height: Dimensions.space4),
                            _SummaryRow(label: 'Comisión pasarela', value: '+ S/ ${_gatewayFee.toStringAsFixed(2)}', valueColor: const Color(0xFF6C63FF)),
                          ],
                          const Divider(height: 20),
                          Row(
                            children: [
                              Text('Total', style: boldLarge.copyWith(fontSize: 16)),
                              const Spacer(),
                              Text('S/ ${total.toStringAsFixed(2)}', style: boldLarge.copyWith(fontSize: 18, color: MyColor.primaryColor)),
                            ],
                          ),
                          const SizedBox(height: Dimensions.space12),
                          // ── Coupon inside Tu pedido ──
                          if (_discount > 0)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space10),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF0FDF4),
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: const Color(0xFF10B981).withValues(alpha: 0.3)),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.check_circle_rounded, color: Color(0xFF10B981), size: 18),
                                  const SizedBox(width: Dimensions.space6),
                                  Expanded(
                                    child: Text(
                                      'Cupón: -S/ ${_discount.toStringAsFixed(2)}',
                                      style: semiBoldSmall.copyWith(color: const Color(0xFF10B981)),
                                    ),
                                  ),
                                  GestureDetector(
                                    onTap: () => setState(() {
                                      _discount = 0;
                                      _showCoupon = false;
                                      _couponCtrl.clear();
                                    }),
                                    child: Container(
                                      padding: const EdgeInsets.all(4),
                                      decoration: BoxDecoration(
                                        color: MyColor.redCancelTextColor.withValues(alpha: 0.1),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Icon(Icons.close_rounded, size: 12, color: MyColor.redCancelTextColor),
                                    ),
                                  ),
                                ],
                              ),
                            )
                          else if (_showCoupon)
                            Row(
                              children: [
                                Expanded(
                                  child: Container(
                                    height: 40,
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFF2F4F7),
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    padding: const EdgeInsets.symmetric(horizontal: Dimensions.space10),
                                    child: TextField(
                                      controller: _couponCtrl,
                                      style: regularDefault.copyWith(color: MyColor.primaryTextColor, fontSize: 13),
                                      decoration: InputDecoration(
                                        hintText: 'Código de cupón',
                                        hintStyle: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 12),
                                        border: InputBorder.none,
                                        isDense: true,
                                        contentPadding: const EdgeInsets.symmetric(vertical: 11),
                                      ),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: Dimensions.space6),
                                GestureDetector(
                                  onTap: _applyingCoupon ? null : _applyCoupon,
                                  child: Container(
                                    height: 40,
                                    padding: const EdgeInsets.symmetric(horizontal: Dimensions.space14),
                                    decoration: BoxDecoration(
                                      color: MyColor.primaryColor,
                                      borderRadius: BorderRadius.circular(10),
                                    ),
                                    child: Center(
                                      child: _applyingCoupon ? const SizedBox(height: 16, width: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white)) : Text('Aplicar', style: semiBoldSmall.copyWith(color: Colors.white, fontSize: 12)),
                                    ),
                                  ),
                                ),
                              ],
                            )
                          else
                            GestureDetector(
                              onTap: () => setState(() => _showCoupon = true),
                              child: Container(
                                padding: const EdgeInsets.symmetric(vertical: Dimensions.space8, horizontal: Dimensions.space10),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF2F4F7),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: Row(
                                  children: [
                                    Icon(Icons.add_rounded, size: 14, color: MyColor.bodyMutedTextColor),
                                    const SizedBox(width: 6),
                                    Text('¿Tienes un cupón de descuento?', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 12)),
                                  ],
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(height: Dimensions.space16),

                    // ── Tip Selector ──
                    _SectionCard(
                      icon: Icons.volunteer_activism_rounded,
                      title: 'Propina para el repartidor',
                      subtitle: '¡Dale un extra por su esfuerzo!',
                      color: const Color(0xFF10B981),
                      child: Row(
                        children: _tipOptions.map((t) {
                          final sel = _selectedTip == t;
                          return Expanded(
                            child: GestureDetector(
                              onTap: () => setState(() => _selectedTip = t),
                              child: AnimatedContainer(
                                duration: const Duration(milliseconds: 200),
                                margin: const EdgeInsets.symmetric(horizontal: 3),
                                padding: const EdgeInsets.symmetric(vertical: Dimensions.space10),
                                decoration: BoxDecoration(
                                  color: sel ? const Color(0xFF10B981) : const Color(0xFFF2F4F7),
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(
                                    color: sel ? const Color(0xFF10B981) : Colors.transparent,
                                    width: 1.5,
                                  ),
                                  boxShadow: sel ? [BoxShadow(color: const Color(0xFF10B981).withValues(alpha: 0.3), blurRadius: 8, offset: const Offset(0, 3))] : [],
                                ),
                                child: Column(
                                  children: [
                                    Text(
                                      t == 0 ? '—' : 'S/ ${t.toInt()}',
                                      style: semiBoldSmall.copyWith(color: sel ? Colors.white : MyColor.primaryTextColor, fontSize: 13),
                                    ),
                                    if (t == 0) Text('Sin', style: regularSmall.copyWith(color: sel ? Colors.white70 : MyColor.bodyMutedTextColor, fontSize: 10)),
                                  ],
                                ),
                              ),
                            ),
                          );
                        }).toList(),
                      ),
                    ),

                    const SizedBox(height: Dimensions.space16),

                    // ── Payment Methods ──
                    _SectionCard(
                      icon: Icons.payment_rounded,
                      title: 'Método de pago',
                      color: const Color(0xFF6C63FF),
                      child: c.gateways.isEmpty
                          ? Row(
                              children: [
                                const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2)),
                                const SizedBox(width: Dimensions.space12),
                                Text('Cargando métodos...', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                              ],
                            )
                          : Column(
                              children: c.gateways.map((gw) {
                                final sel = _selectedGateway?.code == gw.code;
                                final gwColor = _gatewayColor(gw);
                                return Column(
                                  children: [
                                    GestureDetector(
                                      onTap: () => setState(() {
                                        _selectedGateway = gw;
                                        if (!gw.isCash) _cashPayAmountCtrl.clear();
                                      }),
                                      child: AnimatedContainer(
                                        duration: const Duration(milliseconds: 200),
                                        padding: const EdgeInsets.all(Dimensions.space12),
                                        decoration: BoxDecoration(
                                          color: sel ? gwColor.withValues(alpha: 0.07) : const Color(0xFFF7F8FA),
                                          borderRadius: BorderRadius.circular(14),
                                          border: Border.all(
                                            color: sel ? gwColor : Colors.transparent,
                                            width: 1.5,
                                          ),
                                        ),
                                        child: Row(
                                          children: [
                                            Container(
                                              height: 40,
                                              width: 40,
                                              decoration: BoxDecoration(
                                                color: sel ? gwColor.withValues(alpha: 0.15) : const Color(0xFFF2F4F7),
                                                borderRadius: BorderRadius.circular(12),
                                              ),
                                              child: Icon(
                                                _gatewayIcon(gw),
                                                color: sel ? gwColor : MyColor.bodyMutedTextColor,
                                                size: 21,
                                              ),
                                            ),
                                            const SizedBox(width: Dimensions.space12),
                                            Expanded(
                                              child: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  Text(
                                                    _isMercadoPagoGateway(gw) ? 'Tarjeta de débito / crédito' : gw.name ?? '',
                                                    style: boldDefault.copyWith(
                                                      fontSize: 14,
                                                      color: sel ? gwColor : MyColor.primaryTextColor,
                                                    ),
                                                  ),
                                                  if (gw.currency != null)
                                                    Text(
                                                      gw.currency!,
                                                      style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11),
                                                    ),
                                                ],
                                              ),
                                            ),
                                            AnimatedContainer(
                                              duration: const Duration(milliseconds: 200),
                                              height: 22,
                                              width: 22,
                                              decoration: BoxDecoration(
                                                shape: BoxShape.circle,
                                                color: sel ? gwColor : Colors.transparent,
                                                border: Border.all(
                                                  color: sel ? gwColor : MyColor.bodyMutedTextColor,
                                                  width: 2,
                                                ),
                                              ),
                                              child: sel ? const Icon(Icons.check_rounded, size: 13, color: Colors.white) : null,
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                    // Efectivo: cash amount field
                                    if (gw.isCash && sel) ...[
                                      const SizedBox(height: Dimensions.space8),
                                      Container(
                                        height: 48,
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFF2F4F7),
                                          borderRadius: BorderRadius.circular(12),
                                        ),
                                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.space12),
                                        child: Row(
                                          children: [
                                            Icon(Icons.payments_rounded, size: 18, color: gwColor),
                                            const SizedBox(width: 8),
                                            Text('S/', style: boldDefault.copyWith(color: gwColor)),
                                            const SizedBox(width: 6),
                                            Expanded(
                                              child: TextField(
                                                controller: _cashPayAmountCtrl,
                                                keyboardType: const TextInputType.numberWithOptions(decimal: true),
                                                style: regularDefault.copyWith(color: MyColor.primaryTextColor),
                                                decoration: InputDecoration(
                                                  hintText: '¿Con cuánto pagas? (ej: ${total.ceil()})',
                                                  hintStyle: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                                                  border: InputBorder.none,
                                                  isDense: true,
                                                  contentPadding: const EdgeInsets.symmetric(vertical: 15),
                                                ),
                                                onChanged: (_) => setState(() {}),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                    // Non-cash selected: commission info for MercadoPago, QR hint for others
                                    if (!gw.isCash && sel) ...[
                                      const SizedBox(height: Dimensions.space8),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space10),
                                        decoration: BoxDecoration(
                                          color: gwColor.withValues(alpha: 0.07),
                                          borderRadius: BorderRadius.circular(12),
                                          border: Border.all(color: gwColor.withValues(alpha: 0.25), width: 1),
                                        ),
                                        child: Row(
                                          children: [
                                            Icon(
                                              _isMercadoPagoGateway(gw) ? Icons.info_outline_rounded : Icons.qr_code_rounded,
                                              size: 16,
                                              color: gwColor,
                                            ),
                                            const SizedBox(width: 8),
                                            Expanded(
                                              child: Text(
                                                _isMercadoPagoGateway(gw) ? _formatMercadoPagoCommission(c.gateways) : 'Podrás pagar vía QR o número de celular al confirmar',
                                                style: regularSmall.copyWith(color: gwColor, fontSize: 11),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                    if (gw != c.gateways.last) const SizedBox(height: Dimensions.space8),
                                  ],
                                );
                              }).toList(),
                            ),
                    ),
                    const SizedBox(height: Dimensions.space16),

                    // ── Schedule ──
                    _SectionCard(
                      icon: Icons.schedule_rounded,
                      title: 'Programar entrega',
                      color: const Color(0xFFFFB347),
                      child: GestureDetector(
                        onTap: () => _pickSchedule(context),
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 200),
                          padding: const EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space12),
                          decoration: BoxDecoration(
                            color: _scheduledDate != null ? const Color(0xFFFFB347).withValues(alpha: 0.08) : const Color(0xFFF2F4F7),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: _scheduledDate != null ? const Color(0xFFFFB347) : Colors.transparent,
                              width: 1.5,
                            ),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                _scheduledDate != null ? Icons.event_available_rounded : Icons.flash_on_rounded,
                                color: _scheduledDate != null ? const Color(0xFFFFB347) : MyColor.bodyMutedTextColor,
                                size: 20,
                              ),
                              const SizedBox(width: Dimensions.space10),
                              Expanded(
                                child: Text(
                                  _scheduledDate != null ? _formatSchedule() : 'Ahora · Entrega inmediata',
                                  style: regularDefault.copyWith(
                                    color: _scheduledDate != null ? const Color(0xFF92400E) : MyColor.bodyMutedTextColor,
                                    fontWeight: _scheduledDate != null ? FontWeight.w600 : FontWeight.normal,
                                  ),
                                ),
                              ),
                              if (_scheduledDate != null)
                                GestureDetector(
                                  onTap: () => setState(() {
                                    _scheduledDate = null;
                                    _scheduledTime = null;
                                  }),
                                  child: Icon(Icons.close_rounded, size: 16, color: MyColor.bodyMutedTextColor),
                                ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: Dimensions.space16),

                    // ── Contacto + Notas (toggle) ──
                    _SectionCard(
                      icon: Icons.people_rounded,
                      title: 'Contacto',
                      color: const Color(0xFF00C9A7),
                      child: Column(
                        children: [
                          // Name field (compact)
                          Container(
                            height: 44,
                            decoration: BoxDecoration(
                              color: const Color(0xFFF2F4F7),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            padding: const EdgeInsets.symmetric(horizontal: Dimensions.space12),
                            child: Row(
                              children: [
                                Icon(Icons.person_rounded, size: 18, color: MyColor.bodyMutedTextColor),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: TextField(
                                    controller: _nameCtrl,
                                    style: regularDefault.copyWith(color: MyColor.primaryTextColor),
                                    decoration: InputDecoration(
                                      hintText: 'Nombre de contacto',
                                      hintStyle: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                                      border: InputBorder.none,
                                      isDense: true,
                                      contentPadding: const EdgeInsets.symmetric(vertical: 13),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: Dimensions.space8),
                          // Phone field (compact)
                          Container(
                            height: 44,
                            decoration: BoxDecoration(
                              color: const Color(0xFFF2F4F7),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            padding: const EdgeInsets.symmetric(horizontal: Dimensions.space12),
                            child: Row(
                              children: [
                                Icon(Icons.phone_rounded, size: 18, color: MyColor.bodyMutedTextColor),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: TextField(
                                    controller: _phoneCtrl,
                                    style: regularDefault.copyWith(color: MyColor.primaryTextColor),
                                    decoration: InputDecoration(
                                      hintText: '+51 999 999 999',
                                      hintStyle: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                                      border: InputBorder.none,
                                      isDense: true,
                                      contentPadding: const EdgeInsets.symmetric(vertical: 13),
                                    ),
                                    keyboardType: TextInputType.phone,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: Dimensions.space10),
                          // Notes toggle
                          if (!_showNotes)
                            GestureDetector(
                              onTap: () => setState(() => _showNotes = true),
                              child: Container(
                                padding: const EdgeInsets.symmetric(vertical: Dimensions.space10, horizontal: Dimensions.space12),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF2F4F7),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  children: [
                                    Icon(Icons.note_add_rounded, size: 16, color: MyColor.bodyMutedTextColor),
                                    const SizedBox(width: 8),
                                    Text('Agregar notas para el repartidor', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                                  ],
                                ),
                              ),
                            ),
                          if (_showNotes)
                            _PremiumTextField(
                              controller: _notesCtrl,
                              label: 'Notas para el repartidor',
                              hint: 'Ej: Dejar en la puerta, tocar 2 veces',
                              icon: Icons.note_rounded,
                              maxLines: 2,
                            ),
                        ],
                      ),
                    ),
                    const SizedBox(height: Dimensions.space24),

                    // ── Place Order Button ──
                    GestureDetector(
                      onTap: c.checkoutLoading
                          ? null
                          : () async {
                              if (!_canPlaceOrder(c, store)) return;
                              final errorMsg = await c.createOrder(
                                storeId: store.id ?? 0,
                                deliveryAddress: _addressCtrl.text.trim(),
                                deliveryLat: _deliveryLat,
                                deliveryLng: _deliveryLng,
                                contactPhone: _phoneCtrl.text.trim(),
                                contactName: _nameCtrl.text.trim(),
                                notes: _notesCtrl.text.trim(),
                                tip: _selectedTip > 0 ? _selectedTip : null,
                                gatewayCode: _selectedGateway?.code,
                                cashPayAmount: _selectedGateway?.isCash == true ? double.tryParse(_cashPayAmountCtrl.text.trim()) : null,
                                couponCode: _discount > 0 ? _couponCtrl.text.trim() : null,
                                scheduledTime: _scheduledDate?.toIso8601String(),
                              );
                              if (errorMsg == null && mounted) {
                                if (c.mpCheckoutData != null) {
                                  final mpData = c.mpCheckoutData!;
                                  final orderId = c.pendingOrderId ?? 0;
                                  c.mpCheckoutData = null;
                                  c.pendingOrderId = null;
                                  await Get.to(() => DeliveryMercadoPagoCheckoutScreen(mpData: mpData, orderId: orderId));
                                  // Safety net: if user manually pressed back before attempting payment,
                                  // the order won't have been deleted yet — delete it now.
                                  // (If payment was rejected/errored, MP screen already deleted it — this call is a no-op.)
                                  if (orderId > 0) {
                                    await c.deletePendingOrder(orderId);
                                  }
                                } else if (c.paymentRedirectUrl != null && c.paymentRedirectUrl!.isNotEmpty) {
                                  final redirectUrl = c.paymentRedirectUrl!;
                                  final orderId = c.pendingOrderId;
                                  c.paymentRedirectUrl = null;
                                  c.pendingOrderId = null;
                                  final result = await Get.to(() => MyWebViewScreen(model: WebviewModel(url: redirectUrl, rideId: '')));
                                  if (result == 'success') {
                                    if (mounted) {
                                      Get.off(() => const OrderConfirmationScreen());
                                    }
                                  } else {
                                    if (orderId != null) {
                                      await c.deletePendingOrder(orderId);
                                    }
                                    if (mounted) {
                                      Get.snackbar(
                                        'Pago cancelado',
                                        'El pago no fue procesado o fue cancelado. El pedido no ha sido creado.',
                                        backgroundColor: MyColor.redCancelTextColor,
                                        colorText: Colors.white,
                                        duration: const Duration(seconds: 4),
                                        icon: const Icon(Icons.warning_amber_rounded, color: Colors.white),
                                      );
                                    }
                                  }
                                } else {
                                  Get.off(() => const OrderConfirmationScreen());
                                }
                              } else if (mounted) {
                                Get.snackbar(
                                  'Error al crear pedido',
                                  errorMsg ?? 'No se pudo crear el pedido',
                                  backgroundColor: MyColor.redCancelTextColor,
                                  colorText: Colors.white,
                                  duration: const Duration(seconds: 5),
                                  icon: const Icon(Icons.error_rounded, color: Colors.white),
                                );
                              }
                            },
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        height: 58,
                        decoration: BoxDecoration(
                          color: c.checkoutLoading ? MyColor.primaryColor.withValues(alpha: 0.6) : MyColor.primaryColor,
                          borderRadius: BorderRadius.circular(18),
                          boxShadow: [
                            BoxShadow(
                              color: MyColor.primaryColor.withValues(alpha: 0.45),
                              blurRadius: 20,
                              offset: const Offset(0, 8),
                            ),
                          ],
                        ),
                        child: c.checkoutLoading
                            ? const Center(child: CircularProgressIndicator(color: Colors.white, strokeWidth: 3))
                            : Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.shopping_bag_rounded, color: Colors.white, size: 22),
                                  const SizedBox(width: Dimensions.space10),
                                  Text(
                                    'Confirmar pedido · S/ ${total.toStringAsFixed(2)}',
                                    style: boldDefault.copyWith(color: Colors.white, fontSize: 16),
                                  ),
                                ],
                              ),
                      ),
                    ),
                    const SizedBox(height: Dimensions.space32),
                  ],
                ),
              ),
            ],
          );
        },
      ),
    );
  }

  // ── Helpers ──

  Future<void> _loadCheckoutDefaults() async {
    final c = Get.find<DeliveryController>();
    await c.loadGateways();
    if (mounted && c.gateways.isNotEmpty && _selectedGateway == null) {
      setState(() => _selectedGateway = c.gateways.first);
    }

    final hasSelectedDeliveryLocation = c.userLat != null && c.userLng != null && c.currentDeliveryAddress.trim().isNotEmpty && c.currentDeliveryAddress != 'Ubicación actual detectada' && c.currentDeliveryAddress != 'UbicaciÃ³n actual detectada';

    if (hasSelectedDeliveryLocation) {
      _addressCtrl.text = c.currentDeliveryAddress;
      _deliveryLat = c.userLat;
      _deliveryLng = c.userLng;
    }

    if (_addressCtrl.text.trim().isEmpty && Get.isRegistered<UserAddressController>()) {
      final addressController = Get.find<UserAddressController>();
      await addressController.loadAddresses();
      final defaults = addressController.addresses.where((item) => item.isDefault).toList();
      final saved = defaults.isNotEmpty ? defaults.first : (addressController.addresses.isNotEmpty ? addressController.addresses.first : null);
      if (saved != null) {
        _addressCtrl.text = saved.address ?? '';
        _deliveryLat = saved.latitude;
        _deliveryLng = saved.longitude;
      }
    }

    try {
      final response = await c.deliveryRepo.getUserInfo();
      if (response.statusCode == 200) {
        final user = response.responseJson['data']?['user'];
        if (user != null) {
          final name = '${user['firstname'] ?? ''} ${user['lastname'] ?? ''}'.trim();
          final dialCode = user['dial_code']?.toString() ?? '';
          final mobile = user['mobile']?.toString() ?? '';
          if (name.isNotEmpty) _nameCtrl.text = name;
          if (mobile.isNotEmpty) _phoneCtrl.text = dialCode.isNotEmpty ? '+$dialCode$mobile' : mobile;
          if (_addressCtrl.text.trim().isEmpty && user['address'] != null && user['address'].toString().trim().isNotEmpty) {
            _addressCtrl.text = user['address'].toString();
          }
        }
      }
    } catch (_) {}

    if (_addressCtrl.text.trim().isEmpty) {
      if (c.currentDeliveryAddress.isNotEmpty && c.currentDeliveryAddress != 'Ubicación actual detectada') {
        _addressCtrl.text = c.currentDeliveryAddress;
        _deliveryLat = c.userLat;
        _deliveryLng = c.userLng;
      } else {
        await c.detectUserLocation();
        if (c.currentDeliveryAddress.isNotEmpty && c.currentDeliveryAddress != 'Ubicación actual detectada') {
          _addressCtrl.text = c.currentDeliveryAddress;
          _deliveryLat = c.userLat;
          _deliveryLng = c.userLng;
        }
      }
    }

    if ((_deliveryLat == null || _deliveryLng == null) && c.userLat != null && c.userLng != null) {
      _deliveryLat = c.userLat;
      _deliveryLng = c.userLng;
    }

    if (_deliveryLat != null && _deliveryLng != null) {
      c.setDeliveryAddress(_addressCtrl.text, lat: _deliveryLat, lng: _deliveryLng);
      await c.estimateDeliveryFee(
        storeId: widget.store.id ?? 0,
        deliveryLat: _deliveryLat!,
        deliveryLng: _deliveryLng!,
      );
    }

    if (mounted) setState(() {});
  }

  bool _canPlaceOrder(DeliveryController c, StoreModel store) {
    if (c.cartItems.isEmpty) {
      _showValidationError('Tu carrito está vacío');
      return false;
    }
    if (!store.isOpenNow) {
      _showValidationError('La tienda está cerrada en este momento');
      return false;
    }
    final minOrder = store.minOrderAmount ?? 0;
    if (minOrder > 0 && c.cartSubtotal < minOrder) {
      _showValidationError('El pedido mínimo es S/ ${minOrder.toStringAsFixed(2)}');
      return false;
    }
    if (_addressCtrl.text.trim().isEmpty) {
      _showValidationError('Ingresa la dirección de entrega');
      return false;
    }
    if (c.deliveryCoverageError != null) {
      _showValidationError(c.deliveryCoverageError!);
      return false;
    }
    if (_nameCtrl.text.trim().isEmpty) {
      _showValidationError('Ingresa el nombre de contacto');
      return false;
    }
    if (_phoneCtrl.text.trim().isEmpty) {
      _showValidationError('Ingresa el teléfono de contacto');
      return false;
    }
    if (c.gateways.isNotEmpty && _selectedGateway == null) {
      _showValidationError('Selecciona un método de pago');
      return false;
    }
    if (_selectedGateway?.isCash == true) {
      final cashAmount = double.tryParse(_cashPayAmountCtrl.text.trim()) ?? 0;
      final totalCheck = c.cartSubtotal + c.estimatedDeliveryFee + _selectedTip - _discount;
      if (cashAmount < totalCheck) {
        _showValidationError('Indica con cuánto pagará el usuario');
        return false;
      }
    }
    return true;
  }

  void _showValidationError(String message) {
    Get.snackbar(
      'Revisa tu pedido',
      message,
      backgroundColor: MyColor.redCancelTextColor,
      colorText: Colors.white,
      icon: const Icon(Icons.warning_rounded, color: Colors.white),
    );
  }

  void _applyCoupon() async {
    final code = _couponCtrl.text.trim();
    if (code.isEmpty) return;
    setState(() => _applyingCoupon = true);
    final c = Get.find<DeliveryController>();
    bool ok = await c.applyDeliveryCoupon(code, c.cartSubtotal);
    if (ok && mounted) {
      setState(() {
        _discount = c.couponDiscount;
        _applyingCoupon = false;
      });
      Get.snackbar('Cupón aplicado', 'Descuento: -S/ ${c.couponDiscount.toStringAsFixed(2)}', backgroundColor: const Color(0xFF10B981), colorText: Colors.white);
    } else {
      setState(() => _applyingCoupon = false);
      Get.snackbar('Error', 'Cupón inválido o vencido', backgroundColor: MyColor.redCancelTextColor, colorText: Colors.white);
    }
  }

  String _formatSchedule() {
    final d = _scheduledDate;
    final t = _scheduledTime;
    if (d == null) return 'Ahora';
    final months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return '${d.day} ${months[d.month - 1]}${t != null ? '  ·  ${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}' : ''}';
  }

  Future<void> _pickSchedule(BuildContext context) async {
    final now = DateTime.now();
    final date = await showDatePicker(
      context: context,
      initialDate: now.add(const Duration(hours: 1)),
      firstDate: now,
      lastDate: now.add(const Duration(days: 7)),
    );
    if (date == null || !mounted) return;
    final time = await showTimePicker(
      context: context,
      initialTime: TimeOfDay(hour: now.hour + 1, minute: 0),
    );
    if (time == null) return;
    setState(() {
      _scheduledDate = DateTime(date.year, date.month, date.day, time.hour, time.minute);
      _scheduledTime = time;
    });
  }

  /// Returns a distinct brand color for each known payment method.
  Color _gatewayColor(GatewayModel gw) {
    final name = (gw.name ?? '').toLowerCase();
    if (name.contains('efectivo') || gw.isCash) return const Color(0xFF10B981); // green
    if (name.contains('yape')) return const Color(0xFF7C3AED); // purple
    if (name.contains('plin')) return const Color(0xFF0EA5E9); // sky blue
    return const Color(0xFF6C63FF); // indigo fallback
  }

  /// Returns a distinct icon for each known payment method.
  IconData _gatewayIcon(GatewayModel gw) {
    final name = (gw.name ?? '').toLowerCase();
    if (name.contains('efectivo') || gw.isCash) return Icons.payments_rounded;
    if (name.contains('yape')) return Icons.smartphone_rounded;
    if (name.contains('plin')) return Icons.qr_code_scanner_rounded;
    return Icons.credit_card_rounded;
  }

  /// Calculates the MercadoPago gateway fee for [baseTotal].
  double _calcGatewayFee(double baseTotal) {
    if (_selectedGateway == null || !_isMercadoPagoGateway(_selectedGateway!)) return 0;
    final pct = _selectedGateway!.percentCharge ?? 0;
    final fix = _selectedGateway!.fixedCharge ?? 0;
    if (pct <= 0 && fix <= 0) return 0;
    final totalWithFee = pct >= 100 ? baseTotal + fix : (baseTotal + fix) / (1 - (pct / 100));
    return totalWithFee - baseTotal;
  }

  /// Whether [gw] represents a MercadoPago payment method (by name or currency).
  bool _isMercadoPagoGateway(GatewayModel gw) {
    final n = (gw.name ?? '').toLowerCase();
    final c = (gw.currency ?? '').toLowerCase();
    return n == 'mercadopago' || n.contains('mercadopago') || c.contains('mercadopago') || n == 'tarjeta de débito / crédito';
  }

  /// Formats the MercadoPago commission string using the real server gateway charges.
  String _formatMercadoPagoCommission(List<GatewayModel> allGateways) {
    // Prefer a gateway with non-null charges (server), fall back to any matching gateway
    GatewayModel? mp = allGateways.cast<GatewayModel?>().firstWhere(
          (g) => g != null && _isMercadoPagoGateway(g) && g.percentCharge != null,
          orElse: () => null,
        );
    mp ??= allGateways.cast<GatewayModel?>().firstWhere(
          (g) => g != null && _isMercadoPagoGateway(g),
          orElse: () => null,
        );
    final pct = mp?.percentCharge;
    final fix = mp?.fixedCharge;
    final pctStr = pct != null ? pct.toStringAsFixed(1) : '0';
    final fixStr = fix != null ? fix.toStringAsFixed(2) : '0.00';
    return 'Se aplicará un recargo del $pctStr% + S/ $fixStr por comisión de la pasarela de pago';
  }
}

// ── Shared UI Components ──

class _SectionCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? subtitle;
  final Color color;
  final Widget child;

  const _SectionCard({
    required this.icon,
    required this.title,
    this.subtitle,
    required this.color,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 12, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Section header
          Padding(
            padding: const EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space16, Dimensions.space16, Dimensions.space12),
            child: Row(
              children: [
                Container(
                  height: 34,
                  width: 34,
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(icon, color: color, size: 18),
                ),
                const SizedBox(width: Dimensions.space10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: boldDefault.copyWith(fontSize: 15, color: MyColor.primaryTextColor)),
                      if (subtitle != null) Text(subtitle!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 11)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Divider(height: 1, color: MyColor.neutral200),
          Padding(
            padding: const EdgeInsets.all(Dimensions.space16),
            child: child,
          ),
        ],
      ),
    );
  }
}

class _OrderItemRow extends StatelessWidget {
  final CartItemModel item;
  const _OrderItemRow({required this.item});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: Dimensions.space10),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                height: 24,
                width: 24,
                decoration: BoxDecoration(
                  color: MyColor.primaryColor,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Center(
                  child: Text(
                    '${item.quantity}',
                    style: regularSmall.copyWith(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w700),
                  ),
                ),
              ),
              const SizedBox(width: Dimensions.space10),
              Expanded(
                child: Text(
                  item.product.name ?? '',
                  style: regularDefault.copyWith(color: MyColor.primaryTextColor, height: 1.3),
                ),
              ),
              Text(
                'S/ ${item.totalPrice.toStringAsFixed(2)}',
                style: semiBoldSmall.copyWith(color: MyColor.primaryColor),
              ),
            ],
          ),
          if (item.selectedVariation != null)
            Padding(
              padding: const EdgeInsets.only(left: 34, top: 2),
              child: Text('• ${item.selectedVariation!.name}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
            ),
          if (item.selectedAddons.isNotEmpty)
            ...item.selectedAddons.map((a) => Padding(
                  padding: const EdgeInsets.only(left: 34, top: 2),
                  child: Text('+ ${a.name}', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                )),
        ],
      ),
    );
  }
}

class _SummaryRow extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;

  const _SummaryRow({required this.label, required this.value, this.valueColor});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(label, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
        const Spacer(),
        Text(value, style: regularDefault.copyWith(color: valueColor ?? MyColor.primaryTextColor)),
      ],
    );
  }
}

class _PremiumTextField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String hint;
  final IconData icon;
  final int maxLines;
  final TextInputType? keyboardType;

  const _PremiumTextField({
    required this.controller,
    required this.label,
    required this.hint,
    required this.icon,
    this.maxLines = 1,
    this.keyboardType,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: semiBoldSmall.copyWith(color: MyColor.primaryTextColor, fontSize: 12)),
        const SizedBox(height: 6),
        Container(
          decoration: BoxDecoration(
            color: const Color(0xFFF7F8FA),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: MyColor.neutral200),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: EdgeInsets.fromLTRB(Dimensions.space12, maxLines > 1 ? Dimensions.space12 : 0, 0, 0),
                child: Icon(icon, size: 18, color: MyColor.bodyMutedTextColor),
              ),
              Expanded(
                child: TextField(
                  controller: controller,
                  maxLines: maxLines,
                  keyboardType: keyboardType,
                  style: regularDefault.copyWith(color: MyColor.primaryTextColor),
                  decoration: InputDecoration(
                    hintText: hint,
                    hintStyle: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                    border: InputBorder.none,
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: 12),
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
