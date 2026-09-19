import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../catalog/catalog_repository.dart';
import 'listings_controller.dart';

/// Vendor dashboard: my listings with publish/archive actions (M2.2).
class ListingsScreen extends ConsumerWidget {
  const ListingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ThemeData theme = Theme.of(context);
    final AsyncValue<ListingsState> asyncState =
        ref.watch(listingsControllerProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('My listings')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.go('/listings/new'),
        icon: const Icon(Icons.add),
        label: const Text('New listing'),
      ),
      body: asyncState.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (Object error, StackTrace _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              const Icon(Icons.cloud_off_outlined, size: 40),
              const SizedBox(height: 12),
              const Text('Could not load your listings.'),
              TextButton(
                onPressed: () => ref.invalidate(listingsControllerProvider),
                child: const Text('Try again'),
              ),
            ],
          ),
        ),
        data: (ListingsState state) {
          if (state.items.isEmpty) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: <Widget>[
                    const Icon(Icons.storefront_outlined, size: 40),
                    const SizedBox(height: 12),
                    const Text('No listings yet.'),
                    const SizedBox(height: 4),
                    Text(
                      'Create your first listing to reach buyers.',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ),
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => ref.read(listingsControllerProvider.notifier).refresh(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: state.items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (BuildContext context, int index) {
                final Listing listing = state.items[index];

                return Card(
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 8),
                    title: Text(
                      listing.title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.titleMedium
                          ?.copyWith(fontWeight: FontWeight.w600),
                    ),
                    subtitle: Text.rich(
                      TextSpan(
                        text: listing.status == 'active'
                            ? 'Published'
                            : listing.status == 'draft'
                                ? 'Draft - not visible to buyers'
                                : 'Archived',
                        style: theme.textTheme.bodySmall,
                        children: <InlineSpan>[
                          if (!listing.isVerified &&
                              listing.verificationFeeInr != null)
                            TextSpan(
                              text: '\nNot verified - get verified for '
                                  '₹${listing.verificationFeeInr!.toStringAsFixed(2)}',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.primary,
                              ),
                            )
                          else if (!listing.isVerified)
                            const TextSpan(text: '\nNot verified yet'),
                        ],
                      ),
                    ),
                    trailing: PopupMenuButton<String>(
                      icon: const Icon(Icons.more_vert),
                      onSelected: (String action) async {
                        final ListingsController controller =
                            ref.read(listingsControllerProvider.notifier);

                        if (action == 'publish') {
                          await controller.updateListing(
                            listing.id,
                            status: 'active',
                          );
                        } else if (action == 'unpublish') {
                          await controller.updateListing(
                            listing.id,
                            status: 'inactive',
                          );
                        } else if (action == 'archive') {
                          await controller.archive(listing.id);
                        }
                      },
                      itemBuilder: (BuildContext context) =>
                          <PopupMenuEntry<String>>[
                        if (listing.status != 'active')
                          const PopupMenuItem<String>(
                            value: 'publish',
                            child: Text('Publish'),
                          ),
                        if (listing.status == 'active')
                          const PopupMenuItem<String>(
                            value: 'unpublish',
                            child: Text('Unpublish'),
                          ),
                        const PopupMenuItem<String>(
                          value: 'archive',
                          child: Text('Archive'),
                        ),
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
