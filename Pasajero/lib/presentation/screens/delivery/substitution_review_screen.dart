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

class SubstitutionReviewScreen extends StatefulWidget {
  final int favorId;
  final List<ShoppingListItem> pendingItems;
  const SubstitutionReviewScreen({super.key, required this.favorId, required this.pendingItems});

  @override
  State<SubstitutionReviewScreen> createState() => _SubstitutionReviewScreenState();
}

class _SubstitutionReviewScreenState extends State<SubstitutionReviewScreen> {
  late final ShoppingRepo _repo;
  late List<ShoppingListItem> _items;
  bool _isProcessing = false;

  @override
  void initState() {
    super.initState();
    _repo = ShoppingRepo(apiClient: Get.find<ApiClient>());
    _items = List.from(widget.pendingItems);
  }

  Future<void> _approve(ShoppingListItem item, bool approved) async {
    setState(() => _isProcessing = true);
    try {
      final res = await _repo.approveSubstitution(widget.favorId, item.id!, {
        'approved': approved,
      });
      if (res.statusCode == 200 && res.responseJson?['status'] == 'success') {
        setState(() {
          _items.removeWhere((i) => i.id == item.id);
        });
        if (_items.isEmpty) {
          CustomSnackBar.success(successList: ['Todas las sustituciones revisadas']);
          Navigator.pop(context);
        }
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
    return Scaffold(
      backgroundColor: MyColor.getScreenBgColor(),
      body: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: _items.isEmpty
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.check_circle_outline, size: 64, color: MyColor.greenSuccessColor),
                        SizedBox(height: Dimensions.space16),
                        Text('Todas revisadas', style: boldLarge),
                      ],
                    ),
                  )
                : ListView.builder(
                    padding: EdgeInsets.all(Dimensions.space16),
                    itemCount: _items.length,
                    itemBuilder: (_, i) => _buildSubstitutionCard(_items[i]),
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
          colors: [Colors.orange.shade700, Colors.orange.shade500],
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
                Text('Sustituciones pendientes', style: boldExtraLarge.copyWith(color: MyColor.colorWhite, fontSize: 20)),
                SizedBox(height: 2),
                Text(
                  '${_items.length} producto(s) requieren tu revision',
                  style: regularDefault.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.8), fontSize: 13),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSubstitutionCard(ShoppingListItem item) {
    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space16),
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Original item
          Container(
            padding: EdgeInsets.all(Dimensions.space12),
            decoration: BoxDecoration(
              color: MyColor.redCancelTextColor.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
            ),
            child: Row(
              children: [
                Icon(Icons.close_rounded, color: MyColor.redCancelTextColor, size: 20),
                SizedBox(width: Dimensions.space10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('No encontrado', style: boldDefault.copyWith(color: MyColor.redCancelTextColor, fontSize: 12)),
                      Text(item.name ?? '', style: boldLarge),
                    ],
                  ),
                ),
              ],
            ),
          ),
          SizedBox(height: Dimensions.space12),
          // Arrow
          Center(
            child: Icon(Icons.arrow_downward_rounded, color: MyColor.primaryColor, size: 24),
          ),
          SizedBox(height: Dimensions.space12),
          // Substitute
          Container(
            padding: EdgeInsets.all(Dimensions.space12),
            decoration: BoxDecoration(
              color: MyColor.greenSuccessColor.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(Icons.check_circle_outline, color: MyColor.greenSuccessColor, size: 20),
                    SizedBox(width: Dimensions.space10),
                    Text('Sustituto propuesto', style: boldDefault.copyWith(color: MyColor.greenSuccessColor, fontSize: 12)),
                  ],
                ),
                SizedBox(height: Dimensions.space8),
                Text(item.substituteName ?? '', style: boldLarge),
                if (item.substituteNotes != null && item.substituteNotes!.isNotEmpty)
                  Padding(
                    padding: EdgeInsets.only(top: 4),
                    child: Text(item.substituteNotes!, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                  ),
                if (item.substitutePrice != null)
                  Padding(
                    padding: EdgeInsets.only(top: 4),
                    child: Text(
                      'S/ ${item.substitutePrice!.toStringAsFixed(2)}',
                      style: boldExtraLarge.copyWith(color: MyColor.primaryColor, fontSize: 18),
                    ),
                  ),
                if (item.substituteImageUrl != null)
                  Padding(
                    padding: EdgeInsets.only(top: Dimensions.space10),
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: MyImageWidget(
                        imageUrl: item.substituteImageUrl!,
                        height: 150,
                        width: double.infinity,
                        boxFit: BoxFit.cover,
                      ),
                    ),
                  ),
              ],
            ),
          ),
          SizedBox(height: Dimensions.space16),
          // Action buttons
          Row(
            children: [
              Expanded(
                child: RoundedButton(
                  text: 'Rechazar',
                  press: () {
                    if (!_isProcessing) _approve(item, false);
                  },
                  isDisabled: _isProcessing,
                  isOutlined: true,
                ),
              ),
              SizedBox(width: Dimensions.space12),
              Expanded(
                child: RoundedButton(
                  text: 'Aceptar',
                  press: () {
                    if (!_isProcessing) _approve(item, true);
                  },
                  isDisabled: _isProcessing,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
