import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:listingplatform/core/network/api_client.dart';
import 'package:listingplatform/core/storage/token_storage.dart';
import 'package:listingplatform/features/vendor/listings/listings_repository.dart';

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

Map<String, dynamic> _listingJson() => <String, dynamic>{
      'id': 1,
      'title': 'Organic rice',
      'category': 'agro',
      'price': 120,
      'unit': 'kg',
      'moq': 1,
      'images': <String>[],
      'is_verified': false,
      'status': 'draft',
    };

ListingsRepository _repository(_FakeAdapter adapter) {
  final ApiClient api = ApiClient(
    tokenStorage: TokenStorage(storage: _StubSecureStorage()),
  );
  api.dio.httpClientAdapter = adapter;
  return ListingsRepository(apiClient: api);
}

void main() {
  setUp(() => TestWidgetsFlutterBinding.ensureInitialized());

  test('uploadImage posts the photo as multipart and returns its path', () async {
    final _FakeAdapter adapter = _FakeAdapter((RequestOptions options) {
      return ResponseBody.fromString(
        jsonEncode(<String, dynamic>{'path': 'products/organic-rice.jpg'}),
        201,
        headers: <String, List<String>>{
          Headers.contentTypeHeader: <String>[Headers.jsonContentType],
        },
      );
    });
    final ListingsRepository repository = _repository(adapter);

    final String path = await repository.uploadImage(
      filename: 'rice.jpg',
      bytes: Uint8List.fromList(<int>[1, 2, 3, 4]),
    );

    expect(path, 'products/organic-rice.jpg');
    expect(adapter.last!.path, '/media');
    expect(adapter.last!.contentType, contains('multipart/form-data'));

    final FormData form = adapter.last!.data as FormData;
    expect(form.fields, hasLength(1));
    expect(form.fields.single.key, 'directory');
    expect(form.fields.single.value, 'products');
    expect(form.files, hasLength(1));
    expect(form.files.single.key, 'file');
    expect(form.files.single.value.filename, 'rice.jpg');
  });

  test('create sends image paths when photos are attached', () async {
    final _FakeAdapter adapter = _FakeAdapter((RequestOptions options) {
      return ResponseBody.fromString(
        jsonEncode(<String, dynamic>{'data': _listingJson()}),
        201,
        headers: <String, List<String>>{
          Headers.contentTypeHeader: <String>[Headers.jsonContentType],
        },
      );
    });
    final ListingsRepository repository = _repository(adapter);

    await repository.create(
      title: 'Organic rice',
      category: 'agro',
      images: <String>['products/rice.jpg', 'products/rice-2.jpg'],
    );

    final Map<String, dynamic> sent =
        adapter.last!.data as Map<String, dynamic>;
    expect(sent['images'],
        <String>['products/rice.jpg', 'products/rice-2.jpg']);
  });

  test('create leaves images out of the payload when none are attached',
      () async {
    final _FakeAdapter adapter = _FakeAdapter((RequestOptions options) {
      return ResponseBody.fromString(
        jsonEncode(<String, dynamic>{'data': _listingJson()}),
        201,
        headers: <String, List<String>>{
          Headers.contentTypeHeader: <String>[Headers.jsonContentType],
        },
      );
    });
    final ListingsRepository repository = _repository(adapter);

    await repository.create(title: 'Organic rice', category: 'agro');

    expect((adapter.last!.data as Map<String, dynamic>).containsKey('images'),
        isFalse);
  });
}