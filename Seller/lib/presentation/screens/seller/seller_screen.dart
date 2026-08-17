import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_store/core/utils/dimensions.dart';
import 'package:lizto_store/core/utils/my_color.dart';
import 'package:lizto_store/core/utils/style.dart';
import 'package:lizto_store/data/controller/seller/seller_controller.dart';
import 'package:lizto_store/data/controller/seller/seller_notification_service.dart';
import 'package:lizto_store/data/repo/seller/seller_repo.dart';
import 'package:lizto_store/presentation/components/buttons/rounded_button.dart';
import 'package:lizto_store/presentation/screens/seller/seller_dashboard_screen.dart';
import 'package:lizto_store/presentation/screens/seller/mozo_tables_screen.dart';
import 'package:lizto_store/presentation/screens/seller/seller_kitchen_screen.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SellerScreen extends StatefulWidget {
  const SellerScreen({super.key});

  @override
  State<SellerScreen> createState() => _SellerScreenState();
}

class _SellerScreenState extends State<SellerScreen> with SingleTickerProviderStateMixin {
  late SellerController _controller;
  late TextEditingController _nameCtrl, _emailCtrl, _phoneCtrl, _passCtrl, _confirmPassCtrl;
  bool _isLogin = true;
  bool _didNavigate = false;
  final _formKey = GlobalKey<FormState>();
  late AnimationController _animCtrl;
  late Animation<double> _fadeAnim;

  @override
  void initState() {
    super.initState();
    _animCtrl = AnimationController(vsync: this, duration: const Duration(milliseconds: 400));
    _fadeAnim = CurvedAnimation(parent: _animCtrl, curve: Curves.easeInOut);
    _animCtrl.forward();
    _initControllers();
    _controller = Get.put(SellerController(sellerRepo: SellerRepo(prefs: Get.find<SharedPreferences>())), permanent: true);
    Get.put(SellerNotificationService(), permanent: true);
    WidgetsBinding.instance.addPostFrameCallback((_) => _controller.restoreSession());
  }

  void _initControllers() {
    _nameCtrl = TextEditingController();
    _emailCtrl = TextEditingController();
    _phoneCtrl = TextEditingController();
    _passCtrl = TextEditingController();
    _confirmPassCtrl = TextEditingController();
  }

  void _clearFields() {
    _nameCtrl.clear(); _emailCtrl.clear(); _phoneCtrl.clear();
    _passCtrl.clear(); _confirmPassCtrl.clear();
    _controller.errorMessage = null;
    _controller.update();
  }

  void _toggleMode() {
    _animCtrl.reverse().then((_) {
      setState(() => _isLogin = !_isLogin);
      _clearFields();
      _animCtrl.forward();
    });
  }

