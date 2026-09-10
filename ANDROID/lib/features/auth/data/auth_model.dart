/// Model representing authenticated student/user profile.
class UserModel {
  final int id;
  final String username;
  final String name;
  final String role;
  final int? studentId;
  final String? nis;

  const UserModel({
    required this.id,
    required this.username,
    required this.name,
    required this.role,
    this.studentId,
    this.nis,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] as int,
      username: json['username'] as String,
      name: json['name'] as String,
      role: json['role'] as String? ?? 'student',
      studentId: json['student_id'] as int?,
      nis: json['nis'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'username': username,
        'name': name,
        'role': role,
        'student_id': studentId,
        'nis': nis,
      };
}

/// Response payload from `POST /api/v1/auth/login`.
class AuthResponse {
  final UserModel user;
  final String token;
  final String tokenType;
  final String message;

  const AuthResponse({
    required this.user,
    required this.token,
    required this.tokenType,
    required this.message,
  });

  factory AuthResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as Map<String, dynamic>;
    final userJson = data['user'] as Map<String, dynamic>;

    return AuthResponse(
      user: UserModel.fromJson(userJson),
      token: data['token'] as String,
      tokenType: data['token_type'] as String? ?? 'Bearer',
      message: json['message'] as String? ?? 'Login berhasil',
    );
  }
}
