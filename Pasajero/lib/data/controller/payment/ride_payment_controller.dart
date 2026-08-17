import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/helper/string_format_helper.dart';
import 'package:liztogo/core/route/route.dart';
import 'package:liztogo/core/utils/my_strings.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/core/utils/util.dart';
import 'package:liztogo/data/controller/coupon/coupon_controller.dart';
import 'package:liztogo/data/controller/ride/ride_details/ride_details_controller.dart';
import 'package:liztogo/data/model/payment/payment_insert_response_model.dart';
import 'package:liztogo/data/model/gateways/gateway_list_response_model.dart';
import 'package:liztogo/data/model/global/app/app_payment_method.dart';
import 'package:liztogo/data/model/global/app/ride_model.dart';
import 'package:liztogo/data/model/global/response_model/response_model.dart';
import 'package:liztogo/data/model/ride/ride_details_response_model.dart';
import 'package:liztogo/data/model/ride/ride_payment_response_model.dart';
import 'package:liztogo/data/model/webview/webview_model.dart';
import 'package:liztogo/data/repo/payment/payment_repo.dart';
import 'package:liztogo/presentation/components/snack_bar/show_custom_snackbar.dart';

class RidePaymentController extends GetxController {
  PaymentRepo repo;
  CouponController couponController;
  RidePaymentController({required this.repo, required this.couponController});

  bool isLoading = false;
  String imagePath = "";
  String driverImagePath = "";
  String defaultCurrency = "";
  String defaultCurrencySymbol = "";
  String username = "";
  String rideId = "-1";
  RideModel ride = RideModel(id: "-1");
  List<AppPaymentMethod> methodList = [];
  AppPaymentMethod selectedMethod = AppPaymentMethod(id: '-1');
  List<String> tipsList = [];
  TextEditingController tipsController = TextEditingController();
  Map<String, dynamic>? mpCheckoutData;


  void updateTips(String amount) {
    tipsController.text = amount;
    update();
  }

  Future<void> initialData(RideModel data) async {
    defaultCurrency = repo.apiClient.getCurrency();
    defaultCurrencySymbol = repo.apiClient.getCurrency(isSymbol: true);
    username = repo.apiClient.getUserName();
    ride = data;
    rideId = ride.id.toString();
    methodList = [];
    tipsController.text = '';
    tipsList = repo.apiClient.getTipsList();
    update();
    await getRideDetails();
    if (ride.id != '-1') {
      findSelectedGateway();
      if (ride.coupon != null) {
        couponController.updateCoupon(ride.coupon!);
      }
    }
  }

  Future<void> getRideDetails() async {
    isLoading = true;
    update();
    ResponseModel responseModel = await repo.getRidePaymentDetails(rideId);
    if (responseModel.statusCode == 200) {
      RidePaymentResponseModel model = RidePaymentResponseModel.fromJson((responseModel.responseJson));
      if (model.status == MyStrings.success) {
        driverImagePath = '${UrlContainer.domainUrl}/${model.data?.driverImage}';
        RideModel? tempRide = model.data?.ride;
        if (tempRide != null) {
          ride = tempRide;
          couponController.getCouponList(model.data?.coupons ?? []);
        }
        methodList.insertAll(0, MyUtils.getDefaultPaymentMethod());
        methodList.addAll(model.data?.gatewayCurrency ?? []);
        imagePath = '${UrlContainer.domainUrl}/${model.data?.gatewayImage}';
        update();
      } else {
        //   Get.back();
        CustomSnackBar.error(
          errorList: model.message ?? [MyStrings.somethingWentWrong],
        );
      }
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }
    isLoading = false;
    update();
  }

  Future<void> getPaymentList({bool isShouldLoading = true}) async {
    isLoading = isShouldLoading;
    update();
    try {
      ResponseModel responseModel = await repo.getPaymentList();
      if (responseModel.statusCode == 200) {
        GatewayListResponseModel model = GatewayListResponseModel.fromJson((responseModel.responseJson));
        if (model.status == "success") {
          methodList.addAll(model.data?.gatewayCurrency ?? []);
          methodList.insertAll(0, MyUtils.getDefaultPaymentMethod());
          update();
        } else {
          CustomSnackBar.error(
            errorList: model.message ?? [MyStrings.somethingWentWrong],
          );
        }
      } else {
        CustomSnackBar.error(errorList: [responseModel.message]);
      }
    } catch (e) {
      printX(e);
    }
    isLoading = false;
    update();
  }

