import 'package:cbt_client/core/network/http_client_adapter.dart';
import 'package:cbt_client/core/network/network_client.dart';
import 'package:cbt_client/features/auth/data/auth_model.dart';
import 'package:cbt_client/features/auth/data/auth_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

class MockHttpAdapter implements HttpClientAdapter {
  HttpResponseData? response;
  Exception? error;

  @override
  Future<HttpResponseData> get(Uri uri, {Map<String, String>? headers, Duration? timeout}) async {
    if (error != null) throw error!;
    return response ?? const HttpResponseData(statusCode: 200, body: '{"success":true}');
  }

  @override
  Future<HttpResponseData> post(Uri uri, {String? body, Map<String, String>? headers, Duration? timeout}) async {
    if (error != null) throw error!;
    return response ?? const HttpResponseData(statusCode: 200, body: '{"success":true}');
  }

  @override
  Future<HttpResponseData> patch(Uri uri, {String? body, Map<String, String>? headers, Duration? timeout}) async {
    if (error != null) throw error!;
    return response ?? const HttpResponseData(statusCode: 200, body: '{"success":true}');
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('Auth Tests', () {
    late MockHttpAdapter mockAdapter;
    late NetworkClient networkClient;
    late AuthRepository authRepository;

    setUp(() {
      SharedPreferences.setMockInitialValues({});
      mockAdapter = MockHttpAdapter();
      networkClient = NetworkClient(adapter: mockAdapter);
      authRepository = AuthRepository(networkClient: networkClient);
    });

    test('UserModel and AuthResponse parse correctly from backend contract', () {
      final json = {
        'success': true,
        'message': 'Login berhasil',
        'data': {
          'user': {
            'id': 3,
            'username': 'peserta',
            'name': 'Budi Santoso',
            'role': 'student',
            'student_id': 1,
            'nis': '1001',
          },
          'token': '3|sanctum_plain_token_abc',
          'token_type': 'Bearer',
        }
      };

      final authResponse = AuthResponse.fromJson(json);

      expect(authResponse.user.id, equals(3));
      expect(authResponse.user.username, equals('peserta'));
      expect(authResponse.user.name, equals('Budi Santoso'));
      expect(authResponse.user.role, equals('student'));
      expect(authResponse.token, equals('3|sanctum_plain_token_abc'));
      expect(authResponse.tokenType, equals('Bearer'));
    });

    test('login success returns AuthResponse and saves session', () async {
      mockAdapter.response = const HttpResponseData(
        statusCode: 200,
        body: '{"success":true,"message":"Login berhasil","data":{"user":{"id":3,"username":"peserta","name":"Siswa","role":"student"},"token":"tok123","token_type":"Bearer"}}',
      );

      final result = await authRepository.login('peserta', 'peserta123');

      expect(result.token, equals('tok123'));
      expect(result.user.username, equals('peserta'));
    });
  });
}
