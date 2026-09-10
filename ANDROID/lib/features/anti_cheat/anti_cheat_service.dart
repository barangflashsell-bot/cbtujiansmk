import '../../../core/config/api_config.dart';
import '../../../core/config/app_preferences.dart';
import '../../../core/network/network_client.dart';

/// Service for reporting exam integrity events to Laravel activity_logs.
class AntiCheatService {
  final NetworkClient _networkClient;

  AntiCheatService({NetworkClient? networkClient})
      : _networkClient = networkClient ?? NetworkClient();

  /// Logs a security event to `POST /api/v1/activity-logs/event`.
  Future<void> reportEvent({
    required String action,
    String? details,
  }) async {
    final token = AppPreferences.getAuthToken();
    if (token == null) return;

    try {
      final url = '${ApiConfig.baseUrl}/api/v1/activity-logs/event';
      await _networkClient.postJson(
        url,
        body: {
          'action': action,
          'details': details ?? 'Event integritas tercatat dari klien Android',
        },
        token: token,
      );
    } catch (_) {
      // Non-blocking: failures to report anti-cheat event must never interrupt student exam
    }
  }
}
