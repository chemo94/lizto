class WalletModel {
  double? balance;
  double? blockedBalance;
  double? availableBalance;

  WalletModel({this.balance, this.blockedBalance, this.availableBalance});

  factory WalletModel.fromJson(Map<String, dynamic> json) => WalletModel(
        balance: _parse(json["balance"]),
        blockedBalance: _parse(json["blocked_balance"]),
        availableBalance: _parse(json["available_balance"]),
      );

  static double? _parse(dynamic v) {
    if (v == null) return null;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString());
  }
}

class WalletTransactionModel {
  int? id;
  String? trx;
  double? amount;
  double? postBalance;
  double? charge;
  String? trxType;
  String? details;
  String? remark;
  String? status;
  String? createdAt;

  WalletTransactionModel({
    this.id, this.trx, this.amount, this.postBalance, this.charge,
    this.trxType, this.details, this.remark, this.status, this.createdAt,
  });

  factory WalletTransactionModel.fromJson(Map<String, dynamic> json) => WalletTransactionModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        trx: json["trx"]?.toString(),
        amount: _p(json["amount"]),
        postBalance: _p(json["post_balance"]),
        charge: _p(json["charge"]),
        trxType: json["trx_type"]?.toString(),
        details: json["details"]?.toString(),
        remark: json["remark"]?.toString(),
        status: json["status"]?.toString(),
        createdAt: json["created_at"]?.toString(),
      );

  bool get isCredit => trxType == '+';
  bool get isDebit => trxType == '-';

  String get remarkLabel {
    switch (remark) {
      case 'deposit': return 'Recarga';
      case 'withdraw': return 'Retiro';
      case 'payment': return 'Pago';
      case 'refund': return 'Reembolso';
      case 'earning': return 'Ganancia';
      case 'bonus': return 'Bono';
      case 'commission': return 'Comisión';
      case 'deduction': return 'Deducción';
      default: return remark ?? 'Transacción';
    }
  }

  static double? _p(dynamic v) {
    if (v == null) return null;
    if (v is double) return v;
    if (v is int) return v.toDouble();
    return double.tryParse(v.toString());
  }
}
