import 'package:liztogo_pro/core/utils/url_container.dart';

class GlobalUser {
  String? id;
  String? firstname;
  String? lastname;
  String? username;
  String? email;
  String? avatar;
  String? countryCode;
  String? mobile;
  String? refBy;
  String? address;
  String? totalReviews;
  String? avgRating;
  String? status;
  String? kv;
  String? ev;
  String? sv;
  String? profileComplete;
  String? verCodeSendAt;
  String? tsc;
  String? banReason;
  String? createdAt;
  String? updatedAt;
  String? imageWithPath;

  GlobalUser({
    this.id,
    this.firstname,
    this.lastname,
    this.username,
    this.email,
    this.avatar,
    this.countryCode,
    this.mobile,
    this.refBy,
    this.address,
    this.totalReviews,
    this.avgRating,
    this.status,
    this.kv,
    this.ev,
    this.sv,
    this.profileComplete,
    this.verCodeSendAt,
    this.tsc,
    this.banReason,
    this.createdAt,
    this.updatedAt,
    this.imageWithPath,
  });

  factory GlobalUser.fromJson(Map<String, dynamic> json) => GlobalUser(
        id: json["id"]?.toString() ?? '',
        firstname: (json["firstname"] == null || json["firstname"] == 'null') ? '' : json["firstname"].toString(),
        lastname: (json["lastname"] == null || json["lastname"] == 'null') ? '' : json["lastname"].toString(),
        username: (json["username"] == null || json["username"] == 'null') ? '' : json["username"].toString(),
        email: (json["email"] == null || json["email"] == 'null') ? '' : json["email"].toString(),
        avatar: (json["image"] == null || json["image"] == 'null') ? '' : json["image"].toString(),
        countryCode: (json["country_code"] == null || json["country_code"] == 'null') ? '' : json["country_code"].toString(),
        mobile: (json["mobile"] == null || json["mobile"] == 'null') ? '' : json["mobile"].toString(),
        refBy: (json["ref_by"] == null || json["ref_by"] == 'null') ? '' : json["ref_by"].toString(),
        address: (json["address"] == null || json["address"] == 'null') ? '' : json["address"].toString(),
        totalReviews: json["total_reviews"]?.toString() ?? '0',
        avgRating: json["avg_rating"]?.toString() ?? '0',
        status: json["status"]?.toString() ?? '1',
        kv: json["kv"]?.toString() ?? "0",
        ev: json["ev"]?.toString() ?? "0",
        sv: json["sv"]?.toString() ?? "0",
        profileComplete: json["profile_complete"]?.toString() ?? "0",
        verCodeSendAt: (json["ver_code_send_at"] == null || json["ver_code_send_at"] == 'null') ? '' : json["ver_code_send_at"].toString(),
        tsc: (json["tsc"] == null || json["tsc"] == 'null') ? '' : json["tsc"].toString(),
        banReason: (json["ban_reason"] == null || json["ban_reason"] == 'null') ? '' : json["ban_reason"].toString(),
        createdAt: json["created_at"]?.toString(),
        updatedAt: json["updated_at"]?.toString(),
        imageWithPath: json["image"] != null && json["image"] != 'null' ? '${UrlContainer.domainUrl}/${json["image"]}' : '',
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "firstname": firstname,
        "lastname": lastname,
        "username": username,
        "email": email,
        "avatar": avatar,
        "country_code": countryCode,
        "mobile": mobile,
        "ref_by": refBy,
        "address": address,
        "total_reviews": totalReviews,
        "avg_rating": avgRating,
        "status": status,
        "kv": kv,
        "ev": ev,
        "sv": sv,
        "profile_complete": profileComplete,
        "ver_code_send_at": verCodeSendAt,
        "tsc": tsc,
        "ban_reason": banReason,
        "created_at": createdAt,
        "updated_at": updatedAt,
        "image_with_path": imageWithPath,
      };

  String getFullName() {
    return "${firstname ?? ""} ${lastname ?? ""}".trim();
  }
}
