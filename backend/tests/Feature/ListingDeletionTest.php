<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Media;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Vendor delayed listing deletion (M48.1): drafts can go anytime; published
 * listings only after 7 full days unpublished. Orders block deletion.
 */
class ListingDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(string $email = 'delete-vendor@test.com'): User
    {
        $user = User::query()->create([
            'name' => 'Delete Vendor',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Delete Shop',
            'category' => 'traditional',
        ]);

        return $user->fresh();
    }

    private function makeProduct(User $vendor, array $overrides = []): Product
    {
        return $vendor->vendor->products()->create(array_merge([
            'title' => 'Deletable Basket',
            'category' => Product::CATEGORY_TRADITIONAL,
            'price' => 100,
            'moq' => 1,
            'status' => Product::STATUS_DRAFT,
        ], $overrides));
    }

    public function test_a_vendor_can_delete_a_draft_listing(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor);

        $this->actingAs($vendor)
            ->delete(route('vendor.listings.destroy', $product))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_an_active_listing_cannot_be_deleted(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor, ['status' => Product::STATUS_ACTIVE]);

        $this->actingAs($vendor)
            ->delete(route('vendor.listings.destroy', $product))
            ->assertSessionHasErrors('product');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_a_recently_unpublished_listing_cannot_be_deleted_yet(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor, [
            'status' => Product::STATUS_INACTIVE,
            'unpublished_at' => now()->subDays(3),
        ]);

        $this->actingAs($vendor)
            ->delete(route('vendor.listings.destroy', $product))
            ->assertSessionHasErrors('product');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_a_listing_unpublished_for_a_week_can_be_deleted(): void
    {
        Storage::fake('public');
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor, [
            'status' => Product::STATUS_ARCHIVED,
            'unpublished_at' => now()->subDays(8),
            'images' => ['products/old-basket.webp'],
        ]);
        Storage::disk('public')->put('products/old-basket.webp', 'fake-image');
        Media::query()->create([
            'disk' => 'public',
            'path' => 'products/old-basket.webp',
            'mime_type' => 'image/webp',
            'size' => 10,
            'uploaded_by' => $vendor->id,
        ]);

        $this->actingAs($vendor)
            ->delete(route('vendor.listings.destroy', $product))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('media', ['path' => 'products/old-basket.webp']);
        Storage::disk('public')->assertMissing('products/old-basket.webp');
    }

    public function test_a_listing_with_orders_cannot_be_deleted(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor, [
            'status' => Product::STATUS_ARCHIVED,
            'unpublished_at' => now()->subDays(30),
        ]);
        $booking = Booking::query()->create([
            'vendor_id' => $vendor->vendor->id,
            'code' => 'BK-DELETE1',
            'status' => Booking::STATUS_COMPLETED,
            'contact_name' => 'Guest',
            'contact_phone' => '+5550000',
            'contact_phone_index' => BlindIndex::make('+5550000'),
        ]);
        BookingItem::query()->create([
            'booking_id' => $booking->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price_snapshot' => 100,
        ]);

        $this->actingAs($vendor)
            ->delete(route('vendor.listings.destroy', $product))
            ->assertSessionHasErrors('product');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_another_vendor_cannot_delete_the_listing(): void
    {
        $vendor = $this->makeVendor();
        $other = $this->makeVendor('other-vendor@test.com');
        $product = $this->makeProduct($vendor);

        $this->actingAs($other)
            ->delete(route('vendor.listings.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_unpublishing_stamps_the_date_for_the_delete_rule(): void
    {
        $vendor = $this->makeVendor();
        $product = $this->makeProduct($vendor, ['status' => Product::STATUS_ACTIVE]);

        $this->actingAs($vendor)
            ->put(route('vendor.listings.status', $product), ['status' => 'inactive'])
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($product->fresh()->unpublished_at);
    }
}
