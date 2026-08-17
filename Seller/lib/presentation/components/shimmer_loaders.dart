import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';

class ShimmerListLoader extends StatelessWidget {
  final int itemCount;
  final double itemHeight;
  const ShimmerListLoader({super.key, this.itemCount = 6, this.itemHeight = 100});

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: Colors.grey.shade300,
      highlightColor: Colors.grey.shade100,
      child: ListView.builder(
        padding: EdgeInsets.all(Dimensions.space16),
        itemCount: itemCount,
        itemBuilder: (_, __) => _shimmerCard(),
      ),
    );
  }

  Widget _shimmerCard() {
    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space12),
      height: itemHeight,
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
      ),
      child: Row(
        children: [
          Container(
            width: 80, height: itemHeight,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.horizontal(left: Radius.circular(Dimensions.largeRadius)),
            ),
          ),
          SizedBox(width: Dimensions.space12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(width: 140, height: 14, color: Colors.white),
                SizedBox(height: Dimensions.space8),
                Container(width: 100, height: 10, color: Colors.white),
                SizedBox(height: Dimensions.space12),
                Container(width: 80, height: 12, color: Colors.white),
              ],
            ),
          ),
          Padding(
            padding: EdgeInsets.only(right: Dimensions.space12),
            child: Container(width: 50, height: 30, color: Colors.white),
          ),
        ],
      ),
    );
  }
}

class ShimmerGridLoader extends StatelessWidget {
  final int itemCount;
  const ShimmerGridLoader({super.key, this.itemCount = 9});

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: Colors.grey.shade300,
      highlightColor: Colors.grey.shade100,
      child: GridView.builder(
        padding: EdgeInsets.all(Dimensions.space16),
        gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 3,
          crossAxisSpacing: Dimensions.space12,
          mainAxisSpacing: Dimensions.space12,
          childAspectRatio: 0.85,
        ),
        itemCount: itemCount,
        itemBuilder: (_, __) => Container(
          decoration: BoxDecoration(
            color: MyColor.colorWhite,
            borderRadius: BorderRadius.circular(Dimensions.largeRadius),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(width: 52, height: 52, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(Dimensions.defaultRadius))),
              SizedBox(height: Dimensions.space8),
              Container(width: 60, height: 10, color: Colors.white),
            ],
          ),
        ),
      ),
    );
  }
}

class ShimmerCardLoader extends StatelessWidget {
  final int lineCount;
  const ShimmerCardLoader({super.key, this.lineCount = 3});

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: Colors.grey.shade300,
      highlightColor: Colors.grey.shade100,
      child: Container(
        margin: EdgeInsets.all(Dimensions.space16),
        padding: EdgeInsets.all(Dimensions.space16),
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: List.generate(lineCount, (i) => Padding(
            padding: EdgeInsets.only(bottom: i < lineCount - 1 ? Dimensions.space12 : 0),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Container(width: double.infinity, height: 14, color: Colors.white),
              if (i == 0) ...[SizedBox(height: 4), Container(width: 200, height: 10, color: Colors.white)],
            ]),
          )),
        ),
      ),
    );
  }
}
