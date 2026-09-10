import 'package:shared_preferences/shared_preferences.dart';
import 'api_config.dart';

/// Centralized local persistent storage for client settings and session.
class AppPreferences {
  static const String _keyServerUrl = 'cbt_server_url';
  static const String _keyAuthToken = 'cbt_auth_token';
  static const String _keyUserId = 'cbt_user_id';
  static const String _keyUsername = 'cbt_username';
  static const String _keyUserName = 'cbt_user_name';
  static const String _keyStudentId = 'cbt_student_id';
  static const String _keyStudentNis = 'cbt_student_nis';

  static SharedPreferences? _prefs;

  static Future<void> init() async {
    _prefs ??= await SharedPreferences.getInstance();
    final savedUrl = _prefs?.getString(_keyServerUrl);
    if (savedUrl != null && savedUrl.isNotEmpty) {
      try {
        ApiConfig.setBaseUrl(savedUrl);
      } catch (_) {}
    }
  }

  static SharedPreferences get prefs {
    if (_prefs == null) {
      throw StateError('AppPreferences must be initialized via init() first');
    }
    return _prefs!;
  }

  // Server Base URL
  static String getServerUrl() {
    return _prefs?.getString(_keyServerUrl) ?? ApiConfig.baseUrl;
  }

  static Future<void> saveServerUrl(String url) async {
    await _prefs?.setString(_keyServerUrl, url);
    ApiConfig.setBaseUrl(url);
  }

  // Authentication Token
  static String? getAuthToken() {
    return _prefs?.getString(_keyAuthToken);
  }

  static Future<void> saveAuthToken(String token) async {
    await _prefs?.setString(_keyAuthToken, token);
  }

  static bool get isAuthenticated => getAuthToken() != null && getAuthToken()!.isNotEmpty;

  // Student Profile Data
  static Future<void> saveUserSession({
    required int userId,
    required String username,
    required String name,
    required String token,
    int? studentId,
    String? nis,
  }) async {
    await _prefs?.setString(_keyAuthToken, token);
    await _prefs?.setInt(_keyUserId, userId);
    await _prefs?.setString(_keyUsername, username);
    await _prefs?.setString(_keyUserName, name);
    if (studentId != null) await _prefs?.setInt(_keyStudentId, studentId);
    if (nis != null) await _prefs?.setString(_keyStudentNis, nis);
  }

  static int? get userId => _prefs?.getInt(_keyUserId);
  static String? get username => _prefs?.getString(_keyUsername);
  static String? get userName => _prefs?.getString(_keyUserName);
  static int? get studentId => _prefs?.getInt(_keyStudentId);
  static String? get studentNis => _prefs?.getString(_keyStudentNis);

  // Clear session on logout
  static Future<void> clearSession() async {
    await _prefs?.remove(_keyAuthToken);
    await _prefs?.remove(_keyUserId);
    await _prefs?.remove(_keyUsername);
    await _prefs?.remove(_keyUserName);
    await _prefs?.remove(_keyStudentId);
    await _prefs?.remove(_keyStudentNis);
  }
}
