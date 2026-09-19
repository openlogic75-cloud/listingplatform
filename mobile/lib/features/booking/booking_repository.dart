import 'package:dio/dio.dart';

import '../../core/network/api_client.dart';

/// Guest booking result from create/lookup.
class GuestBooking {
  const GuestBooking({
    required this.code,
    required this.status,
    required this.vendorName,
    required this.items,
  });

  final String code;
  final String status;
  final String? vendorName;
  final List<BookingItemLine> items;

  /// Lifecycle labels for display; never emojis, plain words.
  String get statusLabel => switch (status) {
        'pending' => 'Waiting for the seller to confirm',
        'confirmed' => 'Confirmed by the seller',
        'picked_up' => 'Picked up',
        'in_transit' => 'In transit',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        _ => status,
      };

  factory GuestBooking.fromJson(Map<String, dynamic> json) => GuestBooking(
        code: json['code'] as String,
        status: json['status'] as String,
        vendorName: json['vendor'] == null
            ? null
            : (json['vendor'] as Map<String, dynamic>)['display_name']
                as String?,
        items: (json['items'] as List<dynamic>? ?? <dynamic>[])
            .map((dynamic e) =>
                BookingItemLine.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

class BookingItemLine {
  const BookingItemLine({
    required this.title,
    required this.quantity,
    required this.unit,
    this.unitPrice,
  });

  final String? title;
  final int quantity;
  final String? unit;
  final double? unitPrice;

  factory BookingItemLine.fromJson(Map<String, dynamic> json) =>
      BookingItemLine(
        title: json['title'] as String?,
        quantity: (json['quantity'] as num).toInt(),
        unit: json['unit'] as String?,
        unitPrice: (json['unit_price_snapshot'] as num?)?.toDouble(),
      );
}

/// Guest booking API: create (M3.1), lookup (M3.3), cancel while pending.
class BookingRepository {
  BookingRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<GuestBooking> create({
    required int vendorId,
    required String contactName,
    required String contactPhone,
    required int productId,
    required int quantity,
    bool isReseller = false,
    String? notes,
  }) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/bookings',
      data: <String, dynamic>{
        'vendor_id': vendorId,
        'contact_name': contactName,
        'contact_phone': contactPhone,
        'items': <Map<String, dynamic>>[
          <String, dynamic>{'product_id': productId, 'quantity': quantity},
        ],
        'is_reseller': isReseller,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      },
    );

    return GuestBooking.fromJson(
      response.data?['data'] as Map<String, dynamic>,
    );
  }

  Future<GuestBooking> lookup({
    required String code,
    required String phone,
  }) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/bookings/lookup',
      data: <String, dynamic>{'code': code, 'phone': phone},
    );

    return GuestBooking.fromJson(
      response.data?['data'] as Map<String, dynamic>,
    );
  }

  Future<GuestBooking> cancel({
    required String code,
    required String phone,
  }) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/bookings/cancel',
      data: <String, dynamic>{'code': code, 'phone': phone},
    );

    return GuestBooking.fromJson(
      response.data?['data'] as Map<String, dynamic>,
    );
  }
}
