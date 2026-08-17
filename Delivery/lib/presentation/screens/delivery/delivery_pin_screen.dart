import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/presentation/components/buttons/rounded_button.dart';

class PinInputWidget extends StatelessWidget {
  final int length;
  final Function(String) onComplete;
  final bool isError;
  final String? errorMessage;

  const PinInputWidget({
    super.key,
    this.length = 4,
    required this.onComplete,
    this.isError = false,
    this.errorMessage,
  });

  @override
  Widget build(BuildContext context) {
    return _PinInputStateful(
      length: length,
      onComplete: onComplete,
      isError: isError,
      errorMessage: errorMessage,
    );
  }
}

class _PinInputStateful extends StatefulWidget {
  final int length;
  final Function(String) onComplete;
  final bool isError;
  final String? errorMessage;

  const _PinInputStateful({
    required this.length,
    required this.onComplete,
    required this.isError,
    this.errorMessage,
  });

  @override
  State<_PinInputStateful> createState() => _PinInputStatefulState();
}

class _PinInputStatefulState extends State<_PinInputStateful> {
  String _pin = '';

  void _onKey(String key) {
    if (_pin.length < widget.length) {
      _pin += key;
      if (_pin.length == widget.length) {
        widget.onComplete(_pin);
      }
      setState(() {});
    }
  }

  void _onDelete() {
    if (_pin.isNotEmpty) {
      _pin = _pin.substring(0, _pin.length - 1);
      setState(() {});
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(widget.length, (i) {
            return Container(
              margin: EdgeInsets.symmetric(horizontal: Dimensions.space8),
              width: 52, height: 60,
              decoration: BoxDecoration(
                color: widget.isError ? MyColor.redCancelTextColor.withValues(alpha: 0.1) : MyColor.colorWhite,
                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                border: Border.all(
                  color: widget.isError
                      ? MyColor.redCancelTextColor
                      : i < _pin.length
                          ? MyColor.primaryColor
                          : MyColor.borderColor,
                  width: 2,
                ),
              ),
              child: Center(
                child: i < _pin.length
                    ? Container(
                        width: 16, height: 16,
                        decoration: BoxDecoration(
                          color: widget.isError ? MyColor.redCancelTextColor : MyColor.primaryColor,
                          shape: BoxShape.circle,
                        ),
                      )
                    : null,
              ),
            );
          }),
        ),
        if (widget.errorMessage != null) ...[
          SizedBox(height: Dimensions.space8),
          Text(widget.errorMessage!, style: regularSmall.copyWith(color: MyColor.redCancelTextColor)),
        ],
        SizedBox(height: Dimensions.space16),
        SizedBox(
          width: 280,
          child: GridView.count(
            shrinkWrap: true,
            crossAxisCount: 3,
            mainAxisSpacing: Dimensions.space8,
            crossAxisSpacing: Dimensions.space8,
            childAspectRatio: 1.6,
            physics: const NeverScrollableScrollPhysics(),
            children: [
              ...List.generate(9, (i) => _keyButton('${i + 1}')),
              _keyButton('', icon: Icons.fingerprint_rounded),
              _keyButton('0'),
              _keyButton('', icon: Icons.backspace_rounded, onTap: _onDelete),
            ],
          ),
        ),
      ],
    );
  }

  Widget _keyButton(String label, {IconData? icon, VoidCallback? onTap}) {
    return GestureDetector(
      onTap: onTap ?? (label.isNotEmpty ? () => _onKey(label) : null),
      child: Container(
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
          border: Border.all(color: MyColor.borderColor.withValues(alpha: 0.5)),
        ),
        child: Center(
          child: icon != null
              ? Icon(icon, size: 24, color: MyColor.primaryTextColor)
              : Text(label, style: TextStyle(fontSize: 22, fontWeight: FontWeight.w600, color: MyColor.primaryTextColor)),
        ),
      ),
    );
  }
}

class DeliveryPinScreen extends StatefulWidget {
  final int orderId;
  final String? courierName;
  const DeliveryPinScreen({super.key, required this.orderId, this.courierName});

  @override
  State<DeliveryPinScreen> createState() => _DeliveryPinScreenState();
}

class _DeliveryPinScreenState extends State<DeliveryPinScreen> {
  bool _verified = false;
  bool _error = false;

  void _verifyPin(String pin) {
    // Simulate pin verification - would call API in production
    Future.delayed(const Duration(milliseconds: 500), () {
      if (pin == '1234') {
        setState(() { _verified = true; _error = false; });
        Get.snackbar('Verificado', 'Entrega confirmada correctamente',
            backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
        Future.delayed(const Duration(seconds: 1), () => Get.back(result: true));
      } else {
        setState(() => _error = true);
        Future.delayed(const Duration(seconds: 1), () {
          if (mounted) setState(() => _error = false);
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Verificar entrega', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
      ),
      body: Center(
        child: Padding(
          padding: EdgeInsets.all(Dimensions.space20),
          child: _verified
              ? Column(mainAxisSize: MainAxisSize.min, children: [
                  Icon(Icons.check_circle_rounded, size: 80, color: const Color(0xFF10B981)),
                  SizedBox(height: Dimensions.space16),
                  Text('Entrega verificada', style: boldLarge.copyWith(fontSize: Dimensions.fontExtraLarge)),
                ])
              : Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.lock_rounded, size: 48, color: MyColor.primaryColor),
                    SizedBox(height: Dimensions.space16),
                    Text('Ingresa el PIN de verificación', style: boldLarge),
                    SizedBox(height: Dimensions.space4),
                    Text(
                      'Pide al cliente que te muestre el código de 4 dígitos para confirmar la entrega.',
                      textAlign: TextAlign.center,
                      style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                    ),
                    SizedBox(height: Dimensions.space24),
                    PinInputWidget(
                      onComplete: _verifyPin,
                      isError: _error,
                      errorMessage: _error ? 'PIN incorrecto. Intenta de nuevo.' : null,
                    ),
                  ],
                ),
        ),
      ),
    );
  }
}
