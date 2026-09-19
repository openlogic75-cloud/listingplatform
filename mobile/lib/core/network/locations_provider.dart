import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'api_client.dart';

/// A district with its localities, for pickers (M4 base, M4.5 errands).
class DistrictWithLocalities {
  const DistrictWithLocalities({
    required this.id,
    required this.name,
    required this.localities,
  });

  final int id;
  final String name;
  final List<LocalityOption> localities;

  factory DistrictWithLocalities.fromJson(Map<String, dynamic> json) =>
      DistrictWithLocalities(
        id: (json['id'] as num).toInt(),
        name: json['name'] as String,
        localities: (json['localities'] as List<dynamic>? ?? <dynamic>[])
            .map((dynamic e) =>
                LocalityOption.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

class LocalityOption {
  const LocalityOption({required this.id, required this.name});

  final int id;
  final String name;

  factory LocalityOption.fromJson(Map<String, dynamic> json) => LocalityOption(
        id: (json['id'] as num).toInt(),
        name: json['name'] as String,
      );
}

/// Public district/locality reference data (served by /locations, no PII).
class LocationsRepository {
  LocationsRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<List<DistrictWithLocalities>> districts() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/locations');

    return (response.data?['data'] as List<dynamic>? ?? <dynamic>[])
        .map((dynamic e) =>
            DistrictWithLocalities.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}

final Provider<LocationsRepository> locationsRepositoryProvider =
    Provider<LocationsRepository>((Ref ref) {
  return LocationsRepository(apiClient: ref.watch(apiClientProvider));
});

/// Simple async cache of the district list shared across pickers.
final FutureProvider<List<DistrictWithLocalities>> districtsProvider =
    FutureProvider<List<DistrictWithLocalities>>((Ref ref) {
  return ref.watch(locationsRepositoryProvider).districts();
});