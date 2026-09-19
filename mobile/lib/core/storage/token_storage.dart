import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Secure persistence for the Sanctum bearer token.
///
/// flutter_secure_storage uses the Android Keystore-backed encrypted
/// preferences, so tokens never touch plain shared preferences.
class TokenStorage {
  TokenStorage({FlutterSecureStorage? storage})
      : _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(encryptedSharedPreferences: true),
            );

  static const String _key = 'auth_token';

  final FlutterSecureStorage _storage;

  Future<String?> read() => _storage.read(key: _key);

  Future<void> write(String token) => _storage.write(key: _key, value: token);

  Future<void> clear() => _storage.delete(key: _key);
}

final Provider<TokenStorage> tokenStorageProvider =
    Provider<TokenStorage>((Ref ref) => TokenStorage());
