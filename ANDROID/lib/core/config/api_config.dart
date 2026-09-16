/// Centralized configuration for CBT Backend API.
/// Manages Cloud and LAN Base URL, connection timeouts, and endpoint paths.
class ApiConfig {
  ApiConfig._();

  /// Default public cloud server URL on Vercel
  static const String defaultCloudUrl = 'https://cbtsmkpesantrenbustanululum.vercel.app';

  /// Default local school Wi-Fi LAN server address
  static const String defaultLanUrl = 'http://192.168.1.11:8000';

  /// Default development base URL (Cloud URL for out-of-the-box connectivity)
  static const String defaultBaseUrl = defaultCloudUrl;

  /// Default network timeout for standard REST operations (12s for Wi-Fi/cellular resilience).
  static const Duration defaultTimeout = Duration(seconds: 12);

  /// Centralized health check path matching Laravel routes/api/v1.php
  static const String healthPath = '/api/v1/health';

  static String _baseUrl = defaultBaseUrl;

  /// Get current base URL without trailing slash.
  static String get baseUrl => _baseUrl;

  /// Update the base URL centrally for Cloud/LAN environments.
  /// Automatically normalizes protocols (http/https), ports, and strips trailing slashes.
  static void setBaseUrl(String url) {
    if (url.trim().isEmpty) {
      throw ArgumentError('Base URL cannot be empty');
    }
    final cleanUrl = normalizeUrl(url);
    if (!isValidUrl(cleanUrl)) {
      throw ArgumentError('Invalid Base URL format: $cleanUrl');
    }
    _baseUrl = cleanUrl;
  }

  /// Reset base URL to default.
  static void resetToDefault() {
    _baseUrl = defaultBaseUrl;
  }

  /// Constructs full URL for the health check endpoint.
  static String get healthUrl => '$_baseUrl$healthPath';

  /// Intelligently formats and normalizes URL inputs from users.
  /// Adds missing http/https, default port 8000 for local IPs, and removes trailing slash.
  static String normalizeUrl(String raw) {
    var clean = raw.trim();
    if (clean.isEmpty) return defaultBaseUrl;

    while (clean.endsWith('/')) {
      clean = clean.substring(0, clean.length - 1);
    }

    // Auto-detect protocol if user omitted http:// or https://
    if (!clean.startsWith('http://') && !clean.startsWith('https://')) {
      final isIpOrHost = RegExp(r'^(\d+\.\d+\.\d+\.\d+|localhost|[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+)').hasMatch(clean);
      if (isIpOrHost) {
        if (clean.contains('vercel.app') ||
            clean.contains('.com') ||
            clean.contains('.sch.id') ||
            clean.contains('.net') ||
            clean.contains('.org')) {
          clean = 'https://$clean';
        } else {
          clean = 'http://$clean';
        }
      }
    }

    // If it is a bare local IP or localhost without port (e.g., http://192.168.1.11 or http://localhost), append default port :8000
    final uri = Uri.tryParse(clean);
    if (uri != null && uri.host.isNotEmpty) {
      final isIpOrLocalhost = RegExp(r'^\d+\.\d+\.\d+\.\d+$').hasMatch(uri.host) || uri.host == 'localhost';
      if (isIpOrLocalhost && !uri.hasPort && uri.scheme == 'http') {
        clean = '$clean:8000';
      }
    }

    return clean;
  }

  /// Helper to validate if a string is a valid HTTP/HTTPS URL.
  static bool isValidUrl(String url) {
    final normalized = normalizeUrl(url);
    final uri = Uri.tryParse(normalized);
    if (uri == null) return false;
    return (uri.scheme == 'http' || uri.scheme == 'https') &&
        uri.host.isNotEmpty &&
        uri.host.contains(RegExp(r'[a-zA-Z0-9]'));
  }
}