  void findSelectedGateway() {
    selectedMethod = methodList.firstWhereOrNull(
          (AppPaymentMethod p) => p.id.toString() == ride.gatewayCurrencyId,
        ) ??
        MyUtils.getDefaultPaymentMethod()[0];

    update();
  }

  void updateSelectedGateway(AppPaymentMethod method) {
    selectedMethod = method;
    update();
    Get.back();
  }

  bool isSubmitBtnLoading = false;
  Future<void> submitPayment() async {
    isSubmitBtnLoading = true;
    update();
    try {
      ResponseModel responseModel = await repo.submitPayment(
        currency: selectedMethod.currency.toString(),
        type: selectedMethod.id == "-9" ? "2" : '1',
        methodCode: selectedMethod.methodCode.toString(),
        rideId: rideId,
        tips: tipsController.text.isEmpty ? '0' : tipsController.text,
      );
      if (responseModel.statusCode == 200) {
        final json = responseModel.responseJson;
        if (json != null && json['data'] != null && json['data']['checkout_api'] == true) {
          mpCheckoutData = Map<String, dynamic>.from(json['data']);
          isSubmitBtnLoading = false;
          update();
          return;
        }

        if (selectedMethod.id == "-9") {
          RideDetailsResponseModel model = RideDetailsResponseModel.fromJson((responseModel.responseJson));
          if (model.status == "success") {
            if (Get.isRegistered<RideDetailsController>()) {
              Get.find<RideDetailsController>().updatePaymentRequested();
            }
            Get.back();
            CustomSnackBar.success(successList: model.message ?? ['']);
          } else {
            CustomSnackBar.error(
              errorList: model.message ?? [MyStrings.somethingWentWrong],
            );
          }
        } else {
          PaymentInsertResponseModel model = PaymentInsertResponseModel.fromJson((responseModel.responseJson));
          if (model.status == "success") {
            Get.toNamed(
              RouteHelper.webViewScreen,
              arguments: WebviewModel(
                url: model.data?.redirectUrl ?? "",
                rideId: rideId,
              ),
            );
          } else {
            CustomSnackBar.error(
              errorList: model.message ?? [MyStrings.somethingWentWrong],
            );
          }
        }
      } else {
        CustomSnackBar.error(errorList: [responseModel.message]);
      }
    } catch (e) {
      printX(e);
    }
    isSubmitBtnLoading = false;
    update();
  }

  Future<String?> submitMercadoPagoPayment({
    required int depositId,
    required String cardToken,
    required int installments,
    required String paymentMethodId,
    required String? issuerId,
    required String payerEmail,
    required String? docType,
    required String? docNumber,
  }) async {
    isSubmitBtnLoading = true;
    update();
    try {
      ResponseModel response = await repo.processMercadoPagoPayment(
        rideId: rideId,
        depositId: depositId,
        cardToken: cardToken,
        installments: installments,
        paymentMethodId: paymentMethodId,
        issuerId: issuerId,
        payerEmail: payerEmail,
        docType: docType,
        docNumber: docNumber,
      );

      printX('processMercadoPagoPayment ride response [${response.statusCode}]: ${response.responseJson}');

      if (response.statusCode == 200) {
        var json = response.responseJson;
        if (json['status'] == MyStrings.success) {
          isSubmitBtnLoading = false;
          update();
          return null;
        }
        final msg = (json['message']?.toString() ?? json['error']?.toString() ?? json['msg']?.toString());
        isSubmitBtnLoading = false;
        update();
        return (msg != null && msg.isNotEmpty) ? msg : 'Error al procesar el pago';
      } else {
        final json = response.responseJson;
        String? serverMsg;
        if (json is Map) {
          serverMsg = json['message']?.toString() ?? json['error']?.toString() ?? json['msg']?.toString();
        }
        isSubmitBtnLoading = false;
        update();
        return serverMsg ?? 'Pago rechazado o fallido';
      }
    } catch (e) {
      printX('processMercadoPagoPayment ride exception: $e');
      isSubmitBtnLoading = false;
      update();
      return 'Error de red al procesar el pago';
    }
  }
}
