import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'blog_repository.dart';

/// Stories (M22.1/M22.2): blog posts about businesses and farms, including
/// the volunteer-signed verification stories.
class StoriesScreen extends ConsumerStatefulWidget {
  const StoriesScreen({super.key});

  @override
  ConsumerState<StoriesScreen> createState() => _StoriesScreenState();
}

class _StoriesScreenState extends ConsumerState<StoriesScreen> {
  List<Story> _stories = <Story>[];
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
      final List<Story> stories =
          await ref.read(blogRepositoryProvider).list();
      if (mounted) {
        setState(() {
          _stories = stories;
          _loading = false;
        });
      }
    } on DioException {
      if (mounted) {
        setState(() {
          _error = 'Could not load stories. Check your connection.';
          _loading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Stories')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            Text(
              'About the farms, makers and businesses in the marketplace, and '
              'the on-site visits volunteers made.',
              style: theme.textTheme.bodyMedium
                  ?.copyWith(color: theme.colorScheme.onSurfaceVariant),
            ),
            const SizedBox(height: 16),
            if (_loading)
              const Center(child: CircularProgressIndicator())
            else if (_error != null)
              Text(_error!)
            else if (_stories.isEmpty)
              Text(
                'No stories published yet.',
                style: theme.textTheme.bodyLarge,
              )
            else
              for (final Story story in _stories)
                Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  clipBehavior: Clip.antiAlias,
                  child: InkWell(
                    onTap: () => context.go('/stories/${story.slug}'),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        if (story.coverUrl != null)
                          Image.network(
                            story.coverUrl!,
                            height: 150,
                            width: double.infinity,
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                          ),
                        Padding(
                          padding: const EdgeInsets.all(12),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: <Widget>[
                              Text(
                                story.title,
                                style: theme.textTheme.titleMedium
                                    ?.copyWith(fontWeight: FontWeight.w600),
                              ),
                              if (story.publishedAt != null || story.vendorName != null)
                                Padding(
                                  padding: const EdgeInsets.only(top: 4),
                                  child: Text(
                                    <String>[
                                      if (story.publishedAt != null)
                                        '${story.publishedAt!.day.toString().padLeft(2, '0')}/'
                                            '${story.publishedAt!.month.toString().padLeft(2, '0')}/'
                                            '${story.publishedAt!.year}',
                                      if (story.vendorName != null) story.vendorName!,
                                    ].join(' · '),
                                    style: theme.textTheme.bodySmall?.copyWith(
                                      color: theme.colorScheme.onSurfaceVariant,
                                    ),
                                  ),
                                ),
                              const SizedBox(height: 6),
                              Text(story.excerpt, style: theme.textTheme.bodyMedium),
                            ],
                          ),
                        ),
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
