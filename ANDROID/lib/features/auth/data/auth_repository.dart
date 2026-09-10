import '../../../core/config/api_config.dart';
import '../../../core/config/app_preferences.dart';
import '../../../core/network/network_client.dart';
import 'auth_model.dart';

class AuthRepository {
  final NetworkClient _networkClient;

  AuthRepository({NetworkClient? networkClient})
      : _networkClient = networkClient ?? NetworkClient();

  /// Authenticate student via username and password.
  Future<AuthResponse> login(String username, String password) async {
    final url = '${ApiConfig.baseUrl}/api/v1/auth/login';

    final json = await _networkClient.postJson(
      url,
      body: {
        'username': username.trim(),
        'password': password,
      },
    );

    final response = AuthResponse.fromJson(json);

    // Save session locally
    await AppPreferences.saveUserSession(
      userId: response.user.id,
      username: response.user.username,
      name: response.user.name,
      token: response.token,
      studentId: response.user.studentId,
      nis: response.user.nis,
    );

    return response;
  }

  /// Revoke current Sanctum session on server and clear local preferences.
  Future<void> logout() async {
    final token = AppPreferences.getAuthToken();
    if (token != null) {
      try {
        final url = '${ApiConfig.baseUrl}/api/v1/auth/logout';
        await _networkClient.postJson(url, token: token);
      } catch (_) {
        // Silently continue if server unreachable during logout
      }
    }
    await AppPreferences.clearSession();
  }

  /// Get active profile from server.
  Future<UserModel> getProfile() async {
    final token = AppPreferences.getAuthToken();
    final url = '${ApiConfig.baseUrl}/api/v1/auth/me';

    final json = await _networkClient.getJson(url, token: token);
    final data = json['data'] as Map<String, dynamic>;
    final userJson = data['user'] as Map<String, dynamic>;

    return UserModel.fromJson(userJson);
  }
}
