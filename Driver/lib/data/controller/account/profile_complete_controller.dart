import 'dart:io';

import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/helper/shared_preference_helper.dart';
import 'package:liztogo_pro/core/helper/string_format_helper.dart';
import 'package:liztogo_pro/core/route/route.dart';
import 'package:liztogo_pro/core/utils/my_strings.dart';
import 'package:liztogo_pro/data/model/country_model/country_model.dart';
import 'package:liztogo_pro/data/model/global/response_model/response_model.dart';
import 'package:liztogo_pro/data/model/profile/profile_response_model.dart';
import 'package:liztogo_pro/data/model/zone/zone_list_response_model.dart';
import 'package:liztogo_pro/data/repo/account/profile_repo.dart';
import 'package:liztogo_pro/presentation/components/snack_bar/show_custom_snackbar.dart';

import '../../model/profile_complete/profile_complete_post_model.dart';
import '../../model/profile_complete/profile_complete_response_model.dart';

class ProfileCompleteController extends GetxController {
  ProfileRepo profileRepo;
  ProfileCompleteController({required this.profileRepo});

  ProfileResponseModel model = ProfileResponseModel();

  TextEditingController userNameController = TextEditingController();
  TextEditingController emailController = TextEditingController();
  TextEditingController mobileNoController = TextEditingController();
  TextEditingController addressController = TextEditingController();
  TextEditingController stateController = TextEditingController();
  TextEditingController zipCodeController = TextEditingController();
  TextEditingController cityController = TextEditingController();
  TextEditingController referController = TextEditingController();
  TextEditingController countryController = TextEditingController();

  FocusNode emailFocusNode = FocusNode();
  FocusNode mobileNoFocusNode = FocusNode();
  FocusNode zoneFocusNode = FocusNode();
  FocusNode addressFocusNode = FocusNode();
  FocusNode stateFocusNode = FocusNode();
  FocusNode zipCodeFocusNode = FocusNode();
  FocusNode cityFocusNode = FocusNode();
  FocusNode countryFocusNode = FocusNode();
  final FocusNode mobileFocusNode = FocusNode();
  FocusNode userNameFocusNode = FocusNode();
  bool isPhonePreFilled = false;

  Future<void> initialData() async {
    isLoading = true;
    update();
    countryList = profileRepo.apiClient.getOperatingCountries();
    if (countryList.isNotEmpty) {
      selectCountryData(countryList.first);
    }

    String savedPhone = profileRepo.apiClient.sharedPreferences.getString(
          SharedPreferenceHelper.userPhoneNumberKey,
        ) ??
        '';
    if (savedPhone.isNotEmpty && savedPhone != 'null') {
      mobileNoController.text = savedPhone;
    } else {
      mobileNoController.text = '';
    }

    String savedUsername = profileRepo.apiClient.sharedPreferences.getString(
          SharedPreferenceHelper.userNameKey,
        ) ??
        '';
    if (savedUsername.isNotEmpty && savedUsername != 'null') {
      userNameController.text = savedUsername;
    } else {
      userNameController.text = '';
    }

    String loginBy = '';
    try {
      loginBy = profileRepo.apiClient.sharedPreferences.getString('login_by') ?? '';
    } catch (_) {}

    try {
      profileResponseModel = await profileRepo.loadProfileInfo();
      if (profileResponseModel.data != null && profileResponseModel.status?.toLowerCase() == MyStrings.success.toLowerCase()) {
        final driver = profileResponseModel.data?.driver;
        if (driver?.loginBy != null && driver!.loginBy!.isNotEmpty) {
          loginBy = driver.loginBy!;
        }
        if (driver?.mobile != null && driver!.mobile!.isNotEmpty && driver.mobile != 'null') {
          mobileNoController.text = driver.mobile!;
          phoneData = driver.mobile!;
        }
        if (driver?.username != null && driver!.username!.isNotEmpty && driver.username != 'null') {
          userNameController.text = driver.username!;
        }
        if (driver?.address != null && driver!.address!.isNotEmpty && driver.address != 'null') {
          addressController.text = driver.address!;
        }
        if (driver?.city != null && driver!.city!.isNotEmpty && driver.city != 'null') {
          cityController.text = driver.city!;
        }
        if (driver?.state != null && driver!.state!.isNotEmpty && driver.state != 'null') {
          stateController.text = driver.state!;
        }
        if (driver?.zip != null && driver!.zip!.isNotEmpty && driver.zip != 'null') {
          zipCodeController.text = driver.zip!;
        }
      }
    } catch (_) {}

    if (mobileNoController.text.trim() == 'null') {
      mobileNoController.text = '';
    }
    if (userNameController.text.trim() == 'null') {
      userNameController.text = '';
    }

    bool isSocialLogin = loginBy == '1' || loginBy == '2' || loginBy == 'google' || loginBy == 'apple';
    if (isSocialLogin) {
      if (mobileNoController.text.trim() == 'null') {
        mobileNoController.text = '';
      }
      isPhonePreFilled = false;
    } else {
      isPhonePreFilled = mobileNoController.text.trim().isNotEmpty && mobileNoController.text.trim() != 'null';
    }

    isLoading = false;
    update();
  }

