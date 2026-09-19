import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/tokens.dart';
import 'notifications_repository.dart';

/// In-app notification inbox (M8.1): every booking, job, errand and
/// verification event lands here. Push is optional; this is the source of truth.
class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() =>
      _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  InboxPage _page = const InboxPage.empty();
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
      final InboxPage page =
          await ref.read(notificationsRepositoryProvider).inbox();
      if (mounted) {
        setState(() {
          _page = page;
          _loading = false;
        });
      }
    } on DioException catch (e) {
      if (mounted) {
        setState(() {
          _error = e.response?.statusCode == 401
              ? 'Sign in to see your notifications.'
              : 'Could not load notifications. Pull down to try again.';
          _loading = false;
        });
      }
    }
  }

  Future<void> _markRead({String? id}) async {
    try {
      await ref.read(notificationsRepositoryProvider).markRead(id: id);
      await _load();
    } on DioException catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not update notifications.')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final ThemeData theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: <Widget>[
          if (_page.unreadCount > 0)
            TextButton(
              onPressed: () => _markRead(),
              child: const Text('Mark all read'),
            ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? _errorView(theme)
                : _page.items.isEmpty
                    ? _emptyView(theme)
                    : _list(theme),
      ),
    );
  }

  Widget _errorView(ThemeData theme) {
    return ListView(
      padding: const EdgeInsets.all(Spacing.xl),
      children: <Widget>[
        Text(
          _error!,
          style: theme.textTheme.bodyLarge
              ?.copyWith(color: theme.colorScheme.error),
        ),
      ],
    );
  }

  Widget _emptyView(ThemeData theme) {
    return ListView(
      padding: const EdgeInsets.all(Spacing.xl),
      children: <Widget>[
        const SizedBox(height: Spacing.xxxl),
        Icon(
          Icons.notifications_none_outlined,
          size: 48,
          color: theme.colorScheme.onSurfaceVariant,
        ),
        const SizedBox(height: Spacing.md),
        Text(
          'Nothing yet.',
          textAlign: TextAlign.center,
          style: theme.textTheme.titleMedium,
        ),
        const SizedBox(height: Spacing.xs),
        Text(
          'Booking updates, job offers and verification results appear here.',
          textAlign: TextAlign.center,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ],
    );
  }

  Widget _list(ThemeData theme) {
    return ListView.separated(
      padding: const EdgeInsets.all(Spacing.lg),
      itemCount: _page.items.length,
      separatorBuilder: (BuildContext context, int index) =>
          const SizedBox(height: Spacing.sm),
      itemBuilder: (BuildContext context, int index) {
        final InboxNotification item = _page.items[index];
        return Card(
          child: ListTile(
            leading: Icon(
              item.isUnread
                  ? Icons.notifications_active_outlined
                  : Icons.notifications_none_outlined,
              color: item.isUnread
                  ? theme.colorScheme.primary
                  : theme.colorScheme.onSurfaceVariant,
            ),
            title: Text(
              item.title?.isNotEmpty == true ? item.title! : 'Notification',
              style: theme.textTheme.titleSmall?.copyWith(
                fontWeight: item.isUnread ? FontWeight.w600 : FontWeight.w400,
              ),
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: <Widget>[
                if (item.body != null && item.body!.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: Spacing.xs),
                    child: Text(item.body!),
                  ),
                if (item.createdAt != null)
                  Padding(
                    padding: const EdgeInsets.only(top: Spacing.xs),
                    child: Text(
                      _timestampLabel(item.createdAt!),
                      style: theme.textTheme.labelSmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
              ],
            ),
            onTap: item.isUnread ? () => _markRead(id: item.id) : null,
          ),
        );
      },
    );
  }

  /// Plain relative label. No external date package: the app ships with none.
  String _timestampLabel(DateTime at) {
    final Duration age = DateTime.now().difference(at);
    if (age.inMinutes < 1) {
      return 'Just now';
    }
    if (age.inMinutes < 60) {
      return '${age.inMinutes} min ago';
    }
    if (age.inHours < 24) {
      return '${age.inHours} h ago';
    }
    if (age.inDays < 7) {
      return '${age.inDays} d ago';
    }
    return '${at.day.toString().padLeft(2, '0')}/'
        '${at.month.toString().padLeft(2, '0')}/${at.year}';
  }
}