import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';

/// A locality with online-driver count, from the M4.6 overview endpoint.
class OnlineLocality {
  const OnlineLocality({
    required this.id,
    required this.name,
    required this.onlineDrivers,
  });

  final int id;
  final String name;
  final int onlineDrivers;

  factory OnlineLocality.fromJson(Map<String, dynamic> json) =>
      OnlineLocality(
        id: (json['id'] as num).toInt(),
        name: json['name'] as String,
        onlineDrivers: (json['online_drivers'] as num).toInt(),
      );
}

/// Per-district snapshot of who is online right now. Locality answers are
/// localities with >=1 online driver whose base covers them; the
/// districtFallbackCount mirrors the job-matching fallback so the UI can say
/// "none here - N drivers elsewhere in the district" (M4.6, Q8).
class DriversOnline {
  const DriversOnline({
    required this.districtId,
    required this.asOf,
    required this.localities,
    required this.districtFallbackCount,
  });

  final int districtId;
  final DateTime asOf;
  final List<OnlineLocality> localities;
  final int districtFallbackCount;

  factory DriversOnline.fromJson(Map<String, dynamic> json) => DriversOnline(
        districtId: (json['district_id'] as num).toInt(),
        asOf: DateTime.tryParse(json['as_of'] as String? ?? '') ?? DateTime.now(),
        localities: (json['localities'] as List<dynamic>? ?? <dynamic>[])
            .map((dynamic e) =>
                OnlineLocality.fromJson(e as Map<String, dynamic>))
            .toList(),
        districtFallbackCount:
            (json['district_fallback_count'] as num?)?.toInt() ?? 0,
      );
}

/// Public read of driver availability per locality (no map, no GPS).
class DriverOverviewRepository {
  DriverOverviewRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<DriversOnline> overview({
    required int districtId,
    int? localityId,
  }) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>(
      '/drivers-online',
      queryParameters: <String, dynamic>{
        'district_id': districtId,
        if (localityId != null) 'locality_id': localityId,
      },
    );

    return DriversOnline.fromJson(
      response.data?['data'] as Map<String, dynamic>,
    );
  }
}

final Provider<DriverOverviewRepository> driverOverviewRepositoryProvider =
    Provider<DriverOverviewRepository>((Ref ref) {
  return DriverOverviewRepository(apiClient: ref.watch(apiClientProvider));
});