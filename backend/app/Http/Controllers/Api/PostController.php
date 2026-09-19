<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Read-only blog feed for the app (M22.1). Published posts only.
 */
class PostController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Post::query()
                ->published()
                ->with('vendor')
                ->latest('published_at')
                ->limit(50)
                ->get()
                ->map(fn (Post $post) => $this->payload($post)),
        ]);
    }

    public function show(Post $post): JsonResponse
    {
        abort_unless(
            $post->status === Post::STATUS_PUBLISHED && $post->published_at !== null,
            404,
        );

        return response()->json([
            'data' => $this->payload($post) + ['body' => $post->body],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->displayExcerpt(),
            'cover_url' => $post->cover_image !== null
                ? Storage::disk('public')->url($post->cover_image)
                : null,
            'images' => collect($post->images ?? [])
                ->map(fn (string $path) => Storage::disk('public')->url($path))
                ->values()
                ->all(),
            'published_at' => $post->published_at?->toIso8601String(),
            'vendor' => $post->vendor === null ? null : [
                'id' => $post->vendor->id,
                'display_name' => $post->vendor->display_name,
            ],
        ];
    }
}
