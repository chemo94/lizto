import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/data/model/delivery/favor_models.dart';
import 'package:lizto_delivery/presentation/components/customer_design.dart';
import 'package:lizto_delivery/presentation/screens/delivery/favor_create_screen.dart';
import 'package:lizto_delivery/presentation/screens/delivery/favor_list_screen.dart';

/// Punto de entrada del servicio de favores. Solo define la presentacion: el
/// formulario, precios, disponibilidad y seguimiento permanecen en sus flujos
/// API existentes.
class FavorHomeScreen extends StatelessWidget {
  final bool showBottomMenu;
  const FavorHomeScreen({super.key, this.showBottomMenu = false});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: CustomerDesign.surface,
      bottomNavigationBar: showBottomMenu
          ? CustomerBottomNavigation(
              currentIndex: 1,
              onChanged: (index) {
                if (index == 1) return;
                WidgetsBinding.instance.addPostFrameCallback((_) {
                  Get.offAllNamed('/dashboard_screen', arguments: index);
                });
              },
              items: const [
                CustomerNavItem(icon: Icons.shopping_bag_outlined, label: 'Comprar'),
                CustomerNavItem(icon: Icons.volunteer_activism_outlined, label: 'Favor'),
                CustomerNavItem(icon: Icons.receipt_long_outlined, label: 'Actividad'),
                CustomerNavItem(icon: Icons.person_outline_rounded, label: 'Perfil'),
              ],
            )
          : null,
      body: SafeArea(
        child: CustomScrollView(
          physics: const BouncingScrollPhysics(),
          slivers: [
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(20, 18, 20, 120),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  Row(
                    children: [
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Favores', style: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, color: CustomerDesign.ink, letterSpacing: -1)),
                            SizedBox(height: 4),
                            Text('Resolvemos lo que necesitas hoy.', style: TextStyle(color: CustomerDesign.muted, fontSize: 15)),
                          ],
                        ),
                      ),
                      IconButton.filledTonal(
                        onPressed: () => Get.to(() => const FavorListScreen()),
                        icon: const Icon(Icons.history_rounded),
                        tooltip: 'Ver historial de favores',
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  const _FavorHero(),
                  const SizedBox(height: 28),
                  const Text('¿Qué necesitas?', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: CustomerDesign.ink)),
                  const SizedBox(height: 12),
                  _FavorActionCard(
                    icon: Icons.shopping_bag_outlined,
                    tint: CustomerDesign.primary,
                    title: 'Compra por mí',
                    description: 'Indica qué necesitas y dónde comprarlo.',
                    onTap: () => Get.to(() => FavorCreateScreen(favorType: FavorType.buy)),
                  ),
                  const SizedBox(height: 12),
                  _FavorActionCard(
                    icon: Icons.send_rounded,
                    tint: const Color(0xFF6F59E8),
                    title: 'Envía algo',
                    description: 'Recogemos y llevamos tu paquete o documento.',
                    onTap: () => Get.to(() => FavorCreateScreen(favorType: FavorType.send)),
                  ),
                  const SizedBox(height: 28),
                  const _ConfidenceStrip(),
                ]),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FavorHero extends StatelessWidget {
  const _FavorHero();
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(22),
        decoration: BoxDecoration(
          gradient: const LinearGradient(colors: [Color(0xFF173A2A), Color(0xFF117B43)], begin: Alignment.topLeft, end: Alignment.bottomRight),
          borderRadius: BorderRadius.circular(28),
          boxShadow: const [BoxShadow(color: Color(0x35117B43), blurRadius: 24, offset: Offset(0, 12))],
        ),
        child: const Row(
          children: [
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Un favor, sin complicaciones.', style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800, height: 1.1)),
                SizedBox(height: 9),
                Text('Crea tu solicitud, sigue el avance y confirma al recibir.', style: TextStyle(color: Color(0xD9FFFFFF), height: 1.35)),
              ]),
            ),
            SizedBox(width: 14),
            CircleAvatar(radius: 31, backgroundColor: Color(0x33FFFFFF), child: Icon(Icons.volunteer_activism_rounded, color: Colors.white, size: 34)),
          ],
        ),
      );
}

class _FavorActionCard extends StatelessWidget {
  final IconData icon;
  final Color tint;
  final String title;
  final String description;
  final VoidCallback onTap;
  const _FavorActionCard({required this.icon, required this.tint, required this.title, required this.description, required this.onTap});

  @override
  Widget build(BuildContext context) => Semantics(
        button: true,
        label: title,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(24),
          child: Ink(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(24), border: Border.all(color: CustomerDesign.border)),
            child: Row(children: [
              Container(width: 54, height: 54, decoration: BoxDecoration(color: tint.withValues(alpha: .12), borderRadius: BorderRadius.circular(18)), child: Icon(icon, color: tint, size: 28)),
              const SizedBox(width: 15),
              Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(title, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: CustomerDesign.ink)),
                const SizedBox(height: 4),
                Text(description, style: const TextStyle(fontSize: 13, height: 1.3, color: CustomerDesign.muted)),
              ])),
              Icon(Icons.arrow_forward_rounded, color: tint),
            ]),
          ),
        ),
      );
}

class _ConfidenceStrip extends StatelessWidget {
  const _ConfidenceStrip();
  @override
  Widget build(BuildContext context) => const Row(
        children: [
          _Confidence(icon: Icons.location_searching_rounded, text: 'Seguimiento'),
          _Confidence(icon: Icons.chat_bubble_outline_rounded, text: 'Chat directo'),
          _Confidence(icon: Icons.verified_user_outlined, text: 'Entrega segura'),
        ],
      );
}

class _Confidence extends StatelessWidget {
  final IconData icon;
  final String text;
  const _Confidence({required this.icon, required this.text});
  @override
  Widget build(BuildContext context) => Expanded(
        child: Column(children: [
          Icon(icon, color: CustomerDesign.primary, size: 21),
          const SizedBox(height: 7),
          Text(text, textAlign: TextAlign.center, style: const TextStyle(color: CustomerDesign.muted, fontSize: 11, fontWeight: FontWeight.w600)),
        ]),
      );
}
