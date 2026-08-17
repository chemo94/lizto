import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_spinkit/flutter_spinkit.dart';
import '../../../core/utils/dimensions.dart';
import '../../../core/utils/my_color.dart';
import '../../../core/utils/url_container.dart';
import '../../../core/helper/string_format_helper.dart';
import '../../screens/edit_profile/widget/profile_image.dart';

class MyImageWidget extends StatelessWidget {
  final String imageUrl;
  final double height;
  final double width;
  final double radius;
  final BoxFit boxFit;
  final Widget? errorWidget;
  final bool isProfile;
  final Color? color;

  const MyImageWidget({
    super.key,
    required this.imageUrl,
    this.color,
    this.height = 80,
    this.width = 100,
    this.radius = 5,
    this.boxFit = BoxFit.cover,
    this.errorWidget,
    this.isProfile = false,
  });

  @override
  Widget build(BuildContext context) {
    if (imageUrl.isEmpty || imageUrl == 'null' || imageUrl.endsWith('/null')) {
      printX('Image SKIP (null/empty): $imageUrl');
      return errorWidget ?? SizedBox(height: height, width: width, child: isProfile ? ProfileWidget(imagePath: '', onClicked: () {}) : ClipRRect(borderRadius: BorderRadius.circular(radius), child: Center(child: Icon(Icons.store_rounded, color: MyColor.colorGrey.withValues(alpha: 0.3)))));
    }
    final url = imageUrl.startsWith('http') ? imageUrl : '${UrlContainer.domainUrl}/$imageUrl';
    printX('Image LOADING: $url');
    return CachedNetworkImage(
      imageUrl: url,
      color: color,
      imageBuilder: (context, imageProvider) => Container(
        height: height,
        width: width,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(radius),
          image: DecorationImage(image: imageProvider, fit: boxFit, colorFilter: color != null ? ColorFilter.mode(color!, BlendMode.srcIn) : null),
        ),
      ),
      placeholder: (context, url) => SizedBox(
        height: height, width: width,
        child: ClipRRect(borderRadius: BorderRadius.circular(radius), child: Center(child: SpinKitFadingCube(color: MyColor.primaryColor.withValues(alpha: 0.3), size: Dimensions.space20))),
      ),
      errorWidget: (context, url, error) {
        printX('Image FAILED: $imageUrl -> $error');
        return errorWidget ?? SizedBox(
          height: height, width: width,
          child: isProfile ? ProfileWidget(imagePath: '', onClicked: () {}) : ClipRRect(borderRadius: BorderRadius.circular(radius), child: Center(child: Icon(Icons.image, color: MyColor.colorGrey.withValues(alpha: 0.5)))),
        );
      },
    );
  }
}
