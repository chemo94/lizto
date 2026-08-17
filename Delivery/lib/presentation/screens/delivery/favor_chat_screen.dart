import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:lizto_delivery/core/utils/dimensions.dart';
import 'package:lizto_delivery/core/utils/my_color.dart';
import 'package:lizto_delivery/core/utils/style.dart';
import 'package:lizto_delivery/data/controller/delivery/favor_controller.dart';
import 'package:lizto_delivery/data/repo/delivery/favor_repo.dart';
import 'package:lizto_delivery/data/model/delivery/favor_models.dart';
import 'package:lizto_delivery/data/services/api_client.dart';
import 'package:lizto_delivery/presentation/components/image/my_network_image_widget.dart';

class FavorChatScreen extends StatefulWidget {
  final int favorId;
  const FavorChatScreen({super.key, required this.favorId});

  @override
  State<FavorChatScreen> createState() => _FavorChatScreenState();
}

class _FavorChatScreenState extends State<FavorChatScreen> {
  final _msgCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  File? _pendingImageFile;
  bool _sendingImage = false;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<FavorController>()) {
      Get.put(FavorController(favorRepo: FavorRepo(apiClient: Get.find<ApiClient>())));
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<FavorController>();
      c.loadMessages(widget.favorId);
      c.subscribeToFavorChannel(widget.favorId);
    });
  }

  @override
  void dispose() {
    _msgCtrl.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  void _send() {
    final text = _msgCtrl.text.trim();
    if (_pendingImageFile != null) {
      _sendImage();
    } else if (text.isNotEmpty) {
      _msgCtrl.clear();
      Get.find<FavorController>().sendMessage(widget.favorId, text);
    }
  }

  Future<void> _pickAndSendImage() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      allowMultiple: false,
      type: FileType.custom,
      allowedExtensions: ['jpg', 'jpeg', 'png'],
    );
    if (result == null) return;
    setState(() => _pendingImageFile = File(result.files.single.path!));
  }

  void _sendImage() async {
    if (_pendingImageFile == null) return;
    setState(() => _sendingImage = true);
    final c = Get.find<FavorController>();
    await c.sendImage(widget.favorId, _pendingImageFile!);
    if (mounted) {
      setState(() {
        _sendingImage = false;
        _pendingImageFile = null;
      });
    }
  }

  void _clearImage() {
    setState(() => _pendingImageFile = null);
  }

  void _sendQuickMessage(String message) {
    Get.find<FavorController>().sendMessage(widget.favorId, message);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: MyColor.cardBgColor,
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text('Chat con repartidor', style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
      ),
      body: GetBuilder<FavorController>(
        builder: (c) {
          return Column(
            children: [
              // ── Messages ──
              Expanded(
                child: c.loadingMessages
                    ? const Center(child: CircularProgressIndicator())
                    : c.messages.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.chat_bubble_outline_rounded, size: 64, color: MyColor.bodyMutedTextColor.withValues(alpha: 0.3)),
                                SizedBox(height: Dimensions.space12),
                                Text('Sin mensajes aún', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                                SizedBox(height: Dimensions.space4),
                                Text('Envía un mensaje al repartidor', style: regularSmall.copyWith(color: MyColor.bodyMutedTextColor)),
                              ],
                            ),
                          )
                        : ListView.builder(
                            controller: _scrollCtrl,
                            reverse: true,
                            padding: EdgeInsets.all(Dimensions.space12),
                            itemCount: c.messages.length,
                            itemBuilder: (_, i) {
                              final msg = c.messages[i];
                              bool isMe = msg.isFromCustomer;
                              return _buildBubble(msg, isMe);
                            },
                          ),
              ),
              // ── Input ──
              Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    color: MyColor.colorWhite,
                    padding: EdgeInsets.only(left: Dimensions.space12, top: Dimensions.space8),
                    child: SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: Row(children: [
                        _quickAction('Precio encontrado: S/ ', Icons.sell_outlined),
                        _quickAction('Te envío una foto', Icons.photo_camera_outlined),
                        _quickAction('¿Apruebas este cambio?', Icons.help_outline_rounded),
                      ]),
                    ),
                  ),
                  if (_pendingImageFile != null)
                    Container(
                      padding: EdgeInsets.only(left: Dimensions.space12, right: Dimensions.space12, top: Dimensions.space8),
                      child: Row(
                        children: [
                          Stack(
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                                child: Image.file(_pendingImageFile!, width: 60, height: 60, fit: BoxFit.cover),
                              ),
                              Positioned(
                                top: -4,
                                right: -4,
                                child: GestureDetector(
                                  onTap: _clearImage,
                                  child: Container(
                                    padding: EdgeInsets.all(2),
                                    decoration: BoxDecoration(color: MyColor.redCancelTextColor, shape: BoxShape.circle),
                                    child: Icon(Icons.close, size: 14, color: MyColor.colorWhite),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          if (_sendingImage) ...[
                            SizedBox(width: Dimensions.space8),
                            SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)),
                          ],
                        ],
                      ),
                    ),
                  Container(
                    padding: EdgeInsets.fromLTRB(Dimensions.space12, Dimensions.space8, Dimensions.space12, Dimensions.space12),
                    decoration: BoxDecoration(
                      color: MyColor.colorWhite,
                      boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.08), blurRadius: 8, offset: const Offset(0, -2))],
                    ),
                    child: Row(
                      children: [
                        GestureDetector(
                          onTap: _pickAndSendImage,
                          child: Container(
                            padding: EdgeInsets.all(Dimensions.space12),
                            child: Icon(Icons.image_rounded, color: MyColor.bodyMutedTextColor, size: 22),
                          ),
                        ),
                        Expanded(
                          child: TextField(
                            controller: _msgCtrl,
                            decoration: InputDecoration(
                              hintText: 'Escribe un mensaje...',
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(Dimensions.largeRadius),
                                borderSide: BorderSide(color: MyColor.borderColor),
                              ),
                              contentPadding: EdgeInsets.symmetric(horizontal: Dimensions.space15, vertical: Dimensions.space10),
                            ),
                            maxLines: 3,
                            minLines: 1,
                            textInputAction: TextInputAction.send,
                            onSubmitted: (_) => _send(),
                          ),
                        ),
                        SizedBox(width: Dimensions.space8),
                        GestureDetector(
                          onTap: _send,
                          child: Container(
                            padding: EdgeInsets.all(Dimensions.space12),
                            decoration: BoxDecoration(
                              color: MyColor.primaryColor,
                              borderRadius: BorderRadius.circular(Dimensions.defaultRadius),
                            ),
                            child: _sendingImage ? SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: MyColor.colorWhite)) : Icon(Icons.send_rounded, color: MyColor.colorWhite, size: 20),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _quickAction(String text, IconData icon) => Padding(
        padding: EdgeInsets.only(right: Dimensions.space8),
        child: ActionChip(
          avatar: Icon(icon, size: 16, color: MyColor.primaryColor),
          label: Text(text, style: regularSmall.copyWith(color: MyColor.primaryColor, fontWeight: FontWeight.w600)),
          backgroundColor: MyColor.primaryColor.withValues(alpha: .08),
          side: BorderSide(color: MyColor.primaryColor.withValues(alpha: .16)),
          onPressed: () => _sendQuickMessage(text),
        ),
      );

  Widget _buildBubble(FavorMessageModel msg, bool isMe) {
    return Align(
      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: EdgeInsets.only(bottom: Dimensions.space8),
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
        decoration: BoxDecoration(
          color: isMe ? MyColor.primaryColor : MyColor.colorWhite,
          borderRadius: BorderRadius.only(
            topLeft: Radius.circular(Dimensions.defaultRadius),
            topRight: Radius.circular(Dimensions.defaultRadius),
            bottomLeft: isMe ? const Radius.circular(Dimensions.defaultRadius) : const Radius.circular(4),
            bottomRight: isMe ? const Radius.circular(4) : Radius.circular(Dimensions.defaultRadius),
          ),
          boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 4, offset: const Offset(0, 1))],
        ),
        padding: (msg.image != null && msg.image!.isNotEmpty) ? EdgeInsets.zero : EdgeInsets.all(Dimensions.space12),
        child: Column(
          crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
          children: [
            if (!isMe)
              Padding(
                padding: EdgeInsets.fromLTRB(Dimensions.space12, Dimensions.space8, Dimensions.space12, 4),
                child: Text(msg.senderName ?? 'Repartidor', style: regularSmall.copyWith(fontWeight: FontWeight.w600, color: MyColor.primaryColor)),
              ),
            if (msg.image != null && msg.image!.isNotEmpty)
              ClipRRect(
                borderRadius: BorderRadius.vertical(top: Radius.circular(Dimensions.defaultRadius)),
                child: MyImageWidget(
                  imageUrl: msg.image!,
                  width: double.infinity,
                  boxFit: BoxFit.cover,
                ),
              ),
            if (msg.message != null && msg.message!.isNotEmpty)
              Padding(
                padding: (msg.image != null && msg.image!.isNotEmpty) ? EdgeInsets.all(Dimensions.space12) : EdgeInsets.zero,
                child: Text(
                  msg.message ?? '',
                  style: regularDefault.copyWith(color: isMe ? MyColor.colorWhite : MyColor.primaryTextColor),
                ),
              ),
            Padding(
              padding: (msg.image != null && msg.image!.isNotEmpty) ? EdgeInsets.fromLTRB(Dimensions.space12, 0, Dimensions.space12, Dimensions.space8) : EdgeInsets.only(top: 4),
              child: Text(
                _formatTime(msg.createdAt),
                style: regularSmall.copyWith(fontSize: 10, color: isMe ? MyColor.colorWhite.withValues(alpha: 0.6) : MyColor.bodyMutedTextColor),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatTime(String? dateStr) {
    if (dateStr == null) return '';
    try {
      var dt = DateTime.parse(dateStr);
      return '${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return '';
    }
  }
}
