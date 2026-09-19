<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * M22.1: admins write and publish blog stories promoting businesses/farms.
 */
class AdminPostTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'blog-admin@test.com'): User
    {
        return User::query()->create([
            'name' => 'Blog Admin',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_an_admin_can_create_a_draft_story(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.posts.store'), [
                'title' => 'A New Agro Farm in Medziphema',
                'excerpt' => 'Fresh vegetables, grown locally.',
                'body' => "Line one.\n\nLine two.",
                'status' => 'draft',
            ])
            ->assertRedirect(route('admin.posts.index'));

        $post = Post::query()->firstOrFail();

        $this->assertSame('a-new-agro-farm-in-medziphema', $post->slug);
        $this->assertSame(Post::STATUS_DRAFT, $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame($admin->id, $post->author_id);
    }

    public function test_publishing_stamps_the_date(): void
    {
        $admin = $this->admin('publish@test.com');

        $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'Published Story',
            'body' => 'Body text',
            'status' => 'published',
        ]);

        $post = Post::query()->firstOrFail();

        $this->assertSame(Post::STATUS_PUBLISHED, $post->status);
        $this->assertNotNull($post->published_at);
    }

    public function test_a_cover_image_is_stored_as_webp(): void
    {
        Storage::fake('public');
        $admin = $this->admin('cover@test.com');

        $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'With Cover',
            'body' => 'Body',
            'status' => 'published',
            'cover_image' => UploadedFile::fake()->image('farm.png', 400, 300),
        ])->assertRedirect(route('admin.posts.index'));

        $post = Post::query()->firstOrFail();

        $this->assertNotNull($post->cover_image);
        $this->assertStringEndsWith('.webp', $post->cover_image);
    }

    public function test_an_admin_can_edit_and_delete_a_story(): void
    {
        $admin = $this->admin('edit@test.com');
        $this->actingAs($admin)->post(route('admin.posts.store'), [
            'title' => 'Original',
            'body' => 'Body',
            'status' => 'draft',
        ]);
        $post = Post::query()->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.posts.update', $post), [
                'title' => 'Renamed Story',
                'body' => 'Updated body',
                'status' => 'published',
            ])
            ->assertRedirect(route('admin.posts.index'));

        $post->refresh();
        $this->assertSame('Renamed Story', $post->title);
        $this->assertSame('renamed-story', $post->slug);
        $this->assertSame(Post::STATUS_PUBLISHED, $post->status);

        $this->actingAs($admin)
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_non_admins_cannot_manage_stories(): void
    {
        $vendor = User::query()->create([
            'name' => 'Vendor',
            'email' => 'blog-vendor@test.com',
            'email_index' => BlindIndex::make('blog-vendor@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        $this->actingAs($vendor)
            ->get(route('admin.posts.index'))
            ->assertForbidden();
    }
}
