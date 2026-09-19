<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Verification;
use App\Models\VerificationVolunteer;
use App\Services\VerificationReviewService;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M25.1: the platform provides the visit questionnaire; on approval the
 * volunteer's answers and photos become a signed blog story that vouches for
 * the listing, and the badge carries the volunteer's details.
 */
class VerificationStoryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::query()->create([
            'name' => ucfirst($role).' User',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_the_platform_publishes_the_visit_questionnaire(): void
    {
        $this->getJson('/api/v1/verification-questionnaire')
            ->assertOk()
            ->assertJsonPath('data.0.id', 'seller_identity')
            ->assertJsonCount(6, 'data');
    }

    public function test_approval_creates_a_volunteer_signed_story_and_badge_details(): void
    {
        // A vendor with a product, so the badge shows on the listing page.
        $vendorUser = $this->user(User::ROLE_VENDOR, 'story-vendor@test.com');
        $vendor = Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'display_name' => 'Riverside Farm',
            'category' => 'agro',
        ]);
        $product = $vendor->products()->create([
            'title' => 'Riverside Honey',
            'category' => 'agro',
            'price' => 300,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        // A volunteer with a photo set.
        $volunteerUser = $this->user(User::ROLE_VOLUNTEER, 'story-vol@test.com');
        $volunteer = VerificationVolunteer::query()->create([
            'user_id' => $volunteerUser->id,
            'photo_path' => 'avatars/volunteer.webp',
        ]);

        $verification = Verification::query()->create([
            'volunteer_id' => $volunteer->id,
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'notes' => 'Met the owner on site; everything checked out.',
            'checklist' => [
                'seller_identity' => true,
                'address' => true,
                'goods' => true,
                'photos' => true,
            ],
            'evidence' => ['evidence/shop-front.jpg', 'evidence/honey.jpg'],
            'status' => Verification::STATUS_SUBMITTED,
        ]);

        app(VerificationReviewService::class)->approve($verification, $this->user(User::ROLE_ADMIN, 'story-admin@test.com'));

        $badge = Badge::query()->firstOrFail();
        $post = Post::query()->firstOrFail();

        // Badge carries the volunteer's name + photo and links to the story.
        $this->assertSame($volunteerUser->name, $badge->volunteer_name);
        $this->assertSame('avatars/volunteer.webp', $badge->volunteer_photo);
        $this->assertSame($post->id, $badge->post_id);

        // The story is the volunteer's, with their photos and answers.
        $this->assertSame(Post::STATUS_PUBLISHED, $post->status);
        $this->assertSame($volunteerUser->id, $post->author_id);
        $this->assertSame($vendor->id, $post->vendor_id);
        $this->assertSame(['evidence/shop-front.jpg', 'evidence/honey.jpg'], $post->images);
        $this->assertSame('evidence/shop-front.jpg', $post->cover_image);
        $this->assertStringContainsString('Site visit by', $post->body);
        $this->assertStringContainsString('photos', $post->body);
    }

    public function test_the_listing_page_links_to_the_verification_story(): void
    {
        $vendorUser = $this->user(User::ROLE_VENDOR, 'link-vendor@test.com');
        $vendor = Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'display_name' => 'Link Farm',
            'category' => 'agro',
        ]);
        $product = $vendor->products()->create([
            'title' => 'Linked Honey',
            'category' => 'agro',
            'price' => 100,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $volunteerUser = $this->user(User::ROLE_VOLUNTEER, 'link-vol@test.com');
        $volunteer = VerificationVolunteer::query()->create(['user_id' => $volunteerUser->id]);

        $verification = Verification::query()->create([
            'volunteer_id' => $volunteer->id,
            'subject_type' => Product::class,
            'subject_id' => $product->id,
            'notes' => 'All good.',
            'checklist' => ['goods' => true],
            'evidence' => ['evidence/one.jpg'],
            'status' => Verification::STATUS_SUBMITTED,
        ]);

        app(VerificationReviewService::class)->approve($verification, $this->user(User::ROLE_ADMIN, 'link-admin@test.com'));

        $post = Post::query()->firstOrFail();

        $this->get(route('listing.show', $product))
            ->assertOk()
            ->assertSee(route('blog.show', $post), false)
            ->assertSee('not a platform guarantee');
    }
}
