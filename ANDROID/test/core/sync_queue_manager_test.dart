import 'dart:convert';

import 'package:cbt_client/core/config/app_preferences.dart';
import 'package:cbt_client/core/database/local_database.dart';
import 'package:cbt_client/core/database/sync_queue_manager.dart';
import 'package:cbt_client/core/network/http_client_adapter.dart';
import 'package:cbt_client/core/network/network_client.dart';
import 'package:cbt_client/core/network/network_exceptions.dart';
import 'package:cbt_client/features/exam/data/exam_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

class MockHttpAdapter implements HttpClientAdapter {
  HttpResponseData? response;
  Exception? error;
  Uri? lastUri;
  String? lastBody;
  int callCount = 0;

  @override
  Future<HttpResponseData> get(Uri uri, {Map<String, String>? headers, Duration? timeout}) async {
    callCount++;
    lastUri = uri;
    if (error != null) throw error!;
    return response ?? const HttpResponseData(statusCode: 200, body: '{"success":true}');
  }

  @override
  Future<HttpResponseData> post(Uri uri, {String? body, Map<String, String>? headers, Duration? timeout}) async {
    callCount++;
    lastUri = uri;
    lastBody = body;
    if (error != null) throw error!;
    return response ?? const HttpResponseData(statusCode: 200, body: '{"success":true}');
  }

  @override
  Future<HttpResponseData> patch(Uri uri, {String? body, Map<String, String>? headers, Duration? timeout}) async {
    callCount++;
    lastUri = uri;
    lastBody = body;
    if (error != null) throw error!;
    return response ?? const HttpResponseData(statusCode: 200, body: '{"success":true}');
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('SyncQueueManager & Autosave Tests', () {
    late MockHttpAdapter mockAdapter;
    late NetworkClient networkClient;
    late SyncQueueManager syncQueueManager;
    late InMemoryLocalDatabaseDelegate inMemoryDb;

    setUp(() async {
      SharedPreferences.setMockInitialValues({'cbt_auth_token': 'test_token_xyz'});
      await AppPreferences.init();
      await AppPreferences.saveAuthToken('test_token_xyz');

      inMemoryDb = InMemoryLocalDatabaseDelegate();
      LocalDatabase.setDelegate(inMemoryDb);

      mockAdapter = MockHttpAdapter();
      networkClient = NetworkClient(adapter: mockAdapter);
      syncQueueManager = SyncQueueManager(networkClient: networkClient);
    });

    tearDown(() {
      syncQueueManager.stopPeriodicSync();
      LocalDatabase.setDelegate(null);
    });

    // Test 1: Queue menyimpan pending answer
    test('1. Queue menyimpan pending answer', () async {
      const attemptId = 10;
      const questionId = 101;
      const selectedOptionId = 2;

      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: questionId,
        selectedOptionId: selectedOptionId,
        isFlagged: false,
      );

      final count = await LocalDatabase.getPendingSyncCount(attemptId);
      expect(count, equals(1));

      final items = await LocalDatabase.getPendingSyncItems(attemptId);
      expect(items.length, equals(1));
      expect(items[0]['attempt_id'], equals(attemptId));
      expect(items[0]['question_id'], equals(questionId));
      expect(items[0]['selected_option_id'], equals(selectedOptionId));
      expect(items[0]['is_flagged'], equals(0));
    });

    // Test 2: Queue tetap ada ketika sync gagal
    test('2. Queue tetap ada ketika sync gagal', () async {
      const attemptId = 10;
      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: 101,
        selectedOptionId: 3,
      );

      // Simulate network timeout failure
      mockAdapter.error = ConnectionTimeoutException('Connection timed out');

      final syncResult = await syncQueueManager.syncPendingAnswers(attemptId);
      expect(syncResult, isFalse);

      // Queue must remain intact
      final count = await LocalDatabase.getPendingSyncCount(attemptId);
      expect(count, equals(1));
      expect(syncQueueManager.pendingCount, equals(0)); // manager failed before updating count
      await syncQueueManager.refreshPendingCount(attemptId);
      expect(syncQueueManager.pendingCount, equals(1));
    });

    // Test 3: Queue dihapus setelah ACK sukses
    test('3. Queue dihapus setelah ACK sukses', () async {
      const attemptId = 10;
      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: 101,
        selectedOptionId: 4,
      );

      mockAdapter.response = HttpResponseData(
        statusCode: 200,
        body: jsonEncode({
          'success': true,
          'message': 'Antrean jawaban berhasil disinkronkan',
          'data': {
            'status': 'SYNCED',
            'synced_count': 1,
            'remaining_seconds': 3600,
          },
        }),
      );

      final syncResult = await syncQueueManager.syncPendingAnswers(attemptId);
      expect(syncResult, isTrue);

      final count = await LocalDatabase.getPendingSyncCount(attemptId);
      expect(count, equals(0));
      expect(syncQueueManager.pendingCount, equals(0));
    });

