import 'package:cbt_client/core/config/api_config.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ApiConfig Tests (Test 1: Base URL Configuration)', () {
    setUp(() {
      ApiConfig.resetToDefault();
    });

    test('default base URL should match expected dev URL', () {
      expect(ApiConfig.baseUrl, equals(ApiConfig.defaultBaseUrl));
    });

    test('healthUrl should concatenate baseUrl and healthPath correctly', () {
      expect(ApiConfig.healthUrl, equals('${ApiConfig.defaultBaseUrl}/api/v1/health'));
    });

    test('setBaseUrl should update centralized URL and strip trailing slashes', () {
      ApiConfig.setBaseUrl('http://192.168.1.50:8000/');
      expect(ApiConfig.baseUrl, equals('http://192.168.1.50:8000'));
      expect(ApiConfig.healthUrl, equals('http://192.168.1.50:8000/api/v1/health'));
    });

    test('setBaseUrl should throw ArgumentError on invalid URL scheme or format', () {
      expect(() => ApiConfig.setBaseUrl('not-a-valid-url'), throwsArgumentError);
      expect(() => ApiConfig.setBaseUrl('ftp://192.168.1.1:8000'), throwsArgumentError);
      expect(() => ApiConfig.setBaseUrl(''), throwsArgumentError);
    });

    test('resetToDefault restores base URL to default', () {
      ApiConfig.setBaseUrl('http://192.168.10.15:8000');
      expect(ApiConfig.baseUrl, equals('http://192.168.10.15:8000'));
      ApiConfig.resetToDefault();
      expect(ApiConfig.baseUrl, equals(ApiConfig.defaultBaseUrl));
    });

    test('isValidUrl correctly validates various URL forms', () {
      expect(ApiConfig.isValidUrl('http://192.168.1.1:8000'), isTrue);
      expect(ApiConfig.isValidUrl('http://localhost:8000'), isTrue);
      expect(ApiConfig.isValidUrl('https://cbt.sekolah.sch.id'), isTrue);
      expect(ApiConfig.isValidUrl('invalid_string'), isFalse);
      expect(ApiConfig.isValidUrl('http://'), isFalse);
    });
  });
}
