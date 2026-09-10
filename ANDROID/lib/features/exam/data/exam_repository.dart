import '../../../core/config/api_config.dart';
import '../../../core/config/app_preferences.dart';
import '../../../core/database/local_database.dart';
import '../../../core/database/sync_queue_manager.dart';
import '../../../core/network/network_client.dart';
import 'exam_model.dart';

class ExamRepository {
  final NetworkClient _networkClient;
  final SyncQueueManager _syncQueueManager;

  ExamRepository({
    NetworkClient? networkClient,
    SyncQueueManager? syncQueueManager,
  })  : _networkClient = networkClient ?? NetworkClient(),
        _syncQueueManager = syncQueueManager ?? SyncQueueManager();

  SyncQueueManager get syncQueueManager => _syncQueueManager;

  /// Fetch active exams published for the student.
  Future<List<ExamListItem>> getActiveExams() async {
    final token = AppPreferences.getAuthToken();
    final url = '${ApiConfig.baseUrl}/api/v1/exams';

    final json = await _networkClient.getJson(url, token: token);
    final data = json['data'] as Map<String, dynamic>;
    final items = (data['items'] as List<dynamic>?) ?? [];

    return items
        .map((e) => ExamListItem.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// Start a new session or recover existing in-progress attempt.
  Future<ExamAttemptSession> startOrResumeExam(int examId, {String? examToken, String? examTitle}) async {
    final token = AppPreferences.getAuthToken();
    final url = '${ApiConfig.baseUrl}/api/v1/exams/$examId/start';

    final body = <String, dynamic>{};
    if (examToken != null && examToken.isNotEmpty) {
      body['token'] = examToken.trim();
    }

    final json = await _networkClient.postJson(url, body: body, token: token);
    final session = ExamAttemptSession.fromJson(json, examTitle: examTitle);

    // Populate local database with questions and answers for offline resiliency
    final db = await LocalDatabase.database;
    await db.transaction((txn) async {
      await txn.insert(
        'cached_attempts',
        {
          'attempt_id': session.attemptId,
          'exam_id': session.examId,
          'title': session.title,
          'duration_seconds': session.durationSeconds,
          'remaining_seconds': session.remainingSeconds,
          'status': session.status,
          'cached_at': DateTime.now().toIso8601String(),
        },
        conflictAlgorithm: null, // ignore if already exists
      );

      for (final q in session.questions) {
        await txn.insert(
          'cached_questions',
          {
            'question_id': q.id,
            'attempt_id': session.attemptId,
            'order_index': q.orderIndex,
            'content': q.content,
            'question_type': q.questionType,
            'media_path': q.mediaPath,
            'options_json': '',
          },
          conflictAlgorithm: null,
        );
      }
    });

    return session;
  }

  /// Save answer with guaranteed offline-first resilience:
  /// 1. Persist locally to SQLite immediately
  /// 2. Attempt online atomic autosave
  /// 3. Remove from sync queue if online succeeds, otherwise leave in queue for background sync
  /// 4. Returns authoritative server remaining_seconds if online push succeeds
  Future<int?> saveAnswer({
    required int attemptId,
    required int questionId,
    int? selectedOptionId,
    String? essayAnswer,
    bool isFlagged = false,
  }) async {
    // 1. Save locally to SQLite & queue
    await LocalDatabase.saveAnswerLocally(
      attemptId: attemptId,
      questionId: questionId,
      selectedOptionId: selectedOptionId,
      essayAnswer: essayAnswer,
      isFlagged: isFlagged,
    );

    await _syncQueueManager.refreshPendingCount(attemptId);

    // 2. Direct online autosave attempt
    try {
      final token = AppPreferences.getAuthToken();
      final url = '${ApiConfig.baseUrl}/api/v1/attempts/$attemptId/answers';

      final response = await _networkClient.postJson(
        url,
        body: {
          'question_id': questionId,
          'selected_option_id': selectedOptionId,
          'essay_answer': essayAnswer,
          'is_flagged': isFlagged,
        },
        token: token,
      );

      if (response['success'] == true) {
        // Direct save succeeded: flush latest item from sync queue
        final pending = await LocalDatabase.getPendingSyncItems(attemptId);
        final matched = pending.where((row) => row['question_id'] == questionId).map((row) => row['id'] as int).toList();
        await LocalDatabase.removeSyncQueueItems(matched);
        await _syncQueueManager.refreshPendingCount(attemptId);

        final data = response['data'] as Map<String, dynamic>?;
        if (data != null && data.containsKey('remaining_seconds')) {
          return data['remaining_seconds'] as int?;
        }
      }
    } catch (_) {
      // Offline/timeout: answer remains safely saved locally in sync_queue!
    }
    return null;
  }

  /// Fetch authoritative server-side timer state for the attempt.
  Future<Map<String, dynamic>> getAttemptTimer(int attemptId) async {
    final token = AppPreferences.getAuthToken();
    final url = '${ApiConfig.baseUrl}/api/v1/attempts/$attemptId/timer';

    final response = await _networkClient.getJson(url, token: token);
    return (response['data'] as Map<String, dynamic>?) ?? {};
  }

  /// Submit the exam attempt (idempotent submission).
  Future<Map<String, dynamic>> submitExam(int attemptId) async {
    // 1. Flush any pending offline answers first
    await _syncQueueManager.syncPendingAnswers(attemptId);

    // 2. Submit attempt to server
    final token = AppPreferences.getAuthToken();
    final url = '${ApiConfig.baseUrl}/api/v1/attempts/$attemptId/submit';

    final response = await _networkClient.postJson(url, token: token);

    // 3. Clear local attempt cache
    await LocalDatabase.clearAttemptData(attemptId);
    _syncQueueManager.stopPeriodicSync();

    return response;
  }

  /// Fetch exam result for the attempt if published or visible.
  /// Returns null if not published or forbidden.
  Future<ExamResultModel?> getExamResult(int attemptId) async {
    try {
      final token = AppPreferences.getAuthToken();
      final url = '${ApiConfig.baseUrl}/api/v1/attempts/$attemptId/result';

      final response = await _networkClient.getJson(url, token: token);
      if (response['success'] == true && response['data'] != null) {
        return ExamResultModel.fromJson(response['data'] as Map<String, dynamic>);
      }
    } catch (_) {
      // Returns null if result is not published (403), pending, or offline
    }
    return null;
  }
}