    // Test 4: Retry dapat mengirim ulang pending item
    test('4. Retry dapat mengirim ulang pending item', () async {
      const attemptId = 10;
      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: 105,
        selectedOptionId: 1,
      );

      // Attempt 1: Fails (offline)
      mockAdapter.error = ConnectionRefusedException('Network offline');
      final firstAttempt = await syncQueueManager.syncPendingAnswers(attemptId);
      expect(firstAttempt, isFalse);
      expect(await LocalDatabase.getPendingSyncCount(attemptId), equals(1));

      // Attempt 2 (Retry): Network restored, succeeds
      mockAdapter.error = null;
      mockAdapter.response = const HttpResponseData(
        statusCode: 200,
        body: '{"success":true,"message":"Antrean jawaban berhasil disinkronkan"}',
      );

      final retryResult = await syncQueueManager.syncPendingAnswers(attemptId);
      expect(retryResult, isTrue);
      expect(await LocalDatabase.getPendingSyncCount(attemptId), equals(0));
    });

    // Test 5: Duplicate/retry tidak merusak state lokal
    test('5. Duplicate/retry tidak merusak state lokal', () async {
      const attemptId = 10;
      const questionId = 101;

      // First answer
      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: questionId,
        selectedOptionId: 2,
        isFlagged: false,
      );

      // Second answer for same question (user changed their mind or retried)
      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: questionId,
        selectedOptionId: 5,
        isFlagged: true,
      );

      // Verify local_answers table contains only 1 record with latest state
      final localAnswers = await LocalDatabase.getLocalAnswers(attemptId);
      expect(localAnswers.length, equals(1));
      expect(localAnswers[questionId]?['selected_option_id'], equals(5));
      expect(localAnswers[questionId]?['is_flagged'], isTrue);
    });

    // Test 6: Multiple queued answers diproses dengan benar
    test('6. Multiple queued answers diproses dengan benar', () async {
      const attemptId = 10;

      // Enqueue 3 different questions
      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: 101,
        selectedOptionId: 1,
        isFlagged: false,
      );
      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: 102,
        selectedOptionId: 2,
        isFlagged: true,
      );
      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: 103,
        essayAnswer: 'Jawaban uraian lengkap',
        isFlagged: false,
      );

      expect(await LocalDatabase.getPendingSyncCount(attemptId), equals(3));

      mockAdapter.response = HttpResponseData(
        statusCode: 200,
        body: jsonEncode({
          'success': true,
          'message': 'Antrean jawaban berhasil disinkronkan',
          'data': {'synced_count': 3},
        }),
      );

      final syncResult = await syncQueueManager.syncPendingAnswers(attemptId);
      expect(syncResult, isTrue);

      // Verify outbound payload structure matches POST /attempts/{id}/sync contract
      expect(mockAdapter.lastUri?.path, contains('/api/v1/attempts/10/sync'));
      final decodedBody = jsonDecode(mockAdapter.lastBody!) as Map<String, dynamic>;
      final answersList = decodedBody['answers'] as List<dynamic>;
      expect(answersList.length, equals(3));
      expect(answersList[0]['question_id'], equals(101));
      expect(answersList[0]['selected_option_id'], equals(1));
      expect(answersList[1]['question_id'], equals(102));
      expect(answersList[1]['is_flagged'], isTrue);
      expect(answersList[2]['question_id'], equals(103));
      expect(answersList[2]['essay_answer'], equals('Jawaban uraian lengkap'));

      // All 3 items must be deleted from queue upon ACK
      expect(await LocalDatabase.getPendingSyncCount(attemptId), equals(0));
    });

    // Test 7: Local answer tetap tersedia setelah network failure
    test('7. Local answer tetap tersedia setelah network failure', () async {
      const attemptId = 10;
      const questionId = 200;
      const selectedOptionId = 8;

      // Mock network failure on direct autosave endpoint
      mockAdapter.error = ConnectionRefusedException('Server unreachable');

      final examRepository = ExamRepository(
        networkClient: networkClient,
        syncQueueManager: syncQueueManager,
      );

      // saveAnswer persists locally first, then attempts network autosave
      await examRepository.saveAnswer(
        attemptId: attemptId,
        questionId: questionId,
        selectedOptionId: selectedOptionId,
        isFlagged: false,
      );

      // Local answer must still be intact despite network failure!
      final localAnswers = await LocalDatabase.getLocalAnswers(attemptId);
      expect(localAnswers.containsKey(questionId), isTrue);
      expect(localAnswers[questionId]?['selected_option_id'], equals(selectedOptionId));

      // And it must be queued for sync
      expect(await LocalDatabase.getPendingSyncCount(attemptId), equals(1));
    });

    // Test 8: submitExam flushes sync queue before final submission
    test('8. submitExam flushes sync queue before final submission', () async {
      const attemptId = 10;
      await LocalDatabase.saveAnswerLocally(
        attemptId: attemptId,
        questionId: 101,
        selectedOptionId: 2,
      );

      final examRepository = ExamRepository(
        networkClient: networkClient,
        syncQueueManager: syncQueueManager,
      );

      mockAdapter.response = HttpResponseData(
        statusCode: 200,
        body: jsonEncode({
          'success': true,
          'message': 'Ujian berhasil diselesaikan',
          'data': {'status': 'submitted'},
        }),
      );

      final result = await examRepository.submitExam(attemptId);
      expect(result['success'], isTrue);

      // After submit, queue must be cleared and local attempt data cleared
      expect(await LocalDatabase.getPendingSyncCount(attemptId), equals(0));
      final localAnswers = await LocalDatabase.getLocalAnswers(attemptId);
      expect(localAnswers.isEmpty, isTrue);
    });
  });
}
