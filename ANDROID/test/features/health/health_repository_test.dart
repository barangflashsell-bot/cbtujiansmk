import 'dart:async';
import 'dart:io';

import 'package:cbt_client/core/connection/connection_manager.dart';
import 'package:cbt_client/core/connection/connection_state.dart';
import 'package:cbt_client/core/network/http_client_adapter.dart';
import 'package:cbt_client/core/network/network_client.dart';
import 'package:cbt_client/core/network/network_exceptions.dart';
import 'package:cbt_client/features/health/data/health_repository.dart';
import 'package:flutter_test/flutter_test.dart';

class MockHttpAdapter implements HttpClientAdapter {
  HttpResponseData? response;
  Exception? error;

  @override
  Future<HttpResponseData> get(
    Uri uri, {
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    if (error != null) throw error!;
    return response ??
        const HttpResponseData(
          statusCode: 200,
          body: '{"success":true,"message":"Healthy","data":{"status":"healthy","api_version":"v1.0.0","timestamp":"2026-09-11T00:00:00Z"}}',
        );
  }

  @override
  Future<HttpResponseData> post(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    if (error != null) throw error!;
    return response ??
        const HttpResponseData(
          statusCode: 200,
          body: '{"success":true,"message":"Healthy","data":{"status":"healthy","api_version":"v1.0.0","timestamp":"2026-09-11T00:00:00Z"}}',
        );
  }

  @override
  Future<HttpResponseData> patch(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    if (error != null) throw error!;
    return response ??
        const HttpResponseData(
          statusCode: 200,
          body: '{"success":true,"message":"Healthy","data":{"status":"healthy","api_version":"v1.0.0","timestamp":"2026-09-11T00:00:00Z"}}',
        );
  }
}

void main() {
  group('HealthRepository Tests', () {
    late MockHttpAdapter mockAdapter;
    late NetworkClient networkClient;
    late ConnectionManager connectionManager;
    late HealthRepository repository;

    setUp(() {
      mockAdapter = MockHttpAdapter();
      networkClient = NetworkClient(adapter: mockAdapter);
      connectionManager = ConnectionManager();
      repository = HealthRepository(
        networkClient: networkClient,
        connectionManager: connectionManager,
      );
    });

    test('Successful health check updates connectionManager to ONLINE', () async {
      mockAdapter.response = const HttpResponseData(
        statusCode: 200,
        body: '{"success":true,"message":"CBT REST API is active and healthy","data":{"status":"healthy","api_version":"v1.0.0","timestamp":"2026-09-11T00:00:00Z"}}',
      );

      final result = await repository.checkHealth();

      expect(result.isHealthy, isTrue);
      expect(connectionManager.value.status, equals(ServerConnectionStatus.online));
      expect(connectionManager.value.isOnline, isTrue);
      expect(connectionManager.value.message, equals('CBT REST API is active and healthy'));
    });

    test('Timeout updates connectionManager to OFFLINE and rethrows', () async {
      mockAdapter.error = TimeoutException('Operation timeout');

      await expectLater(
        () => repository.checkHealth(),
        throwsA(isA<ConnectionTimeoutException>()),
      );
      expect(connectionManager.value.status, equals(ServerConnectionStatus.offline));
    });

    test('SocketException updates connectionManager to OFFLINE and rethrows', () async {
      mockAdapter.error = const SocketException('Connection refused');

      await expectLater(
        () => repository.checkHealth(),
        throwsA(isA<ConnectionRefusedException>()),
      );
      expect(connectionManager.value.status, equals(ServerConnectionStatus.offline));
    });

    test('HTTP 500 error updates connectionManager to ERROR and rethrows', () async {
      mockAdapter.response = const HttpResponseData(
        statusCode: 500,
        body: '{"message":"Server error"}',
      );

      await expectLater(
        () => repository.checkHealth(),
        throwsA(isA<HttpStatusException>()),
      );
      expect(connectionManager.value.status, equals(ServerConnectionStatus.error));
    });

    test('Invalid JSON contract updates connectionManager to ERROR and rethrows', () async {
      mockAdapter.response = const HttpResponseData(
        statusCode: 200,
        body: '{"success":false,"message":"Down"}',
      );

      await expectLater(
        () => repository.checkHealth(),
        throwsA(isA<InvalidResponseException>()),
      );
      expect(connectionManager.value.status, equals(ServerConnectionStatus.error));
    });
  });
}
