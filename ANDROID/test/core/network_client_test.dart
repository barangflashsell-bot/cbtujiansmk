import 'dart:async';
import 'dart:io';

import 'package:cbt_client/core/network/http_client_adapter.dart';
import 'package:cbt_client/core/network/network_client.dart';
import 'package:cbt_client/core/network/network_exceptions.dart';
import 'package:flutter_test/flutter_test.dart';

/// Fake adapter for controlled boundary testing without external packages.
class FakeHttpClientAdapter implements HttpClientAdapter {
  HttpResponseData? responseToReturn;
  Exception? exceptionToThrow;

  @override
  Future<HttpResponseData> get(
    Uri uri, {
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    if (exceptionToThrow != null) {
      throw exceptionToThrow!;
    }
    return responseToReturn ??
        const HttpResponseData(
          statusCode: 200,
          body: '{"success":true}',
        );
  }

  @override
  Future<HttpResponseData> post(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    if (exceptionToThrow != null) {
      throw exceptionToThrow!;
    }
    return responseToReturn ??
        const HttpResponseData(
          statusCode: 200,
          body: '{"success":true}',
        );
  }

  @override
  Future<HttpResponseData> patch(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    if (exceptionToThrow != null) {
      throw exceptionToThrow!;
    }
    return responseToReturn ??
        const HttpResponseData(
          statusCode: 200,
          body: '{"success":true}',
        );
  }
}

void main() {
  late FakeHttpClientAdapter fakeAdapter;
  late NetworkClient client;

  setUp(() {
    fakeAdapter = FakeHttpClientAdapter();
    client = NetworkClient(adapter: fakeAdapter);
  });

  group('NetworkClient Tests', () {
    test('Test 2: Network timeout throws ConnectionTimeoutException', () async {
      fakeAdapter.exceptionToThrow = TimeoutException('Timeout occurred');

      expect(
        () => client.getJson('http://192.168.1.100:8000/api/v1/health'),
        throwsA(isA<ConnectionTimeoutException>()),
      );
    });

    test('Test 3: HTTP error status throws HttpStatusException', () async {
      fakeAdapter.responseToReturn = const HttpResponseData(
        statusCode: 500,
        body: '{"message":"Internal Server Error"}',
      );

      expect(
        () => client.getJson('http://192.168.1.100:8000/api/v1/health'),
        throwsA(
          isA<HttpStatusException>().having(
            (e) => e.statusCode,
            'statusCode',
            500,
          ),
        ),
      );
    });

    test('HTTP 404 status throws HttpStatusException with 404 and message', () async {
      fakeAdapter.responseToReturn = const HttpResponseData(
        statusCode: 404,
        body: '{"message":"Not Found"}',
      );

      expect(
        () => client.getJson('http://192.168.1.100:8000/api/v1/unknown'),
        throwsA(
          isA<HttpStatusException>()
              .having((e) => e.statusCode, 'statusCode', 404)
              .having((e) => e.message, 'message', 'Not Found'),
        ),
      );
    });

    test('SocketException throws ConnectionRefusedException', () async {
      fakeAdapter.exceptionToThrow = const SocketException('Connection refused');

      expect(
        () => client.getJson('http://192.168.1.100:8000/api/v1/health'),
        throwsA(isA<ConnectionRefusedException>()),
      );
    });

    test('Malformed JSON throws InvalidResponseException', () async {
      fakeAdapter.responseToReturn = const HttpResponseData(
        statusCode: 200,
        body: 'This is not valid JSON <html/>',
      );

      expect(
        () => client.getJson('http://192.168.1.100:8000/api/v1/health'),
        throwsA(isA<InvalidResponseException>()),
      );
    });

    test('Valid JSON parses successfully', () async {
      fakeAdapter.responseToReturn = const HttpResponseData(
        statusCode: 200,
        body: '{"success":true,"message":"OK"}',
      );

      final result = await client.getJson('http://192.168.1.100:8000/api/v1/health');
      expect(result['success'], isTrue);
      expect(result['message'], equals('OK'));
    });
  });
}
