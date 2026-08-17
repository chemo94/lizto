import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:pinput/pinput.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';

/// PIN verification dialog for delivery confirmation.
/// Shows a 4-digit PIN input when the courier needs to verify
/// the delivery PIN with the customer.
class PinVerificationDialog extends StatefulWidget {
  final int jobId;
  final String jobType;
  final String? orderNo;
  final VoidCallback onVerified;

  const PinVerificationDialog({
    super.key,
    required this.jobId,
    required this.jobType,
    this.orderNo,
    required this.onVerified,
  });

  @override
  State<PinVerificationDialog> createState() => _PinVerificationDialogState();
}

class _PinVerificationDialogState extends State<PinVerificationDialog> {
  final pinController = TextEditingController();
  final focusNode = FocusNode();
  bool _isVerifying = false;
  String? _errorText;

  @override
  void dispose() {
    pinController.dispose();
    focusNode.dispose();
    super.dispose();
  }

  Future<void> _verifyPin() async {
    final pin = pinController.text.trim();
    if (pin.length != 4) {
      setState(() => _errorText = 'Ingresa los 4 dígitos del PIN');
      return;
    }

    setState(() {
      _isVerifying = true;
      _errorText = null;
    });

    final controller = Get.find<CourierController>();
    final success = await controller.verifyPin(widget.jobId, pin, type: widget.jobType);

    if (success) {
      if (mounted) Navigator.of(context).pop(true);
      widget.onVerified();
    } else {
      setState(() {
        _isVerifying = false;
        _errorText = controller.pinError ?? 'PIN incorrecto. Intenta de nuevo.';
      });
      pinController.clear();
      focusNode.requestFocus();
    }
  }

  @override
  Widget build(BuildContext context) {
    final defaultPinTheme = PinTheme(
      width: 56,
      height: 56,
      textStyle: const TextStyle(fontSize: 22, fontWeight: FontWeight.w600, color: Color(0xFF1E293B)),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE2E8F0), width: 2),
      ),
    );

    final focusedPinTheme = defaultPinTheme.copyDecorationWith(
      border: Border.all(color: const Color(0xFF8B5CF6), width: 2),
      borderRadius: BorderRadius.circular(12),
    );

    final errorPinTheme = defaultPinTheme.copyDecorationWith(
      border: Border.all(color: Colors.red, width: 2),
      borderRadius: BorderRadius.circular(12),
    );

    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Header
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFF8B5CF6).withOpacity(0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.lock_outline, color: Color(0xFF8B5CF6), size: 32),
            ),
            const SizedBox(height: 16),
            const Text(
              'Verificar PIN de Entrega',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF1E293B)),
            ),
            const SizedBox(height: 8),
            Text(
              widget.orderNo != null
                  ? 'Orden: ${widget.orderNo}\nSolicita el PIN al cliente'
                  : 'Solicita el PIN al cliente para confirmar la entrega',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 13, color: Color(0xFF64748B)),
            ),
            const SizedBox(height: 24),

            // PIN Input
            Pinput(
              controller: pinController,
              focusNode: focusNode,
              length: 4,
              defaultPinTheme: _errorText != null ? errorPinTheme : defaultPinTheme,
              focusedPinTheme: focusedPinTheme,
              showCursor: true,
              onCompleted: (_) => _verifyPin(),
              keyboardType: TextInputType.number,
            ),

            if (_errorText != null) ...[
              const SizedBox(height: 12),
              Text(
                _errorText!,
                style: const TextStyle(color: Colors.red, fontSize: 13, fontWeight: FontWeight.w500),
                textAlign: TextAlign.center,
              ),
            ],

            const SizedBox(height: 24),

            // Buttons
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: _isVerifying ? null : () => Navigator.of(context).pop(false),
                    style: OutlinedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      side: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    child: const Text('Cancelar', style: TextStyle(color: Color(0xFF64748B))),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: _isVerifying ? null : _verifyPin,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF8B5CF6),
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      elevation: 0,
                    ),
                    child: _isVerifying
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : const Text('Verificar PIN', style: TextStyle(fontWeight: FontWeight.w600)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

/// Helper function to show the PIN verification dialog.
/// Returns true if PIN was verified successfully.
Future<bool?> showPinVerificationDialog(BuildContext context, {
  required int jobId,
  required String jobType,
  String? orderNo,
  required VoidCallback onVerified,
}) {
  return showGeneralDialog<bool>(
    context: context,
    barrierDismissible: false,
    barrierLabel: 'PIN Verification',
    pageBuilder: (context, animation, secondaryAnimation) {
      return PinVerificationDialog(
        jobId: jobId,
        jobType: jobType,
        orderNo: orderNo,
        onVerified: onVerified,
      );
    },
    transitionBuilder: (context, animation, secondaryAnimation, child) {
      return FadeTransition(
        opacity: animation,
        child: ScaleTransition(
          scale: CurvedAnimation(parent: animation, curve: Curves.easeOutBack),
          child: child,
        ),
      );
    },
  );
}
