import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../features/catalog/catalog_repository.dart';
import '../../../features/collector/collector_repository.dart';
import '../listings/listings_controller.dart';

/// Vendor: ask for a farm-produce listing to be collected from its
/// sub-division to a hub district (M28.5). The fee is offered directly to the
/// collector; the platform takes nothing.
class CollectionRequestScreen extends ConsumerStatefulWidget {
  const CollectionRequestScreen({super.key});

  @override
  ConsumerState<CollectionRequestScreen> createState() =>
      _CollectionRequestScreenState();
}

class _CollectionRequestScreenState
    extends ConsumerState<CollectionRequestScreen> {
  List<Listing> _listings = <Listing>[];
  List<CollectionHub> _hubs = <CollectionHub>[];
  bool _loading = true;
  bool _saving = false;
  String? _error;

  int? _listingId;
  int? _hubId;
  final TextEditingController _fee = TextEditingController();
  final TextEditingController _address = TextEditingController();

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _fee.dispose();
    _address.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final List<Listing> mine =
          await ref.read(listingsRepositoryProvider).mine();
      final List<Listing> farm = mine
          .where((Listing l) => l.category == 'farm_reseller')
          .toList();
      final List<CollectionHub> hubs =
          await ref.read(collectorRepositoryProvider).hubs();
      if (mounted) {
        setState(() {
          _listings = farm;
          _hubs = hubs;
          _listingId = farm.isNotEmpty ? farm.first.id : null;
          _hubId = hubs.isNotEmpty ? hubs.first.id : null;
          _loading = false;
        });
      }
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _error = collectionErrorMessage(e);
          _loading = false;
        });
      }
    }
  }

  Future<void> _submit() async {
    final int? listingId = _listingId;
    final int? hubId = _hubId;
    if (listingId == null || hubId == null) {
      return;
    }

    setState(() => _saving = true);
    try {
      await ref.read(collectorRepositoryProvider).request(
            productId: listingId,
            destinationDistrictId: hubId,
            feeInr: num.tryParse(_fee.text.trim()),
            address: _address.text.trim(),
          );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Collection requested.')),
        );
        Navigator.of(context).maybePop();
      }
    } on DioException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(collectionErrorMessage(e))),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Request a collection')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(
                      _error!,
                      style: theme.textTheme.bodyLarge
                          ?.copyWith(color: theme.colorScheme.error),
                    ),
                  ),
                )
              : _listings.isEmpty || _hubs.isEmpty
                  ? Padding(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: <Widget>[
                          Icon(
                            Icons.agriculture_outlined,
                            size: 48,
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                          const SizedBox(height: 12),
                          Text(
                            _listings.isEmpty
                                ? 'You have no farm-produce listings yet. Add '
                                    'one in the farm produce (reseller) '
                                    'category first.'
                                : 'No hub districts are set up yet.',
                            textAlign: TextAlign.center,
                            style: theme.textTheme.bodyLarge,
                          ),
                        ],
                      ),
                    )
                  : ListView(
                      padding: const EdgeInsets.all(16),
                      children: <Widget>[
                        Text(
                          'A collector in the listing\'s sub-division brings it '
                          'to the hub you pick. Agree the fee with them '
                          'directly.',
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                        const SizedBox(height: 16),
                        DropdownButtonFormField<int>(
                          initialValue: _listingId,
                          decoration: const InputDecoration(
                            labelText: 'Farm-produce listing',
                          ),
                          items: _listings
                              .map(
                                (Listing l) => DropdownMenuItem<int>(
                                  value: l.id,
                                  child: Text(l.title),
                                ),
                              )
                              .toList(),
                          onChanged: (int? value) =>
                              setState(() => _listingId = value),
                        ),
                        const SizedBox(height: 16),
                        DropdownButtonFormField<int>(
                          initialValue: _hubId,
                          decoration:
                              const InputDecoration(labelText: 'Hub district'),
                          items: _hubs
                              .map(
                                (CollectionHub h) => DropdownMenuItem<int>(
                                  value: h.id,
                                  child: Text(h.name),
                                ),
                              )
                              .toList(),
                          onChanged: (int? value) =>
                              setState(() => _hubId = value),
                        ),
                        const SizedBox(height: 16),
                        TextField(
                          controller: _fee,
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                          decoration: const InputDecoration(
                            labelText: 'Fee for the collector (₹, optional)',
                            helperText: 'Paid directly to the collector.',
                          ),
                        ),
                        const SizedBox(height: 16),
                        TextField(
                          controller: _address,
                          maxLength: 500,
                          decoration: const InputDecoration(
                            labelText: 'Pickup address or landmark (optional)',
                          ),
                        ),
                        const SizedBox(height: 8),
                        FilledButton(
                          onPressed: _saving ? null : _submit,
                          child: Text(
                            _saving ? 'Sending…' : 'Request collection',
                          ),
                        ),
                      ],
                    ),
    );
  }
}
