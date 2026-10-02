import 'dart:async';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_pro/core/utils/dimensions.dart';
import 'package:liztogo_pro/core/utils/style.dart';
import 'package:liztogo_pro/data/controller/ride/ride_request_manager.dart';
import 'package:liztogo_pro/data/model/ride/ride_opportunity_model.dart';
import 'package:liztogo_pro/data/services/api_client.dart';
import 'package:liztogo_pro/presentation/components/image/my_network_image_widget.dart';

class RideRequestScreen extends StatefulWidget {
  final RideOpportunity? opportunity;

  const RideRequestScreen({super.key, this.opportunity});

  @override
  State<RideRequestScreen> createState() => _RideRequestScreenState();
}

class _RideRequestScreenState extends State<RideRequestScreen> with SingleTickerProviderStateMixin {
  late RideOpportunity _opportunity;
  late int _remainingSeconds;
  late int _totalSeconds;
  Timer? _countdownTimer;
  bool _isExpired = false;

  late AnimationController _pulseController;
  late Animation<double> _pulseAnimation;

  @override
  void initState() {
    super.initState();

    final arg = widget.opportunity ?? Get.arguments;
    if (arg is RideOpportunity) {
      _opportunity = arg;
    } else if (arg is Map<String, dynamic>) {
      _opportunity = RideOpportunity.fromMap(arg);
    } else {
      _opportunity = RideRequestManager.instance.currentOpportunity ??
          RideOpportunity(
            id: '-1',
            uid: '',
            pickupLocation: 'Punto de recojo',
            destination: 'Punto de destino',
            fare: 0.0,
            distance: '',
            duration: '',
            expiresAt: DateTime.now().add(const Duration(seconds: 30)),
          );
    }

    _totalSeconds = _opportunity.totalSeconds > 0 ? _opportunity.totalSeconds : 30;
    _remainingSeconds = _opportunity.remainingSeconds;
    if (_remainingSeconds <= 0) {
      _remainingSeconds = _totalSeconds;
    }

    _pulseController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1000),
    )..repeat(reverse: true);

    _pulseAnimation = Tween<double>(begin: 1.0, end: 1.08).animate(
      CurvedAnimation(parent: _pulseController, curve: Curves.easeInOut),
    );

    _startCountdown();
  }

  void _startCountdown() {
    _countdownTimer?.cancel();
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      setState(() {
        if (_remainingSeconds > 1) {
          _remainingSeconds--;
        } else {
          _remainingSeconds = 0;
          _isExpired = true;
          _countdownTimer?.cancel();
          _onTimerExpired();
        }
      });
    });
  }

  void _onTimerExpired() {
    RideRequestManager.instance.onRideExpired(_opportunity);
    Future.delayed(const Duration(milliseconds: 1200), () {
      if (mounted) {
        RideRequestManager.instance.dismissCurrentRequest();
      }
    });
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    _pulseController.dispose();
    super.dispose();
  }

  Color _getTimerColor() {
    final progress = _remainingSeconds / _totalSeconds;
    if (progress > 0.5) return const Color(0xFF16A34A); // Green
    if (progress > 0.25) return const Color(0xFFF59E0B); // Amber
    return const Color(0xFFEF4444); // Red
  }

  @override
  Widget build(BuildContext context) {
    final currencySym = Get.find<ApiClient>().getCurrency(isSymbol: true);
    final progress = (_remainingSeconds / _totalSeconds).clamp(0.0, 1.0);
    final timerColor = _getTimerColor();

    return PopScope(
      canPop: false,
      child: Scaffold(
        backgroundColor: const Color(0xFF0F172A), // Modern deep dark navy
        body: SafeArea(
          child: Column(
            children: [
              // Top Bar with Service Category and Timer
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: Dimensions.space20,
                  vertical: Dimensions.space15,
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.local_taxi_rounded, color: Color(0xFF38BDF8), size: 18),
                          const SizedBox(width: 6),
                          Text(
                            _opportunity.serviceName.toUpperCase(),
                            style: boldDefault.copyWith(
                              color: Colors.white,
                              fontSize: Dimensions.fontSmall,
                              letterSpacing: 0.8,
                            ),
                          ),
                        ],
                      ),
                    ),

                    // Circular Countdown Timer
                    ScaleTransition(
                      scale: _remainingSeconds <= 5 ? _pulseAnimation : const AlwaysStoppedAnimation(1.0),
                      child: Stack(
                        alignment: Alignment.center,
                        children: [
                          SizedBox(
                            width: 52,
                            height: 52,
                            child: CircularProgressIndicator(
                              value: progress,
                              strokeWidth: 4.5,
                              backgroundColor: Colors.white.withValues(alpha: 0.1),
                              valueColor: AlwaysStoppedAnimation<Color>(timerColor),
                            ),
                          ),
                          Text(
                            '$_remainingSeconds',
                            style: boldExtraLarge.copyWith(
                              color: timerColor,
                              fontSize: 18,
                              fontWeight: FontWeight.w900,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Expired Banner if applicable
              if (_isExpired)
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.symmetric(horizontal: Dimensions.space20, vertical: Dimensions.space5),
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEF4444).withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: const Color(0xFFEF4444)),
                  ),
                  child: Center(
                    child: Text(
                      '¡SOLICITUD EXPIRADA!',
                      style: boldDefault.copyWith(color: const Color(0xFFEF4444)),
                    ),
                  ),
                ),

              // Main Content Card
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(horizontal: Dimensions.space20),
                  child: Column(
                    children: [
                      const SizedBox(height: Dimensions.space10),

                      // Fare Display
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: Dimensions.space20, horizontal: Dimensions.space15),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [Color(0xFF1E293B), Color(0xFF0F172A)],
                          ),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.3),
                              blurRadius: 15,
                              offset: const Offset(0, 6),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            Text(
                              'GANANCIA ESTIMADA',
                              style: semiBoldDefault.copyWith(
                                color: Colors.white54,
                                fontSize: Dimensions.fontSmall,
                                letterSpacing: 1.2,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              crossAxisAlignment: CrossAxisAlignment.baseline,
                              textBaseline: TextBaseline.alphabetic,
                              children: [
                                Text(
                                  currencySym,
                                  style: boldExtraLarge.copyWith(
                                    fontSize: 26,
                                    color: const Color(0xFF10B981),
                                  ),
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  _opportunity.fare.toStringAsFixed(2),
                                  style: boldExtraLarge.copyWith(
                                    fontSize: 44,
                                    color: Colors.white,
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: Dimensions.space12),

                            // Distance and Duration Chips
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                if (_opportunity.distance.isNotEmpty)
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.07),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      children: [
                                        const Icon(Icons.straighten_rounded, color: Color(0xFF94A3B8), size: 16),
                                        const SizedBox(width: 5),
                                        Text(
                                          '${_opportunity.distance} km',
                                          style: semiBoldDefault.copyWith(
                                            color: Colors.white70,
                                            fontSize: Dimensions.fontSmall,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                if (_opportunity.distance.isNotEmpty && _opportunity.duration.isNotEmpty)
                                  const SizedBox(width: Dimensions.space10),
                                if (_opportunity.duration.isNotEmpty)
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.07),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      children: [
                                        const Icon(Icons.access_time_rounded, color: Color(0xFF94A3B8), size: 16),
                                        const SizedBox(width: 5),
                                        Text(
                                          _opportunity.duration,
                                          style: semiBoldDefault.copyWith(
                                            color: Colors.white70,
                                            fontSize: Dimensions.fontSmall,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                              ],
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: Dimensions.space20),

                      // Route Timeline Card
                      Container(
                        padding: const EdgeInsets.all(Dimensions.space20),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E293B),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
                        ),
                        child: Column(
                          children: [
                            // Origin
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Column(
                                  children: [
                                    Container(
                                      width: 14,
                                      height: 14,
                                      decoration: BoxDecoration(
                                        color: const Color(0xFF10B981),
                                        shape: BoxShape.circle,
                                        border: Border.all(color: Colors.white, width: 2),
                                      ),
                                    ),
                                    Container(
                                      width: 2,
                                      height: 44,
                                      color: Colors.white.withValues(alpha: 0.2),
                                    ),
                                  ],
                                ),
                                const SizedBox(width: Dimensions.space15),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'PUNTO DE RECOJO',
                                        style: semiBoldDefault.copyWith(
                                          color: const Color(0xFF10B981),
                                          fontSize: 10,
                                          letterSpacing: 0.8,
                                        ),
                                      ),
                                      const SizedBox(height: 3),
                                      Text(
                                        _opportunity.pickupLocation,
                                        style: boldDefault.copyWith(
                                          color: Colors.white,
                                          fontSize: Dimensions.fontDefault,
                                        ),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),

                            // Destination
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Container(
                                  width: 14,
                                  height: 14,
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFEF4444),
                                    shape: BoxShape.circle,
                                    border: Border.all(color: Colors.white, width: 2),
                                  ),
                                ),
                                const SizedBox(width: Dimensions.space15),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'DESTINO',
                                        style: semiBoldDefault.copyWith(
                                          color: const Color(0xFFEF4444),
                                          fontSize: 10,
                                          letterSpacing: 0.8,
                                        ),
                                      ),
                                      const SizedBox(height: 3),
                                      Text(
                                        _opportunity.destination,
                                        style: boldDefault.copyWith(
                                          color: Colors.white,
                                          fontSize: Dimensions.fontDefault,
                                        ),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: Dimensions.space15),

                      // Rider Card
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.space15, vertical: Dimensions.space12),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E293B),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
                        ),
                        child: Row(
                          children: [
                            MyImageWidget(
                              imageUrl: _opportunity.riderAvatar,
                              height: 42,
                              width: 42,
                              radius: 21,
                              isProfile: true,
                            ),
                            const SizedBox(width: Dimensions.space12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    _opportunity.riderName,
                                    style: boldDefault.copyWith(color: Colors.white),
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    'Pasajero verificado',
                                    style: regularDefault.copyWith(
                                      color: Colors.white54,
                                      fontSize: Dimensions.fontSmall,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),

                      if (_opportunity.riderNote.isNotEmpty) ...[
                        const SizedBox(height: Dimensions.space12),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(Dimensions.space12),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.05),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.chat_bubble_outline_rounded, color: Colors.white60, size: 18),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  _opportunity.riderNote,
                                  style: regularDefault.copyWith(color: Colors.white70, fontSize: Dimensions.fontSmall),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),

              // Bottom Actions: ACEPTAR & RECHAZAR
              GetBuilder<RideRequestManager>(
                builder: (manager) {
                  return Container(
                    padding: const EdgeInsets.all(Dimensions.space20),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1E293B),
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.4),
                          blurRadius: 20,
                          offset: const Offset(0, -6),
                        ),
                      ],
                    ),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // Accept Button
                        SizedBox(
                          width: double.infinity,
                          height: 56,
                          child: ElevatedButton(
                            onPressed: (_isExpired || manager.isAccepting)
                                ? null
                                : () async {
                                    _countdownTimer?.cancel();
                                    await manager.acceptRide(_opportunity);
                                  },
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF10B981),
                              disabledBackgroundColor: Colors.grey.shade700,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(16),
                              ),
                              elevation: 4,
                            ),
                            child: manager.isAccepting
                                ? const SizedBox(
                                    width: 24,
                                    height: 24,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.5,
                                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                    ),
                                  )
                                : Row(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      const Icon(Icons.check_circle_rounded, color: Colors.white, size: 24),
                                      const SizedBox(width: 10),
                                      Text(
                                        'ACEPTAR CARRERA ($currencySym ${_opportunity.fare.toStringAsFixed(2)})',
                                        style: boldDefault.copyWith(
                                          color: Colors.white,
                                          fontSize: Dimensions.fontLarge,
                                          fontWeight: FontWeight.w800,
                                        ),
                                      ),
                                    ],
                                  ),
                          ),
                        ),

                        const SizedBox(height: Dimensions.space12),

                        // Reject Button
                        SizedBox(
                          width: double.infinity,
                          height: 48,
                          child: TextButton(
                            onPressed: manager.isAccepting
                                ? null
                                : () {
                                    _countdownTimer?.cancel();
                                    manager.rejectRide(_opportunity);
                                  },
                            style: TextButton.styleFrom(
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                            ),
                            child: Text(
                              'RECHAZAR SOLICITUD',
                              style: semiBoldDefault.copyWith(
                                color: Colors.white54,
                                fontSize: Dimensions.fontDefault,
                                letterSpacing: 0.5,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
