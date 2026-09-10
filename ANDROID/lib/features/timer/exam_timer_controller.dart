import 'dart:async';
import 'package:flutter/foundation.dart';

/// Controller handling real-time countdown timer for exam sessions.
class ExamTimerController extends ChangeNotifier {
  int _remainingSeconds;
  Timer? _ticker;
  final VoidCallback? onTimeExpired;

  ExamTimerController({
    required int initialSeconds,
    this.onTimeExpired,
  }) : _remainingSeconds = initialSeconds > 0 ? initialSeconds : 0;

  int get remainingSeconds => _remainingSeconds;
  bool get isExpired => _remainingSeconds <= 0;
  bool get isWarning => _remainingSeconds <= 300 && _remainingSeconds > 0; // Less than 5 minutes

  void start() {
    _ticker?.cancel();
    if (_remainingSeconds <= 0) {
      onTimeExpired?.call();
      return;
    }

    _ticker = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remainingSeconds > 0) {
        _remainingSeconds--;
        notifyListeners();

        if (_remainingSeconds == 0) {
          timer.cancel();
          onTimeExpired?.call();
        }
      }
    });
  }

  void stop() {
    _ticker?.cancel();
    _ticker = null;
  }

  /// Syncs or adjusts timer with fresh server timestamp.
  void syncWithServer(int serverRemainingSeconds) {
    if (serverRemainingSeconds >= 0) {
      _remainingSeconds = serverRemainingSeconds;
      notifyListeners();
    }
  }

  String get formattedTime {
    final hours = _remainingSeconds ~/ 3600;
    final minutes = (_remainingSeconds % 3600) ~/ 60;
    final seconds = _remainingSeconds % 60;

    if (hours > 0) {
      return '${_pad(hours)}:${_pad(minutes)}:${_pad(seconds)}';
    } else {
      return '${_pad(minutes)}:${_pad(seconds)}';
    }
  }

  String _pad(int n) => n.toString().padLeft(2, '0');

  @override
  void dispose() {
    stop();
    super.dispose();
  }
}
