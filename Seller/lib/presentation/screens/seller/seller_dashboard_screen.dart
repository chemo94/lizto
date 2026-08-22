import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/controller/seller/seller_notification_service.dart';
import 'package:lizto_store/data/model/delivery/delivery_models.dart';
import 'package:lizto_store/presentation/components/image/my_network_image_widget.dart';
import 'package:lizto_store/presentation/screens/seller/seller_favor_create_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_menu_categories_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_order_detail_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_orders_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_product_add_edit_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_store_add_edit_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_wallet_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_stories_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_packages_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_subscriptions_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_analytics_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_notify_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_qr_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_tables_screen.dart';
import 'package:lizto_store/presentation/screens/seller/mozo_tables_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_kitchen_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_cash_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_reports_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_customers_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_billing_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_invoicing_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_expenses_screen.dart';
import 'package:lizto_store/data/repo/seller/seller_panel_repo.dart';
import 'package:lizto_store/data/controller/seller/seller_panel_controller.dart';
import 'package:lizto_store/data/repo/seller/seller_repo.dart';
import 'package:lizto_store/data/controller/seller/seller_package_controller.dart';
import 'package:lizto_store/data/repo/seller/seller_package_repo.dart';
import 'package:lizto_store/data/model/seller/package_models.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SellerDashboardScreen extends StatefulWidget {
  final bool showBackButton;
  const SellerDashboardScreen({super.key, this.showBackButton = false});

  @override
  State<SellerDashboardScreen> createState() => _SellerDashboardScreenState();
}

