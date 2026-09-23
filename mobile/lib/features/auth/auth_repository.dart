import 'package:dio/dio.dart';

import '../../core/network/api_client.dart';

/// Authenticated user profile as returned by the API.
class UserProfile {
  const UserProfile({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    this.phone,
  });

  final int id;
  final String name;
  final String email;
  final String? phone;
  final String role;

  factory UserProfile.fromJson(Map<String, dynamic> json) => UserProfile(
        id: json['id'] as int,
        name: json['name'] as String,
        email: json['email'] as String,
        phone: json['phone'] as String?,
        role: json['role'] as String,
      );
}

/// Token + profile returned by register and login.
class AuthSession {
  const AuthSession({required this.token, required this.profile});

  final String token;
  final UserProfile profile;
}

class RegistrationResult {
  const RegistrationResult.pending(this.message) : session = null;

  const RegistrationResult.authenticated(this.session) : message = null;

  final AuthSession? session;
  final String? message;
}

/// Roles that may register. Buyers browse as guests and never register.
const List<String> kRegisterableRoles = <String>[
  'vendor',
  'driver',
  'collector',
  'skilled_worker',
  'volunteer',
];

const Map<String, String> kRoleLabels = <String, String>{
  'vendor': 'Vendor / Seller',
  'driver': 'Delivery driver / Rider',
  'collector': 'Collector',
  'skilled_worker': 'Skilled worker',
  'volunteer': 'Verification volunteer',
};

/// Talks to /api/v1/auth/*.
class AuthRepository {
  AuthRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<RegistrationResult> register({
    required String name,
    required String email,
    String? phone,
    required String password,
    required String role,
    String? districtId,
    String? displayName,
    String? vendorCategory,
    required bool acceptTerms,
  }) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/auth/register',
      data: <String, dynamic>{
        'name': name,
        'email': email,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        'password': password,
        'password_confirmation': password,
        'role': role,
        // DPDP: explicit acceptance of the Terms and Privacy Policy (M26.1).
        'accept_terms': acceptTerms,
        if (districtId != null) 'district_id': districtId,
        if (displayName != null && displayName.isNotEmpty)
          'display_name': displayName,
        if (vendorCategory != null && vendorCategory.isNotEmpty)
          'vendor_category': vendorCategory,
      },
    );

    final Map<String, dynamic> body = response.data ?? <String, dynamic>{};

    if (response.statusCode == 202 || body['token'] == null) {
      return RegistrationResult.pending(
        body['message'] as String? ??
            'Check your email and click the verification link before signing in.',
      );
    }

    return RegistrationResult.authenticated(AuthSession(
      token: response.data?['token'] as String,
      profile: UserProfile.fromJson(
        response.data?['user'] as Map<String, dynamic>,
      ),
    ));
  }

  Future<void> resendVerification(String email) async {
    await _api.dio.post<Map<String, dynamic>>(
      '/auth/email/verification-notification',
      data: <String, dynamic>{'email': email},
    );
  }

  Future<AuthSession> login({
    required String email,
    required String password,
  }) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/auth/login',
      data: <String, dynamic>{
        'email': email,
        'password': password,
        'device_name': 'android-app',
      },
    );

    return AuthSession(
      token: response.data?['token'] as String,
      profile: UserProfile.fromJson(
        response.data?['user'] as Map<String, dynamic>,
      ),
    );
  }

  Future<UserProfile> me() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/auth/me');

    return UserProfile.fromJson(
      response.data?['user'] as Map<String, dynamic>,
    );
  }

  Future<void> logout() async {
    try {
      await _api.dio.post<void>('/auth/logout');
    } on DioException {
      // Token already invalid server-side; clearing locally is enough.
    }
  }
}
