class SignUpModel {
  final String name;
  final String email;
  final String password;
  final String phone;
  final String address;
  final int zoneId;
  final double? latitude;
  final double? longitude;
  final String? businessName;
  final String? tradeName;
  final String? phoneToken;
  final String? dialCode;

  SignUpModel({
    required this.name,
    required this.email,
    required this.password,
    required this.phone,
    required this.address,
    required this.zoneId,
    this.latitude,
    this.longitude,
    this.businessName,
    this.tradeName,
    this.phoneToken,
    this.dialCode,
  });
}
