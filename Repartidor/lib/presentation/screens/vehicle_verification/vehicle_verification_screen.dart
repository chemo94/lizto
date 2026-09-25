import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/utils/my_icons.dart';
import 'package:liztogo_repartidor/data/controller/vehicle_verification/vehicle_verification_controller.dart';
import 'package:liztogo_repartidor/data/model/global/formdata/global_kyc_form_data.dart';
import 'package:liztogo_repartidor/data/repo/vehicle_verification/vehicle_verification_repo.dart';
import 'package:liztogo_repartidor/presentation/screens/vehicle_verification/widget/vahecle_alrady_veified_widget.dart';
import 'package:liztogo_repartidor/presentation/screens/vehicle_verification/widget/vehicle_verification_pending.dart';
import 'package:liztogo_repartidor/presentation/components/card/custom_app_card.dart';
import 'package:liztogo_repartidor/presentation/components/checkbox/custom_check_box.dart';
import 'package:liztogo_repartidor/presentation/components/custom_drop_down_button_with_text_field.dart';
import 'package:liztogo_repartidor/presentation/components/custom_loader/custom_loader.dart';
import 'package:liztogo_repartidor/presentation/components/custom_radio_button.dart';
import 'package:liztogo_repartidor/presentation/components/image/my_local_image_widget.dart';
import 'package:liztogo_repartidor/presentation/components/text-form-field/custom_text_field.dart';
import 'package:liztogo_repartidor/presentation/components/text/label_text_with_instructions.dart';
import 'package:liztogo_repartidor/presentation/components/app-bar/custom_appbar.dart';
import 'package:liztogo_repartidor/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo_repartidor/presentation/components/divider/custom_spacer.dart';
import '../../../core/utils/dimensions.dart';
import '../../../core/utils/my_color.dart';
import '../../../core/utils/my_strings.dart';
import '../../../core/utils/style.dart';
import 'package:liztogo_repartidor/presentation/components/step_indicator/registration_step_indicator.dart';

class VehicleVerificationScreen extends StatefulWidget {
  const VehicleVerificationScreen({super.key});

  @override
  State<VehicleVerificationScreen> createState() => _VehicleVerificationScreenState();
}

class _VehicleVerificationScreenState extends State<VehicleVerificationScreen> {
  final formKey = GlobalKey<FormState>();

