import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/delivery_controller.dart';
import 'package:lizto_delivery/presentation/components/buttons/rounded_button.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';

class DeliveryReviewScreen extends StatefulWidget {
  final int orderId;
  final String orderNo;
  final String? courierName;
  final String? courierImage;
  final String imagePath;
  const DeliveryReviewScreen({
    super.key,
    required this.orderId,
    required this.orderNo,
    this.courierName,
    this.courierImage,
    this.imagePath = '',
  });

  @override
  State<DeliveryReviewScreen> createState() => _DeliveryReviewScreenState();
}

class _DeliveryReviewScreenState extends State<DeliveryReviewScreen> {
  double _rating = 5;
  final _reviewCtrl = TextEditingController();
  final List<_QuickTag> _tags = [
    _QuickTag('Amable', Icons.sentiment_satisfied_alt),
    _QuickTag('Rápido', Icons.speed),
    _QuickTag('Correcto', Icons.verified),
    _QuickTag('Empaque seguro', Icons.inventory_2),
    _QuickTag('Buen servicio', Icons.thumb_up_alt),
    _QuickTag('Profesional', Icons.workspace_premium),
  ];
  final Set<String> _selectedTags = {};

  @override
  void dispose() {
    _reviewCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final c = Get.find<DeliveryController>();

    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Calificar servicio', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
        leading: IconButton(icon: const Icon(Icons.close, color: MyColor.colorWhite), onPressed: () => Get.back()),
      ),
      body: ListView(
        padding: EdgeInsets.all(Dimensions.space20),
        children: [
          Center(
            child: Column(children: [
              if (widget.courierImage != null && widget.courierImage!.isNotEmpty)
                ClipRRect(
                  borderRadius: BorderRadius.circular(50),
                  child: MyImageWidget(
                    imageUrl: '${widget.imagePath}/${widget.courierImage}',
                    height: 80,
                    width: 80,
                    isProfile: true,
                    boxFit: BoxFit.cover,
                  ),
                )
              else
                CircleAvatar(
                  radius: 40,
                  backgroundColor: MyColor.primaryColor.withValues(alpha: 0.1),
                  child: Icon(Icons.person, color: MyColor.primaryColor, size: 40),
                ),
              SizedBox(height: Dimensions.space12),
              Text(widget.courierName ?? 'Repartidor', style: boldExtraLarge),
              SizedBox(height: Dimensions.space4),
              Text('Pedido ${widget.orderNo}', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
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
              Text('¿Cómo fue tu experiencia?', style: boldLarge),
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
              SizedBox(height: Dimensions.space6),
              Text(_ratingLabel, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor, fontSize: Dimensions.fontDefault)),
              SizedBox(height: Dimensions.space20),
              Text('¿Qué destacas?', style: boldDefault.copyWith(color: MyColor.getTextColor())),
              SizedBox(height: Dimensions.space10),
              Wrap(
                spacing: Dimensions.space8,
                runSpacing: Dimensions.space8,
                children: _tags.map((tag) {
                  final selected = _selectedTags.contains(tag.label);
                  return GestureDetector(
                    onTap: () => setState(() => selected ? _selectedTags.remove(tag.label) : _selectedTags.add(tag.label)),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      padding: EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space8),
                      decoration: BoxDecoration(
                        color: selected ? MyColor.primaryColor.withValues(alpha: 0.1) : MyColor.getScreenBgColor(),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: selected ? MyColor.primaryColor : Colors.transparent, width: 1),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(tag.icon, size: 16, color: selected ? MyColor.primaryColor : MyColor.bodyMutedTextColor),
                          SizedBox(width: Dimensions.space4),
                          Text(tag.label, style: regularDefault.copyWith(color: selected ? MyColor.primaryColor : MyColor.getTextColor(), fontSize: Dimensions.fontSmall)),
                        ],
                      ),
                    ),
                  );
                }).toList(),
              ),
              SizedBox(height: Dimensions.space20),
              TextField(
                controller: _reviewCtrl,
                decoration: InputDecoration(
                  hintText: 'Cuéntanos tu experiencia (opcional)',
                  hintStyle: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                  filled: true,
                  fillColor: MyColor.getScreenBgColor(),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius), borderSide: BorderSide.none),
                  contentPadding: EdgeInsets.all(Dimensions.space14),
                ),
                maxLines: 4,
              ),
            ]),
          ),
          SizedBox(height: Dimensions.space32),
          RoundedButton(
            text: 'Enviar calificación',
            press: () async {
              bool ok = await c.reviewDelivery(
                widget.orderId,
                rating: _rating,
                review: _reviewCtrl.text.trim(),
              );
              if (ok && mounted) {
                Get.back();
                Get.snackbar('Gracias', 'Tu calificación ha sido enviada',
                    backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
              }
            },
            isOutlined: false,
          ),
          SizedBox(height: Dimensions.space40),
        ],
      ),
    );
  }

  String get _ratingLabel {
    switch (_rating.round()) {
      case 1: return 'Muy mala';
      case 2: return 'Mala';
      case 3: return 'Regular';
      case 4: return 'Buena';
      case 5: return 'Excelente';
      default: return '';
    }
  }
}

class _QuickTag {
  final String label;
  final IconData icon;
  _QuickTag(this.label, this.icon);
}
