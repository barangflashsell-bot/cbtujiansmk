import 'dart:async';
import 'package:flutter/foundation.dart';

import '../config/api_config.dart';
import '../config/app_preferences.dart';
import '../network/network_client.dart';
import 'local_database.dart';

/// Manages background offline answer synchronization queue.
class SyncQueueManager extends ChangeNotifier {
  final NetworkClient _networkClient;
  bool _isSyncing = false;
  int _pendingCount = 0;
  Timer? _autoSyncTimer;

  SyncQueueManager({NetworkClient? networkClient})
      : _networkClient = networkClient ?? NetworkClient();

  bool get isSyncing => _isSyncing;
  int get pendingCount => _pendingCount;

  void startPeriodicSync(int attemptId) {
    stopPeriodicSync();
    _autoSyncTimer = Timer.periodic(const Duration(seconds: 15), (_) {
      syncPendingAnswers(attemptId);
    });
  }

  void stopPeriodicSync() {
    _autoSyncTimer?.cancel();
    _autoSyncTimer = null;
  }

  /// Refreshes the pending queue count from SQLite.
  Future<void> refreshPendingCount(int attemptId) async {
    _pendingCount = await LocalDatabase.getPendingSyncCount(attemptId);
    notifyListeners();
  }

  /// Flushes all pending answers to `POST /api/v1/attempts/{attemptId}/sync`.
  Future<bool> syncPendingAnswers(int attemptId) async {
    if (_isSyncing) return false;

    final items = await LocalDatabase.getPendingSyncItems(attemptId);
    if (items.isEmpty) {
      _pendingCount = 0;
      notifyListeners();
      return true;
    }

    _isSyncing = true;
    notifyListeners();

    try {
      final token = AppPreferences.getAuthToken();
      final url = '${ApiConfig.baseUrl}/api/v1/attempts/$attemptId/sync';

      // Format payload according to SyncController contract
      final payload = {
        'answers': items.map((row) {
          return {
            'question_id': row['question_id'],
            'selected_option_id': row['selected_option_id'],
            'essay_answer': row['essay_answer'],
            'is_flagged': (row['is_flagged'] as int) == 1,
            'answered_at': row['answered_at'],
          };
        }).toList(),
      };

      final response = await _networkClient.postJson(url, body: payload, token: token);

      if (response['success'] == true) {
        // Server acknowledged receipt (ACK)
        final itemIds = items.map((i) => i['id'] as int).toList();
        await LocalDatabase.removeSyncQueueItems(itemIds);

        _pendingCount = await LocalDatabase.getPendingSyncCount(attemptId);
        return true;
      }
    } catch (_) {
      // Network failed or offline: keep items in queue safely for next retry
    } finally {
      _isSyncing = false;
      notifyListeners();
    }

    return false;
  }
}