  @override
  void initState() {
    Get.put(VehicleVerificationRepo(apiClient: Get.find()));
    Get.put(VehicleVerificationController(repo: Get.find()));
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<VehicleVerificationController>().beforeInitLoadKycData();
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<VehicleVerificationController>(
      builder: (controller) {
        return Scaffold(
          appBar: CustomAppBar(
            isShowBackBtn: true,
            backBtnPress: () => Get.back(),
            title: controller.isAlreadyVerified ? MyStrings.vehicleInformation.tr : MyStrings.vehicleVerification.tr,
          ),
          body: SingleChildScrollView(
            padding: Dimensions.previewPaddingHV,
            physics: const BouncingScrollPhysics(),
            child: controller.isLoading
                ? const Padding(
                    padding: EdgeInsets.all(Dimensions.space15),
                    child: CustomLoader(isFullScreen: true),
                  )
                : controller.isAlreadyPending
                    ? const VehicleVerificationPendingSection(isPending: true)
                    : controller.isNoDataFound
                        ? const VehicleAlreadyVerifiedWidget(isPending: false)
                        : Form(
                            key: formKey,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                spaceDown(Dimensions.space20),
                                const RegistrationStepIndicator(currentStep: 3),
                                spaceDown(Dimensions.space10),
                                if (controller.formList.isNotEmpty) ...[
                                  CustomAppCard(
                                    width: double.infinity,
                                    child: SingleChildScrollView(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          ListView.builder(
                                            shrinkWrap: true,
                                            physics: const NeverScrollableScrollPhysics(),
                                            scrollDirection: Axis.vertical,
                                            padding: EdgeInsets.zero,
                                            itemCount: controller.formList.length,
                                            itemBuilder: (ctx, index) {
                                              GlobalFormModel? model = controller.formList[index];
                                              return Padding(
                                                padding: const EdgeInsets.all(3),
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  mainAxisSize: MainAxisSize.min,
                                                  children: [
                                                    model.type == 'text' || model.type == 'number' || model.type == 'email' || model.type == 'url'
                                                        ? Column(
                                                            crossAxisAlignment: CrossAxisAlignment.start,
                                                            children: [
                                                              CustomTextField(
                                                                hintText: (model.name ?? '').toLowerCase().capitalizeFirst,
                                                                labelWidget: Column(
                                                                  children: [
                                                                    LabelTextInstruction(
                                                                      text: model.name ?? '',
                                                                      isRequired: model.isRequired == 'optional' ? false : true,
                                                                      instructions: model.instruction,
                                                                      textStyle: regularDefault.copyWith(
                                                                        color: MyColor.getHeadingTextColor(),
                                                                        fontSize: Dimensions.fontLarge,
                                                                      ),
                                                                    ),
                                                                    spaceDown(
                                                                      Dimensions.space10,
                                                                    ),
                                                                  ],
                                                                ),
                                                                isShowInstructionWidget: true,
                                                                instructions: model.instruction,
                                                                labelText: model.name ?? '',
                                                                isRequired: model.isRequired == 'optional' ? false : true,
                                                                textInputType: model.type == 'number'
                                                                    ? TextInputType.number
                                                                    : model.type == 'email'
                                                                        ? TextInputType.emailAddress
                                                                        : model.type == 'url'
                                                                            ? TextInputType.url
                                                                            : TextInputType.text,
                                                                onChanged: (value) {
                                                                  controller.changeSelectedValue(
                                                                    value,
                                                                    index,
                                                                  );
                                                                },
                                                                validator: (value) {
                                                                  if (model.isRequired != 'optional' && value.toString().isEmpty) {
                                                                    return '${model.name.toString().capitalizeFirst} ${MyStrings.isRequired}';
                                                                  } else {
                                                                    return null;
                                                                  }
                                                                },
                                                              ),
                                                              const SizedBox(
                                                                height: Dimensions.space10,
                                                              ),
                                                            ],
                                                          )
                                                        : model.type == 'textarea'
                                                            ? Column(
                                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                                children: [
                                                                  CustomTextField(
                                                                    instructions: model.instruction,
                                                                    isShowInstructionWidget: true,
                                                                    labelText: model.name ?? '',
                                                                    labelWidget: Column(
                                                                      children: [
                                                                        LabelTextInstruction(
                                                                          text: model.name ?? '',
                                                                          isRequired: model.isRequired == 'optional' ? false : true,
                                                                          instructions: model.instruction,
                                                                          textStyle: regularDefault.copyWith(
                                                                            color: MyColor.getHeadingTextColor(),
                                                                            fontSize: Dimensions.fontLarge,
                                                                          ),
                                                                        ),
                                                                        spaceDown(
                                                                          Dimensions.space10,
                                                                        ),
                                                                      ],
                                                                    ),
                                                                    isRequired: model.isRequired == 'optional' ? false : true,
                                                                    hintText: (model.name ?? '').capitalizeFirst,
                                                                    textInputType: TextInputType.multiline,
                                                                    maxLines: 5,
                                                                    onChanged: (value) {
                                                                      controller.changeSelectedValue(
                                                                        value,
                                                                        index,
                                                                      );
                                                                    },
                                                                    validator: (value) {
                                                                      if (model.isRequired != 'optional' && value.toString().isEmpty) {
                                                                        return '${model.name.toString().capitalizeFirst} ${MyStrings.isRequired}';
                                                                      } else {
                                                                        return null;
                                                                      }
                                                                    },
                                                                  ),
                                                                  const SizedBox(
                                                                    height: Dimensions.space10,
                                                                  ),
                                                                ],
                                                              )
                                                            : model.type == 'select'
                                                                ? Column(
                                                                    crossAxisAlignment: CrossAxisAlignment.start,
                                                                    children: [
                                                                      LabelTextInstruction(
                                                                        text: model.name ?? '',
                                                                        isRequired: model.isRequired == 'optional' ? false : true,
                                                                        instructions: model.instruction,
                                                                        textStyle: regularDefault.copyWith(
                                                                          color: MyColor.getHeadingTextColor(),
                                                                          fontSize: Dimensions.fontLarge,
                                                                        ),
                                                                      ),
                                                                      spaceDown(
                                                                        Dimensions.space10,
                                                                      ),
                                                                      CustomDropDownWithTextField(
                                                                        list: model.options ?? [],
                                                                        onChanged: (value) {
                                                                          controller.changeSelectedValue(
                                                                            value,
                                                                            index,
                                                                          );
                                                                        },
                                                                        selectedValue: model.selectedValue,
                                                                      ),
                                                                      const SizedBox(
                                                                        height: Dimensions.space10,
                                                                      ),
                                                                    ],
                                                                  )
                                                                : model.type == 'radio'
                                                                    ? Column(
                                                                        crossAxisAlignment: CrossAxisAlignment.start,
                                                                        children: [
                                                                          LabelTextInstruction(
                                                                            text: model.name ?? '',
                                                                            isRequired: model.isRequired == 'optional' ? false : true,
                                                                            instructions: model.instruction,
                                                                            textStyle: regularDefault.copyWith(
                                                                              color: MyColor.getHeadingTextColor(),
                                                                              fontSize: Dimensions.fontLarge,
                                                                            ),
                                                                          ),
                                                                          spaceDown(
                                                                            Dimensions.space10,
                                                                          ),
                                                                          CustomRadioButton(
                                                                            title: model.name,
                                                                            selectedIndex: controller.formList[index].options?.indexOf(
                                                                                  model.selectedValue ?? '',
                                                                                ) ??
                                                                                0,
                                                                            list: model.options ?? [],
                                                                            onChanged: (selectedIndex) {
                                                                              controller.changeSelectedRadioBtnValue(
                                                                                index,
                                                                                selectedIndex,
                                                                              );
                                                                            },
                                                                          ),
                                                                        ],
                                                                      )
                                                                    : model.type == 'checkbox'
                                                                        ? Column(
                                                                            crossAxisAlignment: CrossAxisAlignment.start,
                                                                            children: [
                                                                              LabelTextInstruction(
                                                                                text: model.name ?? '',
                                                                                isRequired: model.isRequired == 'optional' ? false : true,
                                                                                instructions: model.instruction,
                                                                                textStyle: regularDefault.copyWith(
                                                                                  color: MyColor.getHeadingTextColor(),
                                                                                  fontSize: Dimensions.fontLarge,
                                                                                ),
                                                                              ),
                                                                              spaceDown(
                                                                                Dimensions.space10,
                                                                              ),
                                                                              CustomCheckBox(
                                                                                selectedValue: controller.formList[index].cbSelected,
                                                                                list: model.options ?? [],
                                                                                onChanged: (value) {
                                                                                  controller.changeSelectedCheckBoxValue(
                                                                                    index,
                                                                                    value,
                                                                                  );
                                                                                },
                                                                              ),
                                                                            ],
                                                                          )
                                                                        : model.type == 'file'
                                                                            ? Column(
                                                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                                                children: [
                                                                                  LabelTextInstruction(
                                                                                    text: model.name ?? '',
                                                                                    isRequired: model.isRequired == 'optional' ? false : true,
                                                                                    instructions: model.instruction,
                                                                                    textStyle: regularDefault.copyWith(
                                                                                      color: MyColor.getHeadingTextColor(),
                                                                                      fontSize: Dimensions.fontLarge,
                                                                                    ),
                                                                                  ),
                                                                                  spaceDown(
                                                                                    Dimensions.space10,
                                                                                  ),
                                                                                  CustomTextField(
                                                                                    hintText: model.imageFile == null ? (model.name ?? '') : model.selectedValue ?? MyStrings.chooseFile,
                                                                                    isShowInstructionWidget: true,
                                                                                    instructions: model.instruction,
                                                                                    labelText: '',
                                                                                    readOnly: true,
                                                                                    isRequired: model.isRequired == 'optional' ? false : true,
                                                                                    textInputType: TextInputType.none,
                                                                                    onChanged: (value) {},
                                                                                    onTap: () {
                                                                                      controller.pickFile(
                                                                                        index,
                                                                                      );
                                                                                    },
                                                                                    hintTextStyle: regularLarge.copyWith(
                                                                                      color: MyColor.getPrimaryColor(),
                                                                                    ),
                                                                                    prefixIcon: Padding(
                                                                                      padding: EdgeInsetsDirectional.only(
                                                                                        start: Dimensions.space10,
                                                                                      ),
                                                                                      child: MyLocalImageWidget(
                                                                                        imagePath: MyIcons.attachment,
                                                                                        imageOverlayColor: MyColor.getPrimaryColor(),
                                                                                        width: Dimensions.space30,
                                                                                        height: Dimensions.space30,
                                                                                        boxFit: BoxFit.contain,
                                                                                      ),
                                                                                    ),
                                                                                    validator: (value) {
                                                                                      if (model.isRequired != 'optional' && model.imageFile == null) {
                                                                                        return '${model.name.toString()} ${MyStrings.isRequired}';
                                                                                      } else {
                                                                                        return null;
                                                                                      }
                                                                                    },
                                                                                  ),
                                                                                ],
                                                                              )
                                                                            : model.type == 'datetime'
                                                                                ? Column(
                                                                                    crossAxisAlignment: CrossAxisAlignment.start,
                                                                                    children: [
                                                                                      Padding(
                                                                                        padding: const EdgeInsets.symmetric(
                                                                                          vertical: Dimensions.textToTextSpace,
                                                                                        ),
                                                                                        child: CustomTextField(
                                                                                          isShowInstructionWidget: true,
                                                                                          labelWidget: Column(
                                                                                            children: [
                                                                                              LabelTextInstruction(
                                                                                                text: model.name ?? '',
                                                                                                isRequired: model.isRequired == 'optional' ? false : true,
                                                                                                instructions: model.instruction,
                                                                                                textStyle: regularDefault.copyWith(
                                                                                                  color: MyColor.getHeadingTextColor(),
                                                                                                  fontSize: Dimensions.fontLarge,
                                                                                                ),
                                                                                              ),
                                                                                              spaceDown(
                                                                                                Dimensions.space10,
                                                                                              ),
                                                                                            ],
                                                                                          ),
                                                                                          instructions: model.instruction,
                                                                                          isRequired: model.isRequired == 'optional' ? false : true,
                                                                                          hintText: (model.name ?? '').toString().capitalizeFirst,
                                                                                          labelText: model.name ?? '',
                                                                                          controller: controller.formList[index].textEditingController,
                                                                                          textInputType: TextInputType.datetime,
                                                                                          readOnly: true,
                                                                                          validator: (value) {
                                                                                            if (model.isRequired != 'optional' && value.toString().isEmpty) {
                                                                                              return '${model.name.toString().capitalizeFirst} ${MyStrings.isRequired}';
                                                                                            } else {
                                                                                              return null;
                                                                                            }
                                                                                          },
                                                                                          onTap: () {
                                                                                            controller.changeSelectedDateTimeValue(
                                                                                              index,
                                                                                              context,
                                                                                            );
                                                                                          },
                                                                                          onChanged: (value) {
                                                                                            controller.changeSelectedValue(
                                                                                              value,
                                                                                              index,
                                                                                            );
                                                                                          },
                                                                                        ),
                                                                                      ),
                                                                                    ],
                                                                                  )
                                                                                : model.type == 'date'
                                                                                    ? Column(
                                                                                        crossAxisAlignment: CrossAxisAlignment.start,
                                                                                        children: [
                                                                                          Padding(
                                                                                            padding: const EdgeInsets.symmetric(
                                                                                              vertical: Dimensions.textToTextSpace,
                                                                                            ),
                                                                                            child: CustomTextField(
                                                                                              isShowInstructionWidget: true,
                                                                                              instructions: model.instruction,
                                                                                              labelWidget: Column(
                                                                                                children: [
                                                                                                  LabelTextInstruction(
                                                                                                    text: model.name ?? '',
                                                                                                    isRequired: model.isRequired == 'optional' ? false : true,
                                                                                                    instructions: model.instruction,
                                                                                                    textStyle: regularDefault.copyWith(
                                                                                                      color: MyColor.getHeadingTextColor(),
                                                                                                      fontSize: Dimensions.fontLarge,
                                                                                                    ),
                                                                                                  ),
                                                                                                  spaceDown(
                                                                                                    Dimensions.space10,
                                                                                                  ),
                                                                                                ],
                                                                                              ),
                                                                                              isRequired: model.isRequired == 'optional' ? false : true,
                                                                                              hintText: (model.name ?? '').toString().capitalizeFirst,
                                                                                              labelText: model.name ?? '',
                                                                                              controller: controller.formList[index].textEditingController,
                                                                                              textInputType: TextInputType.datetime,
                                                                                              readOnly: true,
                                                                                              validator: (value) {
                                                                                                if (model.isRequired != 'optional' && value.toString().isEmpty) {
                                                                                                  return '${model.name.toString().capitalizeFirst} ${MyStrings.isRequired}';
                                                                                                } else {
                                                                                                  return null;
                                                                                                }
                                                                                              },
                                                                                              onTap: () {
                                                                                                controller.changeSelectedDateOnlyValue(
                                                                                                  index,
                                                                                                  context,
                                                                                                );
                                                                                              },
                                                                                              onChanged: (value) {
                                                                                                controller.changeSelectedValue(
                                                                                                  value,
                                                                                                  index,
                                                                                                );
                                                                                              },
                                                                                            ),
                                                                                          ),
                                                                                        ],
                                                                                      )
                                                                                    : model.type == 'time'
                                                                                        ? Column(
                                                                                            crossAxisAlignment: CrossAxisAlignment.start,
                                                                                            children: [
                                                                                              Padding(
                                                                                                padding: const EdgeInsets.symmetric(
                                                                                                  vertical: Dimensions.textToTextSpace,
                                                                                                ),
                                                                                                child: CustomTextField(
                                                                                                  isShowInstructionWidget: true,
                                                                                                  instructions: model.instruction,
                                                                                                  labelWidget: Column(
                                                                                                    children: [
                                                                                                      LabelTextInstruction(
                                                                                                        text: model.name ?? '',
                                                                                                        isRequired: model.isRequired == 'optional' ? false : true,
                                                                                                        instructions: model.instruction,
                                                                                                        textStyle: regularDefault.copyWith(
                                                                                                          color: MyColor.getHeadingTextColor(),
                                                                                                          fontSize: Dimensions.fontLarge,
                                                                                                        ),
                                                                                                      ),
                                                                                                      spaceDown(
                                                                                                        Dimensions.space10,
                                                                                                      ),
                                                                                                    ],
                                                                                                  ),
                                                                                                  isRequired: model.isRequired == 'optional' ? false : true,
                                                                                                  hintText: (model.name ?? '').toString().capitalizeFirst,
                                                                                                  labelText: model.name ?? '',
                                                                                                  controller: controller.formList[index].textEditingController,
                                                                                                  textInputType: TextInputType.datetime,
                                                                                                  readOnly: true,
                                                                                                  validator: (value) {
                                                                                                    if (model.isRequired != 'optional' && value.toString().isEmpty) {
                                                                                                      return '${model.name.toString().capitalizeFirst} ${MyStrings.isRequired}';
                                                                                                    } else {
                                                                                                      return null;
                                                                                                    }
                                                                                                  },
                                                                                                  onTap: () {
                                                                                                    controller.changeSelectedTimeOnlyValue(
                                                                                                      index,
                                                                                                      context,
                                                                                                    );
                                                                                                  },
                                                                                                  onChanged: (value) {
                                                                                                    controller.changeSelectedValue(
                                                                                                      value,
                                                                                                      index,
                                                                                                    );
                                                                                                  },
                                                                                                ),
                                                                                              ),
                                                                                            ],
                                                                                          )
                                                                                        : const SizedBox(),
                                                  ],
                                                ),
                                              );
                                            },
                                          ),
                                        ],
                                      ),
                                    ),
                                  ),
                                  spaceDown(Dimensions.space20),
                                ],
                                spaceDown(Dimensions.space15),
                                RoundedButton(
                                  text: MyStrings.submit,
                                  isLoading: controller.submitLoading,
                                  press: () {
                                    if (formKey.currentState!.validate()) {
                                      controller.submitKycData();
                                    }
                                  },
                                ),
                                spaceDown(Dimensions.space15),
                              ],
                            ),
                          ),
          ),
        );
      },
    );
  }
}
