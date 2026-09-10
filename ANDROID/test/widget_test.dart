import 'package:cbt_client/core/connection/connection_manager.dart';
import 'package:cbt_client/core/network/http_client_adapter.dart';
import 'package:cbt_client/core/network/network_client.dart';
import 'package:cbt_client/features/health/data/health_repository.dart';
import 'package:cbt_client/features/health/presentation/health_check_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

class StubHttpAdapter implements HttpClientAdapter {
  @override
  Future<HttpResponseData> get(
    Uri uri, {
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    return const HttpResponseData(
      statusCode: 200,
      body: '{"success":true,"message":"CBT REST API is active and healthy","data":{"status":"healthy","api_version":"v1.0.0","timestamp":"2026-09-11T00:00:00Z"}}',
    );
  }

  @override
  Future<HttpResponseData> post(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    return const HttpResponseData(
      statusCode: 200,
      body: '{"success":true,"message":"OK","data":{}}',
    );
  }

  @override
  Future<HttpResponseData> patch(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    return const HttpResponseData(
      statusCode: 200,
      body: '{"success":true,"message":"OK","data":{}}',
    );
  }
}

void main() {
  testWidgets('HealthCheckScreen renders title, input field and check button', (WidgetTester tester) async {
    final adapter = StubHttpAdapter();
    final client = NetworkClient(adapter: adapter);
    final manager = ConnectionManager();
    final repository = HealthRepository(networkClient: client, connectionManager: manager);

    await tester.pumpWidget(
      MaterialApp(
        home: HealthCheckScreen(repository: repository),
      ),
    );

    // Initial frame
    expect(find.text('CBT Server Diagnostics'), findsOneWidget);
    expect(find.text('Konfigurasi Host CBT (LAN)'), findsOneWidget);
    expect(find.byType(TextField), findsOneWidget);

    // Pump to process initial post-frame health check
    await tester.pumpAndSettle();

    expect(find.text('Status: ONLINE'), findsOneWidget);
    expect(find.text('CBT REST API is active and healthy'), findsOneWidget);
    expect(find.text('Detail Respon Server CBT'), findsOneWidget);
    expect(find.text('v1.0.0'), findsOneWidget);
  });
}
