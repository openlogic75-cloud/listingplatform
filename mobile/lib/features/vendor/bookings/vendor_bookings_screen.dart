import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import 'vendor_bookings_repository.dart';

/// Vendor dashboard: incoming guest bookings with contact details and a
/// one-tap next step in the lifecycle (M3.2).
final FutureProvider<List<VendorBooking>> vendorBookingsProvider =
    FutureProvider<List<VendorBooking>>((Ref ref) {
  return VendorBookingsRepository(apiClient: ref.watch(apiClientProvider))
      .all();
});

class VendorBookingsScreen extends ConsumerWidget {
  const VendorBookingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ThemeData theme = Theme.of(context);
    final AsyncValue<List<VendorBooking>> bookings =
        ref.watch(vendorBookingsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Incoming bookings')),
      body: bookings.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (Object error, StackTrace _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              const Icon(Icons.cloud_off_outlined, size: 40),
              const SizedBox(height: 12),
              const Text('Could not load bookings.'),
              TextButton(
                onPressed: () => ref.invalidate(vendorBookingsProvider),
                child: const Text('Try again'),
              ),
            ],
          ),
        ),
        data: (List<VendorBooking> data) {
          if (data.isEmpty) {
            return const Center(
              child: Text('No bookings yet. They appear here as guests place them.'),
            );
          }

          return RefreshIndicator(
            onRefresh: () async => ref.refresh(vendorBookingsProvider.future),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: data.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (BuildContext context, int index) {
                final VendorBooking booking = data[index];

                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Row(
                          children: <Widget>[
                            Expanded(
                              child: Text(
                                booking.code,
                                style: theme.textTheme.titleMedium
                                    ?.copyWith(fontWeight: FontWeight.w700),
                              ),
                            ),
                            if (booking.isReseller)
                              Chip(
                                label: const Text('Reseller'),
                                labelStyle: theme.textTheme.labelSmall,
                                visualDensity: VisualDensity.compact,
                              ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          booking.statusLabel,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.primary,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 8),
                        for (final String line in booking.items)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 2),
                            child: Text(
                              line,
                              style: theme.textTheme.bodyMedium,
                            ),
                          ),
                        const Divider(height: 24),
                        Text(
                          'Contact',
                          style: theme.textTheme.labelSmall?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                        Text(
                          '${booking.contactName ?? '-'} - '
                          '${booking.contactPhone ?? '-'}',
                          style: theme.textTheme.bodyMedium,
                        ),
                        if (booking.nextAction != null) ...<Widget>[
                          const SizedBox(height: 12),
                          FilledButton(
                            onPressed: () async {
                              await ref
                                  .read(vendorBookingsRepositoryProvider)
                                  .changeStatus(
                                    booking.id,
                                    booking.nextAction!,
                                  );
                              ref.invalidate(vendorBookingsProvider);
                            },
                            child: Text(booking.nextActionLabel),
                          ),
                        ],
                      ],
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

final Provider<VendorBookingsRepository> vendorBookingsRepositoryProvider =
    Provider<VendorBookingsRepository>(
  (Ref ref) =>
      VendorBookingsRepository(apiClient: ref.watch(apiClientProvider)),
);
