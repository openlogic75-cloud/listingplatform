import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';

/// Vendor shop profile as returned by GET /profile.
class VendorProfileData {
  const VendorProfileData({
    required this.name,
    required this.phone,
    required this.displayName,
    required this.category,
    required this.districtId,
    required this.districtName,
    required this.description,
  });

  final String name;
  final String phone;
  final String displayName;
  final String category;
  final int? districtId;
  final String? districtName;
  final String description;

  factory VendorProfileData.fromJson(Map<String, dynamic> user) {
    final Map<String, dynamic> vendor =
        user['vendor'] as Map<String, dynamic>? ?? <String, dynamic>{};

    return VendorProfileData(
      name: user['name'] as String? ?? '',
      phone: user['phone'] as String? ?? '',
      displayName: vendor['display_name'] as String? ?? '',
      category: vendor['category'] as String? ?? '',
      districtId: (vendor['district_id'] as num?)?.toInt(),
      districtName: vendor['district_name'] as String?,
      description: vendor['description'] as String? ?? '',
    );
  }
}

/// Reads and saves the vendor's own shop profile (M9.4). Only the fields
/// PUT /profile accepts for vendors: name, phone, display_name and
/// vendor_description. Category and district are set at registration.
class VendorProfileRepository {
  VendorProfileRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<VendorProfileData> fetch() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/profile');

    return VendorProfileData.fromJson(
      response.data?['user'] as Map<String, dynamic>,
    );
  }

  Future<VendorProfileData> save({
    required String name,
    required String phone,
    required String displayName,
    required String description,
  }) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.put<Map<String, dynamic>>(
      '/profile',
      data: <String, dynamic>{
        'name': name,
        'phone': phone,
        'display_name': displayName,
        'vendor_description': description,
      },
    );

    return VendorProfileData.fromJson(
      response.data?['user'] as Map<String, dynamic>,
    );
  }
}

final Provider<VendorProfileRepository> vendorProfileRepositoryProvider =
    Provider<VendorProfileRepository>((Ref ref) {
  return VendorProfileRepository(apiClient: ref.watch(apiClientProvider));
});
