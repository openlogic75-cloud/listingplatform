import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/storage/token_storage.dart';
import 'auth_repository.dart';

/// Auth session state.
sealed class SessionState {
  const SessionState();
}

class SessionGuest extends SessionState {
  const SessionGuest();
}

class SessionAuthenticated extends SessionState {
  const SessionAuthenticated(this.profile);

  final UserProfile profile;
}

/// Holds the auth session: register, login, restore on start, logout.
class AuthController extends AsyncNotifier<SessionState> {
  AuthRepository get _repository => ref.read(authRepositoryProvider);

  @override
  Future<SessionState> build() async {
    final String? token = await ref.read(tokenStorageProvider).read();

    if (token == null || token.isEmpty) {
      return const SessionGuest();
    }

    try {
      final UserProfile profile = await _repository.me();

      return SessionAuthenticated(profile);
    } on DioException catch (error) {
      if (error.response?.statusCode == 401) {
        // Token revoked server-side: drop it and continue as guest.
        await ref.read(tokenStorageProvider).clear();
      }

      // Server unreachable: stay guest for now and keep the device
      // offline-friendly; the next app start retries the restore.
      return const SessionGuest();
    }
  }

  Future<void> login({
    required String email,
    required String password,
  }) async {
    state = const AsyncLoading();

    try {
      final AuthSession session =
          await _repository.login(email: email, password: password);

      await ref.read(tokenStorageProvider).write(session.token);

      state = AsyncData(SessionAuthenticated(session.profile));
    } on DioException catch (error) {
      state = AsyncError(_message(error), StackTrace.current);
    }
  }

  Future<void> register({
    required String name,
    required String email,
    String? phone,
    required String password,
    required String role,
    required bool acceptTerms,
    String? displayName,
    String? vendorCategory,
  }) async {
    state = const AsyncLoading();

    try {
      final AuthSession session = await _repository.register(
        name: name,
        email: email,
        phone: phone,
        password: password,
        role: role,
        acceptTerms: acceptTerms,
        displayName: displayName,
        vendorCategory: vendorCategory,
      );

      await ref.read(tokenStorageProvider).write(session.token);

      state = AsyncData(SessionAuthenticated(session.profile));
    } on DioException catch (error) {
      state = AsyncError(_message(error), StackTrace.current);
    }
  }

  Future<void> logout() async {
    await _repository.logout();
    await ref.read(tokenStorageProvider).clear();

    state = const AsyncData(SessionGuest());
  }

  String _message(DioException error) {
    final Object? data = error.response?.data;

    if (data is Map<String, dynamic>) {
      final Object? errors = data['errors'];

      if (errors is Map<String, dynamic> && errors.isNotEmpty) {
        final Object? first = errors.values.first;

        if (first is List<dynamic> && first.isNotEmpty) {
          return first.first.toString();
        }
      }

      final Object? message = data['message'];

      if (message is String && message.isNotEmpty) {
        return message;
      }
    }

    return 'Something went wrong. Check your connection and try again.';
  }
}

final AsyncNotifierProvider<AuthController, SessionState>
    authControllerProvider =
    AsyncNotifierProvider<AuthController, SessionState>(
  AuthController.new,
);

final Provider<AuthRepository> authRepositoryProvider =
    Provider<AuthRepository>(
  (Ref ref) => AuthRepository(apiClient: ref.watch(apiClientProvider)),
);
