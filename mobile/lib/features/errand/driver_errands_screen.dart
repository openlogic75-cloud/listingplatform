import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'errand_repository.dart';

/// Errands waiting inside the driver's base localities (M4.5).
class DriverErrandsScreen extends ConsumerStatefulWidget {
  const DriverErrandsScreen({super.key});

  @override
  ConsumerState<DriverErrandsScreen> createState() =>
      _DriverErrandsScreenState();
}

class _DriverErrandsScreenState extends ConsumerState<DriverErrandsScreen> {
  List<Errand> _errands = <Errand>[];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final List<Errand> errands =
          await ref.read(errandRepositoryProvider).available();
      if (mounted) {
        setState(() {
          _errands = errands;
          _loading = false;
        });
      }
    } on DioException catch (_) {
      if (mounted) {
        setState(() {
          _error = 'Could not load errands.';
          _loading = false;
        });
      }
    }
  }

  Future<void> _accept(Errand errand) async {
    try {
      await ref.read(errandRepositoryProvider).accept(errand.id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Errand accepted.')),
        );
      }
      await _load();
    } on DioException catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('That did not go through. Try again.')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Errands near your base')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? ListView(
                    children: <Widget>[
                      Padding(
                        padding: const EdgeInsets.all(24),
                        child: Text(
                          _error!,
                          style: theme.textTheme.bodyLarge
                              ?.copyWith(color: theme.colorScheme.error),
                        ),
                      ),
                    ],
                  )
                : _errands.isEmpty
                    ? ListView(
                        children: <Widget>[
                          Padding(
                            padding: const EdgeInsets.all(24),
                            child: Center(
                              child: Column(
                                children: <Widget>[
                                  Icon(
                                    Icons.directions_run,
                                    size: 48,
                                    color: theme.colorScheme.onSurfaceVariant,
                                  ),
                                  const SizedBox(height: 12),
                                  Text(
                                    'No errands waiting right now.',
                                    style: theme.textTheme.bodyLarge,
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      )
                    : ListView.builder(
                        itemCount: _errands.length,
                        itemBuilder: (BuildContext context, int index) {
                          final Errand errand = _errands[index];
                          return Card(
                            margin: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 6,
                            ),
                            child: ListTile(
                              leading: const Icon(Icons.directions_run),
                              title: Text(
                                errand.description ?? 'Errand',
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: theme.textTheme.titleMedium,
                              ),
                              subtitle: Text(
                                <String>[
                                  if (errand.pickupAddress != null)
                                    'Pickup: ${errand.pickupAddress}',
                                  if (errand.dropAddress != null)
                                    'Drop: ${errand.dropAddress}',
                                  errand.code,
                                ].join('\n'),
                              ),
                              trailing: FilledButton(
                                onPressed: () => _accept(errand),
                                child: const Text('Accept'),
                              ),
                            ),
                          );
                        },
                      ),
      ),
    );
  }
}