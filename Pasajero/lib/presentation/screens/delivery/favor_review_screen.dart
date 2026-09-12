import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/favor_controller.dart';
import 'package:liztogo/presentation/components/buttons/rounded_button.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';

class FavorReviewScreen extends StatefulWidget {
  final int favorId;
  final String orderNo;
  final String? courierName;
  const FavorReviewScreen({super.key, required this.favorId, required this.orderNo, this.courierName});

  @override
  State<FavorReviewScreen> createState() => _FavorReviewScreenState();
}

class _FavorReviewScreenState extends State<FavorReviewScreen> {
  double _rating = 5;
  final _reviewCtrl = TextEditingController();

  @override
  void dispose() {
    _reviewCtrl.dispose();
    super.dispose();
  }

  String get _label {
    switch (_rating.round()) {
      case 1:
        return 'Muy mala';
      case 2:
        return 'Mala';
      case 3:
        return 'Regular';
      case 4:
        return 'Buena';
      case 5:
        return 'Excelente';
      default:
        return '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Calificar favor', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
      ),
      body: ListView(
        padding: EdgeInsets.all(Dimensions.space20),
        children: [
          Center(
            child: Column(children: [
              CircleAvatar(
                radius: 40,
                backgroundColor: const Color(0xFFF59E0B).withValues(alpha: 0.1),
                child: Icon(Icons.volunteer_activism_rounded, color: const Color(0xFFF59E0B), size: 40),
              ),
              SizedBox(height: Dimensions.space12),
              Text(widget.courierName ?? 'Repartidor', style: boldLarge),
              SizedBox(height: Dimensions.space4),
              Text('Favor ${widget.orderNo}', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
            ]),
          ),
          SizedBox(height: Dimensions.space24),
          Container(
            padding: EdgeInsets.all(Dimensions.space20),
            decoration: BoxDecoration(
              color: MyColor.colorWhite,
              borderRadius: BorderRadius.circular(Dimensions.largeRadius),
              boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
            ),
            child: Column(children: [
              Text('¿Cómo fue el servicio?', style: boldLarge),
              SizedBox(height: Dimensions.space16),
              RatingBar.builder(
                initialRating: _rating,
                minRating: 1,
                direction: Axis.horizontal,
                itemCount: 5,
                itemSize: 42,
                itemPadding: EdgeInsets.symmetric(horizontal: 4),
                itemBuilder: (_, __) => Icon(Icons.star_rounded, color: const Color(0xFFF59E0B)),
                onRatingUpdate: (r) => setState(() => _rating = r),
              ),
              SizedBox(height: Dimensions.space4),
              Text(_label, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
              SizedBox(height: Dimensions.space16),
              TextField(
                controller: _reviewCtrl,
                decoration: InputDecoration(
                  hintText: 'Cuéntanos tu experiencia (opcional)',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                ),
                maxLines: 3,
              ),
            ]),
          ),
          SizedBox(height: Dimensions.space32),
          RoundedButton(
            text: 'Enviar calificación',
            press: () async {
              bool ok = await Get.find<FavorController>().reviewFavor(
                widget.favorId,
                rating: _rating,
                review: _reviewCtrl.text.trim(),
              );
              if (ok && mounted) {
                Get.back();
                Get.snackbar('Gracias', 'Tu calificación ha sido enviada', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
              }
            },
            isOutlined: false,
          ),
        ],
      ),
    );
  }
}
