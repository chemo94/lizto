import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

/// Envuelve cualquier widget con una animación de entrada suave.
/// Fade + SlideY desde abajo. Ideal para pantallas de delivery.
class AnimatedScreenEntrance extends StatelessWidget {
  final Widget child;
  final Duration delay;
  final Duration duration;
  final double slideOffset;

  const AnimatedScreenEntrance({
    super.key,
    required this.child,
    this.delay = Duration.zero,
    this.duration = const Duration(milliseconds: 380),
    this.slideOffset = 0.04,
  });

  @override
  Widget build(BuildContext context) {
    return child
        .animate()
        .fadeIn(duration: duration, delay: delay, curve: Curves.easeOut)
        .slideY(
          begin: slideOffset,
          end: 0,
          duration: duration,
          delay: delay,
          curve: Curves.easeOut,
        );
  }
}

/// Extension para aplicar la animación de entrada directamente en cualquier widget.
/// Uso: myWidget.animatedEntrance()
extension AnimatedEntranceExtension on Widget {
  Widget animatedEntrance({
    Duration delay = Duration.zero,
    Duration duration = const Duration(milliseconds: 380),
    double slideOffset = 0.04,
  }) {
    return AnimatedScreenEntrance(
      delay: delay,
      duration: duration,
      slideOffset: slideOffset,
      child: this,
    );
  }
}

/// Anima una lista de items con stagger (cada uno aparece un poco después).
/// Uso: MyCard().animatedStagger(index: i)
extension StaggeredAnimationExtension on Widget {
  Widget animatedStagger({
    required int index,
    Duration baseDuration = const Duration(milliseconds: 350),
    Duration staggerDelay = const Duration(milliseconds: 60),
    double slideOffset = 0.05,
  }) {
    final delay = Duration(milliseconds: staggerDelay.inMilliseconds * index);
    return animate()
        .fadeIn(duration: baseDuration, delay: delay, curve: Curves.easeOut)
        .slideY(
          begin: slideOffset,
          end: 0,
          duration: baseDuration,
          delay: delay,
          curve: Curves.easeOut,
        );
  }
}
