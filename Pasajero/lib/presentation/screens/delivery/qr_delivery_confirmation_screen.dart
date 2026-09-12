import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';

class QrDeliveryConfirmationScreen extends StatelessWidget {
  final String orderNo;
  final String pinCode;
  final String? courierName;
  const QrDeliveryConfirmationScreen({
    super.key,
    required this.orderNo,
    required this.pinCode,
    this.courierName,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Confirmación de entrega', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
      ),
      body: Center(
        child: Padding(
          padding: EdgeInsets.all(Dimensions.space20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                padding: EdgeInsets.all(Dimensions.space20),
                decoration: BoxDecoration(
                  color: MyColor.colorWhite,
                  borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                  boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 12, offset: const Offset(0, 4))],
                ),
                child: Column(children: [
                  Container(
                    padding: EdgeInsets.all(Dimensions.space16),
                    decoration: BoxDecoration(
                      color: MyColor.colorWhite,
                      borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                      border: Border.all(color: MyColor.borderColor.withValues(alpha: 0.5)),
                    ),
                    child: CustomPaint(
                      size: Size(200, 200),
                      painter: _QrPainter(data: 'LIZTOGO:${orderNo}:${pinCode}'),
                    ),
                  ),
                  SizedBox(height: Dimensions.space16),
                  Text('Pedido: $orderNo', style: boldLarge),
                  SizedBox(height: Dimensions.space8),
                  Container(
                    padding: EdgeInsets.symmetric(horizontal: Dimensions.space20, vertical: Dimensions.space12),
                    decoration: BoxDecoration(
                      color: MyColor.primaryColor.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                    ),
                    child: Text(pinCode,
                        style: TextStyle(
                          fontSize: 36,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 12,
                          color: MyColor.primaryColor,
                        )),
                  ),
                  SizedBox(height: Dimensions.space8),
                  Text('PIN de verificación', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                  if (courierName != null) ...[
                    SizedBox(height: Dimensions.space12),
                    Text('Repartidor: $courierName', style: regularDefault),
                  ],
                ]),
              ),
              SizedBox(height: Dimensions.space24),
              Icon(Icons.info_outline_rounded, size: 20, color: MyColor.bodyMutedTextColor),
              SizedBox(height: Dimensions.space4),
              Text(
                'Muestra este código al repartidor para verificar la entrega.',
                textAlign: TextAlign.center,
                style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _QrPainter extends CustomPainter {
  final String data;
  _QrPainter({required this.data});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()..color = const Color(0xFF1A1A2E);
    final moduleCount = 21.0;
    final moduleSize = size.width / moduleCount;

    // Generate a deterministic pattern from the data hash
    final hash = data.hashCode;
    final random = _PseudoRandom(hash);

    // Draw outer border
    final borderPaint = Paint()
      ..color = const Color(0xFF1A1A2E)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3;
    canvas.drawRect(Rect.fromLTWH(0, 0, size.width, size.height), borderPaint);

    // Generate QR-like pattern
    for (int row = 0; row < moduleCount.toInt(); row++) {
      for (int col = 0; col < moduleCount.toInt(); col++) {
        // Finder patterns (3 corners)
        bool isFinder = (row < 7 && col < 7) || (row < 7 && col > 13) || (row > 13 && col < 7);
        bool isFinderBorder = (row < 8 && col < 8) || (row < 8 && col > 12) || (row > 12 && col < 8);

        bool fill;
        if (isFinderBorder && !isFinder) {
          fill = (row == 0 || row == 7 || col == 0 || col == 7) && !(row == 0 && col == 0) && !(row == 7 && col == 0) && !(row == 0 && col == 7);
          fill = fill && (row < 8 && col < 8 || row < 8 && col > 12 || row > 12 && col < 8);
        } else if (isFinder) {
          fill = (row == 0 || row == 6 || col == 0 || col == 6) && row < 7 && col < 7;
        } else {
          fill = random.nextBool();
        }

        if (fill) {
          canvas.drawRect(
            Rect.fromLTWH(col * moduleSize, row * moduleSize, moduleSize, moduleSize),
            paint,
          );
        }
      }
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

class _PseudoRandom {
  int _seed;
  _PseudoRandom(this._seed);

  int next() {
    _seed = (_seed * 1103515245 + 12345) & 0x7fffffff;
    return _seed;
  }

  bool nextBool() => next() % 2 == 0;
}