  @override
  void dispose() {
    _animCtrl.dispose();
    _nameCtrl.dispose(); _emailCtrl.dispose(); _phoneCtrl.dispose();
    _passCtrl.dispose(); _confirmPassCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => GetBuilder<SellerController>(
    builder: (c) {
      if (c.isLoggedIn) {
        if (!_didNavigate) {
          _didNavigate = true;
          WidgetsBinding.instance.addPostFrameCallback((_) {
            if (mounted) {
              if (c.isStaff) {
                if (c.staffPosition == 'cocinero' || (c.staffPermissions.contains('kitchen') && !c.staffPermissions.contains('pos_orders'))) {
                  Get.offAll(() => const SellerKitchenScreen());
                } else if (c.staffPermissions.contains('pos_orders')) {
                  Get.offAll(() => const MozoTablesScreen());
                } else {
                  Get.offAll(() => const SellerDashboardScreen());
                }
              } else {
                Get.offAll(() => const SellerDashboardScreen());
              }
            }
          });
        }
        return const Scaffold(body: Center(child: CircularProgressIndicator()));
      }
      _didNavigate = false;
      return Scaffold(
        body: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter, end: Alignment.bottomCenter,
              colors: [Color(0xFF1A1A2E), Color(0xFF16213E), Color(0xFF0F3460), Color(0xFFF5F5F5)],
              stops: [0.0, 0.3, 0.5, 0.5],
            ),
          ),
          child: SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: FadeTransition(
                  opacity: _fadeAnim,
                  child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
                    const SizedBox(height: 40),
                    _buildBrand(),
                    const SizedBox(height: 32),
                    _buildToggle(),
                    const SizedBox(height: 28),
                    _isLogin ? _buildLoginForm(c) : _buildRegisterForm(c),
                    const SizedBox(height: 40),
                  ]),
                ),
              ),
            ),
          ),
        ),
      );
    },
  );

  Widget _buildBrand() => Column(children: [
    Container(
      width: 90, height: 90,
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: [Color(0xFFE94560), Color(0xFF533483)], begin: Alignment.topLeft, end: Alignment.bottomRight),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [BoxShadow(color: const Color(0xFFE94560).withOpacity(0.35), blurRadius: 24, offset: const Offset(0, 8))],
      ),
      child: const Icon(Icons.store_rounded, size: 48, color: Colors.white),
    ),
    const SizedBox(height: 20),
    Text('Lizto Seller', style: boldExtraLarge.copyWith(color: Colors.white, fontSize: 26, letterSpacing: -0.5)),
  ]);

  Widget _buildToggle() => Container(
    padding: const EdgeInsets.all(4),
    decoration: BoxDecoration(color: Colors.white.withOpacity(0.12), borderRadius: BorderRadius.circular(16)),
    child: Row(children: [
      Expanded(
        child: GestureDetector(
          onTap: _isLogin ? null : _toggleMode,
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 300), curve: Curves.easeOut,
            padding: const EdgeInsets.symmetric(vertical: 14),
            decoration: BoxDecoration(
              color: _isLogin ? Colors.white : Colors.transparent,
              borderRadius: BorderRadius.circular(13),
              boxShadow: _isLogin ? [BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 8, offset: const Offset(0, 2))] : null,
            ),
            child: Text('Iniciar sesión', textAlign: TextAlign.center, style: boldDefault.copyWith(color: _isLogin ? const Color(0xFF1A1A2E) : Colors.white70)),
          ),
        ),
      ),
      Expanded(
        child: GestureDetector(
          onTap: !_isLogin ? null : _toggleMode,
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 300), curve: Curves.easeOut,
            padding: const EdgeInsets.symmetric(vertical: 14),
            decoration: BoxDecoration(
              color: !_isLogin ? Colors.white : Colors.transparent,
              borderRadius: BorderRadius.circular(13),
              boxShadow: !_isLogin ? [BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 8, offset: const Offset(0, 2))] : null,
            ),
            child: Text('Crear cuenta', textAlign: TextAlign.center, style: boldDefault.copyWith(color: !_isLogin ? const Color(0xFF1A1A2E) : Colors.white70)),
          ),
        ),
      ),
    ]),
  );

  Widget _buildLoginForm(SellerController c) => Form(key: _formKey, child: Column(children: [
    _buildTextField(_emailCtrl, 'Correo electrónico', Icons.email_outlined, TextInputType.emailAddress, false, (v) => _validateEmail(v)),
    const SizedBox(height: 16),
    _buildTextField(_passCtrl, 'Contraseña', Icons.lock_outlined, TextInputType.text, true, (v) => _validateRequired(v, 'Contraseña'), onSubmit: _submitLogin),
    if (c.errorMessage != null) ...[const SizedBox(height: 16), _buildError(c.errorMessage!)],
    const SizedBox(height: 28),
    RoundedButton(text: 'Iniciar sesión', isLoading: c.isLoading, press: _submitLogin, isOutlined: false, bgColor: const Color(0xFFE94560), textColor: Colors.white),
    const SizedBox(height: 16),
    Text('¿Olvidaste tu contraseña? Contacta a soporte', style: regularSmall.copyWith(color: Colors.white54), textAlign: TextAlign.center),
  ]));

  Widget _buildRegisterForm(SellerController c) => Form(key: _formKey, child: Column(children: [
    _buildTextField(_nameCtrl, 'Nombre de la tienda', Icons.store_outlined, TextInputType.name, false, (v) => _validateRequired(v, 'Nombre'), capitalization: TextCapitalization.words),
    const SizedBox(height: 16),
    _buildTextField(_emailCtrl, 'Correo electrónico', Icons.email_outlined, TextInputType.emailAddress, false, (v) => _validateEmail(v)),
    const SizedBox(height: 16),
    _buildTextField(_phoneCtrl, 'Teléfono', Icons.phone_outlined, TextInputType.phone, false, (v) => _validateRequired(v, 'Teléfono')),
    const SizedBox(height: 16),
    _buildTextField(_passCtrl, 'Contraseña', Icons.lock_outlined, TextInputType.text, true, (v) => _validatePassword(v)),
    const SizedBox(height: 16),
    _buildTextField(_confirmPassCtrl, 'Confirmar contraseña', Icons.lock_outlined, TextInputType.text, true, (v) => _validateConfirmPassword(v), onSubmit: _submitRegister),
    if (c.errorMessage != null) ...[const SizedBox(height: 16), _buildError(c.errorMessage!)],
    const SizedBox(height: 28),
    RoundedButton(text: 'Crear cuenta', isLoading: c.isLoading, press: _submitRegister, isOutlined: false, bgColor: const Color(0xFF533483), textColor: Colors.white),
    const SizedBox(height: 12),
    Text('Al registrarte aceptas los términos y condiciones', style: regularSmall.copyWith(color: Colors.white38), textAlign: TextAlign.center),
  ]));

  Widget _buildTextField(TextEditingController ctrl, String label, IconData icon, TextInputType keyboard, bool obscure, FormFieldValidator<String> validator, {VoidCallback? onSubmit, TextCapitalization capitalization = TextCapitalization.none}) => Container(
    decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.04), blurRadius: 8, offset: const Offset(0, 2))]),
    child: TextFormField(
      controller: ctrl, keyboardType: keyboard, obscureText: obscure,
      textCapitalization: capitalization,
      textInputAction: onSubmit != null ? TextInputAction.done : TextInputAction.next,
      onFieldSubmitted: onSubmit != null ? (_) => onSubmit() : null,
      decoration: InputDecoration(
        labelText: label, prefixIcon: Icon(icon, size: 20, color: const Color(0xFFE94560)),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide(color: Colors.grey.shade100)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Color(0xFFE94560), width: 1.5)),
        errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: Colors.redAccent)),
        filled: true, fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      ),
      validator: validator,
      style: regularDefault.copyWith(fontSize: 15),
    ),
  );

  Widget _buildError(String msg) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(14),
    decoration: BoxDecoration(color: Colors.redAccent.withOpacity(0.1), borderRadius: BorderRadius.circular(14), border: Border.all(color: Colors.redAccent.withOpacity(0.2))),
    child: Row(children: [
      const Icon(Icons.error_outline, size: 18, color: Colors.redAccent),
      const SizedBox(width: 10),
      Expanded(child: Text(msg, style: regularSmall.copyWith(color: Colors.redAccent))),
    ]),
  );

  String? _validateRequired(String? v, String f) => (v == null || v.trim().isEmpty) ? '$f requerido' : null;
  String? _validateEmail(String? v) {
    if (v == null || v.trim().isEmpty) return 'Correo requerido';
    if (!RegExp(r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$').hasMatch(v.trim())) return 'Correo inválido';
    return null;
  }
  String? _validatePassword(String? v) => (v == null || v.isEmpty) ? 'Requerida' : (v.length < 8 ? 'Mínimo 8 caracteres' : null);
  String? _validateConfirmPassword(String? v) => (v == null || v.isEmpty) ? 'Confirma' : (v != _passCtrl.text ? 'No coinciden' : null);

  void _submitLogin() { if (_formKey.currentState!.validate()) _controller.login(_emailCtrl.text.trim(), _passCtrl.text); }
  void _submitRegister() { if (_formKey.currentState!.validate()) _controller.register(_nameCtrl.text.trim(), _emailCtrl.text.trim(), _phoneCtrl.text.trim(), _passCtrl.text); }
}
