import 'dart:io';
import 'package:flutter/cupertino.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';

class GlobalKYCForm {
  GlobalKYCForm({List<GlobalFormModel>? list}) {
    _list = list;
  }

  List<GlobalFormModel>? _list = [];
  List<GlobalFormModel>? get list => _list;

  GlobalKYCForm.fromJson(dynamic json) {
    try {
      _list = [];

      if (json is List<dynamic>) {
        for (var e in json) {
          if (e == null) continue;
          Map<String, dynamic> itemMap = {};
          if (e is Map) {
            itemMap = Map<String, dynamic>.from(e);
          } else if (e is MapEntry) {
            itemMap = Map<String, dynamic>.from(e.value);
          }
          List<String> optionsList = [];
          if (itemMap['options'] is List) {
            optionsList = (itemMap['options'] as List).map((x) => x.toString()).toList();
          }
          _list?.add(
            GlobalFormModel(
              itemMap['name']?.toString(),
              itemMap['label']?.toString(),
              itemMap['instruction']?.toString(),
              itemMap['is_required']?.toString(),
              itemMap['extensions']?.toString(),
              optionsList,
              itemMap['type']?.toString(),
              '',
            ),
          );
        }
      } else if (json is Map) {
        json.forEach((k, v) {
          if (v == null) return;
          Map<String, dynamic> itemMap = Map<String, dynamic>.from(v is Map ? v : {});
          List<String> optionsList = [];
          if (itemMap['options'] is List) {
            optionsList = (itemMap['options'] as List).map((x) => x.toString()).toList();
          }
          _list?.add(
            GlobalFormModel(
              itemMap['name']?.toString() ?? k.toString(),
              itemMap['label']?.toString() ?? k.toString(),
              itemMap['instruction']?.toString(),
              itemMap['is_required']?.toString(),
              itemMap['extensions']?.toString(),
              optionsList,
              itemMap['type']?.toString(),
              '',
            ),
          );
        });
      }
    } catch (e) {
      printX(e.toString());
    }
  }
}

class GlobalFormModel {
  String? name;
  String? label;
  String? instruction;
  String? isRequired;
  String? extensions;
  List<String>? options;
  String? type;
  dynamic selectedValue;
  File? imageFile;
  List<String>? cbSelected;
  TextEditingController? textEditingController;

  GlobalFormModel(
    this.name,
    this.label,
    this.instruction,
    this.isRequired,
    this.extensions,
    this.options,
    this.type,
    this.selectedValue, {
    this.cbSelected,
    this.imageFile,
  }) {
    textEditingController ??= TextEditingController();
  }
}
