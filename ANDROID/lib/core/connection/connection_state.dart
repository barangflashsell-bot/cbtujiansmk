/// Represents the state of connection between Android client and CBT Server.
enum ServerConnectionStatus {
  unknown,
  checking,
  online,
  offline,
  error,
}

/// Detailed connection state with optional metadata and error message.
class ConnectionStateInfo {
  final ServerConnectionStatus status;
  final String? message;
  final DateTime? lastChecked;
  final String? serverUrl;

  const ConnectionStateInfo({
    required this.status,
    this.message,
    this.lastChecked,
    this.serverUrl,
  });

  factory ConnectionStateInfo.unknown() {
    return const ConnectionStateInfo(status: ServerConnectionStatus.unknown);
  }

  factory ConnectionStateInfo.checking(String url) {
    return ConnectionStateInfo(
      status: ServerConnectionStatus.checking,
      serverUrl: url,
      lastChecked: DateTime.now(),
    );
  }

  factory ConnectionStateInfo.online(String url, {String? message}) {
    return ConnectionStateInfo(
      status: ServerConnectionStatus.online,
      serverUrl: url,
      message: message,
      lastChecked: DateTime.now(),
    );
  }

  factory ConnectionStateInfo.offline(String url, {String? message}) {
    return ConnectionStateInfo(
      status: ServerConnectionStatus.offline,
      serverUrl: url,
      message: message,
      lastChecked: DateTime.now(),
    );
  }

  factory ConnectionStateInfo.error(String url, String errorMessage) {
    return ConnectionStateInfo(
      status: ServerConnectionStatus.error,
      serverUrl: url,
      message: errorMessage,
      lastChecked: DateTime.now(),
    );
  }

  bool get isOnline => status == ServerConnectionStatus.online;
  bool get isChecking => status == ServerConnectionStatus.checking;
}
