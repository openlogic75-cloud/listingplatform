import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:listingplatform/core/network/api_client.dart';
import 'package:listingplatform/core/storage/token_storage.dart';
import 'package:listingplatform/features/blog/blog_repository.dart';

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

ResponseBody _json(Map<String, dynamic> body) => ResponseBody.fromString(
      jsonEncode(body),
      200,
      headers: <String, List<String>>{
        Headers.contentTypeHeader: <String>[Headers.jsonContentType],
      },
    );

BlogRepository _repository(_FakeAdapter adapter) {
  final ApiClient api = ApiClient(
    tokenStorage: TokenStorage(storage: _StubSecureStorage()),
  );
  api.dio.httpClientAdapter = adapter;
  return BlogRepository(apiClient: api);
}

void main() {
  setUp(() => TestWidgetsFlutterBinding.ensureInitialized());

  test('list parses stories with cover and vendor (M22.2)', () async {
    final _FakeAdapter adapter = _FakeAdapter((RequestOptions options) => _json(<String, dynamic>{
          'data': <dynamic>[
            <String, dynamic>{
              'id': 1,
              'title': 'Verified: Riverside Farm',
              'slug': 'verified-riverside-farm',
              'excerpt': 'A site visit by Demo Volunteer.',
              'cover_url': 'http://localhost:8000/storage/evidence/one.webp',
              'images': <dynamic>[],
              'published_at': '2026-09-19T05:41:21+00:00',
              'vendor': <String, dynamic>{'id': 3, 'display_name': 'Riverside Farm'},
            },
          ],
        }));

    final List<Story> stories = await _repository(adapter).list();

    expect(adapter.last!.path, '/posts');
    expect(stories.single.title, 'Verified: Riverside Farm');
    expect(stories.single.vendorName, 'Riverside Farm');
    expect(stories.single.coverUrl, contains('one.webp'));
  });

  test('show parses the body and the visit photos', () async {
    final _FakeAdapter adapter = _FakeAdapter((RequestOptions options) => _json(<String, dynamic>{
          'data': <String, dynamic>{
            'id': 1,
            'title': 'Verified: Riverside Farm',
            'slug': 'verified-riverside-farm',
            'excerpt': 'excerpt',
            'cover_url': null,
            'images': <dynamic>[
              'http://localhost:8000/storage/evidence/one.webp',
              'http://localhost:8000/storage/evidence/two.webp',
            ],
            'published_at': '2026-09-19T05:41:21+00:00',
            'vendor': null,
            'body': 'Site visit by Demo Volunteer.\n\n- Goods confirmed — Yes',
          },
        }));

    final Story story = await _repository(adapter).show('verified-riverside-farm');

    expect(adapter.last!.path, '/posts/verified-riverside-farm');
    expect(story.body, contains('Site visit by'));
    expect(story.images, hasLength(2));
  });
}
