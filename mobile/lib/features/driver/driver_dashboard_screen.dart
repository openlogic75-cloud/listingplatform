import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'driver_dashboard_controller.dart';
import 'driver_repository.dart';

/// Driver dashboard: base of operation + online/offline availability.
class DriverDashboardScreen extends ConsumerStatefulWidget {
  const DriverDashboardScreen({super.key});

  @override
  ConsumerState<DriverDashboardScreen> createState() =>
      _DriverDashboardScreenState();
}

class _DriverDashboardScreenState extends ConsumerState<DriverDashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(driverDashboardProvider.notifier).load();
    });
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);
    final DriverDashboardState state = ref.watch(driverDashboardProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Driver dashboard')),
      body: state.loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(16),
              children: <Widget>[
                if (state.error != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Text(
                      state.error!,
                      style: theme.textTheme.bodyMedium
                          ?.copyWith(color: theme.colorScheme.error),
                    ),
                  ),
                Card(
                  child: ListTile(
                    leading: Icon(
                      state.isOnline
                          ? Icons.wifi_tethering
                          : Icons.wifi_tethering_off,
                      color: state.isOnline
                          ? theme.colorScheme.primary
                          : theme.colorScheme.onSurfaceVariant,
                    ),
                    title: Text(
                      state.isOnline ? 'Online' : 'Offline',
                      style: theme.textTheme.titleMedium,
                    ),
                    subtitle: Text(
                      state.isOnline
                          ? 'You are visible for jobs in your localities.'
                          : 'Go online to receive pickup and delivery jobs.',
                    ),
                    trailing: Switch(
                      value: state.isOnline,
                      onChanged: state.saving
                          ? null
                          : (bool v) => ref
                              .read(driverDashboardProvider.notifier)
                              .toggleOnline(v),
                    ),
                    onTap: state.saving
                        ? null
                        : () => ref
                            .read(driverDashboardProvider.notifier)
                            .toggleOnline(!state.isOnline),
                  ),
                ),
                const SizedBox(height: 12),
                _BaseCard(state: state),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: () => context.go('/driver/jobs'),
                  icon: const Icon(Icons.local_shipping_outlined),
                  label: const Text('View available jobs'),
                ),
              ],
            ),
    );
  }
}

class _BaseCard extends StatelessWidget {
  const _BaseCard({required this.state});

  final DriverDashboardState state;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);
    final DriverBase? base = state.base;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text('Base of operation', style: theme.textTheme.titleMedium),
            const SizedBox(height: 4),
            Text(
              base == null
                  ? 'Set one district plus up to five localities you work from.'
                  : 'District ${base.districtId} with '
                      '${base.localityIds.length}/5 localities selected.',
              style: theme.textTheme.bodyMedium
                  ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: FilledButton.icon(
                onPressed:
                    state.saving ? null : () => _showBasePicker(context),
                icon: const Icon(Icons.edit_location_alt_outlined),
                label: Text(base == null ? 'Set base' : 'Change base'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showBasePicker(BuildContext context) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (_) => const _BasePickerSheet(),
    );
  }
}

class _BasePickerSheet extends ConsumerStatefulWidget {
  const _BasePickerSheet();

  @override
  ConsumerState<_BasePickerSheet> createState() => _BasePickerSheetState();
}
class _BasePickerSheetState extends ConsumerState<_BasePickerSheet> {
  int? _districtId;
  final Set<int> _localityIds = <int>{};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final DriverDashboardState state =
          ref.read(driverDashboardProvider);
      final DriverBase? base = state.base;
      if (base != null && mounted) {
        setState(() {
          _districtId = base.districtId;
          _localityIds.addAll(base.localityIds);
        });
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);
    final DriverDashboardState state = ref.watch(driverDashboardProvider);
    final DistrictWithLocalities? district = state.districts
        .where((DistrictWithLocalities d) => d.id == _districtId)
        .firstOrNull;

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(
          left: 16,
          right: 16,
          top: 16,
          bottom: MediaQuery.of(context).viewInsets.bottom + 16,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text('Your base', style: theme.textTheme.titleLarge),
            const SizedBox(height: 12),
            DropdownButtonFormField<int>(
              initialValue: _districtId,
              decoration: const InputDecoration(
                labelText: 'District',
                border: OutlineInputBorder(),
              ),
              items: state.districts
                  .map((DistrictWithLocalities d) => DropdownMenuItem<int>(
                        value: d.id,
                        child: Text(d.name),
                      ))
                  .toList(),
              onChanged: (int? value) {
                setState(() {
                  _districtId = value;
                  _localityIds.clear();
                });
              },
            ),
            const SizedBox(height: 16),
            Text(
              'Localities ${_localityIds.length}/5',
              style: theme.textTheme.labelLarge,
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: (district?.localities ?? const <LocalityOption>[])
                  .map((LocalityOption l) {
                final bool selected = _localityIds.contains(l.id);
                return FilterChip(
                  label: Text(l.name),
                  selected: selected,
                  onSelected: (bool v) {
                    setState(() {
                      if (v && _localityIds.length < 5) {
                        _localityIds.add(l.id);
                      } else if (!v) {
                        _localityIds.remove(l.id);
                      }
                    });
                  },
                );
              }).toList(),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _districtId == null || _localityIds.isEmpty
                    ? null
                    : () async {
                        Navigator.of(context).pop();
                        await ref
                            .read(driverDashboardProvider.notifier)
                            .saveBase(
                              districtId: _districtId!,
                              localityIds: _localityIds.toList(),
                            );
                      },
                child: const Text('Save base'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}