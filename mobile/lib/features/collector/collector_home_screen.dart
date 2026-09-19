import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'collector_repository.dart';

/// Collector dashboard (M28.4): the sub-division you are signed to and the
/// farm-produce collections waiting there. Not the driver flow.
class CollectorHomeScreen extends ConsumerStatefulWidget {
  const CollectorHomeScreen({super.key});

  @override
  ConsumerState<CollectorHomeScreen> createState() =>
      _CollectorHomeScreenState();
}

class _CollectorHomeScreenState extends ConsumerState<CollectorHomeScreen> {
  CollectorAssignment? _assignment;
  List<CollectionJob> _jobs = <CollectionJob>[];
  bool _loading = true;
  String? _error;
  int? _busyId;

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
      final CollectorRepository repo = ref.read(collectorRepositoryProvider);
      final CollectorAssignment? assignment = await repo.assignment();
      final List<CollectionJob> jobs =
          assignment == null ? <CollectionJob>[] : await repo.jobs();
      if (mounted) {
        setState(() {
          _assignment = assignment;
          _jobs = jobs;
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

  Future<void> _act(
    CollectionJob job,
    Future<CollectionJob> Function(CollectorRepository repo) action,
    String message,
  ) async {
    setState(() => _busyId = job.id);
    try {
      await action(ref.read(collectorRepositoryProvider));
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(message)));
      }
      await _load();
    } on DioException catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(collectionErrorMessage(e))),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _busyId = null);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Farm produce collection')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : ListView(
                children: <Widget>[
                  if (_error != null)
                    Padding(
                      padding: const EdgeInsets.all(24),
                      child: Text(
                        _error!,
                        style: theme.textTheme.bodyLarge
                            ?.copyWith(color: theme.colorScheme.error),
                      ),
                    )
                  else ...<Widget>[
                    _assignmentCard(theme),
                    const SizedBox(height: 8),
                    Padding(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
                      child: Text(
                        'Collections in your sub-division',
                        style: theme.textTheme.titleMedium
                            ?.copyWith(fontWeight: FontWeight.w600),
                      ),
                    ),
                    if (_jobs.isEmpty)
                      Padding(
                        padding: const EdgeInsets.all(24),
                        child: Text(
                          'Nothing waiting right now. New farm-produce '
                          'collections will show up here.',
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: theme.colorScheme.onSurfaceVariant,
                          ),
                        ),
                      )
                    else
                      ..._jobs.map((CollectionJob job) => _jobCard(theme, job)),
                  ],
                ],
              ),
      ),
    );
  }

  Widget _assignmentCard(ThemeData theme) {
    final CollectorAssignment? assignment = _assignment;
    final bool active = assignment?.isActive ?? false;

    return Card(
      margin: const EdgeInsets.all(16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Icon(
              active ? Icons.place_outlined : Icons.hourglass_empty_outlined,
              color: theme.colorScheme.primary,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    active ? 'Your sub-division' : 'Awaiting assignment',
                    style: theme.textTheme.titleMedium
                        ?.copyWith(fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    active
                        ? '${assignment?.locality ?? 'Set'}'
                            '${assignment?.district != null ? ', ${assignment!.district}' : ''}'
                        : 'An admin signs one collector to each sub-division '
                            'before collections arrive.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _jobCard(ThemeData theme, CollectionJob job) {
    final bool busy = _busyId == job.id;
    final String? action = switch (job.status) {
      'requested' || 'assigned' => 'Accept',
      'accepted' => 'Mark collected',
      'in_progress' => 'Mark dropped',
      _ => null,
    };
    final String? nextStatus = switch (job.status) {
      'accepted' => 'in_progress',
      'in_progress' => 'completed',
      _ => null,
    };

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Row(
              children: <Widget>[
                Icon(Icons.agriculture_outlined,
                    color: theme.colorScheme.primary),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    'Collect to ${job.destination ?? 'hub'}',
                    style: theme.textTheme.titleMedium
                        ?.copyWith(fontWeight: FontWeight.w600),
                  ),
                ),
                Text(
                  job.status.replaceAll('_', ' '),
                  style: theme.textTheme.labelMedium?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              <String>[
                if (job.locality != null) 'From ${job.locality}',
                if (job.address != null) job.address!,
                if (job.feeInr != null) 'Fee ₹${job.feeInr} paid directly to you',
              ].join('\n'),
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
            if (action != null) ...<Widget>[
              const SizedBox(height: 12),
              Align(
                alignment: Alignment.centerRight,
                child: FilledButton(
                  onPressed: busy
                      ? null
                      : () => _act(
                            job,
                            (CollectorRepository repo) =>
                                nextStatus == null
                                    ? repo.accept(job.id)
                                    : repo.progress(job.id, nextStatus),
                            nextStatus == null
                                ? 'Collection accepted.'
                                : 'Collection updated.',
                          ),
                  child: Text(action),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
