class GlobalDriverInfoModel {
  String? id;
  String? loginBy;
  String? zone;
  String? firstname;
  String? lastname;
  String? username;
  String? email;
  String? avatar;
  String? countryCode;
  String? mobile;
  String? totalReviews;
  String? avgRating;
  String? onlineStatus;
  String? status;
  String? licenseNumber;
  String? licenseExpire;
  String? licensePhoto;
  String? dv;
  String? vv;
  List<String>? riderRuleId;
  String? ev;
  String? sv;
  String? ts;
  String? tv;
  String? profileComplete;
  String? verCodeSendAt;
  String? tsc;
  String? banReason;
  String? createdAt;
  String? updatedAt;
  String? balance;
  String? walletBalance;
  String? imageWithPath;
  String? image;
  List<String>? rules;
  String? address;
  String? city;
  String? state;
  String? zip;
  String? countryName;
  String? dialCode;

  GlobalDriverInfoModel({
    this.id,
    this.loginBy,
    this.zone,
    this.firstname,
    this.lastname,
    this.username,
    this.email,
    this.avatar,
    this.countryCode,
    this.mobile,
    this.totalReviews,
    this.avgRating,
    this.onlineStatus,
    this.status,
    this.licenseNumber,
    this.licenseExpire,
    this.licensePhoto,
    this.dv,
    this.vv,
    this.riderRuleId,
    this.ev,
    this.sv,
    this.ts,
    this.tv,
    this.profileComplete,
    this.verCodeSendAt,
    this.tsc,
    this.banReason,
    this.createdAt,
    this.updatedAt,
    this.balance,
    this.walletBalance,
    this.rules,
    this.imageWithPath,
    this.image,
    this.address,
    this.city,
    this.state,
    this.zip,
    this.countryName,
    this.dialCode,
  });

