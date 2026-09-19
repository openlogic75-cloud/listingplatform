import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import 'blog_repository.dart';

/// Loads one story by slug.
final FutureProviderFamily<Story, String> storyProvider =
    FutureProvider.family<Story, String>((Ref ref, String slug) {
  return BlogRepository(apiClient: ref.watch(apiClientProvider)).show(slug);
});

/// One story (M22.2): the text, and the photos the volunteer took.
class StoryScreen extends ConsumerWidget {
  const StoryScreen({super.key, required this.slug});

  final String slug;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final ThemeData theme = Theme.of(context);
    final AsyncValue<Story> story = ref.watch(storyProvider(slug));

    return Scaffold(
      appBar: AppBar(title: const Text('Story')),
      body: story.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (Object error, StackTrace _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: <Widget>[
                const Icon(Icons.cloud_off_outlined, size: 40),
                const SizedBox(height: 12),
                const Text('Could not load this story.'),
                const SizedBox(height: 8),
                TextButton(
                  onPressed: () => ref.invalidate(storyProvider(slug)),
                  child: const Text('Try again'),
                ),
              ],
            ),
          ),
        ),
        data: (Story data) => ListView(
          padding: const EdgeInsets.all(16),
          children: <Widget>[
            if (data.coverUrl != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.network(
                  data.coverUrl!,
                  height: 220,
                  width: double.infinity,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                ),
              ),
            const SizedBox(height: 16),
            Text(
              data.title,
              style: theme.textTheme.headlineSmall
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            if (data.publishedAt != null || data.vendorName != null)
              Text(
                <String>[
                  if (data.publishedAt != null)
                    '${data.publishedAt!.day.toString().padLeft(2, '0')}/'
                        '${data.publishedAt!.month.toString().padLeft(2, '0')}/'
                        '${data.publishedAt!.year}',
                  if (data.vendorName != null) data.vendorName!,
                ].join(' · '),
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
            const SizedBox(height: 12),
            Text(data.body, style: theme.textTheme.bodyLarge),
            if (data.images.isNotEmpty) ...<Widget>[
              const SizedBox(height: 24),
              Text('Photos from the visit', style: theme.textTheme.titleMedium),
              const SizedBox(height: 8),
              GridView.count(
                crossAxisCount: 2,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                mainAxisSpacing: 8,
                crossAxisSpacing: 8,
                children: <Widget>[
                  for (final String url in data.images)
                    ClipRRect(
                      borderRadius: BorderRadius.circular(12),
                      child: Image.network(
                        url,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => const SizedBox.shrink(),
                      ),
                    ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
