import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_client.dart';
import 'catalog_repository.dart';

/// Loads one listing by id for the detail screen.
final FutureProviderFamily<ListingDetail, int> listingDetailProvider =
    FutureProvider.family<ListingDetail, int>((Ref ref, int id) {
  return CatalogRepository(apiClient: ref.watch(apiClientProvider)).detail(id);
});

/// Public listing detail (M2.5 in-app). Bookings arrive in M3; the page is
/// honest about that instead of showing a dead action.
class ListingDetailScreen extends ConsumerWidget {
  const ListingDetailScreen({super.key, required this.listingId});

  final int listingId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ThemeData theme = Theme.of(context);
    final AsyncValue<ListingDetail> listing =
        ref.watch(listingDetailProvider(listingId));

    return Scaffold(
      appBar: AppBar(title: const Text('Listing')),
      body: listing.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (Object error, StackTrace _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              const Icon(Icons.cloud_off_outlined, size: 40),
              const SizedBox(height: 12),
              const Text('Could not load this listing.'),
              const SizedBox(height: 8),
              TextButton(
                onPressed: () => ref.invalidate(listingDetailProvider(listingId)),
                child: const Text('Try again'),
              ),
            ],
          ),
        ),
        data: (ListingDetail detail) {
          final Listing data = detail.listing;
          final String shareUrl = detail.shareUrl;

          return ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            if (data.images.isNotEmpty)
              ClipRRect(
                borderRadius: AppRadiusLike.card,
                child: Image.network(
                  data.images.first,
                  height: 220,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => const SizedBox(height: 120),
                ),
              ),
            const SizedBox(height: 16),
            Row(
              children: <Widget>[
                Expanded(
                  child: Text(
                    data.title,
                    style: theme.textTheme.headlineSmall
                        ?.copyWith(fontWeight: FontWeight.w700),
                  ),
                ),
                if (data.isVerified)
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: <Widget>[
                      Icon(Icons.verified_outlined,
                          size: 18, color: theme.colorScheme.primary),
                      const SizedBox(width: 4),
                      Text('Verified', style: theme.textTheme.labelMedium),
                    ],
                  ),
              ],
            ),
            const SizedBox(height: 8),
            if (data.price != null)
              Text(
                '${data.price!.toStringAsFixed(2)}'
                '${data.unit != null ? ' / ${data.unit}' : ''}',
                style: theme.textTheme.headlineSmall
                    ?.copyWith(color: theme.colorScheme.primary),
              ),
            const SizedBox(height: 12),
            if (!data.isVerified && data.verificationFeeInr != null)
              Card(
                color: theme.colorScheme.primaryContainer,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Icon(Icons.fact_check_outlined,
                          color: theme.colorScheme.onPrimaryContainer),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          'Verification available - fee of '
                          '₹${data.verificationFeeInr!.toStringAsFixed(2)} '
                          'paid directly to the visiting volunteer.',
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onPrimaryContainer,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  children: <Widget>[
                    _FactRow(
                        label: 'Category',
                        value: switch (data.category) {
                          'traditional' => 'Traditional products',
                          'agro' => 'Agro products',
                          'rental_homestay' => 'Rental / Homestay',
                          _ => data.category,
                        }),
                    _FactRow(label: 'Minimum order', value: '${data.moq}'),
                    if (data.vendorName != null)
                      _FactRow(label: 'Sold by', value: data.vendorName!),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            if (data.description != null && data.description!.isNotEmpty)
              Text(data.description!, style: theme.textTheme.bodyLarge),
            const SizedBox(height: 24),
            if (data.vendorId != null)
              FilledButton.icon(
                onPressed: () => context.go(
                  '/book/${data.id}'
                  '?vendor=${data.vendorId}'
                  '&moq=${data.moq}'
                  '${data.unit != null ? '&unit=${Uri.encodeComponent(data.unit!)}' : ''}',
                ),
                icon: const Icon(Icons.shopping_bag_outlined),
                label: const Text('Book now'),
              )
            else
              const FilledButton(
                onPressed: null,
                child: Text('Booking unavailable'),
              ),
            const SizedBox(height: 12),
            if (shareUrl.isNotEmpty)
              OutlinedButton.icon(
                onPressed: () => _copyShareLink(context, shareUrl),
                icon: const Icon(Icons.share_outlined),
                label: const Text('Share this listing'),
              ),
            const SizedBox(height: 8),
            Text(
              'No account needed. Settlement happens directly with the '
              'seller - the platform never takes a cut.',
              style: theme.textTheme.bodySmall
                  ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
              textAlign: TextAlign.center,
            ),
          ],
        );
        },
      ),
    );
  }

  Future<void> _copyShareLink(BuildContext context, String shareUrl) async {
    await Clipboard.setData(ClipboardData(text: shareUrl));

    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Listing link copied')),
      );
    }
  }
}

class _FactRow extends StatelessWidget {
  const _FactRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        children: <Widget>[
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: theme.textTheme.bodySmall
                  ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: theme.textTheme.bodyMedium
                  ?.copyWith(fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}

/// Local stand-in for shared card radius while core/widgets land.
class AppRadiusLike {
  AppRadiusLike._();

  static final BorderRadius card = BorderRadius.circular(12);
}
