import 'package:get/get.dart';
import 'package:flutter/material.dart';
import 'package:geocoding/geocoding.dart';
import 'package:liztogo_repartidor/core/utils/util.dart';
import 'package:geolocator/geolocator.dart';
import 'package:liztogo_repartidor/core/helper/shared_preference_helper.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/core/utils/my_strings.dart';
import 'package:liztogo_repartidor/data/model/authorization/authorization_response_model.dart';
import 'package:liztogo_repartidor/data/model/global/response_model/response_model.dart';
import 'package:liztogo_repartidor/data/model/dashboard/dashboard_response_model.dart';
import 'package:liztogo_repartidor/data/model/global/user/global_driver_model.dart';
import 'package:liztogo_repartidor/data/repo/dashboard/dashboard_repo.dart';
import 'package:liztogo_repartidor/environment.dart';
import 'package:liztogo_repartidor/presentation/components/snack_bar/show_custom_snackbar.dart';
import '../../../core/utils/url_container.dart';
import '../../../presentation/components/foreground_task_widget.dart';

class DashBoardController extends GetxController {
  DashBoardRepo repo;
  DashBoardController({required this.repo});
  TextEditingController bidAmountController = TextEditingController();

  int selectedTab = 0;
  void changeTab(int index) {
    selectedTab = index;
    update();
  }

  String? profileImageUrl;
  bool isLoading = true;
  Position? currentPosition;
  String currentAddress = "${MyStrings.loading.tr}...";
  bool userOnline = false;
  String? nextPageUrl;
  int page = 0;
  bool isDriverVerified = true;
  bool isVehicleVerified = true;

  bool isVehicleVerificationPending = false;
  bool isDriverVerificationPending = false;

  String currency = '';
  String currencySym = '';
  String userImagePath = '';

  Future<void> initialData({bool shouldLoad = true}) async {
    isLoading = shouldLoad;
    page = 0;
    nextPageUrl;
    bidAmountController.text = '';
    currency = repo.apiClient.getCurrency();
    currencySym = repo.apiClient.getCurrency(isSymbol: true);
    update();
    await Future.wait([fetchLocation(), loadData(shouldLoad: shouldLoad)]);
    isLoading = false;
    update();
  }

  GlobalDriverInfoModel driver = GlobalDriverInfoModel(id: '-1');

  bool _fetchingLocation = false;

  Future<void> fetchLocation() async {
    if (_fetchingLocation) return;
    _fetchingLocation = true;
    try {
      bool hasPermission = await MyUtils.checkAppLocationPermission(
        onsuccess: () {
          initialData();
        },
      );
      printX(hasPermission);
      if (hasPermission) {
        getCurrentLocationAddress();
        update();
      }
    } finally {
      _fetchingLocation = false;
    }
  }

  Future<void> getCurrentLocationAddress() async {
    try {
      final GeolocatorPlatform geolocator = GeolocatorPlatform.instance;
      currentPosition = await geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.best,
        ),
      );

