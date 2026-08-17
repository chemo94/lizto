import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'package:liztogo/core/utils/dimensions.dart';
import 'package:liztogo/core/utils/my_color.dart';
import 'package:liztogo/core/utils/style.dart';
import 'package:liztogo/core/utils/url_container.dart';
import 'package:liztogo/data/controller/delivery/favor_controller.dart';
import 'package:liztogo/data/repo/delivery/favor_repo.dart';
import 'package:liztogo/data/services/api_client.dart';
import 'package:liztogo/data/model/delivery/favor_models.dart';
import 'package:liztogo/presentation/components/buttons/rounded_button.dart';
import 'package:liztogo/presentation/screens/delivery/favor_tracking_screen.dart';
import 'package:liztogo/presentation/screens/delivery/shopping_list_screen.dart';
import 'package:liztogo/environment.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';

class FavorCreateScreen extends StatefulWidget {
  final String favorType;
  const FavorCreateScreen({super.key, required this.favorType});

  @override
  State<FavorCreateScreen> createState() => _FavorCreateScreenState();
}

class _FavorCreateScreenState extends State<FavorCreateScreen> {
  final _descCtrl = TextEditingController();
  final _storeNameCtrl = TextEditingController();
  final _storeAddressCtrl = TextEditingController();
  final _pickupCtrl = TextEditingController();
  final _deliveryCtrl = TextEditingController();
  final _recipientNameCtrl = TextEditingController();
  final _recipientPhoneCtrl = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  final _pickupFocus = FocusNode();
  final _deliveryFocus = FocusNode();

  List<_PlacePrediction> _pickupPredictions = [];
  List<_PlacePrediction> _deliveryPredictions = [];
  bool _showPickupPredictions = false;
  bool _showDeliveryPredictions = false;
  Timer? _debounce;
  double? _pickupLat, _pickupLng;
  double? _deliveryLat, _deliveryLng;
  bool _gettingPickupLocation = false;
  bool _gettingDeliveryLocation = false;

  String _selectedPackageType = 'document';
  bool _isExpress = false;
  bool _isFragile = false;

  double? _estimatedFee;
  bool _isEstimatingFee = false;
  Map<String, dynamic>? _feeBreakdown;
  Timer? _estimateDebounce;

  bool get isBuyType => widget.favorType == FavorType.buy;

