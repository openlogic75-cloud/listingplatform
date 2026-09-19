import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/locations_provider.dart';
import 'catalog_controller.dart';
import 'catalog_repository.dart';

/// Guest catalog (M2.4): search + filters + results. Buyers never need an
/// account; signing in is for working roles only. The area filter only ever
/// offers active service areas (M9.1/M9.2) — it lists where the platform
/// operates.
class CatalogScreen extends ConsumerStatefulWidget {
  const CatalogScreen({super.key, this.initialCategory});

  /// When set (e.g. the dedicated PG / rentals / homestays entry, M23.1), the
  /// catalog opens already filtered to that category.
  final String? initialCategory;

  @override
  ConsumerState<CatalogScreen> createState() => _CatalogScreenState();
}

class _CatalogScreenState extends ConsumerState<CatalogScreen> {
  final TextEditingController _search = TextEditingController();

  static const List<(String, String)> _categories = <(String, String)>[
    ('', 'All'),
    ('traditional', 'Traditional'),
    ('agro', 'Agro'),
    ('rental_homestay', 'Rental / Homestay'),
  ];

  @override
  void initState() {
    super.initState();

    final String? category = widget.initialCategory;
    if (category != null && category.isNotEmpty) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        ref.read(catalogControllerProvider.notifier).search(category: category);
      });
    }
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);
    final AsyncValue<CatalogState> asyncState =
        ref.watch(catalogControllerProvider);
    final CatalogState? state = asyncState.valueOrNull;
    final AsyncValue<List<DistrictWithLocalities>> districts =
        ref.watch(districtsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Catalog')),
      body: Column(
        children: <Widget>[
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
            child: SearchBar(
              controller: _search,
              hintText: 'Search titles and descriptions',
              onSubmitted: (String value) => ref
                  .read(catalogControllerProvider.notifier)
                  .search(query: value),
              trailing: <Widget>[
                IconButton(
                  icon: const Icon(Icons.search),
                  tooltip: 'Search',
                  onPressed: () => ref
                      .read(catalogControllerProvider.notifier)
                      .search(query: _search.text),
                ),
              ],
            ),
          ),
          SizedBox(
            height: 56,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              children: <Widget>[
                for (final (String value, String label) in _categories)
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: FilterChip(
                      label: Text(label),
                      selected: (state?.category ?? '') == value,
                      onSelected: (bool selected) => ref
                          .read(catalogControllerProvider.notifier)
                          .search(category: selected ? value : ''),
                    ),
                  ),
              ],
            ),
          ),
          if (districts.valueOrNull?.isNotEmpty ?? false) ...<Widget>[
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 0),
              child: Row(
                children: <Widget>[
                  Expanded(
                    child: _areaFilter(
                      theme: theme,
                      label: 'District',
                      allLabel: 'All districts',
                      value: state?.districtId,
                      items: districts.valueOrNull!
                          .map((DistrictWithLocalities d) => (d.id, d.name))
                          .toList(),
                      onChanged: (int? value) => ref
                          .read(catalogControllerProvider.notifier)
                          .filterArea(districtId: value, localityId: null),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _areaFilter(
                      theme: theme,
                      label: 'Locality',
                      allLabel: state?.districtId == null
                          ? 'Pick a district first'
                          : 'All localities',
                      value: state?.localityId,
                      items: _localitiesOf(districts.valueOrNull!, state?.districtId)
                          .map((LocalityOption l) => (l.id, l.name))
                          .toList(),
                      enabled: state?.districtId != null,
                      onChanged: (int? value) => ref
                          .read(catalogControllerProvider.notifier)
                          .filterArea(
                            districtId: state?.districtId,
                            localityId: value,
                          ),
                    ),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 0),
              child: Row(
                children: <Widget>[
                  Icon(
                    Icons.map_outlined,
                    size: 14,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      'Only areas where the platform operates are listed.',
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
          Expanded(child: _buildResults(theme, asyncState, state)),
        ],
      ),
    );
  }

  List<LocalityOption> _localitiesOf(
    List<DistrictWithLocalities> districts,
    int? districtId,
  ) {
    return districts
        .where((DistrictWithLocalities d) => d.id == districtId)
        .expand((DistrictWithLocalities d) => d.localities)
        .toList();
  }

  Widget _areaFilter({
    required ThemeData theme,
    required String label,
    required String allLabel,
    required int? value,
    required List<(int, String)> items,
    required ValueChanged<int?> onChanged,
    bool enabled = true,
  }) {
    return DropdownButtonFormField<int>(
      initialValue: value,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12),
      ),
      items: <DropdownMenuItem<int>>[
        DropdownMenuItem<int>(
          value: null,
          child: Text(
            allLabel,
            style: enabled
                ? null
                : TextStyle(color: theme.disabledColor),
          ),
        ),
        ...<DropdownMenuItem<int>>[
          for (final (int id, String name) in items)
            DropdownMenuItem<int>(value: id, child: Text(name)),
        ],
      ],
      onChanged: enabled ? onChanged : null,
    );
  }

  Widget _buildResults(
    ThemeData theme,
    AsyncValue<CatalogState> asyncState,
    CatalogState? state,
  ) {
    if (asyncState.isLoading && (state?.items.isEmpty ?? true)) {
      return const Center(child: CircularProgressIndicator());
    }

    final CatalogState data = state ?? const CatalogState();

    if (data.error != null && data.items.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              const Icon(Icons.cloud_off_outlined, size: 40),
              const SizedBox(height: 12),
              Text(data.error!, textAlign: TextAlign.center),
            ],
          ),
        ),
      );
    }

    if (data.items.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              const Icon(Icons.storefront_outlined, size: 40),
              const SizedBox(height: 12),
              Text(
                data.query.isEmpty && data.category.isEmpty
                    ? 'No listings yet. Sellers are onboarding now.'
                    : 'Nothing matched. Try different filters.',
                textAlign: TextAlign.center,
                style: theme.textTheme.bodyLarge,
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(catalogControllerProvider.notifier).search(),
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: data.items.length + (data.canLoadMore ? 1 : 0),
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (BuildContext context, int index) {
          if (index >= data.items.length) {
            return const Padding(
              padding: EdgeInsets.symmetric(vertical: 16),
              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
            );
          }

          final Listing listing = data.items[index];

          return _ListingCard(
            listing: listing,
            onTap: () => context.go('/catalog/${listing.id}'),
          );
        },
      ),
    );
  }
}

