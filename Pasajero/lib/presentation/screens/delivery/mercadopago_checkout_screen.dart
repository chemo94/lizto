import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/data/controller/delivery/delivery_controller.dart';
import 'package:liztogo/presentation/screens/delivery/order_confirmation_screen.dart';

class DeliveryMercadoPagoCheckoutScreen extends StatefulWidget {
  final Map<String, dynamic> mpData;
  final int orderId;

  const DeliveryMercadoPagoCheckoutScreen({
    Key? key,
    required this.mpData,
    required this.orderId,
  }) : super(key: key);

  @override
  State<DeliveryMercadoPagoCheckoutScreen> createState() => _DeliveryMercadoPagoCheckoutScreenState();
}

class _DeliveryMercadoPagoCheckoutScreenState extends State<DeliveryMercadoPagoCheckoutScreen> {
  final _formKey = GlobalKey<FormState>();

  final _cardNumberController = TextEditingController();
  final _cardNameController = TextEditingController();
  final _expiryController = TextEditingController();
  final _cvvController = TextEditingController();
  final _docNumberController = TextEditingController();

  String _selectedDocType = 'DNI';
  final List<String> _docTypes = ['DNI', 'RUC', 'CE', 'PASAPORTE'];
  final _emailController = TextEditingController();
  int _selectedInstallments = 1;
  List<dynamic> _installmentsOptions = [];
  bool _isLoadingInstallments = false;

  bool _isTokenizing = false;
  String? _cardBrand;
  String? _detectedPaymentMethodId;
  String? _lastQueriedBin;

  @override
  void initState() {
    super.initState();
    _loadSavedCardholderInfo();
  }

  Future<void> _loadSavedCardholderInfo() async {
    _emailController.text = widget.mpData['payer_email'] ?? '';
    try {
      final prefs = await SharedPreferences.getInstance();
      final savedName = prefs.getString('mp_cardholder_name');
      final savedEmail = prefs.getString('mp_payer_email');
      final savedDocType = prefs.getString('mp_doc_type');
      final savedDocNumber = prefs.getString('mp_doc_number');

      if (mounted) {
        setState(() {
          if (savedName != null && savedName.isNotEmpty) {
            _cardNameController.text = savedName;
          }
          if (savedEmail != null && savedEmail.isNotEmpty) {
            _emailController.text = savedEmail;
          }
          if (savedDocType != null && savedDocType.isNotEmpty) {
            _selectedDocType = savedDocType;
          }
          if (savedDocNumber != null && savedDocNumber.isNotEmpty) {
            _docNumberController.text = savedDocNumber;
          }
        });
      }
    } catch (e) {
      print('Error loading saved cardholder info: $e');
    }
  }

