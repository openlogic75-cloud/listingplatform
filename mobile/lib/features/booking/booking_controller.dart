import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import 'booking_repository.dart';

/// Not-yet-submitted booking draft for one listing.
class BookingDraft {
  const BookingDraft({
    required this.listingId,
    required this.vendorId,
    required this.minimumQuantity,
    this.unit,
  });

  final int listingId;
  final int vendorId;
  final int minimumQuantity;
  final String? unit;
}

/// Booking screen state: draft input, submit progress, placed result.
class BookingFormState {
  const BookingFormState({
    required this.draft,
    this.quantity,
    this.submitting = false,
    this.placed,
    this.error,
  });

  final BookingDraft draft;
  final int? quantity;
  final bool submitting;
  final GuestBooking? placed;
  final String? error;

  int get effectiveQuantity =>
      quantity ?? draft.minimumQuantity;
}

class BookingController extends FamilyAsyncNotifier<BookingFormState, BookingDraft> {
  BookingRepository get _repository => ref.read(bookingRepositoryProvider);

  BookingDraft get draft => arg;

  @override
  Future<BookingFormState> build(BookingDraft draft) async {
    return BookingFormState(draft: draft, quantity: draft.minimumQuantity);
  }

  void setQuantity(int value) {
    state = AsyncData(
      (state.valueOrNull ?? BookingFormState(draft: draft)).copyWith(quantity: value),
    );
  }

  Future<void> submit({
    required String name,
    required String phone,
    bool isReseller = false,
    String? notes,
  }) async {
    state = AsyncData(
      (state.valueOrNull ?? BookingFormState(draft: draft)).copyWith(submitting: true, error: null),
    );

    try {
      final GuestBooking placed = await _repository.create(
        vendorId: draft.vendorId,
        contactName: name,
        contactPhone: phone,
        productId: draft.listingId,
        quantity: state.valueOrNull?.effectiveQuantity ?? draft.minimumQuantity,
        isReseller: isReseller,
        notes: notes,
      );

      state = AsyncData(
        (state.valueOrNull ?? BookingFormState(draft: draft)).copyWith(
          submitting: false,
          placed: placed,
        ),
      );
    } on DioException catch (error) {
      final String message = _extract(error) ??
          'Could not place the booking. Check your connection and try again.';

      state = AsyncData(
        (state.valueOrNull ?? BookingFormState(draft: draft)).copyWith(
          submitting: false,
          error: message,
        ),
      );
    }
  }

  String? _extract(DioException error) {
    final Object? data = error.response?.data;

    if (data is Map<String, dynamic>) {
      final Object? errors = data['errors'];

      if (errors is Map<String, dynamic> && errors.isNotEmpty) {
        final Object? first = errors.values.first;

        if (first is List<dynamic> && first.isNotEmpty) {
          return first.first.toString();
        }
      }
    }

    return null;
  }
}

extension on BookingFormState {
  BookingFormState copyWith({
    int? quantity,
    bool? submitting,
    GuestBooking? placed,
    String? error,
  }) {
    return BookingFormState(
      draft: draft,
      quantity: quantity ?? this.quantity,
      submitting: submitting ?? this.submitting,
      placed: placed ?? this.placed,
      error: error,
    );
  }
}

final bookingControllerProvider =
    AsyncNotifierProvider.family<BookingController, BookingFormState,
        BookingDraft>(BookingController.new);

final Provider<BookingRepository> bookingRepositoryProvider =
    Provider<BookingRepository>(
  (Ref ref) => BookingRepository(apiClient: ref.watch(apiClientProvider)),
);
