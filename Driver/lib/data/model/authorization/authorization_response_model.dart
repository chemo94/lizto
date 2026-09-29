import 'dart:convert';
import 'package:liztogo_pro/data/model/global/user/global_driver_model.dart';

class AuthorizationResponseModel {
  AuthorizationResponseModel({
    String? remark,
    String? status,
    List<String>? message,
    Data? data,
  }) {
    _remark = remark;
    _status = status;
    _message = message;
    _data = data;
  }

  AuthorizationResponseModel.fromJson(dynamic json) {
    dynamic parsed = json;
    if (json is String && json.trim().startsWith('{')) {
      try {
        parsed = jsonDecode(json);
      } catch (_) {}
    }
    if (parsed is Map) {
      _remark = parsed['remark']?.toString();
      _status = parsed['status']?.toString();
      if (parsed['message'] is List) {
        _message = List<String>.from((parsed['message'] as List).map((x) => x.toString()));
      } else if (parsed['message'] is Map) {
        _message = (parsed['message'] as Map).values.map((x) => x.toString()).toList();
      } else if (parsed['message'] != null) {
        _message = [parsed['message'].toString()];
      } else {
        _message = [];
      }
      _data = parsed['data'] != null && parsed['data'] is Map ? Data.fromJson(parsed['data']) : null;
    } else {
      _remark = 'error';
      _status = 'error';
      _message = json != null && json.toString().isNotEmpty ? [json.toString()] : [];
    }
  }

  String? _remark;
  String? _status;
  List<String>? _message;
  Data? _data;

  String? get remark => _remark;
  String? get status => _status;
  List<String>? get message => _message;
  Data? get data => _data;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['remark'] = _remark;
    map['status'] = _status;
    if (_message != null) {
      map['message'] = _message;
    }
    if (_data != null) {
      map['data'] = _data?.toJson();
    }
    return map;
  }
}

class Data {
  Data({String? actionId, GlobalDriverInfoModel? user, String? online}) {
    _actionId = actionId;
    _online = online;
    _user = user;
  }

  Data.fromJson(dynamic json) {
    _actionId = json['action_id'] != null ? json['action_id'].toString() : '';
    _online = json['online'] != null ? json['online'].toString() : 'false';
    _user = json['driver'] != null ? GlobalDriverInfoModel.fromJson(json['driver']) : null;
  }

  String? _actionId;
  String? _online;
  GlobalDriverInfoModel? _user;

  String? get actionId => _actionId;
  String? get online => _online;
  GlobalDriverInfoModel? get user => _user;

  Map<String, dynamic> toJson() {
    final map = <String, dynamic>{};
    map['action_id'] = _actionId;
    map['online'] = _online;
    map['driver'] = _user;
    return map;
  }
}
