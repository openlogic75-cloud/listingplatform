import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/token_storage.dart';

/// Base URL for the Laravel API.
///
/// Override at build/run time:
///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
/// 10.0.2.2 is the Android emulator alias for the host machine loopback.
const String kApiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://10.0.2.2:8000/api/v1',
);

/// Public website base URL, minus the trailing slash.
///
/// Used for shareable links (referral links, listing links) that open in a
/// browser rather than the app. Point it at the deployed site:
///   flutter build apk --dart-define=SITE_BASE_URL=https://example.org
const String kSiteBaseUrl = String.fromEnvironment(
  'SITE_BASE_URL',
  defaultValue: 'http://10.0.2.2:8000',
);

/// Dio HTTP client wired with the Sanctum bearer token.
class ApiClient {
  ApiClient({required TokenStorage tokenStorage})
      : _tokenStorage = tokenStorage,
        dio = Dio(
          BaseOptions(
            baseUrl: kApiBaseUrl,
            connectTimeout: const Duration(seconds: 15),
            receiveTimeout: const Duration(seconds: 20),
            headers: <String, String>{'Accept': 'application/json'},
          ),
        ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (RequestOptions options, RequestInterceptorHandler handler) async {
          final String? token = await _tokenStorage.read();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  final TokenStorage _tokenStorage;
  final Dio dio;
}

final Provider<ApiClient> apiClientProvider = Provider<ApiClient>((Ref ref) {
  return ApiClient(tokenStorage: ref.watch(tokenStorageProvider));
});