  factory GlobalDriverInfoModel.fromJson(Map<String, dynamic> json) => GlobalDriverInfoModel(
        id: json["id"]?.toString() ?? '',
        loginBy: json["login_by"] != null ? json["login_by"].toString() : "0",
        zone: json["zone"]?.toString() ?? '',
        firstname: (json["firstname"] == null || json["firstname"] == 'null') ? '' : json["firstname"].toString(),
        lastname: (json["lastname"] == null || json["lastname"] == 'null') ? '' : json["lastname"].toString(),
        username: (json["username"] == null || json["username"] == 'null') ? '' : json["username"].toString(),
        email: (json["email"] == null || json["email"] == 'null') ? '' : json["email"].toString(),
        avatar: (json["image"] == null || json["image"] == 'null') ? '' : json["image"].toString(),
        countryCode: (json["country_code"] == null || json["country_code"] == 'null') ? '' : json["country_code"].toString(),
        mobile: (json["mobile"] == null || json["mobile"] == 'null') ? '' : json["mobile"].toString(),
        totalReviews: json["total_reviews"]?.toString() ?? '0',
        avgRating: json["avg_rating"]?.toString() ?? '0',
        onlineStatus: json["online_status"]?.toString() ?? '0',
        status: json["status"]?.toString() ?? '1',
        licenseNumber: (json["license_number"] == null || json["license_number"] == 'null') ? '' : json["license_number"].toString(),
        licenseExpire: (json["license_expire"] == null || json["license_expire"] == 'null') ? '' : json["license_expire"].toString(),
        licensePhoto: (json["license_photo"] == null || json["license_photo"] == 'null') ? '' : json["license_photo"].toString(),
        dv: json["dv"]?.toString() ?? "0",
        vv: json["vv"]?.toString() ?? "0",
        riderRuleId: json["rider_rule_id"] == null ? [] : List<String>.from(json["rider_rule_id"]!.map((x) => x)),
        ev: json["ev"]?.toString() ?? "0",
        sv: json["sv"]?.toString() ?? "0",
        ts: json["ts"]?.toString() ?? "",
        tv: json["tv"]?.toString() ?? "0",
        profileComplete: json["profile_complete"]?.toString() ?? "0",
        verCodeSendAt: (json["ver_code_send_at"] == null || json["ver_code_send_at"] == 'null') ? '' : json["ver_code_send_at"].toString(),
        tsc: (json["tsc"] == null || json["tsc"] == 'null') ? '' : json["tsc"].toString(),
        banReason: (json["ban_reason"] == null || json["ban_reason"] == 'null') ? '' : json["ban_reason"].toString(),
        createdAt: json["created_at"]?.toString(),
        updatedAt: json["updated_at"]?.toString(),
        balance: json["balance"] != null ? json["balance"].toString() : '',
        walletBalance: json["wallet_balance"]?.toString(),
        rules: json["rules"] == null ? [] : List<String>.from(json["rules"]!.map((x) => x)),
        imageWithPath: json["image_with_path"]?.toString(),
        image: json["image"]?.toString(),
        address: (json["address"] != null && json["address"] != 'null') ? json["address"].toString() : "",
        city: (json["city"] != null && json["city"] != 'null') ? json["city"].toString() : "",
        state: (json["state"] != null && json["state"] != 'null') ? json["state"].toString() : "",
        zip: (json["zip"] != null && json["zip"] != 'null') ? json["zip"].toString() : "",
        countryName: (json["country_name"] != null && json["country_name"] != 'null') ? json["country_name"].toString() : "",
        dialCode: (json["dial_code"] != null && json["dial_code"] != 'null') ? json["dial_code"].toString() : "",
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "login_by": loginBy,
        "zone": zone,
        "firstname": firstname,
        "lastname": lastname,
        "username": username,
        "email": email,
        "avatar": avatar,
        "country_code": countryCode,
        "mobile": mobile,
        "total_reviews": totalReviews,
        "avg_rating": avgRating,
        "online_status": onlineStatus,
        "status": status,
        "license_number": licenseNumber,
        "license_expire": licenseExpire,
        "license_photo": licensePhoto,
        "dv": dv,
        "vv": vv,
        "rider_rule_id": riderRuleId == null ? [] : List<dynamic>.from(riderRuleId!.map((x) => x)),
        "ev": ev,
        "sv": sv,
        "ts": ts,
        "tv": tv,
        "profile_complete": profileComplete,
        "ver_code_send_at": verCodeSendAt,
        "tsc": tsc,
        "ban_reason": banReason,
        "created_at": createdAt,
        "updated_at": updatedAt,
        "balance": balance,
        "wallet_balance": walletBalance,
        "rules": rules,
        "image_with_path": imageWithPath,
        "address": address,
        "city": city,
        "state": state,
        "zip": zip,
        "country_name": countryName,
        "dial_code": dialCode,
      };
  String getFullName() {
    return "${firstname ?? ""} ${lastname ?? ""}".trim();
  }
}

class Address {
  String? address;
  String? city;
  String? state;
  String? zip;
  String? country;

  Address({this.address, this.city, this.state, this.zip, this.country});

  factory Address.fromJson(Map<String, dynamic> json) => Address(
        address: json["address"],
        city: json["city"],
        state: json["state"],
        zip: json["zip"],
        country: json["country"],
      );

  Map<String, dynamic> toJson() => {
        "address": address,
        "city": city,
        "state": state,
        "zip": zip,
        "country": country,
      };
}

class Rule {
  String? id;
  String? name;
  String? status;
  String? createdAt;
  String? updatedAt;

  Rule({this.id, this.name, this.status, this.createdAt, this.updatedAt});

  factory Rule.fromJson(Map<String, dynamic> json) => Rule(
        id: json["id"].toString(),
        name: json["name"],
        status: json["status"].toString(),
        createdAt: json["created_at"]?.toString(),
        updatedAt: json["updated_at"]?.toString(),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "status": status,
        "created_at": createdAt,
        "updated_at": updatedAt,
      };
}
