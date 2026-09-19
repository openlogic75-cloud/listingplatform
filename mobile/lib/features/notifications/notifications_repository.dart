import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';

/// A row in the in-app notification inbox (M8.1). Written by the backend on
/// booking, job, errand and verification events whether or not push is on.
class InboxNotification {
  const InboxNotification({
    required this.id,
    required this.title,
    required this.body,
    required this.readAt,
    required this.createdAt,
  });

  final String id;
  final String? title;
  final String? body;
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isUnread => readAt == null;

  factory InboxNotification.fromJson(Map<String, dynamic> json) =>
      InboxNotification(
        id: json['id'] as String,
        title: json['title'] as String?,
        body: json['body'] as String?,
        readAt: json['read_at'] == null
            ? null
            : DateTime.tryParse(json['read_at'] as String),
        createdAt: json['created_at'] == null
            ? null
            : DateTime.tryParse(json['created_at'] as String),
      );
}

/// Inbox page: the rows plus the unread counter the badge uses.
class InboxPage {
  const InboxPage({required this.items, required this.unreadCount});

  const InboxPage.empty()
      : items = const <InboxNotification>[],
        unreadCount = 0;

  final List<InboxNotification> items;
  final int unreadCount;
}

/// Notification inbox APIs (M8.1).
class NotificationsRepository {
  NotificationsRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<InboxPage> inbox() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/notifications');

    final List<InboxNotification> items =
        (response.data?['data'] as List<dynamic>? ?? <dynamic>[])
            .map((dynamic e) =>
                InboxNotification.fromJson(e as Map<String, dynamic>))
            .toList();

    return InboxPage(
      items: items,
      unreadCount: (response.data?['unread_count'] as num?)?.toInt() ?? 0,
    );
  }

  /// Marks one notification read, or the whole inbox when [id] is null.
  /// Returns the new unread count.
  Future<int> markRead({String? id}) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.post<Map<String, dynamic>>(
      '/notifications/read',
      data: <String, dynamic>{
        if (id != null) 'id': id,
      },
    );

    return (response.data?['unread_count'] as num?)?.toInt() ?? 0;
  }
}

final Provider<NotificationsRepository> notificationsRepositoryProvider =
    Provider<NotificationsRepository>((Ref ref) {
  return NotificationsRepository(apiClient: ref.watch(apiClientProvider));
});

/// Unread count for badges; refreshed whenever an inbox screen loads.
final FutureProvider<InboxPage> inboxProvider = FutureProvider<InboxPage>(
  (Ref ref) => ref.watch(notificationsRepositoryProvider).inbox(),
);