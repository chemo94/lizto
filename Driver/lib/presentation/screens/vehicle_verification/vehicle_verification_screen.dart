import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/utils/dimensions.dart';
import 'package:liztogo_pro/core/utils/my_color.dart';
import 'package:liztogo_pro/core/utils/my_strings.dart';
import 'package:liztogo_pro/core/utils/style.dart';
import 'package:liztogo_pro/core/utils/url_container.dart';
import 'package:liztogo_pro/data/controller/vehicle_verification/vehicle_verification_controller.dart';
import 'package:liztogo_pro/data/model/global/formdata/global_kyc_form_data.dart';
import 'package:liztogo_pro/data/repo/vehicle_verification/vehicle_verification_repo.dart';
import 'package:liztogo_pro/presentation/screens/vehicle_verification/widget/vahecle_alrady_veified_widget.dart';
import 'package:liztogo_pro/presentation/screens/vehicle_verification/widget/vehicle_verification_pending.dart';
import 'package:liztogo_pro/presentation/screens/vehicle_verification/widget/vehicle_bottom_sheet.dart';
import 'package:liztogo_pro/presentation/screens/vehicle_verification/widget/vehicle_brand_widget.dart';
import 'package:liztogo_pro/presentation/screens/vehicle_verification/widget/vehicle_service_widget.dart';
import 'package:liztogo_pro/presentation/components/card/custom_app_card.dart';
import 'package:liztogo_pro/presentation/components/checkbox/custom_check_box.dart';
import 'package:liztogo_pro/presentation/components/custom_drop_down_button_with_text_field.dart';
import 'package:liztogo_pro/presentation/components/custom_loader/custom_loader.dart';
import 'package:liztogo_pro/presentation/components/custom_radio_button.dart';
import 'package:liztogo_pro/presentation/components/text-form-field/custom_text_field.dart';
import 'package:liztogo_pro/presentation/components/text/label_text_with_instructions.dart';
import 'package:liztogo_pro/presentation/components/app-bar/custom_appbar.dart';
import 'package:liztogo_pro/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo_pro/presentation/components/divider/custom_spacer.dart';
import 'package:liztogo_pro/presentation/components/card/inner_shadow_container.dart';

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

  String _buildImageUrl(String? path, {String basePath = ''}) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http')) return path;
    if (path.startsWith('/')) return '${UrlContainer.domainUrl}$path';
    if (basePath.isNotEmpty) return '${UrlContainer.domainUrl}/$basePath/$path';
    return '${UrlContainer.domainUrl}/$path';
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<VehicleVerificationController>(
      builder: (controller) {
        return Scaffold(
          appBar: CustomAppBar(
            isShowBackBtn: true,
            title: controller.isAlreadyVerified ? 'Información del Vehículo'.tr : 'Verificación de Vehículo'.tr,
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

                                // Selección de Servicio
                                if (controller.serviceList.isNotEmpty) ...[
                                  _buildSectionTitle('Servicio'),
                                  spaceDown(Dimensions.space10),
                                  SizedBox(
                                    height: 130,
                                    child: ListView.builder(
                                      scrollDirection: Axis.horizontal,
                                      itemCount: controller.serviceList.length,
                                      itemBuilder: (context, index) {
                                        final service = controller.serviceList[index];
                                        final isSelected = controller.selectedService?.id == service.id;
                                        return VehicleServiceWidget(
                                          isSelected: isSelected,
                                          image: _buildImageUrl(service.image, basePath: controller.serviceImagePath),
                                          name: service.name ?? '',
                                          onTap: () => controller.selectService(service),
                                        );
                                      },
                                    ),
                                  ),
                                  spaceDown(Dimensions.space20),
                                ],

                                // Selección de Marca
                                if (controller.brandList.isNotEmpty) ...[
                                  _buildSectionTitle('Marca'),
                                  spaceDown(Dimensions.space10),
                                  SizedBox(
                                    height: 130,
                                    child: ListView.builder(
                                      scrollDirection: Axis.horizontal,
                                      itemCount: controller.brandList.length,
                                      itemBuilder: (context, index) {
                                        final brand = controller.brandList[index];
                                        final isSelected = controller.selectedBrand?.id == brand.id;
                                        return VehicleBrandWidget(
                                          isSelected: isSelected,
                                          image: _buildImageUrl(brand.image, basePath: controller.brandImagePath),
                                          name: brand.name ?? '',
                                          onTap: () => controller.selectBrand(brand),
                                        );
                                      },
                                    ),
                                  ),
                                  spaceDown(Dimensions.space20),
                                ],

                                // Selección de Modelo
                                if (controller.selectedBrand != null && controller.modelList.isNotEmpty) ...[
                                  _buildSectionTitle('Modelo'),
                                  spaceDown(Dimensions.space10),
                                  _buildSelectionField(
                                    context: context,
                                    label: controller.selectedModel?.name ?? 'Seleccionar modelo',
                                    onTap: () => VehicleBottomSheet.vehicleModelBottomSheet(context, controller),
                                  ),
                                  spaceDown(Dimensions.space20),
                                ],

                                // Selección de Año
                                if (controller.yearList.isNotEmpty) ...[
                                  _buildSectionTitle('Año'),
                                  spaceDown(Dimensions.space10),
                                  _buildSelectionField(
                                    context: context,
                                    label: controller.selectedYear?.name ?? 'Seleccionar año',
                                    onTap: () => VehicleBottomSheet.vehicleYearBottomSheet(context, controller),
                                  ),
                                  spaceDown(Dimensions.space20),
                                ],

                                // Selección de Color
                                if (controller.colorList.isNotEmpty) ...[
                                  _buildSectionTitle('Color'),
                                  spaceDown(Dimensions.space10),
                                  _buildSelectionField(
                                    context: context,
                                    label: controller.selectedColor?.name ?? 'Seleccionar color',
                                    onTap: () => VehicleBottomSheet.vehicleColorBottomSheet(context, controller),
                                  ),
                                  spaceDown(Dimensions.space20),
                                ],

                                // Número de Vehículo
                                _buildSectionTitle('Número de Vehículo'),
                                spaceDown(Dimensions.space10),
                                CustomTextField(
                                  hintText: 'Ingrese el número de vehículo',
                                  labelText: 'Número de Vehículo',
                                  textInputType: TextInputType.text,
                                  onChanged: (value) => controller.setVehicleNumber(value),
                                  validator: (value) {
                                    if (value.toString().trim().isEmpty) {
                                      return 'Número de vehículo ${MyStrings.isRequired}';
                                    }
                                    return null;
                                  },
                                ),
                                spaceDown(Dimensions.space20),

                                // Reglas del Conductor
                                if (controller.riderRuleList.isNotEmpty) ...[
                                  _buildSectionTitle('Reglas'),
                                  spaceDown(Dimensions.space10),
                                  CustomAppCard(
                                    width: double.infinity,
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        ...controller.riderRuleList.map((rule) {
                                          final isSelected = controller.selectedRuleIds.contains(rule.id);
                                          return CheckboxListTile(
                                            value: isSelected,
                                            side: BorderSide(color: MyColor.borderColor, width: 1.5),
                                            activeColor: MyColor.getPrimaryColor(),
                                            checkboxShape: RoundedRectangleBorder(
                                              borderRadius: BorderRadiusGeometry.circular(
                                                Dimensions.defaultRadius,
                                              ),
                                            ),
                                            title: Text(
                                              rule.name ?? '',
                                              style: regularDefault.copyWith(color: MyColor.colorBlack),
                                            ),
                                            onChanged: (bool? value) {
                                              if (value != null) {
                                                controller.toggleRule(rule.id ?? '');
                                              }
                                            },
                                          );
                                        }),
                                      ],
                                    ),
                                  ),
                                  spaceDown(Dimensions.space20),
                                ],

                                // Imagen del Vehículo
                                _buildSectionTitle('Imagen del Vehículo'),
                                spaceDown(Dimensions.space10),
                                CustomAppCard(
                                  width: double.infinity,
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      if (controller.vehicleImageFile != null) ...[
                                        ClipRRect(
                                          borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                                          child: Image.file(
                                            controller.vehicleImageFile!,
                                            height: 150,
                                            width: double.infinity,
                                            fit: BoxFit.cover,
                                          ),
                                        ),
                                        spaceDown(Dimensions.space10),
                                      ],
                                      RoundedButton(
                                        text: controller.vehicleImageFile == null ? 'Seleccionar Imagen' : 'Cambiar Imagen',
                                        press: () => controller.pickVehicleImage(),
                                      ),
                                    ],
                                  ),
                                ),
                                spaceDown(Dimensions.space20),

                                // Documento del Vehículo
                                _buildSectionTitle('Documento del Vehículo'),
                                spaceDown(Dimensions.space10),
                                CustomAppCard(
                                  width: double.infinity,
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      if (controller.vehicleDocumentFile != null) ...[
                                        Padding(
                                          padding: const EdgeInsets.only(bottom: Dimensions.space10),
                                          child: Row(
                                            children: [
                                              Icon(Icons.description, color: MyColor.primaryColor, size: 40),
                                              spaceDown(Dimensions.space10),
                                              Expanded(
                                                child: Text(
                                                  controller.vehicleDocumentFile!.path.split('/').last,
                                                  style: regularDefault.copyWith(color: MyColor.colorBlack),
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                      RoundedButton(
                                        text: controller.vehicleDocumentFile == null ? 'Seleccionar Documento' : 'Cambiar Documento',
                                        press: () => controller.pickVehicleDocument(),
                                      ),
                                    ],
                                  ),
                                ),
                                spaceDown(Dimensions.space20),

                                // Campos del Formulario Dinámico
                                if (controller.formList.where((m) {
                                  final label = (m.label ?? '').toLowerCase();
                                  final name = (m.name ?? '').toLowerCase();
                                  final isYear = label.contains('año') || label.contains('ano') || label.contains('year') ||
                                                 name.contains('año') || name.contains('ano') || name.contains('year');
                                  return !(isYear && controller.yearList.isNotEmpty);
                                }).isNotEmpty) ...[
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
                                              final label = (model.label ?? '').toLowerCase();
                                              final name = (model.name ?? '').toLowerCase();
                                              final isYear = label.contains('año') || label.contains('ano') || label.contains('year') ||
                                                             name.contains('año') || name.contains('ano') || name.contains('year');
                                              if (isYear && controller.yearList.isNotEmpty) {
                                                return const SizedBox.shrink();
                                              }
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
                                                                controller: model.textEditingController,
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
                                                                  if (isYear && controller.selectedYear != null) {
                                                                    return null;
                                                                  }
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

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: boldDefault.copyWith(
        color: MyColor.getHeadingTextColor(),
        fontSize: Dimensions.fontLarge,
      ),
    );
  }

  Widget _buildSelectionField({
    required BuildContext context,
    required String label,
    required VoidCallback onTap,
  }) {
    return InnerShadowContainer(
      width: double.infinity,
      backgroundColor: MyColor.neutral50,
      borderRadius: Dimensions.largeRadius,
      blur: 6,
      offset: Offset(3, 3),
      shadowColor: MyColor.colorBlack.withValues(alpha: 0.04),
      isShadowTopLeft: true,
      isShadowBottomRight: true,
      padding: EdgeInsetsGeometry.symmetric(
        vertical: Dimensions.space10,
        horizontal: Dimensions.space16,
      ),
      child: InkWell(
        onTap: onTap,
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Expanded(
              child: Text(
                label,
                style: regularDefault.copyWith(
                  color: MyColor.colorBlack,
                ),
              ),
            ),
            Icon(
              Icons.arrow_drop_down,
              color: MyColor.colorBlack,
            ),
          ],
        ),
      ),
    );
  }
}