class _SellerDashboardScreenState extends State<SellerDashboardScreen> with WidgetsBindingObserver {
  late SellerController _c;
  late SellerNotificationService _notif;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    if (!Get.isRegistered<SellerController>()) {
      Get.put(SellerController(sellerRepo: SellerRepo(prefs: Get.find<SharedPreferences>())), permanent: true);
      Get.put(SellerNotificationService(), permanent: true);
    }
    _c = Get.find<SellerController>();
    _notif = Get.find<SellerNotificationService>();
    if (!Get.isRegistered<SellerPackageController>()) {
      Get.put(SellerPackageRepo(apiClient: Get.find()));
      Get.put(SellerPackageController(repo: Get.find()));
    }
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadAll());
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) _loadAll();
  }

  Future<void> _loadAll() async {
    await Future.wait([
      _c.loadDashboard(),
      _c.loadOrders(),
      _c.loadWalletBalance(),
    ]);
    if (!_c.isStaff && _c.stores.isNotEmpty) {
      final storeIds = _c.stores
          .map((s) {
            final raw = s['id'];
            return raw is int ? raw : int.tryParse(raw?.toString() ?? '') ?? 0;
          })
          .where((id) => id > 0)
          .toList();
      if (storeIds.isNotEmpty) {
        Get.find<SellerPackageController>().loadAllStoresSubscriptions(storeIds);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerController>(
      builder: (c) {
        if (c.isStaff) {
          final showOnlyKitchen = c.staffPosition == 'cocinero' || (c.staffPermissions.contains('kitchen') && !c.staffPermissions.contains('pos_orders'));
          final showOnlyTables = c.staffPermissions.contains('pos_orders') && !c.staffPermissions.contains('kitchen') && !c.staffPermissions.contains('billing') && !c.staffPermissions.contains('reports') && !c.staffPermissions.contains('settings') && !c.staffPermissions.contains('products');

          if (showOnlyKitchen || showOnlyTables) {
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (mounted) {
                if (showOnlyKitchen) {
                  Get.offAll(() => const SellerKitchenScreen());
                } else {
                  Get.offAll(() => const MozoTablesScreen());
                }
              }
            });
            return const Scaffold(body: Center(child: CircularProgressIndicator()));
          }
        }
        return Scaffold(
          backgroundColor: MyColor.screenBgColor,
          appBar: AppBar(
            backgroundColor: Colors.transparent,
            elevation: 0,
            automaticallyImplyLeading: widget.showBackButton,
            title: Text('Mi Negocio', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
            flexibleSpace: Container(
                decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [MyColor.primaryColor, Color(0xFF8B0000)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            )),
            actions: [
              Stack(
                children: [
                  IconButton(
                    icon: const Icon(Icons.notifications_outlined, color: MyColor.colorWhite),
                    onPressed: () {
                      _notif.markAllRead();
                      Get.to(() => const SellerOrdersScreen());
                    },
                  ),
                  if (_notif.unreadCount > 0)
                    Positioned(
                      right: 6,
                      top: 6,
                      child: Container(
                        padding: const EdgeInsets.all(5),
                        decoration: const BoxDecoration(
                          color: MyColor.colorWhite,
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          '${_notif.unreadCount}',
                          style: boldExtraSmall.copyWith(color: MyColor.primaryColor, fontSize: 9),
                        ),
                      ),
                    ),
                ],
              ),
              IconButton(
                icon: const Icon(Icons.logout_rounded, color: MyColor.colorWhite),
                onPressed: () {
                  c.logout();
                },
              ),
            ],
          ),
          body: RefreshIndicator(
            onRefresh: _loadAll,
            color: MyColor.primaryColor,
            child: ListView(
              padding: EdgeInsets.zero,
              children: [
                _buildHeader(c),
                if (!c.isStaff) _buildSubscriptionAlert(),
                if (!c.isStaff) _buildMetricCards(c),
                _buildQuickActions(c),
                if (!c.isStaff) _buildRecentOrders(c),
                if (!c.isStaff) _buildStoresSection(c),
                SizedBox(height: Dimensions.space32),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildHeader(SellerController c) {
    final name = c.isStaff ? (c.staffName ?? 'Personal') : (c.sellerData?['seller']?['name']?.toString() ?? 'Vendedor');
    final storesCount = c.sellerData?['total_stores'] ?? 0;
    final productsCount = c.sellerData?['total_products'] ?? 0;
    final store = c.stores.isNotEmpty ? c.stores.first : null;
    final storeImage = store != null ? '${c.storeImagePath}/${store['image'] ?? ''}' : null;

    return Container(
      padding: EdgeInsets.fromLTRB(Dimensions.space20, Dimensions.space16, Dimensions.space20, Dimensions.space24),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [MyColor.primaryColor, Color(0xFF8B0000)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(24),
          bottomRight: Radius.circular(24),
        ),
      ),
      child: Column(
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 28,
                backgroundColor: MyColor.colorWhite.withValues(alpha: 0.2),
                child: storeImage != null
                    ? ClipRRect(
                        borderRadius: BorderRadius.circular(28),
                        child: MyImageWidget(imageUrl: storeImage, height: 56, width: 56, boxFit: BoxFit.cover),
                      )
                    : Icon(Icons.storefront_rounded, size: 32, color: MyColor.colorWhite),
              ),
              SizedBox(width: Dimensions.space14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(c.isStaff ? '¡Hola, bienvenido!' : '¡Bienvenido!', style: regularSmall.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.75))),
                    SizedBox(height: 2),
                    Text(name, style: boldMediumLarge.copyWith(color: MyColor.colorWhite)),
                  ],
                ),
              ),
            ],
          ),
          if (!c.isStaff) ...[
            SizedBox(height: Dimensions.space16),
            Row(
              children: [
                _HeaderStat(label: 'Tiendas', value: '$storesCount'),
                _divider(),
                _HeaderStat(label: 'Productos', value: '$productsCount'),
                _divider(),
                _HeaderStat(label: 'Pedidos', value: '${c.orders.length}'),
              ],
            ),
          ],
        ],
      ),
    );
  }

  Widget _divider() {
    return Container(width: 1, height: 32, color: MyColor.colorWhite.withValues(alpha: 0.3));
  }

  Widget _buildSubscriptionAlert() {
    return GetBuilder<SellerPackageController>(
      builder: (pc) {
        final expiring = _findExpiringSubscription(pc);
        if (expiring == null) return const SizedBox();

        final s = expiring.subscription;
        final planName = s.packageName ?? s.package?.name ?? 'Plan';
        final daysLabel = '${expiring.days} ${expiring.days == 1 ? 'día' : 'días'}';
        final dateLabel = _formatDate(s.expiresAt);

        return Container(
          margin: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space16, Dimensions.space16, 0),
          padding: EdgeInsets.fromLTRB(Dimensions.space14, Dimensions.space12, Dimensions.space14, Dimensions.space4),
          decoration: BoxDecoration(
            color: const Color(0xFFF59E0B).withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(Dimensions.extraRadius),
            border: Border.all(color: const Color(0xFFF59E0B).withValues(alpha: 0.35)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF59E0B).withValues(alpha: 0.2),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.hourglass_top_rounded, color: Color(0xFFD97706), size: 18),
                  ),
                  SizedBox(width: Dimensions.space10),
                  Expanded(
                    child: Text(
                      'Tu suscripción al plan $planName vence en $daysLabel (el $dateLabel). Renueva ahora para evitar interrupciones.',
                      style: regularDefault.copyWith(color: const Color(0xFF92400E), height: 1.45),
                    ),
                  ),
                ],
              ),
              Align(
                alignment: Alignment.centerRight,
                child: TextButton.icon(
                  onPressed: () => Get.to(() => SellerPackagesScreen(storeId: expiring.storeId)),
                  icon: const Icon(Icons.refresh_rounded, size: 16, color: Color(0xFFD97706)),
                  label: Text('Renovar Suscripción', style: boldSmall.copyWith(color: const Color(0xFFD97706))),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  _ExpiringSubscription? _findExpiringSubscription(SellerPackageController pc) {
    _ExpiringSubscription? best;
    for (final entry in pc.subscriptionsByStore.entries) {
      for (final s in entry.value) {
        if (s.status != 'active') continue;
        final expires = DateTime.tryParse(s.expiresAt ?? '');
        if (expires == null) continue;
        final days = (expires.difference(DateTime.now()).inMinutes / 1440).ceil();
        if (days < 0 || days > 7) continue;
        if (best == null || days < best.days) {
          best = _ExpiringSubscription(storeId: entry.key, subscription: s, days: days);
        }
      }
    }
    return best;
  }

  String _formatDate(String? raw) {
    if (raw == null) return '';
    try {
      final d = DateTime.parse(raw).toLocal();
      final dd = d.day.toString().padLeft(2, '0');
      final mm = d.month.toString().padLeft(2, '0');
      return '$dd/$mm/${d.year}';
    } catch (_) {
      return raw;
    }
  }

  Widget _buildMetricCards(SellerController c) {
    return Padding(
      padding: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space20, Dimensions.space16, 0),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                  child: _MetricCard(
                icon: Icons.trending_up_rounded,
                label: 'Ventas hoy',
                value: 'S/ ${c.todayTotalSales.toStringAsFixed(2)}',
                color: const Color(0xFF10B981),
                bgColor: const Color(0xFF10B981).withValues(alpha: 0.1),
              )),
              SizedBox(width: Dimensions.space10),
              Expanded(
                  child: _MetricCard(
                icon: Icons.receipt_long_rounded,
                label: 'Total pedidos',
                value: '${c.orders.length}',
                color: MyColor.primaryColor,
                bgColor: MyColor.primaryColor.withValues(alpha: 0.1),
              )),
            ],
          ),
          SizedBox(height: Dimensions.space10),
          Row(
            children: [
              Expanded(
                  child: _MetricCard(
                icon: Icons.hourglass_empty_rounded,
                label: 'Pendientes',
                value: '${c.pendingOrderCount}',
                color: const Color(0xFFF59E0B),
                bgColor: const Color(0xFFF59E0B).withValues(alpha: 0.1),
              )),
              SizedBox(width: Dimensions.space10),
              Expanded(
                  child: _MetricCard(
                icon: Icons.account_balance_wallet_rounded,
                label: 'Billetera',
                value: 'S/ ${c.walletBalance.toStringAsFixed(2)}',
                color: MyColor.primaryColor,
                bgColor: MyColor.primaryColor.withValues(alpha: 0.1),
              )),
            ],
          ),
          SizedBox(height: Dimensions.space10),
          _MetricCard(
            icon: Icons.payments_outlined,
            label: 'Por cobrar a Lizto',
            value: 'S/ ${c.receivableBalance.toStringAsFixed(2)}',
            color: const Color(0xFF7C3AED),
            bgColor: const Color(0xFF7C3AED).withValues(alpha: 0.1),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActions(SellerController c) {
    final allActions = [
      {
        'icon': Icons.receipt_long_rounded,
        'label': 'Pedidos',
        'color': MyColor.primaryColor,
        'onTap': () => Get.to(() => const SellerOrdersScreen()),
        'permissions': ['pos_orders', 'billing'],
      },
      {
        'icon': Icons.account_balance_wallet_rounded,
        'label': 'Billetera',
        'color': const Color(0xFF10B981),
        'onTap': () => Get.to(() => const SellerWalletScreen()),
        'permissions': ['billing'],
      },
      {
        'icon': Icons.motorcycle_rounded,
        'label': 'Repartidor',
        'color': const Color(0xFFF59E0B),
        'onTap': () => Get.to(() => const SellerFavorCreateScreen()),
        'permissions': ['pos_orders'],
      },
      {
        'icon': Icons.auto_stories_rounded,
        'label': 'Historias',
        'color': const Color(0xFFE94560),
        'onTap': () => Get.to(() => const SellerStoriesScreen()),
        'permissions': ['products'],
      },
      {
        'icon': Icons.receipt_long_rounded,
        'label': 'Comanda',
        'color': const Color(0xFFF97316),
        'onTap': () {
          _initPanel();
          Get.to(() => const MozoTablesScreen());
        },
        'permissions': ['pos_orders'],
      },
      {
        'icon': Icons.table_bar_rounded,
        'label': 'Mesas',
        'color': const Color(0xFF7C3AED),
        'onTap': () {
          _initPanel();
          Get.to(() => const SellerTablesScreen());
        },
        'permissions': ['pos_orders'],
      },
      {
        'icon': Icons.soup_kitchen_rounded,
        'label': 'Cocina',
        'color': const Color(0xFFF59E0B),
        'onTap': () {
          _initPanel();
          Get.to(() => const SellerKitchenScreen());
        },
        'permissions': ['kitchen'],
      },
      {
        'icon': Icons.point_of_sale_rounded,
        'label': 'Caja',
        'color': const Color(0xFF10B981),
        'onTap': () {
          _initPanel();
          Get.to(() => const SellerCashScreen());
        },
        'permissions': ['billing'],
      },
      {
        'icon': Icons.bar_chart_rounded,
        'label': 'Reportes',
        'color': const Color(0xFF3B82F6),
        'onTap': () {
          _initPanel();
          Get.to(() => const SellerReportsScreen());
        },
        'permissions': ['reports'],
      },
      {
        'icon': Icons.people_alt_rounded,
        'label': 'Clientes',
        'color': const Color(0xFFEC4899),
        'onTap': () {
          _initPanel();
          Get.to(() => const SellerCustomersScreen());
        },
        'permissions': ['pos_orders', 'billing'],
      },
      {
        'icon': Icons.receipt_rounded,
        'label': 'Facturación',
        'color': const Color(0xFF10B981),
        'onTap': () {
          _initPanel();
          Get.to(() => const SellerBillingScreen());
        },
        'permissions': ['billing'],
      },
      {
        'icon': Icons.settings_applications_rounded,
        'label': 'Series',
        'color': const Color(0xFF7C3AED),
        'onTap': () {
          _initPanel();
          Get.to(() => const SellerInvoicingScreen());
        },
        'permissions': ['settings'],
      },
      {
        'icon': Icons.money_off_rounded,
        'label': 'Gastos',
        'color': const Color(0xFFEF4444),
        'onTap': () {
          _initPanel();
          Get.to(() => const SellerExpensesScreen());
        },
        'permissions': ['inventory', 'billing'],
      },
    ];

    final filteredActions = allActions.where((action) {
      if (!c.isStaff) return true;
      final perms = action['permissions'] as List<String>;
      return perms.any((p) => c.hasPermission(p));
    }).toList();

    List<Widget> rows = [];
    for (int i = 0; i < filteredActions.length; i += 3) {
      List<Widget> rowChildren = [];
      for (int j = 0; j < 3; j++) {
        if (i + j < filteredActions.length) {
          final act = filteredActions[i + j];
          rowChildren.add(
            Expanded(
              child: _ActionButton(
                icon: act['icon'] as IconData,
                label: act['label'] as String,
                color: act['color'] as Color,
                onTap: act['onTap'] as VoidCallback,
              ),
            ),
          );
        } else {
          rowChildren.add(const Expanded(child: SizedBox()));
        }
        if (j < 2) {
          rowChildren.add(SizedBox(width: Dimensions.space10));
        }
      }
      rows.add(Row(children: rowChildren));
      rows.add(SizedBox(height: Dimensions.space10));
    }

    if (rows.isEmpty) return const SizedBox();

    return Padding(
      padding: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space16, Dimensions.space16, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: EdgeInsets.only(left: Dimensions.space4, bottom: Dimensions.space12),
            child: Text('Acciones rápidas', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
          ),
          ...rows,
        ],
      ),
    );
  }

  Widget _buildRecentOrders(SellerController c) {
    final recent = c.orders.take(5).toList();

    return Padding(
      padding: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space20, Dimensions.space16, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Padding(
                padding: EdgeInsets.only(left: Dimensions.space4),
                child: Text('Pedidos recientes', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
              ),
              TextButton(
                onPressed: () => Get.to(() => const SellerOrdersScreen()),
                child: Text('Ver todos', style: regularDefault.copyWith(color: MyColor.primaryColor)),
              ),
            ],
          ),
          SizedBox(height: Dimensions.space8),
          if (c.isLoadingOrders)
            Container(
              padding: EdgeInsets.all(Dimensions.space32),
              child: Center(child: CircularProgressIndicator(color: MyColor.primaryColor)),
            )
          else if (recent.isEmpty)
            Container(
              width: double.infinity,
              padding: EdgeInsets.all(Dimensions.space32),
              decoration: BoxDecoration(
                color: MyColor.colorWhite,
                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
              ),
              child: Column(
                children: [
                  Icon(Icons.inbox_rounded, size: 48, color: MyColor.neutral300),
                  SizedBox(height: Dimensions.space12),
                  Text('Sin pedidos aún', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                  SizedBox(height: Dimensions.space4),
                  Text('Los pedidos aparecerán aquí', style: regularSmall.copyWith(color: MyColor.neutral300)),
                ],
              ),
            )
          else
            ...recent.map((order) => _buildOrderCard(c, order)),
        ],
      ),
    );
  }

  Widget _buildOrderCard(SellerController c, DeliveryOrderModel order) {
    return GestureDetector(
      onTap: () => Get.to(() => SellerOrderDetailScreen(orderId: order.id ?? 0)),
      child: Container(
        margin: EdgeInsets.only(bottom: Dimensions.space10),
        padding: EdgeInsets.all(Dimensions.space14),
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
          border: order.status == 'pending' ? Border.all(color: Colors.orange.withValues(alpha: 0.3), width: 1.5) : null,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: order.status == 'pending' ? 0.06 : 0.04),
              blurRadius: order.status == 'pending' ? 10 : 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: EdgeInsets.symmetric(horizontal: Dimensions.space8, vertical: Dimensions.space4),
                  decoration: BoxDecoration(
                    color: order.statusColor.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(order.statusIcon, size: 14, color: order.statusColor),
                      SizedBox(width: 4),
                      Text(order.statusLabel, style: boldSmall.copyWith(color: order.statusColor)),
                    ],
                  ),
                ),
                SizedBox(width: Dimensions.space8),
                Text(order.orderNo ?? '', style: semiBoldDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                Spacer(),
                Text(
                  'S/ ${order.total?.toStringAsFixed(2) ?? "0.00"}',
                  style: boldDefault.copyWith(color: MyColor.primaryColor),
                ),
              ],
            ),
            SizedBox(height: Dimensions.space8),
            Row(
              children: [
                Icon(Icons.person_outline_rounded, size: 16, color: MyColor.bodyMutedTextColor),
                SizedBox(width: 4),
                Expanded(
                  child: Text(
                    order.contactName ?? 'Cliente',
                    style: regularDefault,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (order.items != null) Text('${order.items!.length} producto(s)', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
              ],
            ),
            if (order.status == 'pending') ...[
              SizedBox(height: Dimensions.space12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _rejectOrder(c, order),
                      icon: Icon(Icons.close_rounded, size: 18, color: MyColor.redCancelTextColor),
                      label: Text('Rechazar', style: regularSmall.copyWith(color: MyColor.redCancelTextColor)),
                      style: OutlinedButton.styleFrom(
                        side: BorderSide(color: MyColor.redCancelTextColor.withValues(alpha: 0.5)),
                        padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                      ),
                    ),
                  ),
                  SizedBox(width: Dimensions.space10),
                  Expanded(
                    child: ElevatedButton.icon(
                      onPressed: () => _confirmOrder(c, order),
                      icon: Icon(Icons.check_rounded, size: 18, color: MyColor.colorWhite),
                      label: Text('Aceptar', style: regularSmall.copyWith(color: MyColor.colorWhite)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: MyColor.primaryColor,
                        padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
                        elevation: 2,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }

  Future<void> _confirmOrder(SellerController c, DeliveryOrderModel order) async {
    final success = await c.updateOrderStatus(order.id!, 'confirmed');
    if (success) {
      Get.snackbar('Pedido confirmado', 'El pedido ${order.orderNo} ha sido aceptado', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite, snackPosition: SnackPosition.BOTTOM);
    } else {
      Get.snackbar('Error', 'No se pudo confirmar el pedido', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
    }
  }

  Future<void> _rejectOrder(SellerController c, DeliveryOrderModel order) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Rechazar pedido'),
        content: Text('¿Estás seguro de rechazar ${order.orderNo}?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancelar')),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text('Rechazar', style: TextStyle(color: MyColor.redCancelTextColor)),
          ),
        ],
      ),
    );
    if (confirm != true) return;
    final success = await c.updateOrderStatus(order.id!, 'cancelled');
    if (success) {
      Get.snackbar('Pedido rechazado', 'El pedido ${order.orderNo} ha sido cancelado', backgroundColor: Colors.orange, colorText: MyColor.colorWhite, snackPosition: SnackPosition.BOTTOM);
    }
  }

  void _initPanel() {
    if (!Get.isRegistered<SellerPanelController>()) {
      Get.put(SellerPanelRepo(apiClient: Get.find()));
      Get.put(SellerPanelController(repo: Get.find()));
    }
  }

  Widget _buildStoresSection(SellerController c) {
    return Padding(
      padding: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space20, Dimensions.space16, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Padding(
                padding: EdgeInsets.only(left: Dimensions.space4),
                child: Text('Mis Tiendas', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
              ),
              TextButton.icon(
                onPressed: () => Get.to(() => const _SellerAddStoreSheet()),
                icon: Icon(Icons.add_rounded, size: 18, color: MyColor.primaryColor),
                label: Text('Agregar', style: regularDefault.copyWith(color: MyColor.primaryColor)),
              ),
            ],
          ),
          SizedBox(height: Dimensions.space8),
          if (c.stores.isEmpty)
            Container(
              width: double.infinity,
              padding: EdgeInsets.all(Dimensions.space32),
              decoration: BoxDecoration(
                color: MyColor.colorWhite,
                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
              ),
              child: Column(
                children: [
                  Icon(Icons.store_mall_directory_rounded, size: 48, color: MyColor.neutral300),
                  SizedBox(height: Dimensions.space12),
                  Text('No tienes tiendas', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                  SizedBox(height: Dimensions.space4),
                  Text('Crea tu primera tienda', style: regularSmall.copyWith(color: MyColor.neutral300)),
                ],
              ),
            )
          else
            ...c.stores.map((store) => _buildStoreCard(c, store, c.storeImagePath)),
        ],
      ),
    );
  }

  Widget _buildStoreCard(SellerController c, dynamic store, String imagePath) {
    final storeId = store['id'] is int ? store['id'] : int.parse(store['id'].toString());
    final storeName = store['name']?.toString() ?? 'Mi Tienda';
    final isOpen = store['is_open'] == 1 || store['is_open'] == true;

    return GestureDetector(
      onTap: () => _showStoreOptions(context, c, store, storeId, storeName),
      child: Container(
        margin: EdgeInsets.only(bottom: Dimensions.space10),
        padding: EdgeInsets.all(Dimensions.space12),
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
          border: Border.all(color: MyColor.neutral100),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(Dimensions.mediumRadius),
              child: MyImageWidget(
                imageUrl: '$imagePath/${store['image'] ?? ''}',
                height: 56,
                width: 56,
                boxFit: BoxFit.cover,
              ),
            ),
            SizedBox(width: Dimensions.space12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(storeName, style: boldDefault, overflow: TextOverflow.ellipsis),
                      ),
                      Container(
                        padding: EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: isOpen ? const Color(0xFF10B981).withValues(alpha: 0.1) : MyColor.neutral200,
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          isOpen ? 'Abierto' : 'Cerrado',
                          style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            color: isOpen ? const Color(0xFF10B981) : MyColor.bodyMutedTextColor,
                          ),
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 4),
                  Text(
                    '${store['products_count'] ?? 0} productos',
                    style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor),
                  ),
                ],
              ),
            ),
            SizedBox(width: Dimensions.space8),
            Icon(Icons.chevron_right_rounded, color: MyColor.neutral300),
          ],
        ),
      ),
    );
  }

  void _showStoreOptions(BuildContext context, SellerController c, dynamic store, int storeId, String storeName) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: ConstrainedBox(
            constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.75),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(color: MyColor.neutral200, borderRadius: BorderRadius.circular(2)),
                  ),
                  const SizedBox(height: 16),
                  Text(storeName, style: boldLarge.copyWith(fontSize: 18)),
                  Text('¿Qué quieres gestionar?', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                  const SizedBox(height: 20),
                  _StoreOptionTile(
                    icon: Icons.inventory_2_rounded,
                    color: MyColor.primaryColor,
                    title: 'Productos',
                    subtitle: 'Ver, agregar y editar productos',
                    onTap: () {
                      Navigator.pop(ctx);
                      Get.to(() => _SellerProductsScreen(storeId: storeId));
                    },
                  ),
                  const SizedBox(height: 10),
                  _StoreOptionTile(
                    icon: Icons.category_rounded,
                    color: const Color(0xFF7C3AED),
                    title: 'Categorías del menú',
                    subtitle: 'Organiza tus productos en secciones',
                    onTap: () {
                      Navigator.pop(ctx);
                      Get.to(() => SellerMenuCategoriesScreen(storeId: storeId, storeName: storeName));
                    },
                  ),
                  const SizedBox(height: 10),
                  _StoreOptionTile(
                    icon: Icons.edit_note_rounded,
                    color: const Color(0xFF0D9488),
                    title: 'Editar Negocio',
                    subtitle: 'Modifica los datos y horarios de tu tienda',
                    onTap: () {
                      Navigator.pop(ctx);
                      Get.to(() => SellerStoreAddEditScreen(store: store));
                    },
                  ),
                  const SizedBox(height: 10),
                  _StoreOptionTile(
                    icon: Icons.workspace_premium_rounded,
                    color: const Color(0xFF7C3AED),
                    title: 'Planes Empresariales',
                    subtitle: 'Compra paquetes para tu tienda',
                    onTap: () {
                      Navigator.pop(ctx);
                      Get.to(() => SellerPackagesScreen(storeId: storeId));
                    },
                  ),
                  const SizedBox(height: 10),
                  _StoreOptionTile(
                    icon: Icons.card_membership_rounded,
                    color: const Color(0xFFF59E0B),
                    title: 'Mis Suscripciones',
                    subtitle: 'Ver tus planes activos',
                    onTap: () {
                      Navigator.pop(ctx);
                      Get.to(() => SellerSubscriptionsScreen(storeId: storeId));
                    },
                  ),
                  const SizedBox(height: 10),
                  _StoreOptionTile(
                    icon: Icons.analytics_rounded,
                    color: const Color(0xFF3B82F6),
                    title: 'Analytics',
                    subtitle: 'Dashboard de ventas y productos top',
                    onTap: () {
                      Navigator.pop(ctx);
                      Get.to(() => SellerAnalyticsScreen(storeId: storeId));
                    },
                  ),
                  const SizedBox(height: 10),
                  _StoreOptionTile(
                    icon: Icons.notifications_active_rounded,
                    color: const Color(0xFFEC4899),
                    title: 'Notificar Clientes',
                    subtitle: 'Envía push a tus clientes',
                    onTap: () {
                      Navigator.pop(ctx);
                      Get.to(() => SellerNotifyScreen(storeId: storeId));
                    },
                  ),
                  const SizedBox(height: 10),
                  _StoreOptionTile(
                    icon: Icons.qr_code_2_rounded,
                    color: const Color(0xFF0EA5E9),
                    title: 'QR de Tienda',
                    subtitle: 'Genera QR con link a tu menú',
                    onTap: () {
                      Navigator.pop(ctx);
                      Get.to(() => SellerQrScreen(storeId: storeId));
                    },
                  ),
                  const SizedBox(height: 10),
                  _StoreOptionTile(
                    icon: Icons.delete_forever_rounded,
                    color: MyColor.redCancelTextColor,
                    title: 'Eliminar Negocio',
                    subtitle: 'Borra esta tienda definitivamente',
                    onTap: () {
                      Navigator.pop(ctx);
                      showDialog(
                        context: context,
                        builder: (alertCtx) => AlertDialog(
                          title: const Text('Eliminar Tienda'),
                          content: const Text('¿Estás seguro de que deseas eliminar este negocio y todos sus productos?'),
                          actions: [
                            TextButton(onPressed: () => Navigator.pop(alertCtx), child: const Text('Cancelar')),
                            TextButton(
                              onPressed: () async {
                                Navigator.pop(alertCtx);
                                final success = await c.deleteStore(storeId);
                                if (success) {
                                  Get.snackbar('Eliminado', 'Tienda eliminada con éxito', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
                                }
                              },
                              child: Text('Eliminar', style: TextStyle(color: MyColor.redCancelTextColor)),
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
      },
    );
  }
}

class _HeaderStat extends StatelessWidget {
  final String label;
  final String value;
  const _HeaderStat({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Text(value, style: boldMediumLarge.copyWith(color: MyColor.colorWhite, fontSize: 20)),
          SizedBox(height: 2),
          Text(label, style: regularSmall.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.75))),
        ],
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color color;
  final Color bgColor;
  const _MetricCard({required this.icon, required this.label, required this.value, required this.color, required this.bgColor});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space14),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(color: bgColor, borderRadius: BorderRadius.circular(Dimensions.mediumRadius)),
            child: Icon(icon, color: color, size: 22),
          ),
          SizedBox(width: Dimensions.space10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(value, style: boldMediumLarge.copyWith(fontSize: 16)),
                Text(label, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;
  const _ActionButton({required this.icon, required this.label, required this.color, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.symmetric(vertical: Dimensions.space14),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(Dimensions.largeRadius),
          border: Border.all(color: color.withValues(alpha: 0.15)),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 28),
            SizedBox(height: Dimensions.space6),
            Text(label, style: boldSmall.copyWith(color: color)),
          ],
        ),
      ),
    );
  }
}