  TextEditingController searchController = TextEditingController();

  ProfileResponseModel profileResponseModel = ProfileResponseModel();

  String imageUrl = '';

  File? imageFile;
  String emailData = '';
  String countryData = '';
  String countryCodeData = '';
  String phoneCodeData = '';
  String phoneData = '';
  String loginType = '';

  String? countryName;
  String? countryCode;
  String? dialCode;
  ZoneData selectedZone = ZoneData(id: "-1");

  Future<void> loadProfileInfo() async {
    isLoading = true;
    update();
    try {
      profileResponseModel = await profileRepo.loadProfileInfo();
      if (profileResponseModel.data != null && profileResponseModel.status?.toLowerCase() == MyStrings.success.toLowerCase()) {
        emailData = profileResponseModel.data?.driver?.email ?? '';
        countryData = profileResponseModel.data?.driver?.countryName ?? '';
        countryCodeData = profileResponseModel.data?.driver?.countryCode ?? '';
        phoneData = profileResponseModel.data?.driver?.mobile ?? '';
      } else {
        isLoading = false;
        update();
      }
    } catch (e) {
      isLoading = false;
      update();
    }
    isLoading = false;
    update();
  }

  TextEditingController searchCountryController = TextEditingController();
  bool countryLoading = true;
  List<Countries> countryList = [];
  List<Countries> filteredCountries = [];

  bool isLoading = true;
  bool submitLoading = false;

  Future<void> updateProfile() async {
    if (mobileNoController.text.isEmpty) {
      CustomSnackBar.error(errorList: [MyStrings.enterYourPhoneNumber.tr]);
      return;
    }
    if (selectedZone.id == '-1') {
      CustomSnackBar.error(errorList: [MyStrings.selectYourZone]);
      return;
    }
    String username = userNameController.text;
    String mobileNumber = mobileNoController.text;
    String address = addressController.text.toString();
    String city = cityController.text.toString();
    String zip = zipCodeController.text.toString();
    String state = stateController.text.toString();
    String zoneId = selectedZone.id ?? '';
    submitLoading = true;
    update();

    ProfileCompletePostModel model = ProfileCompletePostModel(
      username: username,
      countryName: selectedCountryData.country ?? '',
      countryCode: selectedCountryData.countryCode ?? '',
      mobileNumber: mobileNumber,
      mobileCode: selectedCountryData.dialCode ?? '',
      address: address,
      state: state,
      zip: zip,
      city: city,
      image: null,
      zone: zoneId,
    );

    ResponseModel responseModel = await profileRepo.completeProfile(model);

    if (responseModel.statusCode == 200) {
      ProfileCompleteResponseModel model = ProfileCompleteResponseModel.fromJson((responseModel.responseJson));
      if (model.status?.toLowerCase() == MyStrings.success.toLowerCase()) {
        RouteHelper.checkUserStatusAndGoToNextStep(model.data?.user);
      } else {
        CustomSnackBar.error(
          errorList: model.message ?? [MyStrings.requestFail],
        );
      }
    } else {
      CustomSnackBar.error(errorList: [responseModel.message]);
    }

    submitLoading = false;
    update();
  }

  Countries selectedCountryData = Countries();
  void selectCountryData(Countries value) {
    selectedCountryData = value;
    update();
  }

  bool zoneLoading = false;
  int page = 0;
  List<ZoneData> zoneList = [];
  TextEditingController searchZoneController = TextEditingController();

  Future<void> initZoneData({bool shouldLoad = false}) async {
    zoneLoading = shouldLoad;
    page = 0;
    if (shouldLoad) {
      searchZoneController.clear();
    }
    update();
    await getZoneData(shouldLoad: shouldLoad);
  }

  Future<dynamic> getZoneData({bool shouldLoad = false}) async {
    try {
      page = page + 1;
      if (page == 1) {
        zoneLoading = shouldLoad;
        update();
      }
      ResponseModel mainResponse = await profileRepo.getZoneList(page.toString(), search: searchZoneController.text);

      if (mainResponse.statusCode == 200) {
        ZoneListResponseModel model = ZoneListResponseModel.fromJson((mainResponse.responseJson));

        if (model.status == MyStrings.success) {
          nextPageUrl = model.data?.zones?.nextPageUrl;
          List<ZoneData>? tempList = model.data?.zones?.data;
          if (page == 1) {
            zoneList.clear();
          }
          if (tempList != null && tempList.isNotEmpty) {
            zoneList.addAll(tempList);
          }
          zoneLoading = false;
          update();
        }
      } else {
        CustomSnackBar.error(errorList: [mainResponse.message]);
        zoneLoading = false;
        update();
      }
    } catch (e) {
      printE(e);
    } finally {
      zoneLoading = false;
      update();
    }
  }

  bool hasNext() {
    return nextPageUrl != null && nextPageUrl!.isNotEmpty && nextPageUrl != 'null' ? true : false;
  }

  String? nextPageUrl;

  void selectZone(ZoneData zone) {
    selectedZone = zone;
    update();
  }
}
