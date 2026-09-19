import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'driver_repository.dart';

/// Riverpod state for the driver dashboard: base, availability, and errors.
class DriverDashboardState {
  const DriverDashboardState({
    this.base,
    this.isOnline = false,
    this.districts = const <DistrictWithLocalities>[],
    this.loading = true,
    this.error,
    this.saving = false,
  });

  final DriverBase? base;
  final bool isOnline;
  final List<DistrictWithLocalities> districts;
  final bool loading;
  final String? error;
  final bool saving;

  DriverDashboardState copyWith({
    DriverBase? base,
    bool? isOnline,
    List<DistrictWithLocalities>? districts,
    bool? loading,
    String? error,
    bool? saving,
  }) {
    return DriverDashboardState(
      base: base ?? this.base,
      isOnline: isOnline ?? this.isOnline,
      districts: districts ?? this.districts,
      loading: loading ?? this.loading,
      error: error,
      saving: saving ?? this.saving,
    );
  }
}

class DriverDashboardController extends Notifier<DriverDashboardState> {
  @override
  DriverDashboardState build() => const DriverDashboardState();

  Future<void> load() async {
    state = state.copyWith(loading: true, error: null);
    try {
      final DriverRepository repo = ref.read(driverRepositoryProvider);
      final List<DistrictWithLocalities> districts = await repo.districts();
      final DriverBase? base = await repo.base();
      final bool isOnline = await repo.availability();
      state = state.copyWith(
        districts: districts,
        base: base,
        isOnline: isOnline,
        loading: false,
      );
    } on DioException catch (e) {
      state = state.copyWith(loading: false, error: driverErrorMessage(e));
    }
  }

  Future<void> toggleOnline(bool value) async {
    state = state.copyWith(saving: true, error: null);
    try {
      final DriverRepository repo = ref.read(driverRepositoryProvider);
      final bool isOnline = await repo.setAvailability(isOnline: value);
      state = state.copyWith(isOnline: isOnline, saving: false);
    } on DioException catch (e) {
      state = state.copyWith(saving: false, error: driverErrorMessage(e));
    }
  }

  Future<void> saveBase({
    required int districtId,
    required List<int> localityIds,
  }) async {
    state = state.copyWith(saving: true, error: null);
    try {
      final DriverRepository repo = ref.read(driverRepositoryProvider);
      final DriverBase base = await repo.setBase(
        districtId: districtId,
        localityIds: localityIds,
      );
      state = state.copyWith(base: base, saving: false);
    } on DioException catch (e) {
      state = state.copyWith(saving: false, error: driverErrorMessage(e));
    }
  }
}

String driverErrorMessage(DioException e) {
  final Object? body = e.response?.data;
  if (body is Map<String, dynamic> &&
      body['message'] is String &&
      (body['message'] as String).isNotEmpty) {
    return body['message'] as String;
  }
  return 'Something went wrong. Check your connection and try again.';
}

final NotifierProvider<DriverDashboardController, DriverDashboardState>
    driverDashboardProvider =
    NotifierProvider<DriverDashboardController, DriverDashboardState>(
  DriverDashboardController.new,
);