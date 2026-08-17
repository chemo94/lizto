import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/repo/seller/seller_repo.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SellerWalletScreen extends StatefulWidget {
  const SellerWalletScreen({super.key});

  @override
  State<SellerWalletScreen> createState() => _SellerWalletScreenState();
}

class _SellerWalletScreenState extends State<SellerWalletScreen> {
  late SellerRepo _sellerRepo;

  double? _balance;
  List<Map<String, dynamic>> _transactions = [];

  bool _loadingBalance = false;
  bool _loadingTransactions = false;
  bool _submittingWithdraw = false;

  final _amountCtrl = TextEditingController();
  final _cbuCtrl = TextEditingController();
  final _cuentaCtrl = TextEditingController();
  final _bancoCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _sellerRepo = SellerRepo(prefs: Get.find<SharedPreferences>());
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadBalance();
      _loadTransactions();
    });
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    _cbuCtrl.dispose();
    _cuentaCtrl.dispose();
    _bancoCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadBalance() async {
    setState(() => _loadingBalance = true);
    try {
      final response = await _sellerRepo.getWalletBalance();
      if (response.statusCode == 200) {
        final json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          final data = json['data'];
          final balance = data['balance'] ?? data['available_balance'] ?? data['amount'];
          setState(() => _balance = _parseDouble(balance));
        }
      }
    } catch (_) {}
    setState(() => _loadingBalance = false);
  }

  Future<void> _loadTransactions() async {
    setState(() => _loadingTransactions = true);
    try {
      final response = await _sellerRepo.getWalletTransactions();
      if (response.statusCode == 200) {
        final json = response.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          final raw = json['data']['transactions'] ?? json['data'] ?? [];
          final list = raw is Map ? raw['data'] ?? [] : raw;
          if (list is List) {
            setState(() => _transactions = list.cast<Map<String, dynamic>>());
          }
        }
      }
    } catch (_) {}
    setState(() => _loadingTransactions = false);
  }

  double? _parseDouble(dynamic v) {
    if (v == null) return null;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString());
  }

  bool _isCredit(Map<String, dynamic> tx) {
    final type = tx['trx_type']?.toString() ?? '';
    final amount = _parseDouble(tx['amount']) ?? 0;
    return type == '+' || amount >= 0;
  }

  @override
  Widget build(BuildContext context) {
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
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(Dimensions.largeRadius),
            ),
            child: Column(children: [
              Text('Saldo disponible', style: regularDefault.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.8))),
              SizedBox(height: Dimensions.space8),
              _loadingBalance
                  ? CircularProgressIndicator(color: MyColor.colorWhite)
                  : Text('S/ ${_balance?.toStringAsFixed(2) ?? "0.00"}',
                      style: TextStyle(fontSize: 40, fontWeight: FontWeight.bold, color: MyColor.colorWhite)),
            ]),
          ),
          SizedBox(height: Dimensions.space16),

          // ── Withdraw Button ──
          GestureDetector(
            onTap: _showWithdrawSheet,
            child: Container(
              width: double.infinity,
              padding: EdgeInsets.symmetric(vertical: Dimensions.space14),
              decoration: BoxDecoration(
                color: MyColor.primaryColor,
                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                boxShadow: [
                  BoxShadow(
                    color: MyColor.primaryColor.withValues(alpha: 0.35),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.account_balance_wallet_outlined, color: MyColor.colorWhite, size: 20),
                  SizedBox(width: Dimensions.space8),
                  Text('Solicitar Retiro', style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: 15)),
                ],
              ),
            ),
          ),
          SizedBox(height: Dimensions.space20),

          // ── Transactions ──
          Text('Historial de transacciones', style: boldLarge),
          SizedBox(height: Dimensions.space8),
          if (_loadingTransactions)
            const Center(child: CircularProgressIndicator())
          else if (_transactions.isEmpty)
            Container(
              padding: EdgeInsets.all(Dimensions.space20),
              decoration: BoxDecoration(
                color: MyColor.colorWhite,
                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
              ),
              child: Center(
                child: Text('Sin transacciones', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
              ),
            )
          else
            ..._transactions.map((tx) => _buildTransactionRow(tx)),
        ],
      ),
    );
  }

  Widget _buildTransactionRow(Map<String, dynamic> tx) {
    final isCredit = _isCredit(tx);
    final amount = _parseDouble(tx['amount']) ?? 0;
    final date = tx['created_at']?.toString() ?? '';
    final remark = tx['remark']?.toString() ?? tx['details']?.toString() ?? 'Transacción';
    final details = tx['details']?.toString() ?? '';

    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space6),
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 4, offset: const Offset(0, 1))],
      ),
      child: Row(children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: (isCredit ? const Color(0xFF10B981) : MyColor.redCancelTextColor).withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
          ),
          child: Icon(
            isCredit ? Icons.arrow_downward_rounded : Icons.arrow_upward_rounded,
            color: isCredit ? const Color(0xFF10B981) : MyColor.redCancelTextColor,
            size: 20,
          ),
        ),
        SizedBox(width: Dimensions.space12),
        Expanded(
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(_remarkLabel(remark), style: boldDefault),
            SizedBox(height: 2),
            Text(details, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor), maxLines: 1, overflow: TextOverflow.ellipsis),
          ]),
        ),
        Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
          Text(
            '${isCredit ? "+" : "-"} S/ ${amount.toStringAsFixed(2)}',
            style: boldDefault.copyWith(color: isCredit ? const Color(0xFF10B981) : MyColor.redCancelTextColor),
          ),
          SizedBox(height: 2),
          Text(_formatDate(date), style: regularSmall.copyWith(fontSize: 10, color: MyColor.bodyMutedTextColor)),
        ]),
      ]),
    );
  }

  String _remarkLabel(String remark) {
    switch (remark) {
      case 'deposit':
        return 'Recarga';
      case 'withdraw':
        return 'Retiro';
      case 'payment':
        return 'Pago';
      case 'refund':
        return 'Reembolso';
      case 'earning':
        return 'Ganancia';
      case 'bonus':
        return 'Bono';
      case 'commission':
        return 'Comisión';
      case 'deduction':
        return 'Deducción';
      default:
        return remark;
    }
  }

  String _formatDate(String dt) {
    if (dt.isEmpty) return '';
    try {
      return dt.substring(0, 10);
    } catch (_) {
      return '';
    }
  }

  void _showWithdrawSheet() {
    _amountCtrl.clear();
    _cbuCtrl.clear();
    _cuentaCtrl.clear();
    _bancoCtrl.clear();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setSheetState) {
            return Container(
              padding: EdgeInsets.fromLTRB(
                Dimensions.space20,
                Dimensions.space16,
                Dimensions.space20,
                MediaQuery.of(ctx).viewInsets.bottom + Dimensions.space32,
              ),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(color: MyColor.neutral200, borderRadius: BorderRadius.circular(2)),
                  ),
                  const SizedBox(height: 16),
                  Text('Solicitar Retiro', style: boldLarge.copyWith(fontSize: 18)),
                  Text('Ingresa los datos para tu retiro', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                  const SizedBox(height: 20),
                  TextField(
                    controller: _amountCtrl,
                    decoration: InputDecoration(
                      prefixText: 'S/ ',
                      labelText: 'Monto a retirar',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                    keyboardType: TextInputType.numberWithOptions(decimal: true),
                  ),
                  SizedBox(height: Dimensions.space12),
                  TextField(
                    controller: _cbuCtrl,
                    decoration: InputDecoration(
                      labelText: 'CBU',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                    keyboardType: TextInputType.number,
                  ),
                  SizedBox(height: Dimensions.space12),
                  TextField(
                    controller: _cuentaCtrl,
                    decoration: InputDecoration(
                      labelText: 'Número de cuenta',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                  ),
                  SizedBox(height: Dimensions.space12),
                  TextField(
                    controller: _bancoCtrl,
                    decoration: InputDecoration(
                      labelText: 'Banco',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    ),
                  ),
                  SizedBox(height: Dimensions.space20),
                  GestureDetector(
                    onTap: _submittingWithdraw
                        ? null
                        : () async {
                            final amountText = _amountCtrl.text.trim();
                            final amount = double.tryParse(amountText);
                            if (amount == null || amount <= 0) {
                              Get.snackbar('Error', 'Ingresa un monto válido',
                                  backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
                              return;
                            }
                            if (_balance != null && amount > _balance!) {
                              Get.snackbar('Error', 'Saldo insuficiente',
                                  backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
                              return;
                            }
                            setSheetState(() {});
                            setState(() => _submittingWithdraw = true);
                            try {
                              final response = await _sellerRepo.requestWithdraw({
                                'amount': amount,
                                'cbu': _cbuCtrl.text.trim(),
                                'account_number': _cuentaCtrl.text.trim(),
                                'bank_name': _bancoCtrl.text.trim(),
                              });
                              if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
                                Get.back();
                                Get.snackbar('Solicitud enviada', 'Tu retiro ha sido registrado',
                                    backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
                                _loadBalance();
                                _loadTransactions();
                              } else {
                                Get.snackbar('Error', response.responseJson['message']?.toString() ?? 'No se pudo procesar el retiro',
                                    backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
                              }
                            } catch (_) {
                              Get.snackbar('Error', 'Error de conexión',
                                  backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
                            }
                            setState(() => _submittingWithdraw = false);
                          },
                    child: Container(
                      width: double.infinity,
                      padding: EdgeInsets.symmetric(vertical: Dimensions.space14),
                      decoration: BoxDecoration(
                        color: MyColor.primaryColor,
                        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                      ),
                      child: Center(
                        child: _submittingWithdraw
                            ? SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(color: MyColor.colorWhite, strokeWidth: 2),
                              )
                            : Text('Confirmar Retiro', style: boldDefault.copyWith(color: MyColor.colorWhite)),
                      ),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }
}
