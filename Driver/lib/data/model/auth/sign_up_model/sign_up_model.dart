class SignUpModel {
  final String firstName;
  final String lastName;
  final String email;
  final String password;
  final String refference;
  final String serviceType;
  final bool? agree;
  final String? inviteCode;

  SignUpModel({
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.password,
    this.refference = "",
    this.serviceType = "ride",
    required this.agree,
    this.inviteCode,
  });

  Map<String, dynamic> toMap() {
    return {
      'firstname': firstName,
      'lastname': lastName,
      'email': email,
      'password': password,
      'password_confirmation': password,
      'reference': refference,
      'service_type': serviceType,
      'agree': agree.toString() == 'true' ? 'true' : '',
      'invite_code': inviteCode ?? '',
    };
  }

  factory SignUpModel.fromMap(Map<String, dynamic> map) {
    return SignUpModel(
      firstName: map['firstname'] as String,
      lastName: map['lastname'] as String,
      email: map['email'] as String,
      password: map['password'] as String,
      refference: map['reference'] as String,
      serviceType: map['service_type'] as String? ?? 'ride',
      agree: map['agree'] as bool,
      inviteCode: map['invite_code'] as String?,
    );
  }
}
