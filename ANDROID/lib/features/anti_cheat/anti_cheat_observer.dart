import 'package:flutter/widgets.dart';
import 'anti_cheat_service.dart';

/// Observer monitoring application lifecycle changes during active exam session.
class AntiCheatObserver with WidgetsBindingObserver {
  final AntiCheatService _service;
  final VoidCallback? onWarning;
  int _violationsCount = 0;

  AntiCheatObserver({
    AntiCheatService? service,
    this.onWarning,
  }) : _service = service ?? AntiCheatService();

  int get violationsCount => _violationsCount;

  void attach() {
    WidgetsBinding.instance.addObserver(this);
  }

  void detach() {
    WidgetsBinding.instance.removeObserver(this);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused || state == AppLifecycleState.inactive) {
      _violationsCount++;
      _service.reportEvent(
        action: 'APP_BACKGROUNDED',
        details: 'Aplikasi ditinggalkan / berpindah ke latar belakang (pelanggaran ke-$_violationsCount)',
      );
      onWarning?.call();
    }
  }
}
