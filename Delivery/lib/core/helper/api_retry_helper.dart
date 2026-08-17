import 'package:lizto_delivery/core/helper/string_format_helper.dart';

class ApiRetryHelper {
  static const int maxRetries = 3;
  static const Duration retryDelay = Duration(seconds: 1);

  static Future<T?> retry<T>(
    Future<T> Function() call, {
    int maxAttempts = maxRetries,
    Duration delay = retryDelay,
    String? context,
  }) async {
    for (int attempt = 1; attempt <= maxAttempts; attempt++) {
      try {
        return await call();
      } catch (e) {
        if (context != null) {
          printX('$context - Attempt $attempt/$maxAttempts failed: $e');
        }
        if (attempt == maxAttempts) rethrow;
        await Future.delayed(delay * attempt);
      }
    }
    return null;
  }
}
