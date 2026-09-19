import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'driver_dashboard_controller.dart';
import 'driver_repository.dart';

/// Jobs waiting inside the driver's base localities.
class DriverJobsScreen extends ConsumerStatefulWidget {
  const DriverJobsScreen({super.key});

  @override
  ConsumerState<DriverJobsScreen> createState() => _DriverJobsScreenState();
}

class _DriverJobsScreenState extends ConsumerState<DriverJobsScreen> {
  List<DriverJob> _jobs = <DriverJob>[];
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
      final List<DriverJob> jobs =
          await ref.read(driverRepositoryProvider).jobs();
      if (mounted) {
        setState(() {
          _jobs = jobs;
          _loading = false;
        });
      }
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _error = driverErrorMessage(e);
          _loading = false;
        });
      }
    }
  }

  Future<void> _accept(DriverJob job) async {
    try {
      await ref.read(driverRepositoryProvider).acceptJob(job.id);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Job accepted.')),
        );
      }
      await _load();
    } on DioException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(driverErrorMessage(e))),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Jobs near your base')),
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
                : _jobs.isEmpty
                    ? ListView(
                        children: <Widget>[
                          Padding(
                            padding: const EdgeInsets.all(24),
                            child: Center(
                              child: Column(
                                children: <Widget>[
                                  Icon(
                                    Icons.local_shipping_outlined,
                                    size: 48,
                                    color: theme.colorScheme.onSurfaceVariant,
                                  ),
                                  const SizedBox(height: 12),
                                  Text(
                                    'No jobs waiting right now.',
                                    style: theme.textTheme.bodyLarge,
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'Go online and make sure your base covers '
                                    'the localities you want to work.',
                                    textAlign: TextAlign.center,
                                    style: theme.textTheme.bodyMedium?.copyWith(
                                      color: theme.colorScheme.onSurfaceVariant,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      )
                    : ListView.builder(
                        itemCount: _jobs.length,
                        itemBuilder: (BuildContext context, int index) {
                          final DriverJob job = _jobs[index];
                          return Card(
                            margin: const EdgeInsets.symmetric(
                              horizontal: 16,
                              vertical: 6,
                            ),
                            child: ListTile(
                              leading: Icon(
                                job.type == 'delivery'
                                    ? Icons.local_shipping_outlined
                                    : Icons.archive_outlined,
                              ),
                              title: Text(
                                '${job.type == 'delivery' ? 'Delivery' : 'Pickup'}'
                                '${job.vendorName != null ? ' - ${job.vendorName}' : ''}',
                                style: theme.textTheme.titleMedium,
                              ),
                              subtitle: Text(
                                <String>[
                                  if (job.address != null) job.address!,
                                  if (job.bookingCode != null)
                                    'Booking ${job.bookingCode}',
                                ].join('\n'),
                              ),
                              trailing: FilledButton(
                                onPressed: () => _accept(job),
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