  final List<Map<String, dynamic>> _packageTypes = [
    {'id': 'document', 'label': 'Documento', 'icon': Icons.description_outlined, 'desc': 'Papel, cartas'},
    {'id': 'small', 'label': 'Paquete pequeño', 'icon': Icons.inventory_rounded, 'desc': 'Hasta 2kg'},
    {'id': 'medium', 'label': 'Paquete mediano', 'icon': Icons.inventory, 'desc': '2-10kg'},
    {'id': 'large', 'label': 'Paquete grande', 'icon': Icons.checkroom, 'desc': 'Más de 10kg'},
    {'id': 'food', 'label': 'Comida', 'icon': Icons.restaurant, 'desc': 'Restaurantes'},
    {'id': 'pharmacy', 'label': 'Farmacia', 'icon': Icons.local_pharmacy, 'desc': 'Medicamentos'},
    {'id': 'grocery', 'label': 'Supermercado', 'icon': Icons.shopping_cart, 'desc': 'Viveres'},
  ];

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<FavorController>()) {
      Get.put(FavorController(favorRepo: FavorRepo(apiClient: Get.find<ApiClient>())));
    }
    _pickupCtrl.addListener(_onPickupChanged);
    _deliveryCtrl.addListener(_onDeliveryChanged);
  }

  void _onPickupChanged() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () => _searchPlace(_pickupCtrl.text, true));
  }

  void _onDeliveryChanged() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () => _searchPlace(_deliveryCtrl.text, false));
  }

  Future<void> _searchPlace(String query, bool isPickup) async {
    if (query.length < 3) {
      if (isPickup)
        setState(() {
          _pickupPredictions = [];
          _showPickupPredictions = false;
        });
      else
        setState(() {
          _deliveryPredictions = [];
          _showDeliveryPredictions = false;
        });
      return;
    }
    try {
      final url = '${UrlContainer.googleMapLocationSearch}/place/autocomplete/json?'
          'input=${Uri.encodeComponent(query)}'
          '&key=${Environment.mapKey}'
          '&language=es'
          '&components=country:pe';
      final response = await http.get(Uri.parse(url));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final predictions = (data['predictions'] as List? ?? [])
            .map((p) => _PlacePrediction(
                  description: p['description']?.toString() ?? '',
                  placeId: p['place_id']?.toString() ?? '',
                ))
            .toList();
        if (mounted) {
          setState(() {
            if (isPickup) {
              _pickupPredictions = predictions;
              _showPickupPredictions = predictions.isNotEmpty;
            } else {
              _deliveryPredictions = predictions;
              _showDeliveryPredictions = predictions.isNotEmpty;
            }
          });
        }
      }
    } catch (_) {}
  }

  Future<void> _selectPrediction(_PlacePrediction p, bool isPickup) async {
    if (isPickup) {
      _pickupCtrl.text = p.description;
      setState(() {
        _showPickupPredictions = false;
        _pickupPredictions = [];
      });
    } else {
      _deliveryCtrl.text = p.description;
      setState(() {
        _showDeliveryPredictions = false;
        _deliveryPredictions = [];
      });
    }
    await _getPlaceDetails(p.placeId, isPickup);
    _requestEstimate();
  }

  Future<void> _getPlaceDetails(String placeId, bool isPickup) async {
    try {
      final url = '${UrlContainer.googleMapLocationSearch}/place/details/json?'
          'place_id=$placeId'
          '&key=${Environment.mapKey}'
          '&fields=geometry';
      final response = await http.get(Uri.parse(url));
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final location = data['result']?['geometry']?['location'];
        if (location != null) {
          final lat = (location['lat'] as num).toDouble();
          final lng = (location['lng'] as num).toDouble();
          if (isPickup) {
            _pickupLat = lat;
            _pickupLng = lng;
          } else {
            _deliveryLat = lat;
            _deliveryLng = lng;
          }
        }
      }
    } catch (_) {}
  }

  Future<void> _useCurrentLocation(bool isPickup) async {
    if (isPickup)
      setState(() => _gettingPickupLocation = true);
    else
      setState(() => _gettingDeliveryLocation = true);

    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(accuracy: LocationAccuracy.best),
      );
      final placemarks = await placemarkFromCoordinates(position.latitude, position.longitude);
      String address = '';
      if (placemarks.isNotEmpty) {
        final p = placemarks.first;
        final streetFull = (p.subThoroughfare != null && p.subThoroughfare!.isNotEmpty && !(p.street ?? '').contains(p.subThoroughfare!)) ? '${p.street ?? ''} ${p.subThoroughfare}'.trim() : (p.street ?? '');
        address = '$streetFull, ${p.subLocality ?? ''}, ${p.locality ?? ''}'.trim();
        if (address.isEmpty || address == ', ,') address = '${p.name ?? ''}, ${p.locality ?? ''}';
      }
      if (address.isEmpty) {
        address = '${position.latitude.toStringAsFixed(6)}, ${position.longitude.toStringAsFixed(6)}';
      }
      if (isPickup) {
        _pickupCtrl.text = address;
        _pickupLat = position.latitude;
        _pickupLng = position.longitude;
      } else {
        _deliveryCtrl.text = address;
        _deliveryLat = position.latitude;
        _deliveryLng = position.longitude;
      }
    } catch (_) {
      if (mounted) {
        Get.snackbar('Error', 'No se pudo obtener la ubicación', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      }
    }
    if (isPickup)
      setState(() => _gettingPickupLocation = false);
    else
      setState(() => _gettingDeliveryLocation = false);
    _requestEstimate();
  }

  void _requestEstimate() {
    _estimateDebounce?.cancel();
    _estimateDebounce = Timer(const Duration(milliseconds: 500), _fetchFeeEstimate);
  }

  Future<void> _fetchFeeEstimate() async {
    if (_pickupLat == null || _pickupLng == null || _deliveryLat == null || _deliveryLng == null) {
      if (mounted)
        setState(() {
          _estimatedFee = null;
          _feeBreakdown = null;
        });
      return;
    }
    if (_isEstimatingFee) return;
    setState(() => _isEstimatingFee = true);
    try {
      final repo = Get.find<FavorRepo>();
      final res = await repo.estimateFavorFee(
        pickupLat: _pickupLat,
        pickupLng: _pickupLng,
        deliveryLat: _deliveryLat,
        deliveryLng: _deliveryLng,
      );
      if (res.statusCode == 200 && mounted) {
        final json = res.responseJson;
        if (json['status'] == 'success' && json['data'] != null) {
          final est = json['data']['estimate'];
          if (est != null) {
            setState(() {
              _estimatedFee = (est['delivery_fee'] as num).toDouble();
              _feeBreakdown = Map<String, dynamic>.from(est);
              _feeBreakdown!.remove('delivery_fee');
            });
          }
        }
      }
    } catch (_) {
      if (mounted)
        setState(() {
          _estimatedFee = null;
          _feeBreakdown = null;
        });
    }
    if (mounted) setState(() => _isEstimatingFee = false);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _estimateDebounce?.cancel();
    _pickupCtrl.removeListener(_onPickupChanged);
    _deliveryCtrl.removeListener(_onDeliveryChanged);
    _descCtrl.dispose();
    _storeNameCtrl.dispose();
    _storeAddressCtrl.dispose();
    _pickupCtrl.dispose();
    _deliveryCtrl.dispose();
    _recipientNameCtrl.dispose();
    _recipientPhoneCtrl.dispose();
    _pickupFocus.dispose();
    _deliveryFocus.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final c = Get.find<FavorController>();
    return Scaffold(
      backgroundColor: MyColor.getScreenBgColor(),
      body: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: Form(
              key: _formKey,
              child: ListView(
                padding: EdgeInsets.fromLTRB(Dimensions.space16, Dimensions.space16, Dimensions.space16, Dimensions.space40),
                children: [
                  if (isBuyType) ...[
                    _buildBuyProgress(),
                    SizedBox(height: Dimensions.space16),
                  ],
                  _buildSectionCard(
                    icon: isBuyType ? Icons.shopping_bag_rounded : Icons.inventory_2_rounded,
                    title: isBuyType ? '1. Arma tu compra' : '¿Qué envías?',
                    children: [
                      TextFormField(
                        controller: _descCtrl,
                        decoration: _inputDeco(
                          isBuyType ? 'Describe los productos' : 'Describe el contenido',
                          hint: isBuyType ? 'Ej: 2 panes con pollo, 1 Inca Kola 1.5L' : 'Ej: Documentos legales, llaves, regalos',
                          icon: Icons.description_outlined,
                        ),
                        maxLines: 3,
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                      ),
                      if (!isBuyType) ...[
                        SizedBox(height: Dimensions.space16),
                        Text('Tipo de paquete', style: boldDefault.copyWith(fontSize: Dimensions.fontLarge)),
                        SizedBox(height: Dimensions.space10),
                        Wrap(
                          spacing: Dimensions.space8,
                          runSpacing: Dimensions.space8,
                          children: _packageTypes.map((type) {
                            final isSelected = _selectedPackageType == type['id'];
                            return GestureDetector(
                              onTap: () => setState(() => _selectedPackageType = type['id']),
                              child: Container(
                                padding: EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space10),
                                decoration: BoxDecoration(
                                  color: isSelected ? MyColor.primaryColor.withValues(alpha: 0.1) : MyColor.colorWhite,
                                  borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                                  border: Border.all(
                                    color: isSelected ? MyColor.primaryColor : Colors.grey.shade200,
                                    width: isSelected ? 1.5 : 1,
                                  ),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(type['icon'] as IconData, size: 18, color: isSelected ? MyColor.primaryColor : MyColor.bodyTextColor),
                                    SizedBox(width: Dimensions.space6),
                                    Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Text(type['label'], style: boldDefault.copyWith(fontSize: 13, color: isSelected ? MyColor.primaryColor : MyColor.bodyTextColor)),
                                        Text(type['desc'], style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            );
                          }).toList(),
                        ),
                        SizedBox(height: Dimensions.space14),
                        Row(
                          children: [
                            _buildChipOption(
                              icon: Icons.warning_amber_rounded,
                              label: 'Frágil',
                              isSelected: _isFragile,
                              onTap: () => setState(() => _isFragile = !_isFragile),
                            ),
                            SizedBox(width: Dimensions.space10),
                            _buildChipOption(
                              icon: Icons.bolt_rounded,
                              label: 'Express',
                              isSelected: _isExpress,
                              onTap: () => setState(() => _isExpress = !_isExpress),
                            ),
                          ],
                        ),
                      ],
                      if (isBuyType) ...[_buildBuyDetails()],
                    ],
                  ),
                  SizedBox(height: Dimensions.space16),
                  _buildAddressSection(),
                  SizedBox(height: Dimensions.space16),
                  _buildSectionCard(
                    icon: Icons.person_outline_rounded,
                    title: isBuyType ? '3. Datos de entrega' : '¿Quién recibe?',
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: _recipientNameCtrl,
                              decoration: _inputDeco('Nombre', icon: Icons.person_outline_rounded),
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                            ),
                          ),
                          SizedBox(width: Dimensions.space12),
                          Expanded(
                            child: TextFormField(
                              controller: _recipientPhoneCtrl,
                              decoration: _inputDeco('Teléfono', icon: Icons.phone_outlined),
                              keyboardType: TextInputType.phone,
                              validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                  SizedBox(height: Dimensions.space20),
                  _buildFeeCard(),
                  SizedBox(height: Dimensions.space20),
                  RoundedButton(
                    text: isBuyType ? 'Continuar con mi compra' : (_isExpress ? 'Enviar express' : 'Solicitar servicio'),
                    isLoading: c.sending,
                    press: _submit,
                    isOutlined: false,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBuyProgress() {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: [MyColor.primaryColor, MyColor.primaryColor.withValues(alpha: .78)]),
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
      ),
      child: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Text('Compra fácil, precio claro', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w800)),
        SizedBox(height: 5),
        Text('Indica qué comprar, ubica la tienda y revisa el total estimado antes de solicitar.', style: TextStyle(color: Color(0xDDFFFFFF), height: 1.3)),
        SizedBox(height: 16),
        Row(children: [
          _BuyStep(number: '1', label: 'Compra'),
          _BuyStepLine(),
          _BuyStep(number: '2', label: 'Ruta'),
          _BuyStepLine(),
          _BuyStep(number: '3', label: 'Entrega'),
        ]),
      ]),
    );
  }

  Widget _buildBuyDetails() {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      SizedBox(height: Dimensions.space14),
      TextFormField(
        controller: _storeNameCtrl,
        decoration: _inputDeco('Tienda o comercio', hint: 'Ej: Tambo, Plaza Vea o bodega', icon: Icons.storefront_outlined),
        validator: (v) => (v == null || v.trim().isEmpty) ? 'Indica dónde comprar' : null,
      ),
      SizedBox(height: Dimensions.space14),
      SizedBox(height: Dimensions.space12),
      Container(
        padding: EdgeInsets.all(Dimensions.space12),
        decoration: BoxDecoration(color: MyColor.primaryColor.withValues(alpha: .07), borderRadius: BorderRadius.circular(Dimensions.defaultRadius)),
        child: Row(children: [
          Icon(Icons.auto_graph_rounded, color: MyColor.primaryColor),
          SizedBox(width: Dimensions.space10),
          Expanded(child: Text('No necesitas saber los precios ahora. Coordina opciones, fotos y precios reales por chat con el repartidor.', style: regularSmall.copyWith(color: MyColor.bodyTextColor, height: 1.3))),
        ]),
      ),
    ]);
  }

  Widget _buildHeader() {
    return Container(
      padding: EdgeInsets.only(top: MediaQuery.of(context).padding.top + Dimensions.space12, bottom: Dimensions.space20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [MyColor.primaryColor, MyColor.primaryColor.withValues(alpha: 0.85)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Column(
        children: [
          Row(
            children: [
              IconButton(
                icon: const Icon(Icons.arrow_back_ios_rounded, color: MyColor.colorWhite, size: 20),
                onPressed: () => Get.back(),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      isBuyType ? 'Comprar en tienda' : 'Enviar paquete',
                      style: boldExtraLarge.copyWith(color: MyColor.colorWhite, fontSize: 22),
                    ),
                    SizedBox(height: 2),
                    Text(
                      'Servicio de favores',
                      style: regularDefault.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.8), fontSize: 13),
                    ),
                  ],
                ),
              ),
              Container(
                margin: EdgeInsets.only(right: Dimensions.space12),
                padding: EdgeInsets.symmetric(horizontal: Dimensions.space12, vertical: Dimensions.space6),
                decoration: BoxDecoration(
                  color: MyColor.colorWhite.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(isBuyType ? Icons.shopping_bag_rounded : Icons.local_shipping_rounded, color: MyColor.colorWhite, size: 16),
                    SizedBox(width: Dimensions.space4),
                    Text(isBuyType ? 'Compra' : 'Envío', style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: 12)),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSectionCard({required IconData icon, required String title, required List<Widget> children}) {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: MyColor.primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                ),
                child: Icon(icon, color: MyColor.primaryColor, size: 20),
              ),
              SizedBox(width: Dimensions.space10),
              Text(title, style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
            ],
          ),
          SizedBox(height: Dimensions.space14),
          ...children,
        ],
      ),
    );
  }

  Widget _buildChipOption({required IconData icon, required String label, required bool isSelected, required VoidCallback onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space10),
        decoration: BoxDecoration(
          color: isSelected ? MyColor.primaryColor.withValues(alpha: 0.1) : MyColor.colorWhite,
          borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
          border: Border.all(
            color: isSelected ? MyColor.primaryColor : Colors.grey.shade200,
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 18, color: isSelected ? MyColor.primaryColor : MyColor.bodyTextColor),
            SizedBox(width: Dimensions.space6),
            Text(label, style: boldDefault.copyWith(fontSize: 13, color: isSelected ? MyColor.primaryColor : MyColor.bodyTextColor)),
          ],
        ),
      ),
    );
  }

  Widget _buildAddressSection() {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 10, offset: const Offset(0, 3)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: MyColor.primaryColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                ),
                child: Icon(Icons.alt_route_rounded, color: MyColor.primaryColor, size: 20),
              ),
              SizedBox(width: Dimensions.space10),
              Text(isBuyType ? '2. Ruta de tu compra' : 'Ruta de entrega', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
            ],
          ),
          SizedBox(height: Dimensions.space16),
          Stack(
            children: [
              Column(
                children: [
                  _buildAddressField(
                    controller: _pickupCtrl,
                    focusNode: _pickupFocus,
                    label: isBuyType ? 'Ubicación de la tienda' : 'Punto A - Recogida',
                    hint: isBuyType ? 'Busca la dirección del comercio' : 'Jr. San Martín 123, oficina 302',
                    isLoading: _gettingPickupLocation,
                    onGetLocation: () => _useCurrentLocation(true),
                    predictions: _pickupPredictions,
                    showPredictions: _showPickupPredictions,
                    onSelectPrediction: (p) => _selectPrediction(p, true),
                    onChanged: (_) {},
                  ),
                  Padding(
                    padding: EdgeInsets.symmetric(vertical: Dimensions.space8),
                    child: Row(
                      children: [
                        Container(
                          width: 24,
                          height: 24,
                          decoration: BoxDecoration(
                            color: MyColor.primaryColor.withValues(alpha: 0.1),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(Icons.arrow_downward_rounded, size: 16, color: MyColor.primaryColor),
                        ),
                        Expanded(child: Divider(indent: Dimensions.space10, color: Colors.grey.shade300)),
                      ],
                    ),
                  ),
                  _buildAddressField(
                    controller: _deliveryCtrl,
                    focusNode: _deliveryFocus,
                    label: isBuyType ? '¿Dónde entregamos tu compra?' : 'Punto B - Entrega',
                    hint: 'Av. Pardo 456, dpto 5',
                    isLoading: _gettingDeliveryLocation,
                    onGetLocation: () => _useCurrentLocation(false),
                    predictions: _deliveryPredictions,
                    showPredictions: _showDeliveryPredictions,
                    onSelectPrediction: (p) => _selectPrediction(p, false),
                    onChanged: (_) {},
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildAddressField({
    required TextEditingController controller,
    required FocusNode focusNode,
    required String label,
    required String hint,
    required bool isLoading,
    required VoidCallback onGetLocation,
    required List<_PlacePrediction> predictions,
    required bool showPredictions,
    required Function(_PlacePrediction) onSelectPrediction,
    required Function(String) onChanged,
  }) {
    return Column(
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                children: [
                  TextFormField(
                    controller: controller,
                    focusNode: focusNode,
                    decoration: _inputDeco(label, hint: hint, icon: Icons.location_on_outlined),
                    maxLines: 1,
                    validator: (v) => (v == null || v.trim().isEmpty) ? 'Requerido' : null,
                  ),
                  if (showPredictions)
                    Container(
                      margin: EdgeInsets.only(top: 4),
                      decoration: BoxDecoration(
                        color: MyColor.colorWhite,
                        borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 8, offset: const Offset(0, 4))],
                      ),
                      constraints: BoxConstraints(maxHeight: 180),
                      child: ListView.separated(
                        shrinkWrap: true,
                        padding: EdgeInsets.zero,
                        itemCount: predictions.length,
                        separatorBuilder: (_, __) => Divider(height: 1, color: Colors.grey.shade100),
                        itemBuilder: (ctx, i) => InkWell(
                          onTap: () => onSelectPrediction(predictions[i]),
                          child: Padding(
                            padding: EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space12),
                            child: Row(
                              children: [
                                Icon(Icons.location_on_outlined, size: 18, color: MyColor.primaryColor),
                                SizedBox(width: Dimensions.space10),
                                Expanded(
                                  child: Text(predictions[i].description, style: regularDefault.copyWith(fontSize: Dimensions.fontDefault), maxLines: 2, overflow: TextOverflow.ellipsis),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
            SizedBox(width: Dimensions.space8),
            Container(
              margin: EdgeInsets.only(top: 2),
              decoration: BoxDecoration(
                color: MyColor.primaryColor.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                border: Border.all(color: MyColor.primaryColor.withValues(alpha: 0.2)),
              ),
              child: isLoading
                  ? Padding(
                      padding: EdgeInsets.all(Dimensions.space12),
                      child: SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: MyColor.primaryColor)),
                    )
                  : IconButton(
                      icon: Icon(Icons.my_location_rounded, color: MyColor.primaryColor, size: 22),
                      onPressed: onGetLocation,
                      tooltip: 'Usar mi ubicación actual',
                    ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildFeeCard() {
    return Container(
      padding: EdgeInsets.all(Dimensions.space16),
      decoration: BoxDecoration(
        color: MyColor.primaryColor.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(Dimensions.largeRadius),
        border: Border.all(color: MyColor.primaryColor.withValues(alpha: 0.2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(Icons.receipt_long_rounded, color: MyColor.primaryColor, size: 20),
              SizedBox(width: Dimensions.space8),
              Text(isBuyType ? 'Estimado en tiempo real' : 'Tarifa estimada', style: boldLarge.copyWith(fontSize: Dimensions.fontLarge)),
            ],
          ),
          SizedBox(height: Dimensions.space12),
          if (_estimatedFee != null && _feeBreakdown != null) ...[
            if (isBuyType && _feeBreakdown!['estimated_amount'] != null) _buildFeeRow('Productos', 'S/ ${_feeBreakdown!['estimated_amount']}'),
            if (_feeBreakdown!['base_fare'] != null) _buildFeeRow('Tarifa base', 'S/ ${_feeBreakdown!['base_fare']}'),
            if (_feeBreakdown!['distance_fee'] != null && (_feeBreakdown!['distance_fee'] as num) > 0) _buildFeeRow('Distancia extra', 'S/ ${_feeBreakdown!['distance_fee']}'),
            if (_feeBreakdown!['time_fee'] != null && (_feeBreakdown!['time_fee'] as num) > 0) _buildFeeRow('Tiempo estimado', 'S/ ${_feeBreakdown!['time_fee']}'),
            if (_feeBreakdown!['surge'] != null && (_feeBreakdown!['surge'] as num) > 1) _buildFeeRow('Demanda x${_feeBreakdown!['surge']}', ''),
            if (_feeBreakdown!['distance_km'] != null) _buildFeeRow('Distancia', '${_feeBreakdown!['distance_km']} km'),
            SizedBox(height: Dimensions.space10),
            Divider(color: MyColor.primaryColor.withValues(alpha: 0.2)),
            SizedBox(height: Dimensions.space8),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(isBuyType ? 'Costo estimado del servicio' : 'Costo total', style: boldDefault.copyWith(fontSize: Dimensions.fontLarge)),
                Text(
                  'S/ ${_estimatedFee!.toStringAsFixed(2)}',
                  style: boldExtraLarge.copyWith(color: MyColor.primaryColor, fontSize: 20),
                ),
              ],
            ),
          ] else ...[
            if (_isEstimatingFee)
              Row(
                children: [
                  SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: MyColor.primaryColor)),
                  SizedBox(width: Dimensions.space10),
                  Text('Calculando tarifa...', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                ],
              )
            else if (_pickupLat != null && _deliveryLat != null)
              Row(
                children: [
                  Icon(Icons.error_outline_rounded, size: 16, color: MyColor.bodyMutedTextColor),
                  SizedBox(width: Dimensions.space6),
                  Text('No se pudo calcular', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                ],
              )
            else
              Text('Selecciona origen y destino', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          ],
        ],
      ),
    );
  }

  Widget _buildFeeRow(String label, String value) {
    return Padding(
      padding: EdgeInsets.only(bottom: Dimensions.space6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
          if (value.isNotEmpty) Text(value, style: boldDefault.copyWith(fontSize: 13)),
        ],
      ),
    );
  }

  InputDecoration _inputDeco(String label, {String? hint, IconData? icon, String? prefix}) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      hintStyle: regularDefault.copyWith(color: MyColor.bodyMutedTextColor.withValues(alpha: 0.6), fontSize: 13),
      prefixText: prefix,
      prefixIcon: icon != null ? Icon(icon, size: 20, color: MyColor.primaryColor) : null,
      filled: true,
      fillColor: MyColor.getScreenBgColor(),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
        borderSide: BorderSide(color: Colors.grey.shade200),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
        borderSide: BorderSide(color: MyColor.primaryColor, width: 1.5),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
        borderSide: BorderSide(color: MyColor.redCancelTextColor),
      ),
      contentPadding: EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space14),
    );
  }

  void _submit() {
    if (!_formKey.currentState!.validate()) return;
    final c = Get.find<FavorController>();

    if (_descCtrl.text.trim().isEmpty) {
      Get.snackbar('Error', 'Describe lo que necesitas', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      return;
    }

    c
        .createFavor(
      type: widget.favorType,
      description: _descCtrl.text.trim(),
      storeName: _storeNameCtrl.text.trim(),
      storeAddress: isBuyType ? _pickupCtrl.text.trim() : _storeAddressCtrl.text.trim(),
      pickupAddress: _pickupCtrl.text.trim(),
      pickupLat: _pickupLat,
      pickupLng: _pickupLng,
      deliveryAddress: _deliveryCtrl.text.trim(),
      deliveryLat: _deliveryLat,
      deliveryLng: _deliveryLng,
      recipientName: _recipientNameCtrl.text.trim(),
      recipientPhone: _recipientPhoneCtrl.text.trim(),
    )
        .then((ok) {
      if (ok && mounted) {
        if (isBuyType) {
          // For buy type, navigate to shopping list to build the item list
          Get.off(() => ShoppingListScreen(
                favorId: c.selectedFavor?.id ?? 0,
                storeName: _storeNameCtrl.text.isNotEmpty ? _storeNameCtrl.text : 'Tienda',
              ));
        } else {
          Get.off(() => FavorTrackingScreen(favorId: c.selectedFavor?.id ?? 0));
        }
      } else if (mounted) {
        Get.snackbar('Error', 'No se pudo crear la solicitud', backgroundColor: MyColor.redCancelTextColor, colorText: MyColor.colorWhite);
      }
    });
  }
}

class _PlacePrediction {
  final String description;
  final String placeId;
  _PlacePrediction({required this.description, required this.placeId});
}

class _BuyStep extends StatelessWidget {
  final String number;
  final String label;
  const _BuyStep({required this.number, required this.label});

  @override
  Widget build(BuildContext context) => Column(children: [
        Container(
          width: 27,
          height: 27,
          alignment: Alignment.center,
          decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
          child: Text(number, style: const TextStyle(color: MyColor.primaryColor, fontWeight: FontWeight.w800)),
        ),
        const SizedBox(height: 5),
        Text(label, style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600)),
      ]);
}

class _BuyStepLine extends StatelessWidget {
  const _BuyStepLine();

  @override
  Widget build(BuildContext context) => const Expanded(
        child: Padding(
          padding: EdgeInsets.only(bottom: 18),
          child: Divider(color: Color(0x99FFFFFF), thickness: 1),
        ),
      );
}
