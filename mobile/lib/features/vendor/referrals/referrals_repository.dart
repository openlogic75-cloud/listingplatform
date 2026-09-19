import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';

/// One referral code with its ledger counts (M6.3). Credits are informational:
/// the platform never moves money, it only attributes who brought whom.
class ReferralCode {
  const ReferralCode({
    required this.id,
    required this.code,
    required this.label,
    required this.signups,
    required this.conversions,
  });

  final int id;
  final String code;
  final String? label;
  final int signups;
  final int conversions;

  factory ReferralCode.fromJson(Map<String, dynamic> json) => ReferralCode(
        id: (json['id'] as num).toInt(),
        code: json['code'] as String,
        label: json['label'] as String?,
        signups: (json['signups_count'] as num?)?.toInt() ?? 0,
        conversions: (json['conversions_count'] as num?)?.toInt() ?? 0,
      );
}

/// Vendor referral summary (M6.3): the codes plus their ledger totals.
class ReferralSummary {
  const ReferralSummary({
    required this.codes,
    required this.totalSignups,
    required this.totalConversions,
  });

  const ReferralSummary.empty()
      : codes = const <ReferralCode>[],
        totalSignups = 0,
        totalConversions = 0;

  final List<ReferralCode> codes;
  final int totalSignups;
  final int totalConversions;
}

/// Vendor referral management (M6.3).
class ReferralsRepository {
  ReferralsRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  /// Codes + totals in one call, matching ReferralService::stats().
  Future<ReferralSummary> summary() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/referrals');

    final Map<String, dynamic> data =
        (response.data?['data'] as Map<String, dynamic>?) ??
            <String, dynamic>{};

    final List<ReferralCode> codes =
        (data['codes'] as List<dynamic>? ?? <dynamic>[])
            .map((dynamic e) => ReferralCode.fromJson(e as Map<String, dynamic>))
            .toList();

    return ReferralSummary(
      codes: codes,
      totalSignups: (data['total_signups'] as num?)?.toInt() ?? 0,
      totalConversions: (data['total_conversions'] as num?)?.toInt() ?? 0,
    );
  }

  Future<ReferralCode> create({String? label}) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/referrals',
      data: <String, dynamic>{
        if (label != null && label.isNotEmpty) 'label': label,
      },
    );

    return ReferralCode.fromJson(
      response.data?['data'] as Map<String, dynamic>,
    );
  }
}

final Provider<ReferralsRepository> referralsRepositoryProvider =
    Provider<ReferralsRepository>((Ref ref) {
  return ReferralsRepository(apiClient: ref.watch(apiClientProvider));
});