  @override
  void dispose() {
    _cardNumberController.dispose();
    _cardNameController.dispose();
    _expiryController.dispose();
    _cvvController.dispose();
    _docNumberController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  void _detectBrand(String value) {
    String cleanNumber = value.replaceAll(RegExp(r'\D'), '');
    String brand = 'unknown';
    if (cleanNumber.startsWith(RegExp(r'^4'))) {
      brand = 'visa';
    } else if (cleanNumber.startsWith(RegExp(r'^5[1-5]'))) {
      brand = 'mastercard';
    } else if (cleanNumber.startsWith(RegExp(r'^3[47]'))) {
      brand = 'amex';
    } else if (cleanNumber.startsWith(RegExp(r'^3(?:0[0-5]|[68])'))) {
      brand = 'diners';
    }
    if (_cardBrand != brand) {
      setState(() {
        _cardBrand = brand == 'unknown' ? null : brand;
      });
    }

    if (cleanNumber.length >= 6) {
      String bin = cleanNumber.substring(0, 6);
      if (_lastQueriedBin != bin) {
        _lastQueriedBin = bin;
        _detectPaymentMethod(bin);
      }
    } else {
      _lastQueriedBin = null;
      _detectedPaymentMethodId = null;
    }
  }

  Future<void> _detectPaymentMethod(String bin) async {
    final String publicKey = widget.mpData['public_key'] ?? '';
    if (publicKey.isEmpty) return;
    try {
      final url = Uri.parse(
        'https://api.mercadopago.com/v1/payment_methods/search?public_key=$publicKey&bins=$bin&marketplace=NONE',
      );
      final response = await http.get(url);
      print('MercadoPago BIN search response [${response.statusCode}]: ${response.body}');
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data['results'] is List && (data['results'] as List).isNotEmpty) {
          final firstResult = data['results'][0];
          final String methodId = firstResult['id']?.toString() ?? 'visa';
          print('Detected payment method ID: $methodId');
          if (mounted) {
            setState(() {
              _detectedPaymentMethodId = methodId;
              if (methodId.contains('visa')) {
                _cardBrand = 'visa';
              } else if (methodId.contains('master') || methodId.contains('mastercard')) {
                _cardBrand = 'mastercard';
              } else if (methodId.contains('amex')) {
                _cardBrand = 'amex';
              } else if (methodId.contains('diners')) {
                _cardBrand = 'diners';
              }
            });
            _fetchInstallments(methodId);
          }
        }
      }
    } catch (e) {
      print('Error detecting payment method: $e');
    }
  }

  Future<void> _fetchInstallments(String brand) async {
    final String publicKey = widget.mpData['public_key'] ?? '';
    final double amount = (widget.mpData['amount'] as num?)?.toDouble() ?? 0.0;
    if (publicKey.isEmpty || amount <= 0) return;
    setState(() => _isLoadingInstallments = true);
    try {
      final url = Uri.parse(
        'https://api.mercadopago.com/v1/payment_methods/installments?public_key=$publicKey&amount=$amount&payment_method_id=$brand',
      );
      final response = await http.get(url);
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is List && data.isNotEmpty) {
          final payerCosts = data[0]['payer_costs'] ?? [];
          if (mounted) {
            setState(() {
              _installmentsOptions = payerCosts;
              _selectedInstallments = 1;
            });
          }
        }
      }
    } catch (e) {
      print('Error fetching installments: $e');
    } finally {
      if (mounted) setState(() => _isLoadingInstallments = false);
    }
  }

  Future<void> _processPayment() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isTokenizing = true;
    });

    final String publicKey = widget.mpData['public_key'] ?? '';
    final String cleanCardNum = _cardNumberController.text.replaceAll(RegExp(r'\D'), '');
    final String expiryText = _expiryController.text;
    final List<String> expiryParts = expiryText.split('/');

    if (expiryParts.length != 2) {
      Get.snackbar('Error', 'Fecha de expiración inválida', backgroundColor: Colors.red, colorText: Colors.white);
      setState(() {
        _isTokenizing = false;
      });
      return;
    }

    final int expMonth = int.parse(expiryParts[0]);
    final int expYear = int.parse('20${expiryParts[1]}');

    try {
      final tokenUrl = Uri.parse('https://api.mercadopago.com/v1/card_tokens?public_key=$publicKey');
      final tokenResponse = await http.post(
        tokenUrl,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'card_number': cleanCardNum,
          'expiration_month': expMonth,
          'expiration_year': expYear,
          'security_code': _cvvController.text.trim(),
          'cardholder': {
            'name': _cardNameController.text.trim().toUpperCase(),
            'email': _emailController.text.trim().isNotEmpty ? _emailController.text.trim() : (widget.mpData['payer_email'] ?? ''),
            'identification': {
              'type': _selectedDocType,
              'number': _docNumberController.text.trim(),
            }
          }
        }),
      );

      final tokenData = jsonDecode(tokenResponse.body);
      print('MercadoPago token response [${tokenResponse.statusCode}]: $tokenData');
      if (tokenResponse.statusCode != 200 && tokenResponse.statusCode != 201) {
        final errorMsg = tokenData['message'] ?? 'Error al procesar los datos de la tarjeta';
        Get.snackbar('Error de Tarjeta', errorMsg, backgroundColor: Colors.red, colorText: Colors.white);
        setState(() {
          _isTokenizing = false;
        });
        return;
      }

      final String cardToken = tokenData['id'] ?? '';
      if (cardToken.isEmpty) {
        Get.snackbar('Error de Tarjeta', 'No se pudo obtener el token de la tarjeta', backgroundColor: Colors.red, colorText: Colors.white);
        setState(() { _isTokenizing = false; });
        return;
      }
      String resolvedPaymentMethodId = _detectedPaymentMethodId ?? tokenData['payment_method_id'] ?? tokenData['payment_method']?['id'] ?? _cardBrand ?? 'visa';
      if (resolvedPaymentMethodId == 'mastercard') {
        resolvedPaymentMethodId = 'master';
      }
      final String paymentMethodId = resolvedPaymentMethodId;
      final String? issuerId = tokenData['issuer']?['id']?.toString();

      final controller = Get.find<DeliveryController>();
      final String? resultError = await controller.submitMercadoPagoPayment(
        orderId: widget.orderId,
        cardToken: cardToken,
        installments: _selectedInstallments,
        paymentMethodId: paymentMethodId,
        issuerId: issuerId,
        payerEmail: _emailController.text.trim().isNotEmpty ? _emailController.text.trim() : (widget.mpData['payer_email'] ?? ''),
        docType: _selectedDocType,
        docNumber: _docNumberController.text.trim(),
      );

      if (resultError == null) {
        try {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('mp_cardholder_name', _cardNameController.text.trim());
          await prefs.setString('mp_payer_email', _emailController.text.trim());
          await prefs.setString('mp_doc_type', _selectedDocType);
          await prefs.setString('mp_doc_number', _docNumberController.text.trim());
        } catch (e) {
          print('Error saving cardholder info: $e');
        }
        Get.off(() => const OrderConfirmationScreen());
      } else {
        // Payment was rejected — delete the pending order so it doesn't linger in DB
        final controller2 = Get.find<DeliveryController>();
        await controller2.deletePendingOrder(widget.orderId);
        Get.back();
        // Show rejection reason on the previous screen
        Get.snackbar(
          'Pago rechazado',
          resultError,
          backgroundColor: Colors.red,
          colorText: Colors.white,
          duration: const Duration(seconds: 6),
          snackPosition: SnackPosition.BOTTOM,
        );
      }
    } catch (e) {
      print('MercadoPago payment error: $e');
      // Network / unexpected error — delete the order to keep DB clean
      final ctrl = Get.find<DeliveryController>();
      await ctrl.deletePendingOrder(widget.orderId);
      Get.back();
      Get.snackbar(
        'Error de pago',
        'Ocurrió un error al conectar con el servidor. El pedido fue cancelado.',
        backgroundColor: Colors.red,
        colorText: Colors.white,
        snackPosition: SnackPosition.BOTTOM,
      );
    } finally {
      if (mounted) {
        setState(() {
          _isTokenizing = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final double amount = (widget.mpData['amount'] as num?)?.toDouble() ?? 0.0;

    return Scaffold(
      backgroundColor: MyColor.screenBgColor,
      appBar: AppBar(
        title: const Text('Pago Seguro', style: TextStyle(fontWeight: FontWeight.w600)),
        backgroundColor: MyColor.primaryColor,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded),
          onPressed: () => Get.back(),
        ),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.04),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    )
                  ],
                ),
                child: Column(
                  children: [
                    const Text('TOTAL A PAGAR', style: TextStyle(fontSize: 12, color: MyColor.bodyMutedTextColor, fontWeight: FontWeight.bold, letterSpacing: 0.5)),
                    const SizedBox(height: 4),
                    Text(
                      'S/ ${amount.toStringAsFixed(2)}',
                      style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, color: MyColor.primaryTextColor),
                    ),
                    if (widget.mpData['gateway_fee'] != null && (widget.mpData['gateway_fee'] as num) > 0) ...[
                      const SizedBox(height: 6),
                      Text(
                        '(Incluye comisión S/ ${(widget.mpData['gateway_fee'] as num).toStringAsFixed(2)})',
                        style: const TextStyle(fontSize: 12, color: MyColor.bodyMutedTextColor, fontStyle: FontStyle.italic),
                      ),
                    ]
                  ],
                ),
              ),
              const SizedBox(height: 24),

              AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                height: 200,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: _cardBrand == 'visa'
                        ? [const Color(0xFF0F2027), const Color(0xFF203A43), const Color(0xFF2C5364)]
                        : _cardBrand == 'mastercard'
                            ? [const Color(0xFF3F2B96), const Color(0xFFA8C0ff)]
                            : _cardBrand == 'amex'
                                ? [const Color(0xFF11998e), const Color(0xFF38ef7d)]
                                : [const Color(0xFF1e1e24), const Color(0xFF45484c)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.15),
                      blurRadius: 15,
                      offset: const Offset(0, 8),
                    )
                  ],
                ),
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Icon(Icons.contactless_outlined, color: Colors.white70, size: 32),
                        if (_cardBrand != null)
                          Image.asset(
                            'assets/images/payment_methods/$_cardBrand.png',
                            height: 28,
                            errorBuilder: (context, _, __) => Text(
                              _cardBrand!.toUpperCase(),
                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                            ),
                          )
                        else
                          const Text('CARD', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, letterSpacing: 2)),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Text(
                      _cardNumberController.text.isEmpty
                          ? '•••• •••• •••• ••••'
                          : _cardNumberController.text,
                      style: const TextStyle(color: Colors.white, fontSize: 20, letterSpacing: 2, fontFamily: 'monospace', fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('TITULAR', style: TextStyle(color: Colors.white54, fontSize: 8, fontWeight: FontWeight.bold)),
                              const SizedBox(height: 2),
                              Text(
                                _cardNameController.text.isEmpty
                                    ? 'NOMBRE COMPLETO'
                                    : _cardNameController.text.toUpperCase(),
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                              ),
                            ],
                          ),
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('EXPIRA', style: TextStyle(color: Colors.white54, fontSize: 8, fontWeight: FontWeight.bold)),
                            const SizedBox(height: 2),
                            Text(
                              _expiryController.text.isEmpty ? 'MM/AA' : _expiryController.text,
                              style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                            ),
                          ],
                        )
                      ],
                    )
                  ],
                ),
              ),
              const SizedBox(height: 24),

              Form(
                key: _formKey,
                child: Column(
                  children: [
                    TextFormField(
                      controller: _cardNumberController,
                      keyboardType: TextInputType.number,
                      textInputAction: TextInputAction.next,
                      style: const TextStyle(fontSize: 16, color: MyColor.primaryTextColor),
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                        LengthLimitingTextInputFormatter(16),
                        _CardNumberInputFormatter(),
                      ],
                      onChanged: (val) {
                        _detectBrand(val);
                        setState(() {});
                      },
                      validator: (value) {
                        if (value == null || value.replaceAll(RegExp(r'\D'), '').length < 15) {
                          return 'Ingresa un número de tarjeta válido';
                        }
                        return null;
                      },
                      decoration: _inputDecoration('Número de tarjeta', Icons.credit_card_rounded),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _cardNameController,
                      keyboardType: TextInputType.name,
                      textInputAction: TextInputAction.next,
                      style: const TextStyle(fontSize: 16, color: MyColor.primaryTextColor),
                      onChanged: (val) => setState(() {}),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Ingresa el nombre del titular';
                        }
                        return null;
                      },
                      decoration: _inputDecoration('Nombre del titular (como figura en la tarjeta)', Icons.person_rounded),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _emailController,
                      keyboardType: TextInputType.emailAddress,
                      textInputAction: TextInputAction.next,
                      style: const TextStyle(fontSize: 16, color: MyColor.primaryTextColor),
                      validator: (value) {
                        if (value == null || value.trim().isEmpty || !value.contains('@')) {
                          return 'Ingresa un correo válido';
                        }
                        return null;
                      },
                      decoration: _inputDecoration('Correo electrónico', Icons.email_rounded),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: TextFormField(
                            controller: _expiryController,
                            keyboardType: TextInputType.number,
                            textInputAction: TextInputAction.next,
                            style: const TextStyle(fontSize: 16, color: MyColor.primaryTextColor),
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                              LengthLimitingTextInputFormatter(4),
                              _CardExpiryInputFormatter(),
                            ],
                            onChanged: (val) => setState(() {}),
                            validator: (value) {
                              if (value == null || value.length < 5) {
                                return 'Fecha inválida';
                              }
                              return null;
                            },
                            decoration: _inputDecoration('Expiración (MM/AA)', Icons.calendar_month_rounded),
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: TextFormField(
                            controller: _cvvController,
                            keyboardType: TextInputType.number,
                            textInputAction: TextInputAction.next,
                            obscureText: true,
                            style: const TextStyle(fontSize: 16, color: MyColor.primaryTextColor),
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                              LengthLimitingTextInputFormatter(4),
                            ],
                            validator: (value) {
                              if (value == null || value.length < 3) {
                                return 'CVV inválido';
                              }
                              return null;
                            },
                            decoration: _inputDecoration('CVV (Atrás)', Icons.lock_rounded),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 110,
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: MyColor.borderColor),
                          ),
                          child: DropdownButtonHideUnderline(
                            child: DropdownButton<String>(
                              value: _selectedDocType,
                              onChanged: (String? newValue) {
                                if (newValue != null) {
                                  setState(() {
                                    _selectedDocType = newValue;
                                  });
                                }
                              },
                              items: _docTypes.map<DropdownMenuItem<String>>((String value) {
                                return DropdownMenuItem<String>(
                                  value: value,
                                  child: Text(value, style: const TextStyle(color: MyColor.primaryTextColor, fontSize: 14)),
                                );
                              }).toList(),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: TextFormField(
                            controller: _docNumberController,
                            keyboardType: TextInputType.number,
                            textInputAction: TextInputAction.done,
                            style: const TextStyle(fontSize: 16, color: MyColor.primaryTextColor),
                            inputFormatters: [
                              FilteringTextInputFormatter.digitsOnly,
                              LengthLimitingTextInputFormatter(15),
                            ],
                            validator: (value) {
                              if (value == null || value.trim().length < 8) {
                                return 'Número de documento inválido';
                              }
                              return null;
                            },
                            decoration: _inputDecoration('Nro Documento', Icons.assignment_ind_rounded),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    if (_isLoadingInstallments)
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 8),
                        child: Row(
                          children: [
                            SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2)),
                            SizedBox(width: 12),
                            Text('Consultando cuotas disponibles...', style: TextStyle(fontSize: 13, color: MyColor.bodyMutedTextColor)),
                          ],
                        ),
                      )
                    else if (_installmentsOptions.isNotEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: MyColor.borderColor),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<int>(
                            value: _selectedInstallments,
                            isExpanded: true,
                            onChanged: (int? newValue) {
                              if (newValue != null) {
                                setState(() => _selectedInstallments = newValue);
                              }
                            },
                            items: _installmentsOptions.map<DropdownMenuItem<int>>((cost) {
                              final installments = cost['installments'] ?? 1;
                              final totalAmount = (cost['total_amount'] as num?)?.toDouble() ?? 0.0;
                              final installmentAmount = (cost['installment_amount'] as num?)?.toDouble() ?? 0.0;
                              final label = installments == 1
                                  ? '1 cuota de S/ ${totalAmount.toStringAsFixed(2)}'
                                  : '$installments cuotas de S/ ${installmentAmount.toStringAsFixed(2)} c/u (Total: S/ ${totalAmount.toStringAsFixed(2)})';
                              return DropdownMenuItem<int>(
                                value: installments,
                                child: Text(label, style: const TextStyle(color: MyColor.primaryTextColor, fontSize: 13)),
                              );
                            }).toList(),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 32),

              ElevatedButton(
                onPressed: _isTokenizing ? null : _processPayment,
                style: ElevatedButton.styleFrom(
                  backgroundColor: MyColor.primaryColor,
                  disabledBackgroundColor: MyColor.primaryColor.withOpacity(0.6),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
                child: _isTokenizing
                    ? const SizedBox(
                        height: 24,
                        width: 24,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                      )
                    : const Text(
                        'Pagar Ahora',
                        style: TextStyle(fontSize: 18, color: Colors.white, fontWeight: FontWeight.bold),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  InputDecoration _inputDecoration(String labelText, IconData prefixIcon) {
    return InputDecoration(
      labelText: labelText,
      labelStyle: const TextStyle(color: MyColor.bodyMutedTextColor, fontSize: 14),
      prefixIcon: Icon(prefixIcon, color: MyColor.primaryColor, size: 20),
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(vertical: 16, horizontal: 16),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: MyColor.borderColor),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: MyColor.primaryColor, width: 1.5),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Colors.red),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: const BorderSide(color: Colors.red, width: 1.5),
      ),
    );
  }
}

class _CardNumberInputFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    var text = newValue.text;
    if (newValue.selection.baseOffset == 0) {
      return newValue;
    }
    var buffer = StringBuffer();
    for (int i = 0; i < text.length; i++) {
      buffer.write(text[i]);
      var nonZeroIndex = i + 1;
      if (nonZeroIndex % 4 == 0 && nonZeroIndex != text.length) {
        buffer.write(' ');
      }
    }
    var string = buffer.toString();
    return newValue.copyWith(
      text: string,
      selection: TextSelection.collapsed(offset: string.length),
    );
  }
}

class _CardExpiryInputFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    var text = newValue.text;
    if (newValue.selection.baseOffset == 0) {
      return newValue;
    }
    var buffer = StringBuffer();
    for (int i = 0; i < text.length; i++) {
      buffer.write(text[i]);
      var nonZeroIndex = i + 1;
      if (nonZeroIndex == 2 && nonZeroIndex != text.length) {
        buffer.write('/');
      }
    }
    var string = buffer.toString();
    return newValue.copyWith(
      text: string,
      selection: TextSelection.collapsed(offset: string.length),
    );
  }
}
