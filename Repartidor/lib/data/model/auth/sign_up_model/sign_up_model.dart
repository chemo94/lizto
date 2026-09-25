class SignUpModel {
  final String firstName;
  final String lastName;
  final String email;
  final String password;
  final String refference;
  final String serviceType;
  final String? mobile;
  final String? dialCode;
  final String? phoneToken;
  final bool? agree;

  SignUpModel({
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.password,
    this.refference = "",
    this.serviceType = "delivery",
    this.mobile,
    this.dialCode,
    this.phoneToken,
    required this.agree,
  });

  Map<String, dynamic> toMap() {
    final map = {
      'firstname': firstName,
      'lastname': lastName,
      'email': email,
      'password': password,
      'password_confirmation': password,
      'reference': refference,
      'service_type': serviceType,
      'agree': agree.toString() == 'true' ? 'true' : '',
    };
    if (mobile != null && mobile!.isNotEmpty) {
      map['mobile'] = mobile!;
    }
    if (dialCode != null && dialCode!.isNotEmpty) {
      map['dial_code'] = dialCode!;
    }
    if (phoneToken != null && phoneToken!.isNotEmpty) {
      map['phone_token'] = phoneToken!;
    }
    return map;
  }

  factory SignUpModel.fromMap(Map<String, dynamic> map) {
    return SignUpModel(
      firstName: map['firstname'] as String,
      lastName: map['lastname'] as String,
      email: map['email'] as String,
      password: map['password'] as String,
      refference: map['reference'] as String,
      serviceType: map['service_type'] as String? ?? 'delivery',
      mobile: map['mobile'] as String?,
      dialCode: map['dial_code'] as String?,
      phoneToken: map['phone_token'] as String?,
      agree: map['agree'] as bool,
    );
  }
}
