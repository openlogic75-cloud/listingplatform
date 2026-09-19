<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M22.1: read-only blog API for the app.
 */
class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_api_lists_published_posts_only(): void
    {
        Post::query()->create([
            'title' => 'Api Story',
            'slug' => 'api-story',
            'body' => 'Body',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        Post::query()->create([
            'title' => 'Api Draft',
            'slug' => 'api-draft',
            'body' => 'Body',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->getJson('/api/v1/posts')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Api Story'])
            ->assertJsonMissing(['title' => 'Api Draft']);
    }

    public function test_the_api_returns_a_post_body_and_404s_for_drafts(): void
    {
        Post::query()->create([
            'title' => 'Full Story',
            'slug' => 'full-story',
            'body' => 'The whole story.',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        Post::query()->create([
            'title' => 'Draft Story',
            'slug' => 'draft-story',
            'body' => 'Hidden.',
            'status' => Post::STATUS_DRAFT,
        ]);

        $this->getJson('/api/v1/posts/full-story')
            ->assertOk()
            ->assertJsonPath('data.body', 'The whole story.');

        $this->getJson('/api/v1/posts/draft-story')->assertNotFound();
    }
}