class _ListingCard extends StatelessWidget {
  const _ListingCard({required this.listing, required this.onTap});

  final Listing listing;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: SizedBox(
          height: 96,
          child: Row(
            children: <Widget>[
              SizedBox(
                width: 96,
                child: listing.images.isNotEmpty
                    ? Image.network(
                        listing.images.first,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const _ImagePlaceholder(),
                      )
                    : const _ImagePlaceholder(),
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Row(
                        children: <Widget>[
                          Expanded(
                            child: Text(
                              listing.title,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.titleMedium
                                  ?.copyWith(fontWeight: FontWeight.w600),
                            ),
                          ),
                          if (listing.isVerified)
                            Icon(Icons.verified_outlined,
                                size: 18, color: theme.colorScheme.primary),
                        ],
                      ),
                      const Spacer(),
                      if (listing.price != null)
                        Text(
                          '${listing.price!.toStringAsFixed(2)}'
                          '${listing.unit != null ? ' / ${listing.unit}' : ''}',
                          style: theme.textTheme.labelLarge,
                        ),
                      if (listing.moq > 1)
                        Text(
                          'Minimum order: ${listing.moq}',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ImagePlaceholder extends StatelessWidget {
  const _ImagePlaceholder();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: Theme.of(context).colorScheme.surfaceContainerHighest,
      child: Icon(
        Icons.image_outlined,
        size: 28,
        color: Theme.of(context).colorScheme.onSurfaceVariant,
      ),
    );
  }
}

