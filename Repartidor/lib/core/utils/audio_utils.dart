import 'package:just_audio/just_audio.dart';
import 'package:liztogo_repartidor/core/helper/string_format_helper.dart';
import 'package:liztogo_repartidor/data/services/api_client.dart';
import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:liztogo_repartidor/core/helper/shared_preference_helper.dart';

class AudioUtils {
  static AudioPlayer? _player;

  static Future<void> playNotificationSound() async {
    try {
      String path = '';
      if (Get.isRegistered<ApiClient>()) {
        if (!Get.find<ApiClient>().isNotificationAudioEnable()) return;
        path = Get.find<ApiClient>().getNotificationAudio();
      } else {
        final prefs = await SharedPreferences.getInstance();
        final isEnable = prefs.getString(SharedPreferenceHelper.notificationAudioEnableKey) ?? '1';
        if (isEnable != '1') return;
        path = prefs.getString(SharedPreferenceHelper.notificationAudioKey) ?? '';
      }

      if (path.isEmpty) return;
      await stop();
      _player = AudioPlayer();
      await _player!.setUrl(path);
      await _player!.setLoopMode(LoopMode.one);
      await _player!.play();
    } catch (e) {
      printX(e);
    }
  }

  static Future<void> stop() async {
    try {
      await _player?.stop();
      await _player?.dispose();
      _player = null;
    } catch (_) {}
  }
}
