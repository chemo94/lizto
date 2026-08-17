import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/services/api_client.dart';
import 'package:lizto_delivery/core/utils/url_container.dart';

import '../../../core/utils/url_container.dart';

class DeliveryChatScreen extends StatefulWidget {
  final int orderId;
  final String orderTitle;
  final String courierName;
  final String? courierImage;
  const DeliveryChatScreen({
    super.key,
    required this.orderId,
    required this.orderTitle,
    required this.courierName,
    this.courierImage,
  });

  @override
  State<DeliveryChatScreen> createState() => _DeliveryChatScreenState();
}

class _DeliveryChatScreenState extends State<DeliveryChatScreen> {
  final _msgCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  final List<_ChatMessage> _messages = [];
  bool _isLoading = false;

  final String _baseUrl = 'driver/courier/jobs';
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _loadMessages();
    _startPolling();
  }

  void _startPolling() {
    _pollTimer = Timer.periodic(const Duration(seconds: 5), (_) => _loadMessages());
  }

  @override
  void dispose() {
    _msgCtrl.dispose();
    _scrollCtrl.dispose();
    _pollTimer?.cancel();
    super.dispose();
  }

  Future<void> _loadMessages() async {
    try {
      final apiClient = Get.find<ApiClient>();
      final url = '$driverUrl/${widget.orderId}/messages';
      final response = await apiClient.request(url, 'get', null, passHeader: true);
      if (response.statusCode == 200 && response.responseJson != null) {
        final data = response.responseJson;
        List raw = data['data'] != null ? (data['data']['messages'] ?? data['data'] ?? []) : [];
        if (raw is List) {
          final msgs = raw.map((j) => _ChatMessage.fromJson(j)).toList();
          if (mounted) {
            setState(() => _messages
              ..clear()
              ..addAll(msgs));
            _scrollToBottom();
          }
        }
      }
    } catch (_) {}
  }

  String get driverUrl => '${UrlContainer.baseUrl}$_baseUrl';

  Future<void> _sendMessage() async {
    final text = _msgCtrl.text.trim();
    if (text.isEmpty) return;

    _msgCtrl.clear();
    setState(() => _messages.add(_ChatMessage(text: text, isMine: true)));
    _scrollToBottom();

    try {
      final apiClient = Get.find<ApiClient>();
      final url = '$_baseUrl/${widget.orderId}/messages/send';
      await apiClient.request(
        '$driverUrl/${widget.orderId}/messages/send',
        'post',
        {'message': text},
        passHeader: true,
      );
      _loadMessages();
    } catch (_) {}
  }

  Future<void> _sendImage() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (picked == null) return;

    setState(() => _messages.add(_ChatMessage(imagePath: picked.path, isMine: true)));

    try {
      final apiClient = Get.find<ApiClient>();
      await apiClient.multipartRequest(
        '$driverUrl/${widget.orderId}/messages/send-image',
        'post',
        {},
        files: {'image': File(picked.path)},
        passHeader: true,
      );
      _loadMessages();
    } catch (_) {}
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollCtrl.hasClients) _scrollCtrl.animateTo(_scrollCtrl.position.maxScrollExtent, duration: const Duration(milliseconds: 200), curve: Curves.easeOut);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        leading: IconButton(icon: const Icon(Icons.arrow_back, color: MyColor.colorWhite), onPressed: () => Get.back()),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(widget.courierName, style: boldDefault.copyWith(color: MyColor.colorWhite, fontSize: Dimensions.fontLarge)),
            Text(widget.orderTitle, style: regularSmall.copyWith(color: MyColor.colorWhite.withValues(alpha: 0.85))),
          ],
        ),
        centerTitle: false,
      ),
      body: Column(
        children: [
          Expanded(
            child: _messages.isEmpty
                ? Center(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.chat_bubble_outline_rounded, size: 64, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.4)),
                        SizedBox(height: Dimensions.space12),
                        Text('Sin mensajes aún', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                        Text('Envía un mensaje al repartidor', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor.withValues(alpha: 0.7))),
                      ],
                    ),
                  )
                : ListView.builder(
                    controller: _scrollCtrl,
                    padding: EdgeInsets.all(Dimensions.space14),
                    itemCount: _messages.length,
                    itemBuilder: (ctx, i) => _buildBubble(_messages[i]),
                  ),
          ),
          _buildInputBar(),
        ],
      ),
    );
  }

  Widget _buildBubble(_ChatMessage msg) {
    return Align(
      alignment: msg.isMine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: EdgeInsets.only(bottom: Dimensions.space10),
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
        padding: EdgeInsets.symmetric(horizontal: Dimensions.space14, vertical: Dimensions.space10),
        decoration: BoxDecoration(
          color: msg.isMine ? MyColor.primaryColor : MyColor.getCardBgColor(),
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: msg.isMine ? const Radius.circular(16) : const Radius.circular(4),
            bottomRight: msg.isMine ? const Radius.circular(4) : const Radius.circular(16),
          ),
        ),
        child: msg.imagePath != null
            ? ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.file(
                  File(msg.imagePath!),
                  fit: BoxFit.cover,
                ),
              )
            : SelectableText(
                msg.text ?? '',
                style: regularDefault.copyWith(color: msg.isMine ? MyColor.colorWhite : MyColor.getTextColor()),
              ),
      ),
    );
  }

  Widget _buildInputBar() {
    return Container(
      padding: EdgeInsets.fromLTRB(Dimensions.space14, Dimensions.space10, Dimensions.space6, Dimensions.space14),
      decoration: BoxDecoration(
        color: MyColor.colorWhite,
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 8, offset: const Offset(0, -2))],
      ),
      child: Row(
        children: [
          IconButton(icon: Icon(Icons.image_outlined, color: MyColor.primaryColor), onPressed: _sendImage),
          Expanded(
            child: TextField(
              controller: _msgCtrl,
              decoration: InputDecoration(
                hintText: 'Escribe un mensaje...',
                hintStyle: regularDefault.copyWith(color: MyColor.bodyMutedTextColor),
                filled: true,
                fillColor: MyColor.getScreenBgColor(),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: BorderSide.none),
                contentPadding: EdgeInsets.symmetric(horizontal: Dimensions.space16, vertical: Dimensions.space10),
              ),
              textInputAction: TextInputAction.send,
              onSubmitted: (_) => _sendMessage(),
              maxLines: 3,
              minLines: 1,
            ),
          ),
          SizedBox(width: Dimensions.space6),
          Container(
            decoration: BoxDecoration(color: MyColor.primaryColor, shape: BoxShape.circle),
            child: IconButton(icon: const Icon(Icons.send_rounded, color: MyColor.colorWhite, size: 20), onPressed: _sendMessage),
          ),
        ],
      ),
    );
  }
}

class _ChatMessage {
  final String? text;
  final String? imagePath;
  final bool isMine;
  final DateTime timestamp;

  _ChatMessage({this.text, this.imagePath, this.isMine = false, DateTime? timestamp}) : timestamp = timestamp ?? DateTime.now();

  factory _ChatMessage.fromJson(Map<String, dynamic> json) {
    return _ChatMessage(
      text: json['message']?.toString(),
      imagePath: json['image']?.toString(),
      isMine: json['is_sender'] == true || json['sender_id']?.toString() == json['user_id']?.toString(),
      timestamp: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now() : DateTime.now(),
    );
  }
}
