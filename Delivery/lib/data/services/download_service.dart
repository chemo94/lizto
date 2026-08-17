import 'dart:io';

import 'package:dio/dio.dart';
import 'package:get/instance_manager.dart';
import 'package:open_file/open_file.dart';
import 'package:lizto_delivery/core/helper/shared_preference_helper.dart';
import 'package:lizto_delivery/core/helper/string_format_helper.dart';
import 'package:lizto_delivery/core/utils/my_strings.dart';
import 'package:lizto_delivery/data/services/api_client.dart';
import 'package:lizto_delivery/environment.dart';
import 'package:lizto_delivery/presentation/components/snack_bar/show_custom_snackbar.dart';
import 'package:path_provider/path_provider.dart';
import 'package:permission_handler/permission_handler.dart';

class DownloadService {
  static String? extractFileExtension(String value) {
    RegExp regExp = RegExp(r'\.([a-zA-Z0-9]+)$');
    Match? match = regExp.firstMatch(value);
    return match?.group(1);
  }

  static Future<bool> downloadPDF({
    required String url,
    required String fileName,
  }) async {

    await [
      Permission.storage,
      Permission.mediaLibrary,
    ].request();

    print("🔵 URL: $url");

    String accessToken = Get.find<ApiClient>()
        .sharedPreferences
        .getString(SharedPreferenceHelper.accessTokenKey) ??
        "";

    print("🔵 TOKEN: $accessToken");

    Dio dio = Dio();

    Directory directory;

    if (Platform.isAndroid) {
      directory = Directory('/storage/emulated/0/Download');
    } else if (Platform.isIOS) {
      directory = await getApplicationDocumentsDirectory();
    } else {
      throw UnsupportedError("Unsupported platform");
    }

    String filePath = "${directory.path}/$fileName";

    try {

      Response response = await dio.download(
        url,
        filePath,
        options: Options(
          headers: {
            "Authorization": "Bearer $accessToken",
            "dev-token": Environment.devToken,
            "Accept": "application/pdf",
          },
          validateStatus: (status) {
            return true; // 👈 esto evita que Dio lance excepción automática
          },
        ),
        onReceiveProgress: (received, total) {
          if (total != -1) {
            print("Progress: ${(received / total * 100).toStringAsFixed(2)}%");
          }
        },
      );

      print("🔵 STATUS CODE: ${response.statusCode}");

      if (response.statusCode == 200) {
        print("✅ PDF descargado en: $filePath");
        openDownloadedFile(filePath);
        return true;
      } else {
        print("❌ ERROR BODY: ${response.data}");
        return false;
      }

    } on DioException catch (e) {

      print("❌ DIO ERROR TYPE: ${e.type}");
      print("❌ STATUS: ${e.response?.statusCode}");
      print("❌ RESPONSE DATA: ${e.response?.data}");
      print("❌ HEADERS: ${e.response?.headers}");
      print("❌ STACKTRACE: ${e.stackTrace}");

      return false;

    } catch (e, stack) {

      print("❌ GENERAL ERROR: $e");
      print("❌ STACKTRACE: $stack");

      return false;
    }
  }

  static Future<void> openDownloadedFile(String filePath) async {
    try {
      await OpenFile.open(filePath);
    } catch (e) {
      printX("ERROR: ${e.toString()}");
    }
  }
}
