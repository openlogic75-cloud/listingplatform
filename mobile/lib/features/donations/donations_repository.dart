import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';

/// Admin-managed UPI donation settings (M6.1). Display-only — the platform
/// never stores or processes payment data.
class DonationSettings {
  const DonationSettings({
    required this.upiId,
    required this.qrUrl,
    required this.upiDeepLink,
  });

  final String? upiId;
  final String? qrUrl;
  final String? upiDeepLink;

  factory DonationSettings.fromJson(Map<String, dynamic> json) =>
      DonationSettings(
        upiId: json['upi_id'] as String?,
        qrUrl: json['qr_url'] as String?,
        upiDeepLink: json['upi_deep_link'] as String?,
      );
}

class DonationsRepository {
  DonationsRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<DonationSettings?> show() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/donation');

    final Object? raw = response.data?['data'];
    if (raw == null) {
      return null;
    }
    return DonationSettings.fromJson(raw as Map<String, dynamic>);
  }
}

final Provider<DonationsRepository> donationsRepositoryProvider =
    Provider<DonationsRepository>((Ref ref) {
  return DonationsRepository(apiClient: ref.watch(apiClientProvider));
});

final FutureProvider<DonationSettings?> donationSettingsProvider =
    FutureProvider<DonationSettings?>((Ref ref) {
  return ref.watch(donationsRepositoryProvider).show();
});