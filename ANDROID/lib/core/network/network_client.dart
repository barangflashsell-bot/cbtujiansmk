import 'dart:async';
import 'dart:convert';
import 'dart:io';

import '../config/api_config.dart';
import 'http_client_adapter.dart';
import 'network_exceptions.dart';

/// Centralized network client for executing HTTP operations.
/// Handles timeouts, response code checks, JSON decoding, Bearer auth, and typed errors.
class NetworkClient {
  final HttpClientAdapter _adapter;

  NetworkClient({HttpClientAdapter? adapter})
      : _adapter = adapter ?? IoHttpClientAdapter();

  Map<String, String> _buildHeaders(Map<String, String>? customHeaders, String? token) {
    final headers = <String, String>{};
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }
    if (customHeaders != null) {
      headers.addAll(customHeaders);
    }
    return headers;
  }

  /// Performs a GET request, validates HTTP status, and parses JSON response.
  Future<Map<String, dynamic>> getJson(
    String url, {
    Map<String, String>? headers,
    String? token,
    Duration? timeout,
  }) async {
    return _request(
      () => _adapter.get(
        Uri.parse(url),
        headers: _buildHeaders(headers, token),
        timeout: timeout ?? ApiConfig.defaultTimeout,
      ),
    );
  }

  /// Performs a POST request with JSON payload.
  Future<Map<String, dynamic>> postJson(
    String url, {
    Map<String, dynamic>? body,
    Map<String, String>? headers,
    String? token,
    Duration? timeout,
  }) async {
    final jsonString = body != null ? jsonEncode(body) : null;
    return _request(
      () => _adapter.post(
        Uri.parse(url),
        body: jsonString,
        headers: _buildHeaders(headers, token),
        timeout: timeout ?? ApiConfig.defaultTimeout,
      ),
    );
  }

  /// Performs a PATCH request with JSON payload.
  Future<Map<String, dynamic>> patchJson(
    String url, {
    Map<String, dynamic>? body,
    Map<String, String>? headers,
    String? token,
    Duration? timeout,
  }) async {
    final jsonString = body != null ? jsonEncode(body) : null;
    return _request(
      () => _adapter.patch(
        Uri.parse(url),
        body: jsonString,
        headers: _buildHeaders(headers, token),
        timeout: timeout ?? ApiConfig.defaultTimeout,
      ),
    );
  }

  Future<Map<String, dynamic>> _request(Future<HttpResponseData> Function() call) async {
    try {
      final response = await call();

      if (response.statusCode < 200 || response.statusCode >= 300) {
        // Try parsing error message from JSON response body
        String? errorMessage;
        try {
          final decoded = jsonDecode(response.body);
          if (decoded is Map<String, dynamic> && decoded['message'] != null) {
            errorMessage = decoded['message'].toString();
          }
        } catch (_) {}

        throw HttpStatusException(response.statusCode, errorMessage ?? response.body);
      }

      final decoded = jsonDecode(response.body);
      if (decoded is! Map<String, dynamic>) {
        throw const InvalidResponseException('Payload respon bukan merupakan JSON object');
      }

      return decoded;
    } on TimeoutException {
      throw const ConnectionTimeoutException();
    } on SocketException {
      throw const ConnectionRefusedException();
    } on FormatException {
      throw const InvalidResponseException('Gagal memproses format JSON dari server');
    } on NetworkException {
      rethrow;
    } catch (e) {
      throw ConnectionRefusedException(e.toString());
    }
  }
}