class _StoreOptionTile extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String title;
  final String subtitle;
  final VoidCallback onTap;
  const _StoreOptionTile({required this.icon, required this.color, required this.title, required this.subtitle, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.06),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: color.withValues(alpha: 0.15)),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: boldDefault),
                  const SizedBox(height: 2),
                  Text(subtitle, style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                ],
              ),
            ),
            Icon(Icons.arrow_forward_ios_rounded, size: 16, color: color),
          ],
        ),
      ),
    );
  }
}

class _SellerAddStoreSheet extends StatelessWidget {
  const _SellerAddStoreSheet();

  @override
  Widget build(BuildContext context) => const SellerStoreAddEditScreen();
}

class _SellerProductsScreen extends StatefulWidget {
  final int storeId;
  const _SellerProductsScreen({required this.storeId});

  @override
  State<_SellerProductsScreen> createState() => _SellerProductsScreenState();
}

class _SellerProductsScreenState extends State<_SellerProductsScreen> {
  final Set<int> _togglingIds = {};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<SellerController>().loadProducts(widget.storeId);
    });
  }

  @override
  Widget build(BuildContext context) {
    return GetBuilder<SellerController>(
      builder: (c) {
        return Scaffold(
          backgroundColor: MyColor.cardBgColor,
          appBar: AppBar(
            backgroundColor: MyColor.primaryColor,
            title: Text('Productos', style: boldLarge.copyWith(color: MyColor.colorWhite)),
            centerTitle: true,
          ),
          floatingActionButton: FloatingActionButton.extended(
            backgroundColor: MyColor.primaryColor,
            onPressed: () => Get.to(() => SellerProductAddEditScreen(storeId: widget.storeId)),
            icon: const Icon(Icons.add, color: MyColor.colorWhite),
            label: Text('Agregar', style: boldDefault.copyWith(color: MyColor.colorWhite)),
          ),
          body: c.isLoading
              ? const Center(child: CircularProgressIndicator())
              : c.products.isEmpty
                  ? Center(child: Text('Sin productos', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)))
                  : RefreshIndicator(
                      onRefresh: () => c.loadProducts(widget.storeId),
                      child: ListView.builder(
                        padding: EdgeInsets.all(Dimensions.space16),
                        itemCount: c.products.length,
                        itemBuilder: (_, i) {
                          final p = c.products[i];
                          return GestureDetector(
                            onTap: () => Get.to(() => SellerProductAddEditScreen(storeId: widget.storeId, product: p)),
                            child: Container(
                              margin: EdgeInsets.only(bottom: Dimensions.space10),
                              padding: EdgeInsets.all(Dimensions.space12),
                              decoration: BoxDecoration(
                                color: MyColor.colorWhite,
                                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.04),
                                    blurRadius: 6,
                                    offset: const Offset(0, 2),
                                  )
                                ],
                              ),
                              child: Row(
                                children: [
                                  ClipRRect(
                                    borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                                    child: MyImageWidget(
                                      imageUrl: '${c.productImagePath}/${p.image}',
                                      height: 56,
                                      width: 56,
                                      boxFit: BoxFit.cover,
                                    ),
                                  ),
                                  SizedBox(width: Dimensions.space12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(p.name ?? '', style: boldDefault),
                                        SizedBox(height: 4),
                                        Text(
                                          'S/ ${p.finalPrice.toStringAsFixed(2)}',
                                          style: boldDefault.copyWith(color: MyColor.primaryColor),
                                        ),
                                      ],
                                    ),
                                  ),
                                  if (_togglingIds.contains(p.id))
                                    Padding(
                                      padding: EdgeInsets.all(Dimensions.space10),
                                      child: SizedBox(
                                        height: 20,
                                        width: 20,
                                        child: CircularProgressIndicator(strokeWidth: 2, color: MyColor.primaryColor),
                                      ),
                                    )
                                  else
                                    Switch(
                                      value: p.status == 1,
                                      activeTrackColor: MyColor.primaryColor,
                                      onChanged: (val) async {
                                        setState(() => _togglingIds.add(p.id!));
                                        final repo = c.sellerRepo;
                                        final response = await repo.toggleProductStatus(p.id!);
                                        setState(() => _togglingIds.remove(p.id!));
                                        if (response.statusCode == 200 && response.responseJson['status'] == 'success') {
                                          setState(() => p.status = val ? 1 : 0);
                                          Get.snackbar('Actualizado', val ? 'Producto habilitado' : 'Producto deshabilitado', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite, snackPosition: SnackPosition.BOTTOM);
                                        } else {
                                          Get.snackbar('Error', 'No se pudo actualizar el producto', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite, snackPosition: SnackPosition.BOTTOM);
                                        }
                                      },
                                    ),
                                  IconButton(
                                    icon: Icon(Icons.delete_outline, color: MyColor.redCancelTextColor, size: 22),
                                    onPressed: () {
                                      showDialog(
                                        context: context,
                                        builder: (ctx) => AlertDialog(
                                          title: const Text('Eliminar Producto'),
                                          content: const Text('¿Estás seguro de que deseas eliminar este producto?'),
                                          actions: [
                                            TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancelar')),
                                            TextButton(
                                              onPressed: () async {
                                                Navigator.pop(ctx);
                                                final success = await c.deleteProduct(p.id!, widget.storeId);
                                                if (success) {
                                                  Get.snackbar('Eliminado', 'Producto eliminado con éxito', backgroundColor: const Color(0xFF10B981), colorText: MyColor.colorWhite);
                                                }
                                              },
                                              child: Text('Eliminar', style: TextStyle(color: MyColor.redCancelTextColor)),
                                            ),
                                          ],
                                        ),
                                      );
                                    },
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                      ),
                    ),
        );
      },
    );
  }
}

class _ExpiringSubscription {
  final int storeId;
  final SellerSubscriptionModel subscription;
  final int days;
  const _ExpiringSubscription({required this.storeId, required this.subscription, required this.days});
}
