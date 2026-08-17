import 'package:get/get.dart';
import 'package:lizto_delivery/core/helper/string_format_helper.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';
import 'package:lizto_delivery/data/model/delivery/wallet_model.dart';
import 'package:lizto_delivery/data/model/global/response_model/response_model.dart';
import 'package:lizto_delivery/data/repo/delivery/wallet_repo.dart';

class WalletController extends GetxController {
  final WalletRepo walletRepo;
  WalletController({required this.walletRepo});

  WalletModel? wallet;
  List<WalletTransactionModel> transactions = [];

  bool loadingBalance = false;
  bool loadingTransactions = false;
  bool addingFunds = false;

  Future<void> loadBalance() async {
    loadingBalance = true;
    update();
    try {
      ResponseModel response = await walletRepo.getBalance();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          wallet = WalletModel.fromJson(json['data']);
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingBalance = false;
    update();
  }

  Future<void> loadTransactions() async {
    loadingTransactions = true;
    update();
    try {
      ResponseModel response = await walletRepo.getTransactions();
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success && json['data'] != null) {
          var raw = json['data']['transactions'];
          var list = raw is Map ? raw['data'] ?? [] : raw;
          transactions = (list as List).map((x) => WalletTransactionModel.fromJson(x)).toList();
        }
      }
    } catch (e) {
      printX(e);
    }
    loadingTransactions = false;
    update();
  }

  Future<bool> addFunds(double amount) async {
    addingFunds = true;
    update();
    try {
      ResponseModel response = await walletRepo.addFunds(amount);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          await loadBalance();
          await loadTransactions();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    addingFunds = false;
    update();
    return false;
  }

  Future<bool> withdraw(double amount, int methodCode) async {
    try {
      ResponseModel response = await walletRepo.withdrawFunds(amount, methodCode);
      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          await loadBalance();
          await loadTransactions();
          return true;
        }
      }
    } catch (e) {
      printX(e);
    }
    return false;
  }
}
