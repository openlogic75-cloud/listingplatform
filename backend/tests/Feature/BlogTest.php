<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M22.1: public blog shows published stories only.
 */
class BlogTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(string $title, string $status, ?string $body = null): Post
    {
        return Post::query()->create([
            'title' => $title,
            'slug' => Post::uniqueSlug($title),
            'body' => $body ?? 'A story body.',
            'status' => $status,
            'published_at' => $status === Post::STATUS_PUBLISHED ? now() : null,
        ]);
    }

    public function test_the_blog_lists_published_stories_only(): void
    {
        $this->makePost('Live Farm Story', Post::STATUS_PUBLISHED);
        $this->makePost('Unfinished Draft', Post::STATUS_DRAFT);

        $this->get(route('blog'))
            ->assertOk()
            ->assertSee('Live Farm Story')
            ->assertDontSee('Unfinished Draft');
    }

    public function test_a_published_story_renders_escaped_with_a_vendor_link(): void
    {
        $post = $this->makePost('Story With Line Breaks', Post::STATUS_PUBLISHED, "First line\nSecond line");

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('Story With Line Breaks')
            ->assertSee('First line');
    }

    public function test_a_draft_story_is_not_publicly_viewable(): void
    {
        $post = $this->makePost('Secret Draft', Post::STATUS_DRAFT);

        $this->get(route('blog.show', $post))->assertNotFound();
    }
}
