import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:liztogo_repartidor/core/utils/my_color.dart';
import 'package:liztogo_repartidor/core/utils/style.dart';
import 'package:liztogo_repartidor/data/controller/delivery/courier_controller.dart';
import 'package:liztogo_repartidor/data/model/delivery/favor_models.dart';

class CourierChatScreen extends StatefulWidget {
  final int jobId;
  final String customerName;
  const CourierChatScreen({super.key, required this.jobId, required this.customerName});

  @override
  State<CourierChatScreen> createState() => _CourierChatScreenState();
}

class _CourierChatScreenState extends State<CourierChatScreen> {
  final _textCtrl = TextEditingController();
  final _scrollCtrl = ScrollController();
  final _focusNode = FocusNode();
  int _previousMessageCount = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final c = Get.find<CourierController>();
      c.messages.clear();
      c.loadMessages(widget.jobId);
      c.subscribeToChatChannel(widget.jobId);
    });
  }

  @override
  void dispose() {
    _textCtrl.dispose();
    _scrollCtrl.dispose();
    _focusNode.dispose();
    Get.find<CourierController>().unsubscribeFromChatChannel();
    super.dispose();
  }

  void _scrollToBottom() {
    if (!_scrollCtrl.hasClients) return;
    final max = _scrollCtrl.position.maxScrollExtent;
    if (max <= 0) return;
    _scrollCtrl.animateTo(max, duration: const Duration(milliseconds: 300), curve: Curves.easeOut);
  }

  void _sendText(CourierController c) async {
    final text = _textCtrl.text.trim();
    if (text.isEmpty) return;
    _textCtrl.clear();
    _focusNode.requestFocus();
    await c.sendMessage(widget.jobId, text);
    _scrollToBottom();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF2F2F7),
      appBar: AppBar(
        backgroundColor: MyColor.primaryColor,
        title: Text(widget.customerName, style: boldLarge.copyWith(color: MyColor.colorWhite)),
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white),
          onPressed: () => Get.back(),
        ),
      ),
      body: GetBuilder<CourierController>(
        builder: (c) {
          if (c.messages.length != _previousMessageCount) {
            _previousMessageCount = c.messages.length;
            WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToBottom());
          }
          return Column(
            children: [
              Expanded(
                child: c.loadingMessages
                    ? const Center(child: CircularProgressIndicator())
                    : c.messages.isEmpty
                        ? Center(
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.chat_bubble_outline_rounded, size: 64, color: MyColor.bodyMutedTextColor.withOpacity(0.3)),
                                const SizedBox(height: 12),
                                Text('Sin mensajes', style: regularDefault.copyWith(color: MyColor.bodyMutedTextColor)),
                              ],
                            ),
                          )
                        : GestureDetector(
                            onTap: () => FocusScope.of(context).unfocus(),
                            child: ListView.builder(
                              controller: _scrollCtrl,
                              padding: const EdgeInsets.all(12),
                              itemCount: c.messages.length,
                              itemBuilder: (_, i) => _buildBubble(c.messages[i]),
                            ),
                          ),
              ),
              _buildInputBar(c),
            ],
          );
        },
      ),
    );
  }

  Widget _buildBubble(FavorMessageModel msg) {
    final isMe = msg.isFromCourier;
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Align(
        alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
        child: Container(
          constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.75),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            color: isMe ? MyColor.primaryColor : MyColor.colorWhite,
            borderRadius: BorderRadius.only(
              topLeft: const Radius.circular(18),
              topRight: const Radius.circular(18),
              bottomLeft: isMe ? const Radius.circular(18) : const Radius.circular(4),
              bottomRight: isMe ? const Radius.circular(4) : const Radius.circular(18),
            ),
          ),
          child: Column(
            crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
            children: [
              if (msg.image != null && msg.image!.isNotEmpty) ...[
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Image.network(msg.image!, width: 180, fit: BoxFit.cover, errorBuilder: (_, __, ___) => const Icon(Icons.broken_image, size: 48)),
                ),
                const SizedBox(height: 6),
              ],
              if (msg.message != null && msg.message!.isNotEmpty)
                Text(
                  msg.message!,
                  style: regularDefault.copyWith(color: isMe ? Colors.white : MyColor.primaryTextColor, fontSize: 15),
                ),
              const SizedBox(height: 4),
              Text(
                _formatTime(msg.createdAt),
                style: regularSmall.copyWith(color: isMe ? Colors.white70 : MyColor.bodyMutedTextColor, fontSize: 10),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInputBar(CourierController c) {
    return SafeArea(
      top: false,
      child: Container(
        padding: const EdgeInsets.fromLTRB(8, 8, 8, 8),
        decoration: BoxDecoration(
          color: MyColor.colorWhite,
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.06), blurRadius: 8, offset: const Offset(0, -2))],
        ),
        child: Row(
          children: [
            IconButton(
              icon: Icon(Icons.image_rounded, color: MyColor.primaryColor.withOpacity(0.7)),
              onPressed: () async {
                final result = await FilePicker.platform.pickFiles(type: FileType.image);
                if (result != null && result.files.single.path != null) {
                  await c.sendImage(widget.jobId, File(result.files.single.path!));
                  _scrollToBottom();
                }
              },
            ),
            Expanded(
              child: Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF2F2F7),
                  borderRadius: BorderRadius.circular(24),
                ),
                child: TextField(
                  controller: _textCtrl,
                  focusNode: _focusNode,
                  style: regularDefault.copyWith(fontSize: 15),
                  decoration: const InputDecoration(
                    hintText: 'Escribe un mensaje...',
                    border: InputBorder.none,
                    contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 11),
                  ),
                  textInputAction: TextInputAction.send,
                  onSubmitted: (_) => _sendText(c),
                  minLines: 1,
                  maxLines: 4,
                ),
              ),
            ),
            const SizedBox(width: 6),
            GestureDetector(
              onTap: c.sendingMessage ? null : () => _sendText(c),
              child: Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(color: MyColor.primaryColor, shape: BoxShape.circle),
                child: Center(
                  child: c.sendingMessage
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatTime(String? raw) {
    if (raw == null) return '';
    try {
      final dt = DateTime.parse(raw).toLocal();
      return '${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return '';
    }
  }
}
