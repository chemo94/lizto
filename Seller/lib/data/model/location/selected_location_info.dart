class SelectedLocationInfo {
  final int? id;
  SelectedLocationInfo({this.id});
  factory SelectedLocationInfo.fromJson(Map<String, dynamic> json) => SelectedLocationInfo(id: json['id']);
  Map<String, dynamic> toJson() => {'id': id};
}
