import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/route/route.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/presentation/components/buttons/rounded_button.dart';
import 'package:lizto_delivery/presentation/screens/delivery/order_list_screen.dart';

class OrderConfirmationScreen extends StatelessWidget {
  const OrderConfirmationScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      body: Center(
        child: Padding(
          padding: EdgeInsets.all(Dimensions.space20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.check_circle_rounded, size: 100, color: const Color(0xFF10B981)),
              SizedBox(height: Dimensions.space20),
              Text('Pedido realizado', style: boldLarge.copyWith(fontSize: Dimensions.fontExtraLarge)),
              SizedBox(height: Dimensions.space8),
              Text(
                'Tu pedido ha sido enviado a la tienda. Te notificaremos cuando esté listo.',
                textAlign: TextAlign.center,
                style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
              ),
              SizedBox(height: Dimensions.space32),
              RoundedButton(
                text: 'Ver mis pedidos',
                press: () => Get.off(() => const OrderListScreen()),
                isOutlined: false,
              ),
              SizedBox(height: Dimensions.space12),
              TextButton(
                onPressed: () => Get.offAllNamed(RouteHelper.dashboard),
                child: Text('Seguir comprando', style: regularDefault.copyWith(color: MyColor.primaryColor)),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
