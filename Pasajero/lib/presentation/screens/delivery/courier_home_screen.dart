import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/data/controller/delivery/courier_controller.dart';
import 'package:liztogo/data/repo/delivery/courier_repo.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'package:liztogo/presentation/screens/delivery/courier_earnings_screen.dart';
import 'package:liztogo/presentation/screens/delivery/courier_job_detail_screen.dart';
import 'package:liztogo/data/services/pusher_service.dart';

class CourierHomeScreen extends StatefulWidget {
  const CourierHomeScreen({super.key});

  @override
  State<CourierHomeScreen> createState() => _CourierHomeScreenState();
}

class _CourierHomeScreenState extends State<CourierHomeScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    if (!Get.isRegistered<CourierController>()) {
      Get.put(CourierController(courierRepo: CourierRepo(apiClient: Get.find<ApiClient>())));
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<CourierController>();
      c.loadPendingJobs();
      c.loadActiveJobs();

      // Subscribe to real-time nearby couriers channel for job broadcasts
      try {
        final channel = 'private-nearby-couriers';
        PusherManager().checkAndInitIfNeeded(channel);
        PusherManager().addListener((event) {
          if (event.channelName == channel && event.eventName == 'new_job_available') {
            c.loadPendingJobs();
            Get.snackbar(
              '💼 Nuevo Trabajo Disponible',
              'Hay un nuevo pedido esperando reparto cerca de ti',
              backgroundColor: MyColor.primaryColor,
              colorText: MyColor.colorWhite,
            );
          }
        });
      } catch (_) {}
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<CourierController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Panel de Repartidor', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            actions: [
              IconButton(
                icon: Icon(Icons.account_balance_wallet_rounded, color: MyColor.colorWhite),
                onPressed: () => Get.to(() => const CourierEarningsScreen()),
              ),
              Padding(
                padding: EdgeInsets.only(right: Dimensions.space8),
                child: Switch(
                  value: c.isOnline,
                  onChanged: (_) => c.toggleOnline(),
                  activeColor: const Color(0xFF10B981),
                  inactiveTrackColor: MyColor.colorWhite.withValues(alpha: 0.3),
                ),
              ),
            ],
            bottom: PreferredSize(
              preferredSize: Size.fromHeight(80),
              child: Column(
                children: [
                  Padding(
                    padding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: Dimensions.space6),
                    child: Row(
                      children: [
                        Icon(Icons.circle, size: 8, color: c.isOnline ? const Color(0xFF10B981) : MyColor.redCancelTextColor),
                        SizedBox(width: 4),
                        Text(c.isOnline ? 'En línea' : 'Fuera de línea',
                            style: regularSmall.copyWith(color: MyColor.colorWhite)),
                        Spacer(),
                      ],
                    ),
                  ),
                  TabBar(
                    controller: _tabController,
                    indicatorColor: MyColor.colorWhite,
                    labelColor: MyColor.colorWhite,
                    unselectedLabelColor: MyColor.colorWhite.withValues(alpha: 0.6),
                    tabs: [
                      Tab(text: 'Disponibles (${c.pendingJobs.length})'),
                      Tab(text: 'Activos (${c.activeJobs.length})'),
                    ],
                  ),
                ],
              ),
            ),
          ),
          body: TabBarView(
            controller: _tabController,
            children: [
              _buildPendingTab(c),
              _buildActiveTab(c),
            ],
          ),
        );
      },
    );
  }

  Widget _buildPendingTab(CourierController c) {
    if (c.isLoading) return const Center(child: CircularProgressIndicator());
    if (!c.isOnline) {
      return Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.wifi_off_rounded, size: 64, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3)),
          SizedBox(height: Dimensions.space12),
          Text('Estás fuera de línea', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          SizedBox(height: Dimensions.space4),
          Text('Activa el modo en línea para recibir pedidos', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
        ]),
      );
    }
    if (c.pendingJobs.isEmpty) {
      return RefreshIndicator(
        onRefresh: () => c.loadPendingJobs(),
        child: ListView(
          children: [
            SizedBox(height: MediaQuery.of(context).size.height * 0.3),
            Center(
              child: Column(mainAxisSize: MainAxisSize.min, children: [
                Icon(Icons.inbox_rounded, size: 64, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3)),
                SizedBox(height: Dimensions.space12),
                Text('Sin pedidos disponibles', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                SizedBox(height: Dimensions.space4),
                Text('Te notificaremos cuando lleguen nuevos pedidos', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
              ]),
            ),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => c.loadPendingJobs(),
      child: ListView.builder(
        padding: EdgeInsets.all(Dimensions.space12),
        itemCount: c.pendingJobs.length,
        itemBuilder: (_, i) => _buildJobCard(c, c.pendingJobs[i], isPending: true),
      ),
    );
  }

  Widget _buildActiveTab(CourierController c) {
    if (c.isLoading) return const Center(child: CircularProgressIndicator());
    if (c.activeJobs.isEmpty) {
      return Center(
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.local_shipping_rounded, size: 64, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3)),
          SizedBox(height: Dimensions.space12),
          Text('Sin pedidos activos', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
        ]),
      );
    }
    return RefreshIndicator(
      onRefresh: () => c.loadActiveJobs(),
      child: ListView.builder(
        padding: EdgeInsets.all(Dimensions.space12),
        itemCount: c.activeJobs.length,
        itemBuilder: (_, i) => _buildJobCard(c, c.activeJobs[i], isPending: false),
      ),
    );
  }

  Widget _buildJobCard(CourierController c, var job, {required bool isPending}) {
    return Container(
      margin: EdgeInsets.only(bottom: Dimensions.space10),
      padding: EdgeInsets.all(Dimensions.space12),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
        border: isPending ? Border.all(color: MyColor.primaryColor.withValues(alpha: 0.3)) : null,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            Container(
              padding: EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: 2),
              decoration: BoxDecoration(
                color: job.isFavor ? const Color(0xFFF59E0B).withValues(alpha: 0.1) : MyColor.primaryColor.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(job.isFavor ? 'Favor' : 'Delivery', style: regularSmall.copyWith(color: job.isFavor ? const Color(0xFFF59E0B) : MyColor.primaryColor, fontWeight: FontWeight.w600, fontSize: 10)),
            ),
            SizedBox(width: Dimensions.space8),
            Expanded(child: Text(job.orderNo ?? '', style: boldDefault)),
            Text('S/ ${job.totalEarning?.toStringAsFixed(2) ?? "0.00"}', style: boldDefault.copyWith(color: const Color(0xFF10B981))),
          ]),
          SizedBox(height: Dimensions.space8),
          if (job.description != null)
            Text(job.description!, style: regularDefault, maxLines: 2, overflow: TextOverflow.ellipsis),
          SizedBox(height: Dimensions.space8),
          _locationRow(Icons.location_on_outlined, job.pickupAddress ?? '', 'Recogida'),
          SizedBox(height: 4),
          _locationRow(Icons.flag_rounded, job.deliveryAddress ?? '', 'Entrega'),
          SizedBox(height: Dimensions.space8),
          Row(children: [
            if (job.deliveryFee != null)
              _infoTag('Delivery: S/ ${job.deliveryFee!.toStringAsFixed(2)}'),
            SizedBox(width: Dimensions.space8),
            _infoTag(job.statusLabel),
          ]),
          SizedBox(height: Dimensions.space8),
          Row(mainAxisAlignment: MainAxisAlignment.end, children: [
            if (isPending) ...[
              SizedBox(
                height: 36,
                child: OutlinedButton(
                  onPressed: () => _acceptJob(c, job),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF10B981),
                    side: BorderSide(color: const Color(0xFF10B981)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                    padding: EdgeInsets.symmetric(horizontal: Dimensions.space16),
                  ),
                  child: Text('Aceptar', style: boldDefault.copyWith(fontSize: Dimensions.fontSmall)),
                ),
              ),
            ],
            SizedBox(width: Dimensions.space8),
            SizedBox(
              height: 36,
              child: ElevatedButton(
                onPressed: () => Get.to(() => CourierJobDetailScreen(jobId: job.id ?? 0)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: MyColor.primaryColor, foregroundColor: MyColor.colorWhite,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                  padding: EdgeInsets.symmetric(horizontal: Dimensions.space16),
                ),
                child: Text('Ver detalle', style: boldDefault.copyWith(fontSize: Dimensions.fontSmall)),
              ),
            ),
          ]),
        ],
      ),
    );
  }

  Widget _locationRow(IconData icon, String address, String label) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: MyColor.bodyMutedTextColor),
        SizedBox(width: 4),
        Expanded(
          child: Text(address, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
        ),
      ],
    );
  }

  Widget _infoTag(String text) {
    return Container(
      padding: EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: MyColor.primaryColor.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(text, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor, fontSize: 10)),
    );
  }

  void _acceptJob(CourierController c, var job) {
    Get.defaultDialog(
      title: 'Aceptar pedido',
      middleText: '¿Aceptas este ${job.isFavor ? "favor" : "pedido"} por S/ ${job.totalEarning?.toStringAsFixed(2) ?? "0.00"}?',
      textConfirm: 'Aceptar',
      textCancel: 'Cancelar',
      confirmTextColor: MyColor.colorWhite,
      onConfirm: () async {
        Get.back();
        bool ok = await c.acceptJob(job.id ?? 0, job.type ?? 'delivery');
        if (ok) {
          Get.snackbar('Aceptado', 'Pedido aceptado correctamente',
              backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
        }
      },
    );
  }
}
