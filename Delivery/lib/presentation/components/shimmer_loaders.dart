import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';

class ShimmerListLoader extends StatelessWidget {
  final int itemCount;
  final double itemHeight;
  const ShimmerListLoader({super.key, this.itemCount = 6, this.itemHeight = 100});

  @override
  Widget build(BuildContext context) => _shimmer(child: ListView.builder(
    padding: EdgeInsets.all(Dimensions.space16), itemCount: itemCount,
    itemBuilder: (_, __) => _shimmerCard(),
  ));

  Widget _shimmerCard() => Container(
    margin: EdgeInsets.only(bottom: Dimensions.space12), height: itemHeight,
    decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(Dimensions.largeRadius)),
    child: Row(children: [
      Container(width: 80, height: itemHeight, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.horizontal(left: Radius.circular(Dimensions.largeRadius)))),
      SizedBox(width: Dimensions.space12),
      Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [
        Container(width: 140, height: 14, color: Colors.white), SizedBox(height: Dimensions.space8),
        Container(width: 100, height: 10, color: Colors.white), SizedBox(height: Dimensions.space12),
        Container(width: 80, height: 12, color: Colors.white),
      ])),
      Padding(padding: EdgeInsets.only(right: Dimensions.space12), child: Container(width: 50, height: 30, color: Colors.white)),
    ]),
  );

  Widget _shimmer({required Widget child}) => Shimmer.fromColors(baseColor: Colors.grey.shade300, highlightColor: Colors.grey.shade100, child: child);
}

// ── Store List Skeleton ──
class ShimmerStoreListLoader extends StatelessWidget {
  final int count;
  const ShimmerStoreListLoader({super.key, this.count = 4});

  @override
  Widget build(BuildContext context) => Shimmer.fromColors(
    baseColor: Colors.grey.shade300, highlightColor: Colors.grey.shade100,
    child: ListView.builder(padding: EdgeInsets.all(16), itemCount: count, itemBuilder: (_, __) => Container(
      margin: EdgeInsets.only(bottom: 14), height: 130,
      decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(18)),
      child: Row(children: [
        Container(width: 120, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.horizontal(left: Radius.circular(18)))),
        SizedBox(width: 14),
        Expanded(child: Padding(padding: EdgeInsets.symmetric(vertical: 16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Container(width: 160, height: 16, color: Colors.white), SizedBox(height: 8),
          Container(width: 120, height: 12, color: Colors.white), Spacer(),
          Row(children: [Container(width: 60, height: 20, color: Colors.white), SizedBox(width: 20), Container(width: 40, height: 20, color: Colors.white)]),
        ]))),
      ]),
    )),
  );
}

// ── Store Detail Skeleton ──
class ShimmerStoreDetailLoader extends StatelessWidget {
  const ShimmerStoreDetailLoader({super.key});
  @override
  Widget build(BuildContext context) => Shimmer.fromColors(
    baseColor: Colors.grey.shade300, highlightColor: Colors.grey.shade100,
    child: SingleChildScrollView(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Container(height: 200, color: Colors.white),
      SizedBox(height: 20),
      Padding(padding: EdgeInsets.symmetric(horizontal: 16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Container(width: 200, height: 24, color: Colors.white), SizedBox(height: 12),
        Container(width: 150, height: 14, color: Colors.white), SizedBox(height: 20),
        Row(children: [Container(width: 60, height: 30, color: Colors.white), SizedBox(width: 12), Container(width: 60, height: 30, color: Colors.white)]),
        SizedBox(height: 24),
        Container(height: 120, color: Colors.white),
        SizedBox(height: 16),
        Row(children: [Container(width: 100, height: 100, color: Colors.white), SizedBox(width: 12), Container(width: 100, height: 100, color: Colors.white)]),
      ])),
    ])),
  );
}

// ── Order List Skeleton ──
class ShimmerOrderListLoader extends StatelessWidget {
  final int count;
  const ShimmerOrderListLoader({super.key, this.count = 5});
  @override
  Widget build(BuildContext context) => Shimmer.fromColors(
    baseColor: Colors.grey.shade300, highlightColor: Colors.grey.shade100,
    child: ListView.builder(padding: EdgeInsets.all(16), itemCount: count, itemBuilder: (_, __) => Container(
      margin: EdgeInsets.only(bottom: 12), padding: EdgeInsets.all(16),
      decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(16)),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [Container(width: 120, height: 16, color: Colors.white), Spacer(), Container(width: 70, height: 20, color: Colors.white)]),
        SizedBox(height: 10),
        Container(width: 180, height: 12, color: Colors.white),
        SizedBox(height: 8),
        Container(width: 140, height: 12, color: Colors.white),
      ]),
    )),
  );
}

// ── Grid Loader ──
class ShimmerGridLoader extends StatelessWidget {
  final int itemCount;
  const ShimmerGridLoader({super.key, this.itemCount = 6});
  @override
  Widget build(BuildContext context) => Shimmer.fromColors(
    baseColor: Colors.grey.shade300, highlightColor: Colors.grey.shade100,
    child: GridView.builder(
      padding: EdgeInsets.all(Dimensions.space16), shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(crossAxisCount: 3, mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: 0.8),
      itemCount: itemCount,
      itemBuilder: (_, __) => Container(decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(16)), child: Column(children: [
        Expanded(child: Container(decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16)))),
        Container(height: 20, width: 60, color: Colors.white),
        SizedBox(height: 8),
      ])),
    ),
  );
}

// ── Horizontal Scroll Skeleton ──
class ShimmerHorizontalLoader extends StatelessWidget {
  final double itemWidth;
  final int count;
  const ShimmerHorizontalLoader({super.key, this.itemWidth = 170, this.count = 4});
  @override
  Widget build(BuildContext context) => Shimmer.fromColors(
    baseColor: Colors.grey.shade300, highlightColor: Colors.grey.shade100,
    child: SizedBox(height: 220, child: ListView.separated(
      scrollDirection: Axis.horizontal, padding: EdgeInsets.symmetric(horizontal: 16),
      itemCount: count, separatorBuilder: (_, __) => SizedBox(width: 14),
      itemBuilder: (_, __) => Container(width: itemWidth, decoration: BoxDecoration(color: MyColor.colorWhite, borderRadius: BorderRadius.circular(18)), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Container(height: 120, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(18)))),
        Padding(padding: EdgeInsets.all(10), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Container(width: 120, height: 14, color: Colors.white), SizedBox(height: 8),
          Container(width: 80, height: 10, color: Colors.white),
        ])),
      ])),
    )),
  );
}
