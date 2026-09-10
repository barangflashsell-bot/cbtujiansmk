import 'dart:async';
import 'dart:convert';
import 'dart:io';

/// Minimal HTTP Response abstraction.
class HttpResponseData {
  final int statusCode;
  final String body;

  const HttpResponseData({
    required this.statusCode,
    required this.body,
  });
}

/// Abstract contract for HTTP execution.
/// Enables test isolation without external mocking packages.
abstract class HttpClientAdapter {
  Future<HttpResponseData> get(
    Uri uri, {
    Map<String, String>? headers,
    Duration? timeout,
  });

  Future<HttpResponseData> post(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  });

  Future<HttpResponseData> patch(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  });
}

/// Default implementation using standard Dart SDK `dart:io` HttpClient.
/// 0 third-party packages required.
class IoHttpClientAdapter implements HttpClientAdapter {
  final HttpClient _client;

  IoHttpClientAdapter({HttpClient? client}) : _client = client ?? HttpClient();

  @override
  Future<HttpResponseData> get(
    Uri uri, {
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    return _sendRequest('GET', uri, headers: headers, timeout: timeout);
  }

  @override
  Future<HttpResponseData> post(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    return _sendRequest('POST', uri, body: body, headers: headers, timeout: timeout);
  }

  @override
  Future<HttpResponseData> patch(
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    return _sendRequest('PATCH', uri, body: body, headers: headers, timeout: timeout);
  }

  Future<HttpResponseData> _sendRequest(
    String method,
    Uri uri, {
    String? body,
    Map<String, String>? headers,
    Duration? timeout,
  }) async {
    final effectiveTimeout = timeout ?? const Duration(seconds: 8);
    _client.connectionTimeout = effectiveTimeout;

    try {
      final HttpClientRequest request;
      switch (method.toUpperCase()) {
        case 'POST':
          request = await _client.postUrl(uri).timeout(effectiveTimeout);
          break;
        case 'PATCH':
          request = await _client.patchUrl(uri).timeout(effectiveTimeout);
          break;
        case 'GET':
        default:
          request = await _client.getUrl(uri).timeout(effectiveTimeout);
          break;
      }

      request.headers.set(HttpHeaders.acceptHeader, 'application/json');
      if (body != null) {
        request.headers.set(HttpHeaders.contentTypeHeader, 'application/json; charset=utf-8');
      }

      if (headers != null) {
        headers.forEach((key, value) {
          request.headers.set(key, value);
        });
      }

      if (body != null) {
        request.write(body);
      }

      final response = await request.close().timeout(effectiveTimeout);
      final responseBody = await utf8.decoder.bind(response).join().timeout(effectiveTimeout);

      return HttpResponseData(
        statusCode: response.statusCode,
        body: responseBody,
      );
    } on SocketException {
      throw const SocketException('Connection refused or unreachable');
    } on TimeoutException {
      throw TimeoutException('Network operation timed out');
    }
  }

  void close() {
    _client.close();
  }
}
