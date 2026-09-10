/// Centralized configuration for CBT Backend API.
/// Manages LAN Base URL, connection timeouts, and endpoint paths.
class ApiConfig {
  ApiConfig._();

  /// Default development base URL.
  /// 10.0.2.2 maps to host localhost on Android Emulator.
  /// 127.0.0.1 maps to localhost on Windows Desktop / Local unit tests.
  static const String defaultBaseUrl = 'http://10.0.2.2:8000';

  /// Default network timeout for standard REST operations.
  static const Duration defaultTimeout = Duration(seconds: 5);

  /// Centralized health check path matching Laravel routes/api/v1.php
  static const String healthPath = '/api/v1/health';

  static String _baseUrl = defaultBaseUrl;

  /// Get current base URL without trailing slash.
  static String get baseUrl => _baseUrl;

  /// Update the base URL centrally for LAN environments.
  /// Automatically strips trailing slashes and validates format.
  static void setBaseUrl(String url) {
    final cleanUrl = url.trim();
    if (!isValidUrl(cleanUrl)) {
      throw ArgumentError('Invalid Base URL format: $cleanUrl');
    }
    _baseUrl = _normalizeUrl(cleanUrl);
  }

  /// Reset base URL to default.
  static void resetToDefault() {
    _baseUrl = defaultBaseUrl;
  }

  /// Constructs full URL for the health check endpoint.
  static String get healthUrl => '$_baseUrl$healthPath';

  /// Helper to validate if a string is a valid HTTP/HTTPS URL.
  static bool isValidUrl(String url) {
    final uri = Uri.tryParse(url.trim());
    if (uri == null) return false;
    return (uri.scheme == 'http' || uri.scheme == 'https') &&
        uri.host.isNotEmpty;
  }

  /// Normalizes URL by removing any trailing slash.
  static String _normalizeUrl(String url) {
    if (url.endsWith('/')) {
      return url.substring(0, url.length - 1);
    }
    return url;
  }
}
