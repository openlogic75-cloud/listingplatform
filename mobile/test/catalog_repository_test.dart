import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:listingplatform/core/network/api_client.dart';
import 'package:listingplatform/core/storage/token_storage.dart';
import 'package:listingplatform/features/catalog/catalog_repository.dart';

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

ResponseBody _catalogResponse() => ResponseBody.fromString(
      jsonEncode(<String, dynamic>{
        'data': <dynamic>[
          <String, dynamic>{
            'id': 1,
            'title': 'Bamboo basket',
            'category': 'traditional',
            'price': 150,
            'moq': 1,
            'images': <String>[],
            'is_verified': false,
          },
        ],
        'meta': <String, dynamic>{'total': 1, 'last_page': 1},
      }),
      200,
      headers: <String, List<String>>{
        Headers.contentTypeHeader: <String>[Headers.jsonContentType],
      },
    );

CatalogRepository _repository(_FakeAdapter adapter) {
  final ApiClient api = ApiClient(
    tokenStorage: TokenStorage(storage: _StubSecureStorage()),
  );
  api.dio.httpClientAdapter = adapter;
  return CatalogRepository(apiClient: api);
}

void main() {
  setUp(() => TestWidgetsFlutterBinding.ensureInitialized());

  test('browse sends the service-area filter ids when set (M9.2)',
      () async {
    final _FakeAdapter adapter =
        _FakeAdapter((RequestOptions options) => _catalogResponse());
    final CatalogRepository repository = _repository(adapter);

    final ({List<Listing> items, int total, int lastPage}) result =
        await repository.browse(districtId: 3, localityId: 7);

    expect(adapter.last!.path, '/catalog');
    expect(adapter.last!.queryParameters['district_id'], 3);
    expect(adapter.last!.queryParameters['locality_id'], 7);
    expect(result.total, 1);
    expect(result.items.single.title, 'Bamboo basket');
  });

  test('browse omits the area params when no filter is set', () async {
    final _FakeAdapter adapter =
        _FakeAdapter((RequestOptions options) => _catalogResponse());
    final CatalogRepository repository = _repository(adapter);

    await repository.browse();

    expect(adapter.last!.queryParameters.containsKey('district_id'), isFalse);
    expect(adapter.last!.queryParameters.containsKey('locality_id'), isFalse);
    expect(adapter.last!.queryParameters['page'], 1);
  });
}
