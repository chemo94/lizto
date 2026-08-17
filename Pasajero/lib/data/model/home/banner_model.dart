class BannerModel {
  int? id;
  String? title;
  String? image;
  String? link;
  int? status;
  int? sortOrder;

  BannerModel({this.id, this.title, this.image, this.link, this.status, this.sortOrder});

  factory BannerModel.fromJson(Map<String, dynamic> json) => BannerModel(
        id: json["id"] is int ? json["id"] : int.tryParse(json["id"]?.toString() ?? ''),
        title: json["title"]?.toString(),
        image: json["image"]?.toString(),
        link: json["link"]?.toString(),
        status: json["status"] is int ? json["status"] : int.tryParse(json["status"]?.toString() ?? ''),
        sortOrder: json["sort_order"] is int ? json["sort_order"] : int.tryParse(json["sort_order"]?.toString() ?? ''),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "title": title,
        "image": image,
        "link": link,
        "status": status,
        "sort_order": sortOrder,
      };
}
