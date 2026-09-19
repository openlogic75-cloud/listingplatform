import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';

/// One recorded consent row (M7.1): what was agreed to, which text version,
/// and when. Revoking is owner-only and happens through [ConsentsRepository].
class ConsentRecord {
  const ConsentRecord({
    required this.id,
    required this.consentKey,
    required this.textVersion,
    required this.purpose,
    required this.grantedAt,
  });

  final int id;
  final String consentKey;
  final String textVersion;
  final String purpose;
  final DateTime? grantedAt;

  /// Plain labels - consent keys are internal identifiers, never shown raw.
  String get label => switch (consentKey) {
        'registration' => 'Account registration',
        'booking_contact' => 'Contact details shared with the vendor',
        'errand_contact' => 'Contact details shared with the driver',
        'notifications' => 'Push notifications',
        _ => 'Consent',
      };

  factory ConsentRecord.fromJson(Map<String, dynamic> json) => ConsentRecord(
        id: (json['id'] as num).toInt(),
        consentKey: json['consent_key'] as String,
        textVersion: json['text_version'] as String,
        purpose: json['purpose'] as String,
        grantedAt: json['granted_at'] == null
            ? null
            : DateTime.tryParse(json['granted_at'] as String),
      );
}

/// Result of a completed data export (M7.2).
class ExportResult {
  const ExportResult({required this.requestId, required this.downloadUrl});

  final int requestId;
  final String downloadUrl;

  factory ExportResult.fromJson(Map<String, dynamic> json) {
    final Map<String, dynamic> data =
        json['data'] as Map<String, dynamic>? ?? <String, dynamic>{};
    return ExportResult(
      requestId: (data['request_id'] as num?)?.toInt() ?? 0,
      downloadUrl: data['download_url'] as String? ?? '',
    );
  }
}

/// Self-serve privacy controls (M7.1-M7.3).
class PrivacyRepository {
  PrivacyRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<List<ConsentRecord>> consents() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/consents');

    return (response.data?['data'] as List<dynamic>? ?? <dynamic>[])
        .map((dynamic e) => ConsentRecord.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// Revokes a consent. Returns nothing: the caller reloads the list.
  Future<void> revokeConsent(int id) async {
    await _api.dio.delete<void>('/consents/$id');
  }

  /// Requests a machine-readable copy of all personal data.
  Future<ExportResult> requestExport() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>('/export');

    return ExportResult.fromJson(
      response.data ?? <String, dynamic>{},
    );
  }

  /// Requests account deletion. The backend anonymises personal details
  /// immediately and records the request for audit.
  Future<int> requestDeletion() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>('/deletion');

    final Map<String, dynamic> data =
        response.data?['data'] as Map<String, dynamic>? ?? <String, dynamic>{};

    return (data['request_id'] as num?)?.toInt() ?? 0;
  }
}

final Provider<PrivacyRepository> privacyRepositoryProvider =
    Provider<PrivacyRepository>((Ref ref) {
  return PrivacyRepository(apiClient: ref.watch(apiClientProvider));
});

final FutureProvider<List<ConsentRecord>> consentsProvider =
    FutureProvider<List<ConsentRecord>>(
  (Ref ref) => ref.watch(privacyRepositoryProvider).consents(),
);