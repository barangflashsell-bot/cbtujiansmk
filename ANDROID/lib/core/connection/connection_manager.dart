import 'package:flutter/foundation.dart';
import 'connection_state.dart';

/// Manages connection state transitions for the CBT Client.
class ConnectionManager extends ValueNotifier<ConnectionStateInfo> {
  ConnectionManager([ConnectionStateInfo? initial])
      : super(initial ?? ConnectionStateInfo.unknown());

  void setChecking(String serverUrl) {
    value = ConnectionStateInfo.checking(serverUrl);
  }

  void setOnline(String serverUrl, {String? message}) {
    value = ConnectionStateInfo.online(serverUrl, message: message);
  }

  void setOffline(String serverUrl, {String? message}) {
    value = ConnectionStateInfo.offline(serverUrl, message: message);
  }

  void setError(String serverUrl, String errorMessage) {
    value = ConnectionStateInfo.error(serverUrl, errorMessage);
  }

  void reset() {
    value = ConnectionStateInfo.unknown();
  }
}
