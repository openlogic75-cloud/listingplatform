import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/network/locations_provider.dart';

export '../../core/network/locations_provider.dart'
    show DistrictWithLocalities, LocalityOption;

/// Driver base of operation: one district plus up to five localities.
class DriverBase {
  const DriverBase({required this.districtId, required this.localityIds});

  final int districtId;
  final List<int> localityIds;

  factory DriverBase.fromJson(Map<String, dynamic> json) => DriverBase(
        districtId: (json['district_id'] as num).toInt(),
        localityIds: (json['localities'] as List<dynamic>? ?? <dynamic>[])
            .map((dynamic e) => (e as num).toInt())
            .toList(),
      );
}

/// A logistics pickup/delivery job offered to the driver.
class DriverJob {
  const DriverJob({
    required this.id,
    required this.type,
    required this.status,
    required this.address,
    required this.bookingCode,
    required this.vendorName,
    required this.createdAt,
  });

  final int id;
  final String type;
  final String status;
  final String? address;
  final String? bookingCode;
  final String? vendorName;
  final DateTime? createdAt;

  factory DriverJob.fromJson(Map<String, dynamic> json) => DriverJob(
        id: (json['id'] as num).toInt(),
        type: json['type'] as String,
        status: json['status'] as String,
        address: json['address'] as String?,
        bookingCode: json['booking_code'] as String?,
        vendorName: json['vendor_display_name'] as String?,
        createdAt: json['created_at'] == null
            ? null
            : DateTime.tryParse(json['created_at'] as String),
      );
}

/// Driver APIs (M4): base of operation, availability toggle, job queue.
class DriverRepository {
  DriverRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<List<DistrictWithLocalities>> districts() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>(
      '/locations',
    );

    return (response.data?['data'] as List<dynamic>? ?? <dynamic>[])
        .map((dynamic e) =>
            DistrictWithLocalities.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<DriverBase?> base() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/driver/base');

    final Object? raw = response.data?['data'];
    if (raw == null) {
      return null;
    }
    return DriverBase.fromJson(raw as Map<String, dynamic>);
  }

  Future<DriverBase> setBase({
    required int districtId,
    required List<int> localityIds,
  }) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/driver/base',
      data: <String, dynamic>{
        'district_id': districtId,
        'locality_ids': localityIds,
      },
    );

    return DriverBase.fromJson(
      response.data?['data'] as Map<String, dynamic>,
    );
  }

  Future<DriverBase> updateLocalities(List<int> localityIds) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.put<Map<String, dynamic>>(
      '/driver/base/localities',
      data: <String, dynamic>{'locality_ids': localityIds},
    );

    return DriverBase.fromJson(
      response.data?['data'] as Map<String, dynamic>,
    );
  }

  Future<bool> availability() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/driver/availability');

    final Object? raw = response.data?['data'];
    if (raw == null) {
      return false;
    }
    return (raw as Map<String, dynamic>)['is_online'] == true;
  }

  Future<bool> setAvailability({required bool isOnline}) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.put<Map<String, dynamic>>(
      '/driver/availability',
      data: <String, dynamic>{'is_online': isOnline},
    );

    return (response.data?['data']
            as Map<String, dynamic>)['is_online'] ==
        true;
  }

  Future<List<DriverJob>> jobs() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/logistics/jobs');

    return (response.data?['data'] as List<dynamic>? ?? <dynamic>[])
        .map((dynamic e) => DriverJob.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<DriverJob> acceptJob(int jobId) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>('/logistics/jobs/$jobId/accept');
    return DriverJob.fromJson(response.data?['data'] as Map<String, dynamic>);
  }

  Future<DriverJob> progressJob(int jobId, String status) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/logistics/jobs/$jobId/status',
      data: <String, dynamic>{'status': status},
    );
    return DriverJob.fromJson(response.data?['data'] as Map<String, dynamic>);
  }
}

final Provider<DriverRepository> driverRepositoryProvider =
    Provider<DriverRepository>((Ref ref) {
  return DriverRepository(apiClient: ref.watch(apiClientProvider));
});