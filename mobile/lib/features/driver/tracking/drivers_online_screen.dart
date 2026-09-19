import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/locations_provider.dart';
import '../../../core/theme/tokens.dart';
import 'driver_overview_repository.dart';

/// Which drivers are online now, per locality (M4.6). No map, no GPS, no ETA
/// - just an honest per-locality list/count surfaced from the M4.6 endpoint.
/// Guests can open this without an account.
class DriversOnlineScreen extends ConsumerStatefulWidget {
  const DriversOnlineScreen({super.key});

  @override
  ConsumerState<DriversOnlineScreen> createState() =>
      _DriversOnlineScreenState();
}

class _DriversOnlineScreenState extends ConsumerState<DriversOnlineScreen> {
  int? _districtId;
  int? _localityId;

  bool _loading = false;
  String? _error;
  DriversOnline? _overview;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);
    final AsyncValue<List<DistrictWithLocalities>> districts =
        ref.watch(districtsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Drivers online now')),
      body: districts.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (Object error, StackTrace _) => Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              const Icon(Icons.cloud_off_outlined, size: 40),
              const SizedBox(height: Spacing.md),
              const Text('Could not load location data.'),
              const SizedBox(height: Spacing.xs),
              TextButton(
                onPressed: () =>
                    ref.invalidate(districtsProvider),
                child: const Text('Try again'),
              ),
            ],
          ),
        ),
        data: (List<DistrictWithLocalities> districtList) =>
            _body(theme, districtList),
      ),
    );
  }

  Widget _body(
    ThemeData theme,
    List<DistrictWithLocalities> districtList,
  ) {
    final DistrictWithLocalities? district = districtList
        .where((DistrictWithLocalities d) => d.id == _districtId)
        .firstOrNull;

    return ListView(
      padding: const EdgeInsets.all(Spacing.lg),
      children: <Widget>[
        Text(
          'Choose a district to see who is online in each locality. '
          'No live tracking - this is a plain snapshot from the last '
          'driver online/offline toggle.',
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
        const SizedBox(height: Spacing.lg),
        DropdownButtonFormField<int>(
          initialValue: _districtId,
          decoration: const InputDecoration(
            labelText: 'District',
            border: OutlineInputBorder(),
          ),
          items: districtList
              .map((DistrictWithLocalities d) => DropdownMenuItem<int>(
                    value: d.id,
                    child: Text(d.name),
                  ))
              .toList(),
          onChanged: (int? value) {
            setState(() {
              _districtId = value;
              _localityId = null;
              _overview = null;
              _error = null;
            });
            if (value != null) {
              _load(districtId: value);
            }
          },
        ),
        const SizedBox(height: Spacing.md),
        if (district != null)
          DropdownButtonFormField<int?>(
            initialValue: _localityId,
            decoration: const InputDecoration(
              labelText: 'Locality',
              border: OutlineInputBorder(),
            ),
            items: <DropdownMenuItem<int?>>[
              const DropdownMenuItem<int?>(
                value: null,
                child: Text('Whole district'),
              ),
              ...district.localities.map(
                    (LocalityOption l) => DropdownMenuItem<int?>(
                      value: l.id,
                      child: Text(l.name),
                    ),
                  ),
            ],
            onChanged: (int? value) {
              setState(() {
                _localityId = value;
                _overview = null;
                _error = null;
              });
              _load(districtId: _districtId!, localityId: value);
            },
          ),
        const SizedBox(height: Spacing.lg),
        if (_loading)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: Spacing.xl),
            child: Center(child: CircularProgressIndicator()),
          )
        else if (_error != null)
          Column(
            children: <Widget>[
              Text(
                _error!,
                style: theme.textTheme.bodyMedium
                    ?.copyWith(color: theme.colorScheme.error),
              ),
              const SizedBox(height: Spacing.sm),
              OutlinedButton.icon(
                onPressed: () =>
                    _load(districtId: _districtId!, localityId: _localityId),
                icon: const Icon(Icons.refresh),
                label: const Text('Try again'),
              ),
            ],
          )
        else if (_overview != null)
          _results(theme, _overview!),
      ],
    );
  }

  Widget _results(ThemeData theme, DriversOnline overview) {
    final String districtName = districtNameFor(overview.districtId);
    final bool local = _localityId != null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: <Widget>[
        Card(
          child: Padding(
            padding: const EdgeInsets.all(Spacing.lg),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                Text('Online in ${local ? 'this locality' : districtName}',
                    style: theme.textTheme.titleMedium),
                const SizedBox(height: Spacing.sm),
                if (overview.localities.isEmpty)
                  Text(
                    local
                        ? 'No drivers are online in this locality right now.'
                        : 'No drivers are online in $districtName right now.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  )
                else
                  ...overview.localities.map(
                    (OnlineLocality l) => Padding(
                      padding: const EdgeInsets.only(bottom: Spacing.sm),
                      child: Row(
                        children: <Widget>[
                          Icon(
                            Icons.person_pin_circle_outlined,
                            color: theme.colorScheme.primary,
                          ),
                          const SizedBox(width: Spacing.md),
                          Expanded(
                            child: Text(
                              l.name,
                              style: theme.textTheme.bodyLarge,
                            ),
                          ),
                          Text(
                            '${l.onlineDrivers} online',
                            style: theme.textTheme.labelLarge?.copyWith(
                              color: theme.colorScheme.primary,
                              fontWeight: FontWeight.w600,
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
        const SizedBox(height: Spacing.md),
        if (overview.localities.isEmpty &&
            overview.districtFallbackCount > 0)
          Card(
            child: ListTile(
              leading: const Icon(Icons.satellite_alt_outlined),
              title: Text(
                '${overview.districtFallbackCount} driver'
                '${overview.districtFallbackCount == 1 ? '' : 's'} online '
                'elsewhere in $districtName',
              ),
              subtitle: const Text(
                'Nearby drivers in the district can still pick up work here.',
              ),
            ),
          ),
        const SizedBox(height: Spacing.md),
        Text(
          'Updated ${_when(overview.asOf)}.',
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ],
    );
  }

  Future<void> _load({required int districtId, int? localityId}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final DriversOnline overview =
          await ref.read(driverOverviewRepositoryProvider).overview(
                districtId: districtId,
                localityId: localityId,
              );
      if (mounted) {
        setState(() {
          _overview = overview;
          _loading = false;
        });
      }
    } on DioException catch (_) {
      if (mounted) {
        setState(() {
          _error = 'Could not load driver availability.';
          _loading = false;
        });
      }
    }
  }

  String districtNameFor(int districtId) {
    final List<DistrictWithLocalities> districts =
        ref.read(districtsProvider).valueOrNull ?? const <DistrictWithLocalities>[];
    return districts
            .where((DistrictWithLocalities d) => d.id == districtId)
            .firstOrNull
            ?.name ??
        'this district';
  }

  String _when(DateTime asOf) {
    final DateTime local = asOf.toLocal();
    final String hh = local.hour.toString().padLeft(2, '0');
    final String mm = local.minute.toString().padLeft(2, '0');
    return "${local.day.toString().padLeft(2, '0')}/"
        '${local.month.toString().padLeft(2, '0')} '
        '$hh:$mm';
  }
}