      if (currentPosition != null) {
        if (Environment.addressPickerFromGoogleMapApi) {
          currentAddress = await repo.getActualAddress(currentPosition!.latitude, currentPosition!.longitude) ?? 'Unknown location..';
        } else {
          final placemarks = await placemarkFromCoordinates(currentPosition!.latitude, currentPosition!.longitude);
          if (placemarks.isNotEmpty) {
            currentAddress = _formatAddress(placemarks.first);
          } else {
            currentAddress = 'Unknown location..';
          }
        }
      }
      update();
    } catch (e) {
      printX("Error: $e");
      CustomSnackBar.error(
        errorList: [MyStrings.somethingWentWrongWhileTakingLocation],
      );
    }
  }

  String _formatAddress(Placemark placemark) {
    final street = placemark.street ?? '';
    final subLocality = placemark.subLocality ?? '';
    final locality = placemark.locality ?? '';
    final country = placemark.country ?? '';

    return [
      street,
      subLocality,
      locality,
      country,
    ].where((part) => part.isNotEmpty).join(', ');
  }

  Future<void> loadData({bool shouldLoad = true}) async {
    try {
      page = page + 1;
      if (page == 1) {
        isLoading = shouldLoad;
        update();
      }

      ResponseModel responseModel = await repo.getDashboardData(
        page: page.toString(),
      );

      if (responseModel.statusCode == 200) {
        DashBoardRideResponseModel model = DashBoardRideResponseModel.fromJson(
          (responseModel.responseJson),
        );
        if (model.status == MyStrings.success) {
          userImagePath = '${UrlContainer.domainUrl}/${model.data?.userImagePath}';

          isDriverVerified = model.data?.driverInfo?.dv == "1" ? true : false;
          isVehicleVerified = model.data?.driverInfo?.vv == "1" ? true : false;

          isVehicleVerificationPending = model.data?.driverInfo?.vv == "2" ? true : false;
          isDriverVerificationPending = model.data?.driverInfo?.dv == "2" ? true : false;

          userOnline = model.data?.driverInfo?.onlineStatus == "1" ? true : false;
          if (userOnline) {
            final permission = await Geolocator.checkPermission();
            if (permission != LocationPermission.always) {
              userOnline = false;
              repo.apiClient.setOnlineStatus(false);
            }
          }
          startForegroundTask();
          driver = model.data?.driverInfo ?? GlobalDriverInfoModel(id: '-1');
          repo.apiClient.sharedPreferences.setString(
            SharedPreferenceHelper.userProfileKey,
            model.data?.driverInfo?.imageWithPath ?? '',
          );

          profileImageUrl = "${UrlContainer.domainUrl}/${model.data?.driverImagePath}/${model.data?.driverInfo?.image}";

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
      printE(e);
    } finally {
      isLoading = false;
      update();
    }
  }

  bool hasNext() {
    return nextPageUrl != null && nextPageUrl!.isNotEmpty && nextPageUrl != 'null' ? true : false;
  }

  //Driver Online Status Change
  bool isChangingOnlineStatusLoading = false;
  Future<void> onlineStatusSubmit({bool isFromRideDetails = false}) async {
    try {
      ResponseModel responseModel = await repo.onlineStatus(
        lat: currentPosition?.latitude.toString() ?? "",
        long: currentPosition?.longitude.toString() ?? "",
      );
      if (responseModel.statusCode == 200) {
        AuthorizationResponseModel model = AuthorizationResponseModel.fromJson(
          (responseModel.responseJson),
        );
        if (model.status == MyStrings.success) {
          repo.apiClient.setOnlineStatus(
            model.data?.online.toString() == 'true',
          );
          if (model.data?.online.toString() == 'true') {
            userOnline = true;
          } else {
            userOnline = false;
          }
          startForegroundTask();
          isChangingOnlineStatusLoading = false;
          await loadData(shouldLoad: true);
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
      printE(e);
    } finally {
      isChangingOnlineStatusLoading = false;
      update();
    }
  }

  Future<void> startForegroundTask() async {
    try {
      if (userOnline) {
        await foregroundTaskKey.currentState?.startForegroundTask();
      } else {
        await foregroundTaskKey.currentState?.stopForegroundTask();
      }
    } catch (e) {
      printE(e);
    }
  }

  Future<void> changeOnlineStatus(bool value) async {
    bool hasPermission = await MyUtils.checkAppLocationPermission();
    printX(hasPermission);
    if (hasPermission) {
      await _updateOnlineStatus(value);
    }
  }

  Future<void> _updateOnlineStatus(bool value) async {
    if (value) {
      final permission = await Geolocator.checkPermission();
      if (permission != LocationPermission.always) {
        if (permission == LocationPermission.denied) {
          await Geolocator.requestPermission();
        }
        final updatedPermission = await Geolocator.checkPermission();
        if (updatedPermission != LocationPermission.always) {
          Get.dialog(
            AlertDialog(
              title: const Text('Ubicación en segundo plano'),
              content: const Text(
                'Para activar el modo online y mostrar tu ubicación a los clientes, '
                'necesitamos el permiso "Permitir siempre".\n\n'
                'Se abrirá la configuración de la app. Selecciona "Permitir siempre" en Ubicación.',
              ),
              actions: [
                TextButton(
                  onPressed: () => Get.back(),
                  child: const Text('Ahora no'),
                ),
                ElevatedButton(
                  onPressed: () {
                    Get.back();
                    Geolocator.openAppSettings();
                  },
                  child: const Text('Siguiente'),
                ),
              ],
            ),
          );
          userOnline = false;
          update();
          return;
        }
      }
      if (currentPosition == null) {
        await getCurrentLocationAddress();
      }
      if (currentPosition == null) {
        CustomSnackBar.error(errorList: [MyStrings.locationServiceDisableMsg]);
        return;
      }
    }
    userOnline = value;
    update();
    await onlineStatusSubmit();
    update();
  }
}
