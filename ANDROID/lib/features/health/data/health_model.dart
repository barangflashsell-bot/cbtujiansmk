import '../../../core/network/network_exceptions.dart';

/// Data model representing the response from Laravel CBT `GET /api/v1/health`.
class HealthResponse {
  final bool success;
  final String message;
  final String status;
  final String apiVersion;
  final String timestamp;

  const HealthResponse({
    required this.success,
    required this.message,
    required this.status,
    required this.apiVersion,
    required this.timestamp,
  });

  /// Factory parser enforcing strict validation against the backend contract.
  factory HealthResponse.fromJson(Map<String, dynamic> json) {
    if (json['success'] != true) {
      throw const InvalidResponseException('Health check mengindikasikan status tidak sukses');
    }

    final data = json['data'];
    if (data is! Map<String, dynamic>) {
      throw const InvalidResponseException('Payload "data" tidak ditemukan atau tidak valid');
    }

    final status = data['status'] as String?;
    final apiVersion = data['api_version'] as String?;
    final timestamp = data['timestamp'] as String?;

    if (status == null || apiVersion == null || timestamp == null) {
      throw const InvalidResponseException('Properti status, api_version, atau timestamp hilang');
    }

    return HealthResponse(
      success: json['success'] as bool,
      message: (json['message'] as String?) ?? 'Healthy',
      status: status,
      apiVersion: apiVersion,
      timestamp: timestamp,
    );
  }

  bool get isHealthy => status.toLowerCase() == 'healthy';
}
