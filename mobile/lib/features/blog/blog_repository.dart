import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';

/// A published story (M22.1): about a business or farm. The verification
/// stories published when a visit is approved appear here too.
class Story {
  const Story({
    required this.id,
    required this.title,
    required this.slug,
    required this.excerpt,
    required this.images,
    this.coverUrl,
    this.publishedAt,
    this.vendorName,
    this.body = '',
  });

  final int id;
  final String title;
  final String slug;
  final String excerpt;
  final List<String> images;
  final String? coverUrl;
  final DateTime? publishedAt;
  final String? vendorName;
  final String body;

  factory Story.fromJson(Map<String, dynamic> json) => Story(
        id: (json['id'] as num).toInt(),
        title: json['title'] as String,
        slug: json['slug'] as String,
        excerpt: json['excerpt'] as String? ?? '',
        images: (json['images'] as List<dynamic>? ?? <dynamic>[])
            .map((dynamic e) => e.toString())
            .toList(),
        coverUrl: json['cover_url'] as String?,
        publishedAt: json['published_at'] == null
            ? null
            : DateTime.tryParse(json['published_at'] as String),
        vendorName: json['vendor'] == null
            ? null
            : (json['vendor'] as Map<String, dynamic>)['display_name'] as String?,
        body: json['body'] as String? ?? '',
      );
}

/// Read-only stories feed (M22.1), consumed by the app.
class BlogRepository {
  BlogRepository({required ApiClient apiClient}) : _api = apiClient;

  final ApiClient _api;

  Future<List<Story>> list() async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/posts');

    return (response.data?['data'] as List<dynamic>? ?? <dynamic>[])
        .map((dynamic e) => Story.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<Story> show(String slug) async {
    final Response<Map<String, dynamic>> response =
        await _api.dio.get<Map<String, dynamic>>('/posts/$slug');

    return Story.fromJson(response.data?['data'] as Map<String, dynamic>);
  }
}

final Provider<BlogRepository> blogRepositoryProvider =
    Provider<BlogRepository>((Ref ref) {
  return BlogRepository(apiClient: ref.watch(apiClientProvider));
});
