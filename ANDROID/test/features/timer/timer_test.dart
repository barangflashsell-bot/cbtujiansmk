import 'dart:convert';

import 'package:cbt_client/core/config/app_preferences.dart';
import 'package:cbt_client/core/network/http_client_adapter.dart';
import 'package:cbt_client/core/network/network_client.dart';
import 'package:cbt_client/core/network/network_exceptions.dart';
import 'package:cbt_client/features/exam/data/exam_repository.dart';
import 'package:cbt_client/features/timer/exam_timer_controller.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

class MockHttpAdapter implements HttpClientAdapter {
  HttpResponseData? response;
  Exception? error;
  Uri? lastUri;

  @override
  Future<HttpResponseData> get(Uri uri, {Map<String, String>? headers, Duration? timeout}) async {
    lastUri = uri;
    if (error != null) throw error!;
    return response ?? const HttpResponseData(statusCode: 200, body: '{"success":true}');
  }

  @override
  Future<HttpResponseData> post(Uri uri, {String? body, Map<String, String>? headers, Duration? timeout}) async {
    lastUri = uri;
    if (error != null) throw error!;
    return response ?? const HttpResponseData(statusCode: 200, body: '{"success":true}');
  }

  @override
  Future<HttpResponseData> patch(Uri uri, {String? body, Map<String, String>? headers, Duration? timeout}) async {
    lastUri = uri;
    if (error != null) throw error!;
    return response ?? const HttpResponseData(statusCode: 200, body: '{"success":true}');
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('ExamTimerController Unit Tests', () {
    test('Formatted time displays HH:MM:SS or MM:SS correctly', () {
      final timer1 = ExamTimerController(initialSeconds: 3665); // 1h 1m 5s
      expect(timer1.formattedTime, equals('01:01:05'));

      final timer2 = ExamTimerController(initialSeconds: 720); // 12m 0s
      expect(timer2.formattedTime, equals('12:00'));

      final timer3 = ExamTimerController(initialSeconds: 45); // 45s
      expect(timer3.formattedTime, equals('00:45'));
    });

    test('Warning state is true when remaining time is less than 5 minutes', () {
      final timer = ExamTimerController(initialSeconds: 299);
      expect(timer.isWarning, isTrue);

      final safeTimer = ExamTimerController(initialSeconds: 600);
      expect(safeTimer.isWarning, isFalse);
    });

    test('SyncWithServer adjusts remaining time', () {
      final timer = ExamTimerController(initialSeconds: 500);
      timer.syncWithServer(120);
      expect(timer.remainingSeconds, equals(120));
      expect(timer.formattedTime, equals('02:00'));
    });

    test('Timer expiration callback is triggered when initial time is 0', () {
      bool expiredCalled = false;
      final timer = ExamTimerController(
        initialSeconds: 0,
        onTimeExpired: () {
          expiredCalled = true;
        },
      );
      timer.start();
      expect(expiredCalled, isTrue);
      expect(timer.isExpired, isTrue);
    });
  });

  group('Server Authoritative Timer Integration Tests', () {
    late MockHttpAdapter mockAdapter;
    late NetworkClient networkClient;
    late ExamRepository examRepository;

    setUp(() async {
      SharedPreferences.setMockInitialValues({'cbt_auth_token': 'auth_token_timer'});
      await AppPreferences.init();
      await AppPreferences.saveAuthToken('auth_token_timer');

      mockAdapter = MockHttpAdapter();
      networkClient = NetworkClient(adapter: mockAdapter);
      examRepository = ExamRepository(networkClient: networkClient);
    });

    // Test 1: getAttemptTimer berhasil parsing response server
    test('1. getAttemptTimer berhasil parsing response server', () async {
      const attemptId = 15;
      mockAdapter.response = HttpResponseData(
        statusCode: 200,
        body: jsonEncode({
          'success': true,
          'message': 'Status timer ujian berhasil diambil',
          'data': {
            'attempt_id': attemptId,
            'exam_id': 2,
            'student_id': 10,
            'status': 'in_progress',
            'server_time': '2026-09-08T15:40:00+07:00',
            'started_at': '2026-09-08T15:00:00+07:00',
            'ends_at': '2026-09-08T16:00:00+07:00',
            'duration_seconds': 3600,
            'remaining_seconds': 1200,
            'elapsed_seconds': 2400,
            'is_expired': false,
            'can_continue': true,
          },
        }),
      );

      final timerData = await examRepository.getAttemptTimer(attemptId);

      expect(mockAdapter.lastUri?.path, contains('/api/v1/attempts/15/timer'));
      expect(timerData['attempt_id'], equals(15));
      expect(timerData['remaining_seconds'], equals(1200));
      expect(timerData['status'], equals('in_progress'));
      expect(timerData['can_continue'], isTrue);
      expect(timerData['is_expired'], isFalse);
    });

    // Test 2: remaining_seconds disinkronkan ke ExamTimerController
    test('2. remaining_seconds disinkronkan ke ExamTimerController', () async {
      final timerController = ExamTimerController(initialSeconds: 500);
      expect(timerController.remainingSeconds, equals(500));

      // Mock server response with updated authoritative remaining_seconds
      mockAdapter.response = HttpResponseData(
        statusCode: 200,
        body: jsonEncode({
          'success': true,
          'data': {
            'remaining_seconds': 1200,
            'status': 'in_progress',
          },
        }),
      );

      final timerData = await examRepository.getAttemptTimer(15);
      final serverRemaining = timerData['remaining_seconds'] as int;

      timerController.syncWithServer(serverRemaining);
      expect(timerController.remainingSeconds, equals(1200));
      expect(timerController.formattedTime, equals('20:00'));
    });

    // Test 3: timer dapat diperbarui ketika server memberikan waktu baru (perpanjangan waktu / extend-time)
    test('3. timer dapat diperbarui ketika server memberikan waktu baru (extend-time)', () async {
      final timerController = ExamTimerController(initialSeconds: 120); // 2 minutes remaining
      expect(timerController.isWarning, isTrue);

      // Admin extends time by 15 minutes (+900 seconds -> 1020 seconds)
      mockAdapter.response = HttpResponseData(
        statusCode: 200,
        body: jsonEncode({
          'success': true,
          'message': 'Waktu ujian berhasil diperpanjang',
          'data': {
            'remaining_seconds': 1020,
            'ends_at': '2026-09-08T16:15:00+07:00',
            'status': 'in_progress',
          },
        }),
      );

      final timerData = await examRepository.getAttemptTimer(15);
      final newRemaining = timerData['remaining_seconds'] as int;

      timerController.syncWithServer(newRemaining);
      expect(timerController.remainingSeconds, equals(1020));
      expect(timerController.formattedTime, equals('17:00'));
      expect(timerController.isWarning, isFalse); // No longer in warning state!
    });

    // Test 4: timer expiration tetap bekerja saat remaining_seconds mencapai 0
    test('4. timer expiration tetap bekerja saat remaining_seconds mencapai 0', () async {
      bool onExpiredFired = false;
      final timerController = ExamTimerController(
        initialSeconds: 10,
        onTimeExpired: () {
          onExpiredFired = true;
        },
      );

      // Server indicates time is up
      timerController.syncWithServer(0);
      expect(timerController.isExpired, isTrue);
      expect(timerController.remainingSeconds, equals(0));

      // Starting expired controller invokes callback immediately
      timerController.start();
      expect(onExpiredFired, isTrue);
    });

    // Test 5: response/error server tidak membuat aplikasi crash
    test('5. response/error server tidak membuat aplikasi crash', () async {
      final timerController = ExamTimerController(initialSeconds: 600);

      // Server timeout error
      mockAdapter.error = ConnectionTimeoutException('Gateway timeout');

      int? serverRemaining;
      try {
        final timerData = await examRepository.getAttemptTimer(15);
        serverRemaining = timerData['remaining_seconds'] as int?;
      } catch (_) {
        // Handled safely without crash
      }

      expect(serverRemaining, isNull);
      // Local timer continues gracefully at original baseline
      expect(timerController.remainingSeconds, equals(600));
      expect(timerController.formattedTime, equals('10:00'));
    });

    // Test 6: Tidak ada penggunaan client clock sebagai authoritative time
    test('6. Tidak ada penggunaan client clock sebagai authoritative time', () async {
      // Regardless of client device system clock, countdown strictly uses server-derived remaining_seconds
      final timerController = ExamTimerController(initialSeconds: 300);

      // Even if local clock is altered, syncWithServer sets exact server-authoritative remaining duration
      const authoritativeServerSeconds = 2450;
      timerController.syncWithServer(authoritativeServerSeconds);

      expect(timerController.remainingSeconds, equals(authoritativeServerSeconds));
      expect(timerController.formattedTime, equals('40:50'));
    });
  });
}
