import 'package:flutter/material.dart';
import 'package:liztogo_repartidor/core/utils/dimensions.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/model/delivery/courier_models.dart';

class CourierOrderRadarCard extends StatefulWidget {
  final CourierJobModel job;
  final Function(CourierJobModel) onAccept;
  final Function(CourierJobModel)? onReject;

  const CourierOrderRadarCard({
    super.key,
    required this.job,
    required this.onAccept,
    this.onReject,
  });

  @override
  State<CourierOrderRadarCard> createState() => _CourierOrderRadarCardState();
}

class _CourierOrderRadarCardState extends State<CourierOrderRadarCard> {
  double _dragPosition = 0.0;
  bool _isAccepted = false;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final totalEarning = widget.job.totalEarning ?? widget.job.deliveryFee ?? 0.0;
    final isExpress = widget.job.isExpress;

    return Container(
      margin: const EdgeInsets.symmetric(
        horizontal: Dimensions.space15,
        vertical: Dimensions.space8,
      ),
      padding: const EdgeInsets.all(Dimensions.space15),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1E293B) : MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.space15),
        border: Border.all(
          color: isExpress
              ? const Color(0xFFF59E0B)
              : (isDark ? Colors.white10 : MyColor.borderColor),
          width: isExpress ? 1.5 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: isDark
                ? Colors.black.withValues(alpha: 0.3)
                : MyColor.shadowColor.withValues(alpha: 0.6),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Top Row: Type & Earning Badge
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: Dimensions.space10,
                      vertical: Dimensions.space4,
                    ),
                    decoration: BoxDecoration(
                      color: widget.job.type == 'favor'
                          ? const Color(0xFF8B5CF6).withValues(alpha: 0.15)
                          : MyColor.primaryColor.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(Dimensions.space8),
                    ),
                    child: Text(
                      widget.job.type == 'favor' ? 'ENVIÓ RAPIDO' : 'REPARTO',
                      style: boldDefault.copyWith(
                        color: widget.job.type == 'favor'
                            ? const Color(0xFF8B5CF6)
                            : MyColor.primaryColor,
                        fontSize: Dimensions.fontExtraSmall,
                      ),
                    ),
                  ),
                  if (isExpress) ...[
                    const SizedBox(width: Dimensions.space6),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: Dimensions.space8,
                        vertical: Dimensions.space4,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(Dimensions.space8),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.bolt_rounded, size: 12, color: Color(0xFFF59E0B)),
                          Text(
                            'EXPRÉS',
                            style: boldDefault.copyWith(
                              color: const Color(0xFFF59E0B),
                              fontSize: Dimensions.fontExtraSmall,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (widget.job.points != null && widget.job.points! > 0) ...[
                        Container(
                          margin: const EdgeInsets.only(right: 6),
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF59E0B).withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            '+${widget.job.points} pts',
                            style: boldDefault.copyWith(
                              fontSize: 10,
                              color: const Color(0xFFD97706),
                            ),
                          ),
                        ),
                      ],
                      Text(
                        'Ganancia est.',
                        style: regularDefault.copyWith(
                          color: isDark ? Colors.grey[400] : MyColor.bodyMutedTextColor,
                          fontSize: Dimensions.fontExtraSmall,
                        ),
                      ),
                    ],
                  ),
                  Text(
                    'S/ ${totalEarning.toStringAsFixed(2)}',
                    style: boldOverLarge.copyWith(
                      color: isDark ? const Color(0xFF34D399) : MyColor.primaryColor,
                      fontSize: 20,
                    ),
                  ),
                  if (widget.job.fareBreakdown?.tip != null && widget.job.fareBreakdown!.tip! > 0)
                    Text(
                      '+ S/ ${widget.job.fareBreakdown!.tip!.toStringAsFixed(2)} propina',
                      style: semiBoldDefault.copyWith(
                        fontSize: 10,
                        color: const Color(0xFFCA8A04),
                      ),
                    ),
                ],
              ),
            ],
          ),
          const SizedBox(height: Dimensions.space12),

          // Business / Sender Name
          if (widget.job.storeName != null && widget.job.storeName!.isNotEmpty) ...[
            Row(
              children: [
                const Icon(Icons.storefront_rounded, size: 18, color: MyColor.primaryColor),
                const SizedBox(width: Dimensions.space8),
                Expanded(
                  child: Text(
                    widget.job.storeName!,
                    style: boldDefault.copyWith(
                      color: isDark ? Colors.white : MyColor.primaryTextColor,
                      fontSize: Dimensions.fontMedium,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            const SizedBox(height: Dimensions.space10),
          ],

          // Addresses Route Layout (Pickup -> Delivery)
          Container(
            padding: const EdgeInsets.all(Dimensions.space10),
            decoration: BoxDecoration(
              color: isDark ? Colors.black.withValues(alpha: 0.2) : MyColor.neutral50,
              borderRadius: BorderRadius.circular(Dimensions.space10),
            ),
            child: Column(
              children: [
                // Pickup
                Row(
                  children: [
                    const Icon(Icons.circle, color: Color(0xFF10B981), size: 12),
                    const SizedBox(width: Dimensions.space10),
                    Expanded(
                      child: Text(
                        widget.job.pickupAddress ?? 'Punto de recogida',
                        style: regularDefault.copyWith(
                          color: isDark ? Colors.grey[300] : MyColor.bodyTextColor,
                          fontSize: Dimensions.fontSmall,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const Padding(
                  padding: EdgeInsets.only(left: 5, top: 2, bottom: 2),
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: SizedBox(
                      height: 12,
                      child: VerticalDivider(color: Colors.grey, width: 1, thickness: 1),
                    ),
                  ),
                ),
                // Delivery
                Row(
                  children: [
                    const Icon(Icons.location_on_rounded, color: Colors.redAccent, size: 14),
                    const SizedBox(width: Dimensions.space10),
                    Expanded(
                      child: Text(
                        widget.job.deliveryAddress ?? 'Punto de entrega',
                        style: boldDefault.copyWith(
                          color: isDark ? Colors.white : MyColor.primaryTextColor,
                          fontSize: Dimensions.fontSmall,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: Dimensions.space15),

          // Slide to Accept Button Component
          LayoutBuilder(
            builder: (context, constraints) {
              final maxDrag = constraints.maxWidth - 54;
              return Container(
                height: 52,
                decoration: BoxDecoration(
                  color: isDark ? const Color(0xFF0F172A) : const Color(0xFFECFDF5),
                  borderRadius: BorderRadius.circular(26),
                  border: Border.all(
                    color: MyColor.primaryColor.withValues(alpha: 0.3),
                  ),
                ),
                child: Stack(
                  children: [
                    Center(
                      child: Text(
                        _isAccepted ? '¡PEDIDO ACEPTADO!' : 'DESLIZA PARA ACEPTAR  >>>',
                        style: boldDefault.copyWith(
                          color: _isAccepted
                              ? Colors.green
                              : MyColor.primaryColor.withValues(alpha: 0.8),
                          fontSize: Dimensions.fontDefault,
                          letterSpacing: 0.8,
                        ),
                      ),
                    ),
                    Positioned(
                      left: _dragPosition,
                      top: 3,
                      bottom: 3,
                      child: GestureDetector(
                        onHorizontalDragUpdate: (details) {
                          setState(() {
                            _dragPosition += details.delta.dx;
                            if (_dragPosition < 0) _dragPosition = 0;
                            if (_dragPosition > maxDrag) _dragPosition = maxDrag;
                          });
                        },
                        onHorizontalDragEnd: (details) {
                          if (_dragPosition >= maxDrag * 0.75) {
                            setState(() {
                              _dragPosition = maxDrag;
                              _isAccepted = true;
                            });
                            widget.onAccept(widget.job);
                          } else {
                            setState(() {
                              _dragPosition = 0;
                            });
                          }
                        },
                        child: Container(
                          width: 46,
                          height: 46,
                          decoration: const BoxDecoration(
                            color: MyColor.primaryColor,
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black26,
                                blurRadius: 6,
                                offset: Offset(2, 2),
                              ),
                            ],
                          ),
                          child: const Icon(
                            Icons.arrow_forward_rounded,
                            color: MyColor.colorWhite,
                            size: 24,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}
