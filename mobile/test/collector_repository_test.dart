import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:listingplatform/core/network/api_client.dart';
import 'package:listingplatform/core/storage/token_storage.dart';
import 'package:listingplatform/features/collector/collector_repository.dart';

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

CollectorRepository _repository(_FakeAdapter adapter) {
  final ApiClient api = ApiClient(
    tokenStorage: TokenStorage(storage: _StubSecureStorage()),
  );
  api.dio.httpClientAdapter = adapter;
  return CollectorRepository(apiClient: api);
}

void main() {
  setUp(() => TestWidgetsFlutterBinding.ensureInitialized());

  test('assignment reads the signed sub-division, or null (M28.1)', () async {
    final CollectorRepository repo = _repository(
      _FakeAdapter((RequestOptions options) => _json(<String, dynamic>{
            'data': <String, dynamic>{
              'is_active': true,
              'locality_id': 12,
              'locality': 'Tuli',
              'district': 'Mokokchung',
              'assigned_at': '2026-09-19T09:00:00+00:00',
            },
          })),
    );

    final CollectorAssignment? assignment = await repo.assignment();

    expect(assignment, isNotNull);
    expect(assignment!.localityId, 12);
    expect(assignment.locality, 'Tuli');
    expect(assignment.district, 'Mokokchung');
    expect(assignment.isActive, isTrue);
  });

  test('jobs parses collections and their hub destination (M28.3)', () async {
    final CollectorRepository repo = _repository(
      _FakeAdapter((RequestOptions options) => _json(<String, dynamic>{
            'data': <dynamic>[
              <String, dynamic>{
                'id': 31,
                'status': 'assigned',
                'fee_inr': '250.00',
                'address': 'Near the market',
                'locality': 'Tuli',
                'district': 'Mokokchung',
                'destination': 'Dimapur',
                'assigned_to_me': true,
              },
            ],
          })),
    );

    final List<CollectionJob> jobs = await repo.jobs();

    expect(jobs, hasLength(1));
    expect(jobs.first.id, 31);
    expect(jobs.first.status, 'assigned');
    expect(jobs.first.destination, 'Dimapur');
    expect(jobs.first.feeInr, 250);
  });

  test('request posts the listing, hub and fee (M28.5)', () async {
    final _FakeAdapter adapter = _FakeAdapter(
      (RequestOptions options) => _json(<String, dynamic>{
        'data': <String, dynamic>{
          'id': 9,
          'status': 'assigned',
          'fee_inr': 300,
          'address': null,
          'locality': 'Tuli',
          'district': 'Mokokchung',
          'destination': 'Dimapur',
          'assigned_to_me': true,
        },
      }),
    );
    final CollectorRepository repo = _repository(adapter);

    await repo.request(productId: 5, destinationDistrictId: 2, feeInr: 300);

    expect(adapter.last?.path, '/collections');
    final Map<String, dynamic> body =
        adapter.last?.data as Map<String, dynamic>;
    expect(body['product_id'], 5);
    expect(body['destination_district_id'], 2);
    expect(body['fee_inr'], 300);
  });

  test('accept and progress move a collection along', () async {
    final _FakeAdapter adapter = _FakeAdapter(
      (RequestOptions options) => _json(<String, dynamic>{
        'data': <String, dynamic>{
          'id': 9,
          'status': options.path.endsWith('/accept') ? 'accepted' : 'completed',
          'fee_inr': null,
          'address': null,
          'locality': null,
          'district': null,
          'destination': null,
          'assigned_to_me': true,
        },
      }),
    );
    final CollectorRepository repo = _repository(adapter);

    final CollectionJob accepted = await repo.accept(9);
    expect(accepted.status, 'accepted');

    final CollectionJob done = await repo.progress(9, 'completed');
    expect(done.status, 'completed');
  });
}
