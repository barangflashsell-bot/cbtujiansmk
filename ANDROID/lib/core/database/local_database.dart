import 'package:flutter/foundation.dart';
import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';

/// Delegate contract for local database operations to allow unit testing without SQLite native driver.
abstract class LocalDatabaseDelegate {
  Future<void> saveAnswerLocally({
    required int attemptId,
    required int questionId,
    int? selectedOptionId,
    String? essayAnswer,
    bool isFlagged = false,
  });

  Future<Map<int, Map<String, dynamic>>> getLocalAnswers(int attemptId);

  Future<List<Map<String, dynamic>>> getPendingSyncItems(int attemptId);

  Future<void> removeSyncQueueItems(List<int> queueIds);

  Future<int> getPendingSyncCount(int attemptId);

  Future<void> clearAttemptData(int attemptId);
}

/// In-memory implementation of LocalDatabaseDelegate for hermetic unit testing.
class InMemoryLocalDatabaseDelegate implements LocalDatabaseDelegate {
  final Map<String, Map<String, dynamic>> _localAnswers = {};
  final List<Map<String, dynamic>> _syncQueue = [];
  int _nextQueueId = 1;

  @override
  Future<void> saveAnswerLocally({
    required int attemptId,
    required int questionId,
    int? selectedOptionId,
    String? essayAnswer,
    bool isFlagged = false,
  }) async {
    final nowIso = DateTime.now().toIso8601String();
    final key = '$attemptId-$questionId';

    // 1. Upsert current local answer
    _localAnswers[key] = {
      'attempt_id': attemptId,
      'question_id': questionId,
      'selected_option_id': selectedOptionId,
      'essay_answer': essayAnswer,
      'is_flagged': isFlagged ? 1 : 0,
      'updated_at': nowIso,
    };

    // 2. Append to sync queue
    _syncQueue.add({
      'id': _nextQueueId++,
      'attempt_id': attemptId,
      'question_id': questionId,
      'selected_option_id': selectedOptionId,
      'essay_answer': essayAnswer,
      'is_flagged': isFlagged ? 1 : 0,
      'answered_at': nowIso,
    });
  }

  @override
  Future<Map<int, Map<String, dynamic>>> getLocalAnswers(int attemptId) async {
    final result = <int, Map<String, dynamic>>{};
    for (final entry in _localAnswers.values) {
      if (entry['attempt_id'] == attemptId) {
        final qId = entry['question_id'] as int;
        result[qId] = {
          'selected_option_id': entry['selected_option_id'],
          'essay_answer': entry['essay_answer'],
          'is_flagged': (entry['is_flagged'] as int?) == 1,
        };
      }
    }
    return result;
  }

  @override
  Future<List<Map<String, dynamic>>> getPendingSyncItems(int attemptId) async {
    return _syncQueue
        .where((row) => row['attempt_id'] == attemptId)
        .map((row) => Map<String, dynamic>.from(row))
        .toList();
  }

  @override
  Future<void> removeSyncQueueItems(List<int> queueIds) async {
    _syncQueue.removeWhere((row) => queueIds.contains(row['id'] as int));
  }

  @override
  Future<int> getPendingSyncCount(int attemptId) async {
    return _syncQueue.where((row) => row['attempt_id'] == attemptId).length;
  }

  @override
  Future<void> clearAttemptData(int attemptId) async {
    _localAnswers.removeWhere((key, val) => val['attempt_id'] == attemptId);
    _syncQueue.removeWhere((row) => row['attempt_id'] == attemptId);
  }
}

/// Manages local SQLite storage for exam caching and offline answer queuing.
class LocalDatabase {
  static const String _dbName = 'cbt_offline.db';
  static const int _dbVersion = 1;

  static Database? _database;
  static LocalDatabaseDelegate? _delegate;

  @visibleForTesting
  static void setDelegate(LocalDatabaseDelegate? delegate) {
    _delegate = delegate;
  }

