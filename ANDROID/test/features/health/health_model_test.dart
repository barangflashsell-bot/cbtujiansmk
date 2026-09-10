import 'package:cbt_client/core/network/network_exceptions.dart';
import 'package:cbt_client/features/health/data/health_model.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('HealthModel Tests', () {
    test('Test 4: Valid health response JSON parses successfully', () {
      final json = {
        'success': true,
        'message': 'CBT REST API is active and healthy',
        'data': {
          'status': 'healthy',
          'api_version': 'v1.0.0',
          'timestamp': '2026-09-11T00:20:00+07:00',
        }
      };

      final response = HealthResponse.fromJson(json);

      expect(response.success, isTrue);
      expect(response.message, equals('CBT REST API is active and healthy'));
      expect(response.status, equals('healthy'));
      expect(response.apiVersion, equals('v1.0.0'));
      expect(response.timestamp, equals('2026-09-11T00:20:00+07:00'));
      expect(response.isHealthy, isTrue);
    });

    test('Test 5a: Health response with success=false throws InvalidResponseException', () {
      final json = {
        'success': false,
        'message': 'Maintenance mode',
        'data': null,
      };

      expect(
        () => HealthResponse.fromJson(json),
        throwsA(isA<InvalidResponseException>()),
      );
    });

    test('Test 5b: Health response missing data throws InvalidResponseException', () {
      final json = {
        'success': true,
        'message': 'OK',
      };

      expect(
        () => HealthResponse.fromJson(json),
        throwsA(isA<InvalidResponseException>()),
      );
    });

    test('Test 5c: Health response with missing required fields in data throws InvalidResponseException', () {
      final json = {
        'success': true,
        'message': 'OK',
        'data': {
          'status': 'healthy',
          // missing api_version and timestamp
        }
      };

      expect(
        () => HealthResponse.fromJson(json),
        throwsA(isA<InvalidResponseException>()),
      );
    });
  });
}
