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
