import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'directory_repository.dart';

/// Public skilled-worker directory (M17.3), filterable by trade. Guests can
/// browse; contact details are not shown (the platform keeps worker PII
/// private).
class WorkersDirectoryScreen extends ConsumerStatefulWidget {
  const WorkersDirectoryScreen({super.key});

  @override
  ConsumerState<WorkersDirectoryScreen> createState() =>
      _WorkersDirectoryScreenState();
}

class _WorkersDirectoryScreenState
    extends ConsumerState<WorkersDirectoryScreen> {
  List<DirectoryCategory> _categories = <DirectoryCategory>[];
  List<DirectoryWorker> _workers = <DirectoryWorker>[];
  int? _selected;
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
      final DirectoryRepository repo = ref.read(directoryRepositoryProvider);
      final List<DirectoryWorker> workers =
          await repo.workers(categoryId: _selected);
      final List<DirectoryCategory> categories =
          _categories.isEmpty ? await repo.workerCategories() : _categories;

      if (mounted) {
        setState(() {
          _workers = workers;
          _categories = categories;
          _loading = false;
        });
      }
    } on DioException {
      if (mounted) {
        setState(() {
          _error = 'Could not load skilled workers. Check your connection.';
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Skilled workers')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            Text(
              'Find people who do the work you need. Filter by trade.',
              style: theme.textTheme.bodyMedium
                  ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
            if (_categories.isNotEmpty) ...<Widget>[
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: <Widget>[
                  FilterChip(
                    label: const Text('All trades'),
                    selected: _selected == null,
                    onSelected: (_) {
                      setState(() => _selected = null);
                      _load();
                    },
                  ),
                  for (final DirectoryCategory category in _categories)
                    FilterChip(
                      label: Text(category.name),
                      selected: _selected == category.id,
                      onSelected: (_) {
                        setState(() => _selected = category.id);
                        _load();
                      },
                    ),
                ],
              ),
            ],
            const SizedBox(height: 16),
            if (_loading)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 40),
                child: Center(child: CircularProgressIndicator()),
              )
            else if (_error != null)
              _Message(icon: Icons.cloud_off_outlined, text: _error!)
            else if (_workers.isEmpty)
              _Message(
                icon: Icons.handyman_outlined,
                text: _selected == null
                    ? 'No skilled workers listed yet.'
                    : 'No workers for that trade yet.',
              )
            else
              for (final DirectoryWorker worker in _workers)
                Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Row(
                          children: <Widget>[
                            Expanded(
                              child: Text(
                                worker.name,
                                style: theme.textTheme.titleMedium
                                    ?.copyWith(fontWeight: FontWeight.w600),
                              ),
                            ),
                            if (worker.district != null)
                              Text(
                                worker.district!,
                                style: theme.textTheme.bodySmall?.copyWith(
                                  color: theme.colorScheme.onSurfaceVariant,
                                ),
                              ),
                          ],
                        ),
                        if (worker.categories.isNotEmpty) ...<Widget>[
                          const SizedBox(height: 8),
                          Wrap(
                            spacing: 6,
                            runSpacing: 6,
                            children: <Widget>[
                              for (final DirectoryCategory skill
                                  in worker.categories)
                                Chip(
                                  label: Text(skill.name),
                                  visualDensity: VisualDensity.compact,
                                ),
                            ],
                          ),
                        ],
                        if (worker.services != null &&
                            worker.services!.isNotEmpty) ...<Widget>[
                          const SizedBox(height: 8),
                          Text(worker.services!,
                              style: theme.textTheme.bodyMedium),
                        ],
                      ],
                    ),
                  ),
                ),
          ],
        ),
      ),
    );
  }
}

class _Message extends StatelessWidget {
  const _Message({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 40),
      child: Column(
        children: <Widget>[
          Icon(icon, size: 40, color: theme.colorScheme.onSurfaceVariant),
          const SizedBox(height: 12),
          Text(text, textAlign: TextAlign.center, style: theme.textTheme.bodyLarge),
        ],
      ),
    );
  }
}
