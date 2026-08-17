import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo/data/model/delivery/favor_models.dart';
import 'package:liztogo/presentation/components/customer_design.dart';
import 'package:liztogo/presentation/screens/delivery/favor_create_screen.dart';
import 'package:liztogo/presentation/screens/delivery/favor_list_screen.dart';

/// Rediseño visual del ingreso a Favor. Mantiene los formularios y el flujo
/// existente, que siguen obteniendo disponibilidad y tarifas desde API.
class FavorHomeScreen extends StatelessWidget {
  const FavorHomeScreen({super.key});

  @override
  Widget build(BuildContext context) => Scaffold(
        backgroundColor: CustomerDesign.surface,
        body: SafeArea(
          child: ListView(
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(20, 18, 20, 118),
            children: [
              Row(children: [
                // Only the title reserves the space occupied by the dashboard menu.
                const SizedBox(width: 56),
                const Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('Favores', style: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: -1, color: CustomerDesign.ink)),
                  SizedBox(height: 4),
                  Text('Resolvemos lo que necesitas hoy.', style: TextStyle(color: CustomerDesign.muted, fontSize: 15)),
                ])),
                IconButton.filledTonal(onPressed: () => Get.to(() => const FavorListScreen()), icon: const Icon(Icons.history_rounded), tooltip: 'Historial de favores'),
              ]),
              const SizedBox(height: 24),
              const _Hero(),
              const SizedBox(height: 28),
              const Text('¿Qué necesitas?', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: CustomerDesign.ink)),
              const SizedBox(height: 12),
              _Action(icon: Icons.shopping_bag_outlined, color: CustomerDesign.primary, title: 'Compra por mí', description: 'Indica qué necesitas y dónde comprarlo.', onTap: () => Get.to(() => FavorCreateScreen(favorType: FavorType.buy))),
              const SizedBox(height: 12),
              _Action(icon: Icons.send_rounded, color: const Color(0xFF6F59E8), title: 'Envía algo', description: 'Recogemos y llevamos tu paquete o documento.', onTap: () => Get.to(() => FavorCreateScreen(favorType: FavorType.send))),
              const SizedBox(height: 30),
              const Row(children: [
                _Trust(icon: Icons.location_searching_rounded, label: 'Seguimiento'),
                _Trust(icon: Icons.chat_bubble_outline_rounded, label: 'Chat directo'),
                _Trust(icon: Icons.verified_user_outlined, label: 'Entrega segura'),
              ]),
            ],
          ),
        ),
      );
}

class _Hero extends StatelessWidget {
  const _Hero();
  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(22),
        decoration: BoxDecoration(gradient: const LinearGradient(colors: [Color(0xFF173A2A), Color(0xFF117B43)]), borderRadius: BorderRadius.circular(28), boxShadow: const [BoxShadow(color: Color(0x35117B43), blurRadius: 24, offset: Offset(0, 12))]),
        child: const Row(children: [
          Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Un favor, sin complicaciones.', style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.w800, height: 1.1)),
            SizedBox(height: 9),
            Text('Crea tu solicitud, sigue el avance y confirma al recibir.', style: TextStyle(color: Color(0xD9FFFFFF), height: 1.35)),
          ])),
          SizedBox(width: 14),
          CircleAvatar(radius: 31, backgroundColor: Color(0x33FFFFFF), child: Icon(Icons.volunteer_activism_rounded, color: Colors.white, size: 34)),
        ]),
      );
}

class _Action extends StatelessWidget {
  final IconData icon;
  final Color color;
  final String title;
  final String description;
  final VoidCallback onTap;
  const _Action({required this.icon, required this.color, required this.title, required this.description, required this.onTap});
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
              Container(width: 54, height: 54, decoration: BoxDecoration(color: color.withValues(alpha: .12), borderRadius: BorderRadius.circular(18)), child: Icon(icon, color: color, size: 28)),
              const SizedBox(width: 15),
              Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text(title, style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: CustomerDesign.ink)), const SizedBox(height: 4), Text(description, style: const TextStyle(fontSize: 13, height: 1.3, color: CustomerDesign.muted))])),
              Icon(Icons.arrow_forward_rounded, color: color),
            ])),
      ));
}

class _Trust extends StatelessWidget {
  final IconData icon;
  final String label;
  const _Trust({required this.icon, required this.label});
  @override
  Widget build(BuildContext context) => Expanded(child: Column(children: [Icon(icon, color: CustomerDesign.primary, size: 21), const SizedBox(height: 7), Text(label, textAlign: TextAlign.center, style: const TextStyle(color: CustomerDesign.muted, fontSize: 11, fontWeight: FontWeight.w600))]));
}
