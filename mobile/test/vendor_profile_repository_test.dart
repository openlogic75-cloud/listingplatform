import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:listingplatform/core/network/api_client.dart';
import 'package:listingplatform/core/storage/token_storage.dart';
import 'package:listingplatform/features/vendor/profile/vendor_profile_repository.dart';

class _StubSecureStorage extends FlutterSecureStorage {
  @override
  Future<String?> read({
    required String key,
    IOSOptions? iOptions,
    AndroidOptions? aOptions,
    LinuxOptions? lOptions,
    WebOptions? webOptions,
    MacOsOptions? mOptions,
    WindowsOptions? wOptions,
  }) async =>
      null;
}

class _FakeAdapter implements HttpClientAdapter {
  _FakeAdapter(this.onRequest);

  final ResponseBody Function(RequestOptions options) onRequest;
  RequestOptions? last;

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    last = options;
    return onRequest(options);
  }

  @override
  void close({bool force = false}) {}
}

ResponseBody _profileResponse() => ResponseBody.fromString(
      jsonEncode(<String, dynamic>{
        'user': <String, dynamic>{
          'name': 'Vendor V',
          'phone': '+5550009',
          'role': 'vendor',
          'vendor': <String, dynamic>{
            'display_name': 'Test Shop',
            'category': 'traditional',
            'district_id': 2,
            'district_name': 'Dimapur',
            'description': 'Handwoven goods.',
          },
        },
      }),
      200,
      headers: <String, List<String>>{
        Headers.contentTypeHeader: <String>[Headers.jsonContentType],
      },
    );

VendorProfileRepository _repository(_FakeAdapter adapter) {
  final ApiClient api = ApiClient(
    tokenStorage: TokenStorage(storage: _StubSecureStorage()),
  );
  api.dio.httpClientAdapter = adapter;
  return VendorProfileRepository(apiClient: api);
}

void main() {
  setUp(() => TestWidgetsFlutterBinding.ensureInitialized());

  test('fetch maps the vendor profile fields including district name (M9.4)',
      () async {
    final _FakeAdapter adapter =
        _FakeAdapter((RequestOptions options) => _profileResponse());
    final VendorProfileRepository repository = _repository(adapter);

    final VendorProfileData profile = await repository.fetch();

    expect(adapter.last!.path, '/profile');
    expect(profile.name, 'Vendor V');
    expect(profile.displayName, 'Test Shop');
    expect(profile.category, 'traditional');
    expect(profile.districtId, 2);
    expect(profile.districtName, 'Dimapur');
    expect(profile.description, 'Handwoven goods.');
  });

  test('save sends exactly the updatable profile fields', () async {
    final _FakeAdapter adapter =
        _FakeAdapter((RequestOptions options) => _profileResponse());
    final VendorProfileRepository repository = _repository(adapter);

    await repository.save(
      name: 'Vendor V Two',
      phone: '+5550010',
      displayName: 'Renamed Shop',
      description: 'New description.',
    );

    expect(adapter.last!.path, '/profile');
    expect(
      adapter.last!.data,
      <String, dynamic>{
        'name': 'Vendor V Two',
        'phone': '+5550010',
        'display_name': 'Renamed Shop',
        'vendor_description': 'New description.',
      },
    );
  });
}
