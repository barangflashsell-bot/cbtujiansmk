import 'package:cbt_client/core/connection/connection_manager.dart';
import 'package:cbt_client/core/connection/connection_state.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('ConnectionManager Tests', () {
    late ConnectionManager manager;

    setUp(() {
      manager = ConnectionManager();
    });

    test('Initial state is UNKNOWN', () {
      expect(manager.value.status, equals(ServerConnectionStatus.unknown));
      expect(manager.value.isOnline, isFalse);
      expect(manager.value.isChecking, isFalse);
    });

    test('Test 7: Connection state transitions (UNKNOWN -> CHECKING -> ONLINE)', () {
      final states = <ServerConnectionStatus>[];
      manager.addListener(() {
        states.add(manager.value.status);
      });

      manager.setChecking('http://192.168.1.100:8000');
      expect(manager.value.status, equals(ServerConnectionStatus.checking));
      expect(manager.value.isChecking, isTrue);
      expect(manager.value.serverUrl, equals('http://192.168.1.100:8000'));

      manager.setOnline('http://192.168.1.100:8000', message: 'Connected');
      expect(manager.value.status, equals(ServerConnectionStatus.online));
      expect(manager.value.isOnline, isTrue);
      expect(manager.value.message, equals('Connected'));

      expect(states, [
        ServerConnectionStatus.checking,
        ServerConnectionStatus.online,
      ]);
    });

    test('Test 6a: Offline state transition (CHECKING -> OFFLINE)', () {
      manager.setChecking('http://10.0.2.2:8000');
      manager.setOffline('http://10.0.2.2:8000', message: 'Host unreachable');

      expect(manager.value.status, equals(ServerConnectionStatus.offline));
      expect(manager.value.isOnline, isFalse);
      expect(manager.value.message, equals('Host unreachable'));
    });

    test('Test 6b: Error state transition (CHECKING -> ERROR)', () {
      manager.setChecking('http://10.0.2.2:8000');
      manager.setError('http://10.0.2.2:8000', 'HTTP 500: Server error');

      expect(manager.value.status, equals(ServerConnectionStatus.error));
      expect(manager.value.isOnline, isFalse);
      expect(manager.value.message, equals('HTTP 500: Server error'));
    });

    test('Reset transitions state back to UNKNOWN', () {
      manager.setOnline('http://localhost:8000');
      expect(manager.value.status, equals(ServerConnectionStatus.online));

      manager.reset();
      expect(manager.value.status, equals(ServerConnectionStatus.unknown));
    });
  });
}
