<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M45.2: new and edited vendor listings wait for admin approval.
 */
class ListingApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::query()->create([
            'name' => ucfirst($role).' User',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'email_verified_at' => now(),
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    public function test_new_listing_is_pending_until_admin_approves_it(): void
    {
        config()->set('app.require_listing_approval', true);
        $vendorUser = $this->user(User::ROLE_VENDOR, 'approval-vendor@test.com');
        $admin = $this->user(User::ROLE_ADMIN, 'approval-admin@test.com');
        $vendor = Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'display_name' => 'Approval Farm',
            'category' => Vendor::CATEGORY_AGRO,
        ]);

        $response = $this->actingAs($vendorUser, 'sanctum')->postJson('/api/v1/listings', [
            'title' => 'Pending tomatoes',
            'category' => Product::CATEGORY_AGRO,
            'price' => 40,
            'moq' => 2,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $response->assertCreated()->assertJsonPath('data.status', Product::STATUS_PENDING);

        $product = Product::query()->where('vendor_id', $vendor->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.listings.approve', $product))
            ->assertRedirect(route('admin.listings.index'));

        $this->assertSame(Product::STATUS_ACTIVE, $product->fresh()->status);
    }

    public function test_editing_an_active_listing_returns_it_to_pending(): void
    {
        config()->set('app.require_listing_approval', true);
        $vendorUser = $this->user(User::ROLE_VENDOR, 'edit-approval-vendor@test.com');
        $vendor = Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'display_name' => 'Edit Farm',
            'category' => Vendor::CATEGORY_AGRO,
        ]);
        $product = $vendor->products()->create([
            'title' => 'Published tomatoes',
            'category' => Product::CATEGORY_AGRO,
            'price' => 40,
            'moq' => 2,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($vendorUser, 'sanctum')
            ->putJson("/api/v1/listings/{$product->id}", [
                'title' => 'Updated tomatoes',
                'category' => Product::CATEGORY_AGRO,
                'price' => 45,
                'moq' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', Product::STATUS_PENDING);
    }

    public function test_listing_images_require_public_visibility_consent(): void
    {
        config()->set('app.require_listing_approval', true);
        config()->set('app.require_listing_image_consent', true);
        $vendorUser = $this->user(User::ROLE_VENDOR, 'image-consent-vendor@test.com');
        $vendor = Vendor::query()->create([
            'user_id' => $vendorUser->id,
            'display_name' => 'Image Consent Farm',
            'category' => Vendor::CATEGORY_AGRO,
        ]);

        $this->actingAs($vendorUser, 'sanctum')
            ->postJson('/api/v1/listings', [
                'title' => 'Public image listing',
                'category' => Product::CATEGORY_AGRO,
                'price' => 40,
                'moq' => 2,
                'images' => ['products/not-uploaded.webp'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image_public_consent');
    }
}
