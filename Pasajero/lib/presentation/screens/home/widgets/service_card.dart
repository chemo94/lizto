import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:flutter/services.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/data/controller/map/home_map_controller.dart';
import 'package:liztogo/data/model/global/app/app_service_model.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';

class ServiceCard extends StatefulWidget {
  final AppService service;
  final HomeController controller;

  const ServiceCard({
    super.key,
    required this.service,
    required this.controller,
  });

  @override
  State<ServiceCard> createState() => _ServiceCardState();
}

class _ServiceCardState extends State<ServiceCard> {
  bool _isEditingPrice = false;
  late final FocusNode _priceFocusNode;

  @override
  void initState() {
    super.initState();
    _priceFocusNode = FocusNode();
  }

  @override
  void dispose() {
    _priceFocusNode.dispose();
    super.dispose();
  }

  void _startPriceEditing() {
    final controller = widget.controller;
    controller.amountController.text = controller.mainAmount.toStringAsFixed(2);
    setState(() => _isEditingPrice = true);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _priceFocusNode.requestFocus();
    });
  }

  void _savePrice() {
    final controller = widget.controller;
    final value = StringConverter.formatDouble(controller.amountController.text);
    final isMercadoPago = controller.selectedPaymentMethod.name?.toLowerCase() == 'mercadopago' || controller.selectedPaymentMethod.name?.toLowerCase() == 'mercado pago';
    final multiplier = isMercadoPago ? 1.05 : 1.0;
    final minimum = StringConverter.formatDouble(
          controller.selectedService.minAmount?.toString() ?? controller.selectedService.cityMinFare?.toString() ?? '0',
        ) *
        multiplier;
    final maximum = StringConverter.formatDouble(
          controller.selectedService.maxAmount?.toString() ?? controller.selectedService.cityMaxFare?.toString() ?? '0',
        ) *
        multiplier;

    if (value <= 0 || (minimum > 0 && value < minimum) || (maximum > 0 && value > maximum)) {
      CustomSnackBar.error(
        errorList: [
          '${MyStrings.pleaseEnterMinimum.tr} ${controller.defaultCurrencySymbol}${minimum.toStringAsFixed(2)} to ${controller.defaultCurrencySymbol}${maximum.toStringAsFixed(2)}',
        ],
      );
      return;
    }

    controller.updateMainAmount(value);
    _priceFocusNode.unfocus();
    setState(() => _isEditingPrice = false);
  }

  @override
  Widget build(BuildContext context) {
    final service = widget.service;
    final controller = widget.controller;
    final isSelected = service.id == controller.selectedService.id;
    final isOfferService = service.allowOffer == true;
    final currency = controller.homeRepo.apiClient.getCurrency(isSymbol: true);
    final recommendedPrice = double.tryParse(
          service.recommendAmount ?? service.cityRecommendFare ?? '',
        ) ??
        0;
    final paymentName = controller.selectedPaymentMethod.name?.toLowerCase();
    final isMercadoPago = paymentName == 'mercadopago' || paymentName == 'mercado pago';
    final price = isSelected && isOfferService ? controller.mainAmount : (isMercadoPago ? recommendedPrice * 1.05 : recommendedPrice);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(20),
        onTap: () => controller.selectService(service, shouldLoadFare: false),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          constraints: const BoxConstraints(minHeight: 86),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          decoration: BoxDecoration(
            color: MyColor.cardBgColor,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: isSelected ? MyColor.primaryColor : MyColor.neutral100,
              width: isSelected ? 1.5 : 1,
            ),
            boxShadow: [
              BoxShadow(
                color: MyColor.primaryTextColor.withValues(alpha: 0.035),
                blurRadius: 10,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Row(
            children: [
              SizedBox(
                width: 86,
                height: 64,
                child: MyImageWidget(
                  imageUrl: '${UrlContainer.domainUrl}/${controller.serviceImagePath}/${service.image}',
                  height: 64,
                  width: 86,
                  radius: 8,
                  boxFit: BoxFit.contain,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            service.name?.tr ?? '',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: semiBoldSmall.copyWith(
                              fontSize: 16,
                              color: MyColor.primaryTextColor,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        if (isSelected) ...[
                          const SizedBox(width: 6),
                          _PassengerCapacityBadge(service: service),
                          const SizedBox(width: 4),
                        ],
                        if (isSelected)
                          const Icon(
                            Icons.check_circle_rounded,
                            size: 19,
                            color: MyColor.primaryColor,
                          ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    _buildNearestDriverEta(),
                    const SizedBox(height: 5),
                    if (isOfferService)
                      _buildOfferControls(currency, price, isSelected)
                    else
                      Text(
                        '$currency${price.toStringAsFixed(2)}',
                        style: boldDefault.copyWith(
                          fontSize: 16,
                          color: MyColor.primaryTextColor,
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNearestDriverEta() {
    if (!Get.isRegistered<HomeMapController>()) {
      return Text(
        'Buscando conductor cercano',
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: regularDefault.copyWith(fontSize: 12, color: MyColor.bodyMutedTextColor),
      );
    }

    return GetBuilder<HomeMapController>(
      builder: (mapController) {
        if (mapController.nearbyDrivers.isEmpty) {
          return Text(
            'Buscando conductor cercano',
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: regularDefault.copyWith(fontSize: 12, color: MyColor.bodyMutedTextColor),
          );
        }

        final nearest = mapController.nearbyDrivers.reduce(
          (current, next) => next.distanceKm < current.distanceKm ? next : current,
        );
        final estimatedMinutes = (nearest.distanceKm * 3).ceil().clamp(1, 99);
        return Row(
          children: [
            const Icon(Icons.access_time_rounded, size: 13, color: MyColor.bodyMutedTextColor),
            const SizedBox(width: 3),
            Flexible(
              child: Text(
                'Conductor más cercano · ~$estimatedMinutes min',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: regularDefault.copyWith(fontSize: 12, color: MyColor.bodyMutedTextColor),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildOfferControls(String currency, double price, bool isSelected) {
    final controller = widget.controller;
    return Row(
      children: [
        _OfferStepButton(
          label: '−0.5',
          onTap: () {
            if (isSelected) controller.adjustSelectedServiceOffer(-0.5);
          },
          enabled: isSelected,
        ),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 6),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Flexible(
                  child: _isEditingPrice && isSelected
                      ? TextField(
                          controller: controller.amountController,
                          focusNode: _priceFocusNode,
                          textAlign: TextAlign.center,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          inputFormatters: [
                            FilteringTextInputFormatter.allow(RegExp(r'[0-9.,]')),
                            LengthLimitingTextInputFormatter(8),
                          ],
                          onSubmitted: (_) => _savePrice(),
                          style: boldDefault.copyWith(fontSize: 15, color: MyColor.primaryTextColor),
                          decoration: InputDecoration(
                            prefixText: currency,
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(vertical: 4),
                            border: UnderlineInputBorder(
                              borderSide: BorderSide(color: MyColor.primaryColor.withValues(alpha: 0.5)),
                            ),
                          ),
                        )
                      : Text(
                          '$currency${price.toStringAsFixed(2)}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: boldDefault.copyWith(fontSize: 15, color: MyColor.primaryTextColor),
                        ),
                ),
                if (isSelected && !_isEditingPrice) ...[
                  const SizedBox(width: 4),
                  InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: _startPriceEditing,
                    child: const Padding(
                      padding: EdgeInsets.all(4),
                      child: Icon(Icons.edit_rounded, size: 14, color: MyColor.bodyMutedTextColor),
                    ),
                  ),
                ] else if (isSelected && _isEditingPrice) ...[
                  const SizedBox(width: 2),
                  InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: _savePrice,
                    child: const Padding(
                      padding: EdgeInsets.all(4),
                      child: Icon(Icons.check_rounded, size: 16, color: MyColor.primaryColor),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
        _OfferStepButton(
          label: '+0.5',
          onTap: () {
            if (isSelected) controller.adjustSelectedServiceOffer(0.5);
          },
          enabled: isSelected,
        ),
      ],
    );
  }
}

class _PassengerCapacityBadge extends StatelessWidget {
  final AppService service;

  const _PassengerCapacityBadge({required this.service});

  @override
  Widget build(BuildContext context) {
    final capacity = int.tryParse(service.maxPassengers ?? '') ?? 12;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
      decoration: BoxDecoration(
        color: MyColor.primaryColor.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.people_alt_outlined, size: 13, color: MyColor.primaryColor),
          const SizedBox(width: 3),
          Text(
            '$capacity',
            style: semiBoldSmall.copyWith(fontSize: 11, color: MyColor.primaryColor),
          ),
        ],
      ),
    );
  }
}

class _OfferStepButton extends StatelessWidget {
  final String label;
  final VoidCallback onTap;
  final bool enabled;

  const _OfferStepButton({
    required this.label,
    required this.onTap,
    required this.enabled,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 28,
      child: OutlinedButton(
        onPressed: enabled ? onTap : null,
        style: OutlinedButton.styleFrom(
          foregroundColor: enabled ? MyColor.primaryColor : MyColor.bodyMutedTextColor,
          side: BorderSide(
            color: enabled ? MyColor.primaryColor.withValues(alpha: 0.35) : MyColor.neutral200,
          ),
          padding: const EdgeInsets.symmetric(horizontal: 9),
          minimumSize: const Size(0, 28),
          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
          visualDensity: VisualDensity.compact,
          shape: const StadiumBorder(),
        ),
        child: Text(label, style: semiBoldSmall.copyWith(fontSize: 11)),
      ),
    );
  }
}
