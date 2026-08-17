class BannerModel {
  final int? id;
  final String? image;
  BannerModel({this.id, this.image});
  factory BannerModel.fromJson(Map<String, dynamic> json) => BannerModel(
    id: json['id'],
    image: json['image']?.toString(),
  );
  Map<String, dynamic> toJson() => {'id': id, 'image': image};
}
