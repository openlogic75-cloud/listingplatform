import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:listingplatform/core/network/api_client.dart';
import 'package:listingplatform/core/storage/token_storage.dart';
import 'package:listingplatform/features/directory/directory_repository.dart';

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

DirectoryRepository _repository(_FakeAdapter adapter) {
  final ApiClient api = ApiClient(
    tokenStorage: TokenStorage(storage: _StubSecureStorage()),
  );
  api.dio.httpClientAdapter = adapter;
  return DirectoryRepository(apiClient: api);
}

void main() {
  setUp(() => TestWidgetsFlutterBinding.ensureInitialized());

  test('workers sends the category filter and parses skills (M17.3)', () async {
    final _FakeAdapter adapter = _FakeAdapter((RequestOptions options) => _json(<String, dynamic>{
          'data': <dynamic>[
            <String, dynamic>{
              'id': 7,
              'name': 'Asha Electric',
              'district': 'Dimapur',
              'services': 'Wiring',
              'skill_categories': <dynamic>[
                <String, dynamic>{'id': 3, 'name': 'Electrician'},
              ],
            },
          ],
          'meta': <String, dynamic>{
            'categories': <dynamic>[
              <String, dynamic>{'id': 3, 'name': 'Electrician'},
            ],
          },
        }));

    final DirectoryRepository repository = _repository(adapter);
    final List<DirectoryWorker> workers = await repository.workers(categoryId: 3);

    expect(adapter.last!.path, '/workers');
    expect(adapter.last!.queryParameters['category_id'], 3);
    expect(workers.single.name, 'Asha Electric');
    expect(workers.single.categories.single.name, 'Electrician');
  });

  test('transport parses the public phone, online flag and categories (M18.2)', () async {
    final _FakeAdapter adapter = _FakeAdapter((RequestOptions options) => _json(<String, dynamic>{
          'data': <dynamic>[
            <String, dynamic>{
              'id': 4,
              'name': 'Ravi Rider',
              'phone': '9876501234',
              'is_online': true,
              'district': 'Kohima',
              'transport_categories': <dynamic>[
                <String, dynamic>{'id': 1, 'name': 'Bike delivery'},
              ],
            },
          ],
          'meta': <String, dynamic>{
            'categories': <dynamic>[
              <String, dynamic>{'id': 1, 'name': 'Bike delivery'},
            ],
          },
        }));

    final DirectoryRepository repository = _repository(adapter);
    final List<DirectoryDriver> drivers = await repository.transport();

    expect(adapter.last!.path, '/transport');
    expect(drivers.single.name, 'Ravi Rider');
    expect(drivers.single.phone, '9876501234');
    expect(drivers.single.isOnline, isTrue);
    expect(drivers.single.categories.single.name, 'Bike delivery');
  });

  test('workerCategories reads meta.categories', () async {
    final _FakeAdapter adapter = _FakeAdapter((RequestOptions options) => _json(<String, dynamic>{
          'data': <dynamic>[],
          'meta': <String, dynamic>{
            'categories': <dynamic>[
              <String, dynamic>{'id': 5, 'name': 'Plumber'},
            ],
          },
        }));

    final DirectoryRepository repository = _repository(adapter);
    final List<DirectoryCategory> categories = await repository.workerCategories();

    expect(categories.single.name, 'Plumber');
  });
}
