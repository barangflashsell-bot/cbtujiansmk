/// Base class for all network-related errors in the CBT client.
abstract class NetworkException implements Exception {
  final String message;
  const NetworkException(this.message);

  @override
  String toString() => message;
}

/// Thrown when connection attempt exceeds configured timeout.
class ConnectionTimeoutException extends NetworkException {
  const ConnectionTimeoutException([super.message = 'Koneksi ke server timeout']);
}

/// Thrown when server cannot be reached (e.g. Wi-Fi down, server inactive).
class ConnectionRefusedException extends NetworkException {
  const ConnectionRefusedException([super.message = 'Tidak dapat terhubung ke server CBT']);
}

/// Thrown when server returns an HTTP error status code (e.g. 404, 500).
class HttpStatusException extends NetworkException {
  final int statusCode;
  final String? responseBody;

  HttpStatusException(this.statusCode, [this.responseBody])
      : super(responseBody != null && responseBody.isNotEmpty
            ? responseBody
            : 'Server mengembalikan HTTP status $statusCode');
}

/// Thrown when response body is not valid JSON or violates expected schema.
class InvalidResponseException extends NetworkException {
  const InvalidResponseException([super.message = 'Respon server tidak valid']);
}