  static Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDatabase();
    return _database!;
  }

  static Future<Database> _initDatabase() async {
    final dbPath = await getDatabasesPath();
    final path = p.join(dbPath, _dbName);

    return await openDatabase(
      path,
      version: _dbVersion,
      onCreate: (db, version) async {
        // Cached exam attempt metadata
        await db.execute('''
          CREATE TABLE cached_attempts (
            attempt_id INTEGER PRIMARY KEY,
            exam_id INTEGER NOT NULL,
            title TEXT,
            subject_name TEXT,
            duration_seconds INTEGER,
            remaining_seconds INTEGER,
            ends_at TEXT,
            started_at TEXT,
            status TEXT,
            cached_at TEXT
          )
        ''');

        // Cached questions with options JSON (never contains is_correct)
        await db.execute('''
          CREATE TABLE cached_questions (
            question_id INTEGER PRIMARY KEY,
            attempt_id INTEGER NOT NULL,
            order_index INTEGER NOT NULL,
            content TEXT NOT NULL,
            question_type TEXT,
            media_path TEXT,
            options_json TEXT NOT NULL
          )
        ''');

        // Current state of answers locally
        await db.execute('''
          CREATE TABLE local_answers (
            attempt_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            selected_option_id INTEGER,
            essay_answer TEXT,
            is_flagged INTEGER DEFAULT 0,
            updated_at TEXT,
            PRIMARY KEY (attempt_id, question_id)
          )
        ''');

        // Pending sync queue for offline changes
        await db.execute('''
          CREATE TABLE sync_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            attempt_id INTEGER NOT NULL,
            question_id INTEGER NOT NULL,
            selected_option_id INTEGER,
            essay_answer TEXT,
            is_flagged INTEGER DEFAULT 0,
            answered_at TEXT NOT NULL
          )
        ''');
      },
    );
  }

  // --- Local Answers Operations ---
  static Future<void> saveAnswerLocally({
    required int attemptId,
    required int questionId,
    int? selectedOptionId,
    String? essayAnswer,
    bool isFlagged = false,
  }) async {
    if (_delegate != null) {
      return _delegate!.saveAnswerLocally(
        attemptId: attemptId,
        questionId: questionId,
        selectedOptionId: selectedOptionId,
        essayAnswer: essayAnswer,
        isFlagged: isFlagged,
      );
    }

    final db = await database;
    final nowIso = DateTime.now().toIso8601String();

    await db.transaction((txn) async {
      // 1. Upsert current local answer
      await txn.insert(
        'local_answers',
        {
          'attempt_id': attemptId,
          'question_id': questionId,
          'selected_option_id': selectedOptionId,
          'essay_answer': essayAnswer,
          'is_flagged': isFlagged ? 1 : 0,
          'updated_at': nowIso,
        },
        conflictAlgorithm: ConflictAlgorithm.replace,
      );

      // 2. Append to sync queue
      await txn.insert('sync_queue', {
        'attempt_id': attemptId,
        'question_id': questionId,
        'selected_option_id': selectedOptionId,
        'essay_answer': essayAnswer,
        'is_flagged': isFlagged ? 1 : 0,
        'answered_at': nowIso,
      });
    });
  }

  static Future<Map<int, Map<String, dynamic>>> getLocalAnswers(int attemptId) async {
    if (_delegate != null) {
      return _delegate!.getLocalAnswers(attemptId);
    }

    final db = await database;
    final rows = await db.query(
      'local_answers',
      where: 'attempt_id = ?',
      whereArgs: [attemptId],
    );

    final map = <int, Map<String, dynamic>>{};
    for (final r in rows) {
      final qId = r['question_id'] as int;
      map[qId] = {
        'selected_option_id': r['selected_option_id'],
        'essay_answer': r['essay_answer'],
        'is_flagged': (r['is_flagged'] as int?) == 1,
      };
    }
    return map;
  }

  // --- Sync Queue Operations ---
  static Future<List<Map<String, dynamic>>> getPendingSyncItems(int attemptId) async {
    if (_delegate != null) {
      return _delegate!.getPendingSyncItems(attemptId);
    }

    final db = await database;
    return await db.query(
      'sync_queue',
      where: 'attempt_id = ?',
      whereArgs: [attemptId],
      orderBy: 'id ASC',
    );
  }

  static Future<void> removeSyncQueueItems(List<int> queueIds) async {
    if (queueIds.isEmpty) return;
    if (_delegate != null) {
      return _delegate!.removeSyncQueueItems(queueIds);
    }

    final db = await database;
    await db.delete(
      'sync_queue',
      where: 'id IN (${List.filled(queueIds.length, '?').join(',')})',
      whereArgs: queueIds,
    );
  }

  static Future<int> getPendingSyncCount(int attemptId) async {
    if (_delegate != null) {
      return _delegate!.getPendingSyncCount(attemptId);
    }

    final db = await database;
    final count = Sqflite.firstIntValue(
      await db.rawQuery(
        'SELECT COUNT(*) FROM sync_queue WHERE attempt_id = ?',
        [attemptId],
      ),
    );
    return count ?? 0;
  }

  static Future<void> clearAttemptData(int attemptId) async {
    if (_delegate != null) {
      return _delegate!.clearAttemptData(attemptId);
    }

    final db = await database;
    await db.transaction((txn) async {
      await txn.delete('cached_attempts', where: 'attempt_id = ?', whereArgs: [attemptId]);
      await txn.delete('cached_questions', where: 'attempt_id = ?', whereArgs: [attemptId]);
      await txn.delete('local_answers', where: 'attempt_id = ?', whereArgs: [attemptId]);
      await txn.delete('sync_queue', where: 'attempt_id = ?', whereArgs: [attemptId]);
    });
  }
}
