import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/wallet_controller.dart';
import 'package:lizto_delivery/data/repo/delivery/wallet_repo.dart';
import 'package:lizto_delivery/data/services/api_client.dart';
import 'package:lizto_delivery/presentation/components/buttons/rounded_button.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final _amountCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<WalletController>()) {
      Get.put(WalletController(walletRepo: WalletRepo(apiClient: Get.find<ApiClient>())));
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<WalletController>();
      c.loadBalance();
      c.loadTransactions();
    });
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<WalletController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Mi Billetera', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
          ),
          body: ListView(
            padding: EdgeInsets.all(Dimensions.space16),
            children: [
              // ── Balance Card ──
              Container(
                padding: EdgeInsets.all(Dimensions.space20),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: [MyColor.primaryColor, MyColor.primaryColor.withValues(alpha: 0.7)],
                    begin: Alignment.topLeft, end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                ),
                child: Column(children: [
                  Text('Saldo disponible', style: regularDefault.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.8))),
                  SizedBox(height: Dimensions.space8),
                  c.loadingBalance
                      ? CircularProgressIndicator(color: MyColor.colorWhite)
                      : Text('S/ ${c.wallet?.availableBalance?.toStringAsFixed(2) ?? "0.00"}',
                          style: TextStyle(fontSize: 40, fontWeight: FontWeight.bold, color: MyColor.colorWhite)),
                  SizedBox(height: Dimensions.space16),
                  Row(mainAxisAlignment: MainAxisAlignment.spaceAround, children: [
                    _balanceStat('Total', 'S/ ${c.wallet?.balance?.toStringAsFixed(2) ?? "0.00"}'),
                    _balanceStat('Bloqueado', 'S/ ${c.wallet?.blockedBalance?.toStringAsFixed(2) ?? "0.00"}'),
                  ]),
                ]),
              ),
              SizedBox(height: Dimensions.space16),

              // ── Add Funds ──
              Container(
                padding: EdgeInsets.all(Dimensions.space16),
                decoration: BoxDecoration(
                  color: MyColor.colorWhite,
                  borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                  boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
                ),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Agregar fondos', style: boldLarge),
                  SizedBox(height: Dimensions.space8),
                  TextField(
                    controller: _amountCtrl,
                    decoration: InputDecoration(
                      prefixText: 'S/ ',
                      hintText: 'Monto a recargar',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                    keyboardType: TextInputType.numberWithOptions(decimal: true),
                  ),
                  SizedBox(height: Dimensions.space12),
                  RoundedButton(
                    text: 'Recargar',
                    isLoading: c.addingFunds,
                    press: () async {
                      double? amt = double.tryParse(_amountCtrl.text.trim());
                      if (amt == null || amt <= 0) {
                        Get.snackbar('Error', 'Ingresa un monto válido',
                            backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
                        return;
                      }
                      bool ok = await c.addFunds(amt);
                      if (ok) {
                        _amountCtrl.clear();
                        Get.snackbar('Recarga exitosa', 'Fondos agregados correctamente',
                            backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
                      }
                    },
                    isOutlined: false,
                  ),
                ]),
              ),
              SizedBox(height: Dimensions.space20),

              // ── Transactions ──
              Text('Historial de transacciones', style: boldLarge),
              SizedBox(height: Dimensions.space8),
              if (c.loadingTransactions)
                const Center(child: CircularProgressIndicator())
              else if (c.transactions.isEmpty)
                Container(
                  padding: EdgeInsets.all(Dimensions.space20),
                  decoration: BoxDecoration(
                    color: MyColor.colorWhite,
                    borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                  ),
                  child: Center(child: Text('Sin transacciones', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor))),
                )
              else
                ...c.transactions.map((tx) => Container(
                      margin: EdgeInsets.only(bottom: Dimensions.space6),
                      padding: EdgeInsets.all(Dimensions.space12),
                      decoration: BoxDecoration(
                        color: MyColor.colorWhite,
                        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 4, offset: const Offset(0, 1))],
                      ),
                      child: Row(children: [
                        Container(
                          width: 40, height: 40,
                          decoration: BoxDecoration(
                            color: (tx.isCredit ? const Color(0xFF10B981) : MyColor.redCancelTextColor).withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                          ),
                          child: Icon(
                            tx.isCredit ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
                            color: tx.isCredit ? const Color(0xFF10B981) : MyColor.redCancelTextColor,
                            size: 20,
                          ),
                        ),
                        SizedBox(width: Dimensions.space12),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text(tx.remarkLabel, style: boldDefault),
                            SizedBox(height: 2),
                            Text(tx.details ?? '', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 1, overflow: TextOverflow.ellipsis),
                          ]),
                        ),
                        Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
                          Text(
                            '${tx.isCredit ? "+" : "-"} S/ ${tx.amount?.toStringAsFixed(2) ?? "0.00"}',
                            style: boldDefault.copyWith(color: tx.isCredit ? const Color(0xFF10B981) : MyColor.redCancelTextColor),
                          ),
                          SizedBox(height: 2),
                          Text(_formatDate(tx.createdAt), style: regularSmall.copyWith(fontSize: 10, color: MyColor.bodyMutedTextColor)),
                        ]),
                      ]),
                    )),
            ],
          ),
        );
      },
    );
  }

  Widget _balanceStat(String label, String value) {
    return Column(children: [
      Text(value, style: boldDefault.copyWith(color: MyColor.colorWhite)),
      SizedBox(height: 2),
      Text(label, style: regularSmall.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.7))),
    ]);
  }

  String _formatDate(String? dt) {
    if (dt == null) return '';
    try { return dt.substring(0, 10); } catch (_) { return ''; }
  }
}
