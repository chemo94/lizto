import 'package:flutter/material.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';

class RegistrationStepIndicator extends StatelessWidget {
  final int currentStep; // 1: Datos personales, 2: Licencia/KYC, 3: Vehículo

  const RegistrationStepIndicator({
    super.key,
    required this.currentStep,
  });

  @override
  Widget build(BuildContext context) {
    final stepTitles = [
      'Datos Personales',
      'Licencia y DNI',
      'Vehículo',
    ];

    final stepSubtitles = [
      'Paso 1 de 3 · Información personal y zona',
      'Paso 2 de 3 · Fotos de licencia y documentos',
      'Paso 3 de 3 · Información de tu vehículo',
    ];

    final activeIndex = (currentStep - 1).clamp(0, 2);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: MyColor.borderColor.withValues(alpha: 0.8)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header with step counter pill
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                stepSubtitles[activeIndex],
                style: boldDefault.copyWith(
                  color: MyColor.primaryColor,
                  fontSize: 12,
                  letterSpacing: 0.2,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                decoration: BoxDecoration(
                  color: MyColor.primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '$currentStep/3',
                  style: boldDefault.copyWith(
                    color: MyColor.primaryColor,
                    fontSize: 11,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Stepper bar
          Row(
            children: [
              _buildStepNode(1, stepTitles[0], Icons.person_rounded),
              _buildConnector(1),
              _buildStepNode(2, stepTitles[1], Icons.badge_rounded),
              _buildConnector(2),
              _buildStepNode(3, stepTitles[2], Icons.two_wheeler_rounded),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildStepNode(int step, String label, IconData icon) {
    final isDone = step < currentStep;
    final isCurrent = step == currentStep;

    Color bgColor;
    Color iconColor;
    Color borderColor;

    if (isDone) {
      bgColor = const Color(0xFF10B981);
      iconColor = Colors.white;
      borderColor = const Color(0xFF10B981);
    } else if (isCurrent) {
      bgColor = MyColor.primaryColor;
      iconColor = Colors.white;
      borderColor = MyColor.primaryColor;
    } else {
      bgColor = Colors.grey.shade100;
      iconColor = Colors.grey.shade400;
      borderColor = Colors.grey.shade300;
    }

    return Expanded(
      flex: 3,
      child: Column(
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: bgColor,
              shape: BoxShape.circle,
              border: Border.all(color: borderColor, width: 2),
              boxShadow: isCurrent
                  ? [
                      BoxShadow(
                        color: MyColor.primaryColor.withValues(alpha: 0.3),
                        blurRadius: 8,
                        offset: const Offset(0, 2),
                      ),
                    ]
                  : null,
            ),
            child: Center(
              child: isDone
                  ? const Icon(Icons.check, size: 18, color: Colors.white)
                  : Icon(icon, size: 16, color: iconColor),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            label,
            textAlign: TextAlign.center,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: regularSmall.copyWith(
              fontSize: 10.5,
              fontWeight: isCurrent ? FontWeight.w700 : (isDone ? FontWeight.w600 : FontWeight.w400),
              color: isCurrent
                  ? MyColor.primaryColor
                  : (isDone ? const Color(0xFF10B981) : MyColor.bodyMutedTextColor),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildConnector(int step) {
    final isDone = step < currentStep;

    return Expanded(
      flex: 2,
      child: Container(
        margin: const EdgeInsets.only(bottom: 20),
        height: 3,
        decoration: BoxDecoration(
          color: isDone ? const Color(0xFF10B981) : Colors.grey.shade200,
          borderRadius: BorderRadius.circular(2),
        ),
      ),
    );
  }
}
