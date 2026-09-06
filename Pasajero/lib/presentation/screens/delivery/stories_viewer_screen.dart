import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/repo/delivery/delivery_repo.dart';
import 'package:liztogo/presentation/components/image/my_network_image_widget.dart';

class StoriesViewerScreen extends StatefulWidget {
  final int storeId;
  final String storeName;
  final String storeImage;
  final List<dynamic> stories;
  final String mediaPath;
  const StoriesViewerScreen({super.key, required this.storeId, required this.storeName, required this.storeImage, required this.stories, required this.mediaPath});

  @override
  State<StoriesViewerScreen> createState() => _StoriesViewerScreenState();
}

class _StoriesViewerScreenState extends State<StoriesViewerScreen> with SingleTickerProviderStateMixin {
  int _currentIdx = 0;
  AnimationController? _progressCtrl;
  Timer? _timer;
  bool _paused = false;

  @override
  void initState() {
    super.initState();
    _startStory();
    _markView();
  }

  void _markView() async {
    final story = widget.stories[_currentIdx];
    final id = story['id'] as int?;
    if (id != null) {
      await DeliveryRepo(apiClient: Get.find()).apiClient.request(
            '${UrlContainer.baseUrl}delivery/stories/view/$id',
            'post',
            null,
            passHeader: true,
          );
    }
  }

  void _startStory() {
    _progressCtrl?.dispose();
    final story = widget.stories[_currentIdx];
    final duration = (story['duration'] as int? ?? 5).toDouble();
    _progressCtrl = AnimationController(vsync: this, duration: Duration(seconds: duration.toInt()));
    _progressCtrl?.addStatusListener((s) {
      if (s == AnimationStatus.completed && !_paused) {
        if (_currentIdx < widget.stories.length - 1) {
          setState(() => _currentIdx++);
          _markView();
          _startStory();
        } else {
          Get.back();
        }
      }
    });
    _progressCtrl?.forward();
  }

  void _goNext() {
    _progressCtrl?.stop();
    if (_currentIdx < widget.stories.length - 1) {
      setState(() => _currentIdx++);
      _markView();
      _startStory();
    } else {
      Get.back();
    }
  }

  void _goPrev() {
    _progressCtrl?.stop();
    if (_currentIdx > 0) {
      setState(() => _currentIdx--);
      _startStory();
    }
  }

  @override
  void dispose() {
    _progressCtrl?.dispose();
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final story = widget.stories[_currentIdx];
    return Scaffold(
      backgroundColor: Colors.black,
      body: GestureDetector(
        onTapDown: (d) {
          setState(() => _paused = true);
          _progressCtrl?.stop();
        },
        onTapUp: (d) {
          setState(() => _paused = false);
          _progressCtrl?.forward();
          if (d.globalPosition.dx > MediaQuery.of(context).size.width / 2) {
            _goNext();
          } else {
            _goPrev();
          }
        },
        onLongPressStart: (_) {
          _paused = true;
          _progressCtrl?.stop();
        },
        onLongPressEnd: (_) {
          _paused = false;
          _progressCtrl?.forward();
        },
        child: Stack(children: [
          MyImageWidget(
            imageUrl: '${widget.mediaPath}/${story['media_url']?.toString().split('/').last ?? story['media_url'] ?? ''}',
            height: double.infinity,
            width: double.infinity,
            boxFit: BoxFit.contain,
          ),
          SafeArea(
            child: Column(children: [
              Padding(
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                  child: Row(
                      children: List.generate(
                          widget.stories.length,
                          (i) => Expanded(
                                child: Container(
                                  height: 3,
                                  margin: EdgeInsets.symmetric(horizontal: 2),
                                  decoration: BoxDecoration(color: Colors.white.withOpacity(i < _currentIdx ? 1 : (i == _currentIdx ? 1 : 0.3)), borderRadius: BorderRadius.circular(2)),
                                ),
                              )))),
              Padding(
                  padding: EdgeInsets.symmetric(horizontal: 16),
                  child: Row(children: [
                    Container(width: 36, height: 36, decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: Colors.white, width: 2)), child: ClipOval(child: MyImageWidget(imageUrl: widget.storeImage, boxFit: BoxFit.cover))),
                    SizedBox(width: 10),
                    Expanded(child: Text(widget.storeName, style: boldDefault.copyWith(color: Colors.white))),
                    IconButton(icon: const Icon(Icons.close, color: Colors.white), onPressed: () => Get.back()),
                  ])),
            ]),
          ),
          if (story['caption'] != null && (story['caption'] as String).isNotEmpty) Positioned(bottom: 40, left: 16, right: 16, child: Text(story['caption'], style: regularDefault.copyWith(color: Colors.white), textAlign: TextAlign.center)),
        ]),
      ),
    );
  }
}
