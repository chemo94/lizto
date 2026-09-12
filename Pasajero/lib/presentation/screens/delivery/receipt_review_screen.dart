import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/model/delivery/shopping_models.dart';
import 'package:liztogo/data/repo/delivery/shopping_repo.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'package:liztogo/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';

class ReceiptReviewScreen extends StatefulWidget {
  final int favorId;
  final double actualTotal;
  final String? receiptUrl;
  final String? storePhotoUrl;
  final ShoppingBudget? budget;

  const ReceiptReviewScreen({
    super.key,
    required this.favorId,
    required this.actualTotal,
    this.receiptUrl,
    this.storePhotoUrl,
    this.budget,
  });

  @override
  State<ReceiptReviewScreen> createState() => _ReceiptReviewScreenState();
}

class _ReceiptReviewScreenState extends State<ReceiptReviewScreen> {
  late final ShoppingRepo _repo;
  bool _isProcessing = false;

  @override
  void initState() {
    super.initState();
    _repo = ShoppingRepo(apiClient: Get.find<ApiClient>());
  }

  Future<void> _confirmPurchase(bool approved) async {
    setState(() => _isProcessing = true);
    try {
      final res = await _repo.confirmPurchase(widget.favorId, {
        'approved': approved,
        'payment_method': 'cash',
      });
      if (res.statusCode == 200 && res.responseJson?['status'] == 'success') {
        if (approved) {
          CustomSnackBar.success(successList: ['Compra confirmada. El repartidor esta en camino.']);
        } else {
          CustomSnackBar.success(successList: ['Compra cancelada']);
        }
        Navigator.pop(context);
        Navigator.pop(context); // Go back to tracking
      } else {
        CustomSnackBar.error(errorList: ['Error al procesar']);
      }
    } catch (e) {
      CustomSnackBar.error(errorList: ['Error de conexion']);
    }
    setState(() => _isProcessing = false);
  }

  @override
  Widget build(BuildContext context) {
    final overage = widget.budget?.isOverBudget == true ? widget.budget!.overageAmount : 0.0;

    return Scaffold(
      backgroundColor: MyColor.getScreenBgColor(),
      body: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: ListView(
              padding: EdgeInsets.all(Dimensions.space16),
              children: [
                _buildReceiptImages(),
                SizedBox(height: Dimensions.space16),
                _buildTotalCard(overage),
                SizedBox(height: Dimensions.space16),
                _buildItemsSummary(),
                SizedBox(height: Dimensions.space20),
                RoundedButton(
                  text: 'Confirmar y pagar S/ ${widget.actualTotal.toStringAsFixed(2)}',
                  isLoading: _isProcessing,
                  press: () => _confirmPurchase(true),
                ),
                SizedBox(height: Dimensions.space12),
                RoundedButton(
                  text: 'Rechazar compra',
                  press: () {
                    if (!_isProcessing) _confirmPurchase(false);
                  },
                  isDisabled: _isProcessing,
                  isOutlined: true,
                ),
                SizedBox(height: Dimensions.space40),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: EdgeInsets.only(
        top: MediaQuery.of(context).padding.top + Dimensions.space12,
        bottom: Dimensions.space20,
      ),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [MyColor.primaryColor, MyColor.primaryColor.withValues(alpha: 0.85)],
        ),
      ),
      child: Row(
        children: [
          IconButton(
            icon: const Icon(Icons.arrow_back_ios_rounded, color: MyColor.colorWhite, size: 20),
            onPressed: () => Get.back(),
          ),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Revisar compra', style: boldExtraLarge.copyWith(color: MyColor.colorWhite, fontSize: 20)),
                SizedBox(height: 2),
                Text('Verifica los productos y el total', style: regularDefault.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.8), fontSize: 13)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildReceiptImages() {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Comprobantes', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
          SizedBox(height: Dimensions.space12),
          if (widget.storePhotoUrl != null) ...[
            Text('Foto de productos', style: boldDefault.copyWith(fontSize: 13)),
            SizedBox(height: Dimensions.space8),
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: MyImageWidget(
                imageUrl: widget.storePhotoUrl!,
                height: 200,
                width: double.infinity,
                boxFit: BoxFit.cover,
              ),
            ),
          ],
          if (widget.receiptUrl != null) ...[
            SizedBox(height: Dimensions.space16),
            Text('Ticket/Factura', style: boldDefault.copyWith(fontSize: 13)),
            SizedBox(height: Dimensions.space8),
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: MyImageWidget(
                imageUrl: widget.receiptUrl!,
                height: 200,
                width: double.infinity,
                boxFit: BoxFit.cover,
              ),
            ),
          ],
          if (widget.storePhotoUrl == null && widget.receiptUrl == null)
            Center(
              child: Padding(
                padding: EdgeInsets.all(Dimensions.space20),
                child: Text('No hay comprobantes disponibles', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildTotalCard(double overage) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: overage > 0 ? Colors.orange.withValues(alpha: 0.05) : MyColor.primaryColor.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        border: Border.all(
          color: overage > 0 ? Colors.orange.withValues(alpha: 0.3) : MyColor.primaryColor.withValues(alpha: 0.2),
        ),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Total de la compra', style: boldLarge),
              Text(
                'S/ ${widget.actualTotal.toStringAsFixed(2)}',
                style: boldExtraLarge.copyWith(color: MyColor.primaryColor, fontSize: 24),
              ),
            ],
          ),
          if (widget.budget != null) ...[
            SizedBox(height: Dimensions.space10),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text('Presupuesto maximo', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                Text('S/ ${widget.budget!.maxProductBudget?.toStringAsFixed(2) ?? '0.00'}', style: boldDefault),
              ],
            ),
            if (overage > 0) ...[
              SizedBox(height: Dimensions.space8),
              Container(
                padding: EdgeInsets.all(Dimensions.space10),
                decoration: BoxDecoration(
                  color: Colors.orange.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(Icons.warning_amber_rounded, color: Colors.orange, size: 20),
                    SizedBox(width: Dimensions.space8),
                    Expanded(
                      child: Text(
                        'Excede el presupuesto en S/ ${overage.toStringAsFixed(2)}',
                        style: boldDefault.copyWith(color: Colors.orange.shade800, fontSize: 13),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ],
      ),
    );
  }

  Widget _buildItemsSummary() {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Resumen', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
          SizedBox(height: Dimensions.space12),
          _buildSummaryRow('Productos', 'S/ ${widget.actualTotal.toStringAsFixed(2)}'),
          if (widget.budget?.maxDeliveryFee != null) _buildSummaryRow('Delivery fee', 'S/ ${widget.budget!.maxDeliveryFee!.toStringAsFixed(2)}'),
          Divider(color: Colors.grey.shade200),
          _buildSummaryRow(
            'Total a pagar',
            'S/ ${(widget.actualTotal + (widget.budget?.maxDeliveryFee ?? 0)).toStringAsFixed(2)}',
            isBold: true,
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryRow(String label, String value, {bool isBold = false}) {
    return Padding(
      padding: EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: isBold ? boldDefault : regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          Text(value, style: isBold ? boldExtraLarge.copyWith(color: MyColor.primaryColor) : boldDefault),
        ],
      ),
    );
  }
}
