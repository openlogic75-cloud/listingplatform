import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';

/// The sub-division a collector is signed to (M28.1). Null until an admin
/// assigns one.
class CollectorAssignment {
  const CollectorAssignment({
    required this.isActive,
    required this.localityId,
    required this.locality,
    required this.district,
  });

  final bool isActive;
  final int localityId;
  final String? locality;
  final String? district;

  factory CollectorAssignment.fromJson(Map<String, dynamic> json) =>
      CollectorAssignment(
        isActive: json['is_active'] == true,
        localityId: (json['locality_id'] as num).toInt(),
        locality: json['locality'] as String?,
        district: json['district'] as String?,
      );
}

/// A farm-produce collection (M28.3): pickup at the farm's sub-division, drop
/// at a hub district. The fee is paid directly to the collector.
class CollectionJob {
  const CollectionJob({
    required this.id,
    required this.status,
    required this.feeInr,
    required this.address,
    required this.locality,
    required this.district,
    required this.destination,
  });

  final int id;
  final String status;
  final num? feeInr;
  final String? address;
  final String? locality;
  final String? district;
  final String? destination;

  factory CollectionJob.fromJson(Map<String, dynamic> json) => CollectionJob(
        id: (json['id'] as num).toInt(),
        status: json['status'] as String,
        feeInr: json['fee_inr'] == null
            ? null
            : num.tryParse(json['fee_inr'].toString()),
        address: json['address'] as String?,
        locality: json['locality'] as String?,
        district: json['district'] as String?,
        destination: json['destination'] as String?,
      );
}

/// A hub district farm produce can be collected to.
class CollectionHub {
  const CollectionHub({required this.id, required this.name});

  final int id;
  final String name;

  factory CollectionHub.fromJson(Map<String, dynamic> json) => CollectionHub(
        id: (json['id'] as num).toInt(),
        name: json['name'] as String,
      );
}

/// Collector and farm-produce collection APIs (M28): sub-division assignment,
/// the collection queue, and a vendor's request to collect farm produce.
class CollectorRepository {
  CollectorRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<CollectorAssignment?> assignment() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/collector/assignment');

    final Object? raw = response.data?['data'];
    if (raw == null) {
      return null;
    }
    return CollectorAssignment.fromJson(raw as Map<String, dynamic>);
  }

  Future<List<CollectionJob>> jobs() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/collections');

    return (response.data?['data'] as List<dynamic>? ?? <dynamic>[])
        .map((dynamic e) => CollectionJob.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<CollectionJob> accept(int id) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>('/collections/$id/accept');
    return CollectionJob.fromJson(response.data?['data'] as Map<String, dynamic>);
  }

  Future<CollectionJob> progress(int id, String status) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/collections/$id/status',
      data: <String, dynamic>{'status': status},
    );
    return CollectionJob.fromJson(response.data?['data'] as Map<String, dynamic>);
  }

  /// Vendor: ask for one of your farm-produce listings to be collected to a hub.
  Future<CollectionJob> request({
    required int productId,
    required int destinationDistrictId,
    num? feeInr,
    String? address,
  }) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/collections',
      data: <String, dynamic>{
        'product_id': productId,
        'destination_district_id': destinationDistrictId,
        if (feeInr != null) 'fee_inr': feeInr,
        if (address != null && address.isNotEmpty) 'address': address,
      },
    );
    return CollectionJob.fromJson(response.data?['data'] as Map<String, dynamic>);
  }

  /// Hub districts, read from the reseller farm-produce feed's meta.
  Future<List<CollectionHub>> hubs() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/reseller-produce');

    final Map<String, dynamic> meta =
        response.data?['meta'] as Map<String, dynamic>? ?? <String, dynamic>{};
    return (meta['hubs'] as List<dynamic>? ?? <dynamic>[])
        .map((dynamic e) => CollectionHub.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}

String collectionErrorMessage(DioException e) {
  final Object? body = e.response?.data;
  if (body is Map<String, dynamic> &&
      body['message'] is String &&
      (body['message'] as String).isNotEmpty) {
    return body['message'] as String;
  }
  return 'Something went wrong. Check your connection and try again.';
}

final Provider<CollectorRepository> collectorRepositoryProvider =
    Provider<CollectorRepository>((Ref ref) {
  return CollectorRepository(apiClient: ref.watch(apiClientProvider));
});
