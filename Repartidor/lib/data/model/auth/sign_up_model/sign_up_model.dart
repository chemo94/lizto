class SignUpModel {
  final String firstName;
  final String lastName;
  final String email;
  final String password;
  final String refference;
  final String serviceType;
  final bool? agree;

  SignUpModel({
    required this.firstName,
    required this.lastName,
    required this.email,
    required this.password,
    this.refference = "",
    this.serviceType = "delivery",
    required this.agree,
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
    };
  }

  factory SignUpModel.fromMap(Map<String, dynamic> map) {
    return SignUpModel(
      firstName: map['firstname'] as String,
      lastName: map['lastname'] as String,
      email: map['email'] as String,
      password: map['password'] as String,
      refference: map['reference'] as String,
      serviceType: map['service_type'] as String? ?? 'delivery',
      agree: map['agree'] as bool,
    );
  }
}
