import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import 'booking_repository.dart';

/// Loads a guest booking by code + phone; cancel goes through the same repo.
final FutureProviderFamily<GuestBooking, ({String code, String phone})>
    guestBookingProvider = FutureProvider.family<GuestBooking,
        ({String code, String phone})>(
  (Ref ref, ({String code, String phone}) keys) {
    return BookingRepository(apiClient: ref.watch(apiClientProvider))
        .lookup(code: keys.code, phone: keys.phone);
  },
);

/// Guest tracking (M3.3): booking code + phone. Cancel while pending only.
class BookingLookupScreen extends ConsumerStatefulWidget {
  const BookingLookupScreen({super.key});

  @override
  ConsumerState<BookingLookupScreen> createState() =>
      _BookingLookupScreenState();
}

class _BookingLookupScreenState extends ConsumerState<BookingLookupScreen> {
  final TextEditingController _code = TextEditingController();
  final TextEditingController _phone = TextEditingController();
  ({String code, String phone})? _query;
  bool _cancelling = false;
  String? _cancelError;

  @override
  void dispose() {
    _code.dispose();
    _phone.dispose();
    super.dispose();
  }

  void _search() {
    setState(() {
      _cancelError = null;
      _query = (code: _code.text.trim(), phone: _phone.text.trim());
    });
  }

  Future<void> _cancel() async {
    final ({String code, String phone})? current = _query;

    if (current == null) {
      return;
    }

    setState(() => _cancelling = true);

    try {
      await BookingRepository(apiClient: ref.read(apiClientProvider)).cancel(
        code: current.code,
        phone: current.phone,
      );

      if (mounted) {
        ref.invalidate(guestBookingProvider(current));
      }
    } on DioException catch (error) {
      final Object? data = error.response?.data;
      String? message;

      if (data is Map<String, dynamic> &&
          data['errors'] is Map<String, dynamic>) {
        final Object? first =
            (data['errors'] as Map<String, dynamic>).values.first;

        if (first is List<dynamic> && first.isNotEmpty) {
          message = first.first.toString();
        }
      }

      setState(
        () => _cancelError = message ??
            'Could not cancel right now. Contact the seller instead.',
      );
    } finally {
      if (mounted) {
        setState(() => _cancelling = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Track a booking')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: <Widget>[
          TextFormField(
            controller: _code,
            textCapitalization: TextCapitalization.characters,
            decoration: const InputDecoration(
              labelText: 'Booking code',
              helperText: 'Shown when the booking was placed (BK-XXXXXX).',
            ),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(
              labelText: 'Phone used for the booking',
            ),
          ),
          const SizedBox(height: 16),
          FilledButton(
            onPressed: _search,
            child: const Text('Find my booking'),
          ),
          const SizedBox(height: 24),
          if (_query != null)
            ref
                .watch(guestBookingProvider(_query!))
                .when(
                  loading: () =>
                      const Center(child: CircularProgressIndicator()),
                  error: (Object error, StackTrace _) => const Text(
                    'No booking matches that code and phone number.',
                  ),
                  data: (GuestBooking booking) => _BookingCard(
                    booking: booking,
                    cancelling: _cancelling,
                    cancelError: _cancelError,
                    onCancel: booking.status == 'pending' ? _cancel : null,
                  ),
                ),
        ],
      ),
    );
  }
}

class _BookingCard extends StatelessWidget {
  const _BookingCard({
    required this.booking,
    required this.cancelling,
    required this.cancelError,
    required this.onCancel,
  });

  final GuestBooking booking;
  final bool cancelling;
  final String? cancelError;
  final VoidCallback? onCancel;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              booking.code,
              style: theme.textTheme.titleLarge
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Text(booking.statusLabel, style: theme.textTheme.bodyLarge),
            const SizedBox(height: 12),
            for (final BookingItemLine line in booking.items)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 4),
                child: Row(
                  children: <Widget>[
                    Expanded(
                      child: Text(
                        line.title ?? 'Item',
                        style: theme.textTheme.bodyMedium,
                      ),
                    ),
                    Text(
                      'x${line.quantity}'
                      '${line.unit != null ? ' ${line.unit}' : ''}',
                      style: theme.textTheme.bodyMedium
                          ?.copyWith(fontWeight: FontWeight.w600),
                    ),
                  ],
                ),
              ),
            if (booking.vendorName != null)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text(
                  'Seller: ${booking.vendorName}',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ),
            if (onCancel != null) ...<Widget>[
              const SizedBox(height: 12),
              if (cancelError != null)
                Text(
                  cancelError!,
                  style: theme.textTheme.bodySmall
                      ?.copyWith(color: theme.colorScheme.error),
                ),
              const SizedBox(height: 4),
              OutlinedButton(
                onPressed: cancelling ? null : onCancel,
                child: cancelling
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Cancel this booking'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

