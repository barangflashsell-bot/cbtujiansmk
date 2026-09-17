import 'dart:io';

import '../../../core/config/api_config.dart';
import '../../../core/connection/connection_manager.dart';
import '../../../core/network/network_client.dart';
import '../../../core/network/network_exceptions.dart';
import 'health_model.dart';

/// Repository coordinating health check queries and connection state updates.
class HealthRepository {
  final NetworkClient _networkClient;
  final ConnectionManager _connectionManager;

  HealthRepository({
    NetworkClient? networkClient,
    ConnectionManager? connectionManager,
  })  : _networkClient = networkClient ?? NetworkClient(),
        _connectionManager = connectionManager ?? ConnectionManager();

  ConnectionManager get connectionManager => _connectionManager;

  /// Automatically discovers the CBT server on the local Wi-Fi/LAN network.
  /// Iterates through common local host candidates and subnet IPs with fast timeout.
  Future<String?> autoDiscoverServer() async {
    // 1. Check current configured base URL first
    try {
      final currentResp = await checkHealth(customBaseUrl: ApiConfig.baseUrl);
      if (currentResp.isHealthy) {
        return ApiConfig.baseUrl;
      }
    } catch (_) {}

    // 2. Discover local Wi-Fi subnet candidates
    try {
      final interfaces = await NetworkInterface.list(
        includeLoopback: false,
        type: InternetAddressType.IPv4,
      );

      for (final iface in interfaces) {
        for (final addr in iface.addresses) {
          final ip = addr.address;
          if (ip.startsWith('192.168.') || ip.startsWith('10.') || ip.startsWith('172.')) {
            final prefix = ip.substring(0, ip.lastIndexOf('.'));
            final candidates = [
              'http://192.168.1.11:8000',
              'http://$prefix.11:8000',
              'http://$prefix.1:8000',
              'http://$prefix.2:8000',
              'http://$prefix.50:8000',
              'http://$prefix.100:8000',
              'http://$prefix.200:8000',
            ];

            for (final targetUrl in candidates) {
              try {
                final healthResp = await checkHealth(customBaseUrl: targetUrl);
                if (healthResp.isHealthy) {
                  return targetUrl;
                }
              } catch (_) {}
            }
          }
        }
      }
    } catch (_) {}

    // 3. Check Cloud Server fallback
    try {
      final cloudResp = await checkHealth(customBaseUrl: ApiConfig.defaultCloudUrl);
      if (cloudResp.isHealthy) {
        return ApiConfig.defaultCloudUrl;
      }
    } catch (_) {}

    return null;
  }

  /// Checks the server health endpoint and updates connection state accordingly.
  Future<HealthResponse> checkHealth({String? customBaseUrl}) async {
    final baseUrl = customBaseUrl ?? ApiConfig.baseUrl;
    final healthUrl = '$baseUrl${ApiConfig.healthPath}';

    _connectionManager.setChecking(baseUrl);

    try {
      final json = await _networkClient.getJson(healthUrl);
      final response = HealthResponse.fromJson(json);

      if (response.isHealthy) {
        _connectionManager.setOnline(baseUrl, message: response.message);
      } else {
        _connectionManager.setError(baseUrl, 'Server status: ${response.status}');
      }

      return response;
    } on ConnectionTimeoutException catch (e) {
      _connectionManager.setOffline(baseUrl, message: e.message);
      rethrow;
    } on ConnectionRefusedException catch (e) {
      _connectionManager.setOffline(baseUrl, message: e.message);
      rethrow;
    } on HttpStatusException catch (e) {
      _connectionManager.setError(baseUrl, e.message);
      rethrow;
    } on InvalidResponseException catch (e) {
      _connectionManager.setError(baseUrl, e.message);
      rethrow;
    } catch (e) {
      _connectionManager.setError(baseUrl, e.toString());
      rethrow;
    }
  }
}
