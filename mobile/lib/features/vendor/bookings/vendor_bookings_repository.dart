import 'package:dio/dio.dart';

import '../../../core/network/api_client.dart';

/// A vendor booking with its items.
class VendorBooking {
  const VendorBooking({
    required this.id,
    required this.code,
    required this.status,
    required this.items,
    required this.isReseller,
    required this.contactName,
    required this.contactPhone,
  });

  final int id;
  final String code;
  final String status;
  final List<String> items;
  final bool isReseller;
  final String? contactName;
  final String? contactPhone;

  String get statusLabel => switch (status) {
        'pending' => 'Pending - waiting for you to confirm',
        'confirmed' => 'Confirmed - arrange pickup',
        'picked_up' => 'Picked up',
        'in_transit' => 'In transit',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled by the buyer',
        _ => status,
      };

  /// Next allowed transition, or null when the lifecycle is closed.
  String? get nextAction => switch (status) {
        'pending' => 'confirmed',
        'confirmed' => 'picked_up',
        'picked_up' => 'in_transit',
        'in_transit' => 'delivered',
        'delivered' => 'completed',
        _ => null,
      };

  String get nextActionLabel => switch (nextAction) {
        'confirmed' => 'Confirm',
        'picked_up' => 'Mark picked up',
        'in_transit' => 'Mark in transit',
        'delivered' => 'Mark delivered',
        'completed' => 'Complete',
        _ => '',
      };

  factory VendorBooking.fromJson(Map<String, dynamic> json) {
    final Map<String, dynamic>? contact =
        json['contact'] as Map<String, dynamic>?;

    return VendorBooking(
      id: (json['id'] as num).toInt(),
      code: json['code'] as String,
      status: json['status'] as String,
      items: (json['items'] as List<dynamic>? ?? <dynamic>[])
          .map((dynamic e) {
        final Map<String, dynamic> item = e as Map<String, dynamic>;

        return '${item['quantity']} x ${item['title'] ?? 'Item'}';
      }).toList(),
      isReseller: json['is_reseller'] as bool? ?? false,
      contactName: contact?['name'] as String?,
      contactPhone: contact?['phone'] as String?,
    );
  }
}

class VendorBookingsRepository {
  VendorBookingsRepository({required ApiClient apiClient})
      : _api = apiClient;

  final ApiClient _api;

  Future<List<VendorBooking>> all() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/vendor/bookings');

    final List<dynamic> raw =
        response.data?['data'] as List<dynamic>? ?? <dynamic>[];

    return raw
        .map(
            (dynamic e) => VendorBooking.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<void> changeStatus(int bookingId, String status) async {
    await _api.dio.post<Map<String, dynamic>>(
      '/vendor/bookings/$bookingId/status',
      data: <String, dynamic>{'status': status},
    );
  }
}
