import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/controller/home/home_controller.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';

class BannerSlider extends StatefulWidget {
  const BannerSlider({super.key});

  @override
  State<BannerSlider> createState() => _BannerSliderState();
}

class _BannerSliderState extends State<BannerSlider> {
  final PageController _pageController = PageController();
  Timer? _timer;
  int _currentPage = 0;

  @override
  void initState() {
    super.initState();
    _startAutoPlay();
  }

  void _startAutoPlay() {
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 4), (_) {
      final banners = Get.find<HomeController>().bannersList;
      if (banners.length < 2) return;
      int nextPage = (_currentPage + 1) % banners.length;
      if (_pageController.hasClients) {
        _pageController.animateToPage(nextPage, duration: const Duration(milliseconds: 400), curve: Curves.easeInOut);
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<HomeController>(
      builder: (homeCtrl) {
        final banners = homeCtrl.bannersList;
        final imagePath = homeCtrl.bannerImagePath;

        if (banners.isEmpty) return const SizedBox.shrink();

        final String fullImagePath = imagePath.startsWith('http') ? imagePath : '${UrlContainer.domainUrl}/$imagePath';

        return Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(
              height: 140,
              child: PageView.builder(
                controller: _pageController,
                onPageChanged: (index) => setState(() => _currentPage = index),
                itemCount: banners.length,
                itemBuilder: (context, index) {
                  final banner = banners[index];
                  return ClipRRect(
                    borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                    child: MyImageWidget(
                      imageUrl: '$fullImagePath/${banner.image}',
                      width: double.infinity,
                      height: 140,
                      boxFit: BoxFit.cover,
                    ),
                  );
                },
              ),
            ),
            if (banners.length > 1) ...[
              SizedBox(height: Dimensions.space8),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: List.generate(banners.length, (index) {
                  return AnimatedContainer(
                    duration: const Duration(milliseconds: 300),
                    margin: EdgeInsets.symmetric(horizontal: 3),
                    height: 6,
                    width: _currentPage == index ? 20 : 6,
                    decoration: BoxDecoration(
                      color: _currentPage == index ? MyColor.primaryColor : MyColor.bodyMutedTextColor.withValues(alpha: 0.4),
                      borderRadius: BorderRadius.circular(3),
                    ),
                  );
                }),
              ),
            ],
          ],
        );
      },
    );
  }

}
