<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendorWithProduct(array $productOverrides = []): Product
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Meera Devi',
            'email' => 'booking-vendor@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'vendor',
            'display_name' => 'Meera Organic Farm',
            'vendor_category' => 'agro',
        ]);

        $register->assertCreated();

        return Product::query()->create($productOverrides + [
            'vendor_id' => Vendor::query()->firstOrFail()->id,
            'category' => 'agro',
            'title' => 'Wildflower honey',
            'price' => 180,
            'unit' => 'jar',
            'moq' => 2,
            'stock' => 40,
            'status' => 'active',
        ]);
    }

    private function guestPayload(Product $product, array $overrides = []): array
    {
        return $overrides + [
            'vendor_id' => $product->vendor_id,
            'contact_name' => 'Guest Buyer',
            'contact_phone' => '9876512345',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ];
    }

    private function vendorToken(): string
    {
        return (string) $this->postJson('/api/v1/auth/login', [
            'email' => 'booking-vendor@example.test',
            'password' => 'secret1234',
        ])->json('token');
    }

    public function test_a_guest_can_book_without_an_account(): void
    {
        $product = $this->makeVendorWithProduct();

        $response = $this->postJson('/api/v1/bookings', $this->guestPayload($product));

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.items.0.quantity', 3)
            ->assertJsonPath('data.items.0.unit_price_snapshot', fn (mixed $price): bool => abs((float) $price - 180.0) < 0.001)
            ->assertJsonPath('data.settled_offline', true);

        // Guest view never contains anyone's contact data.
        $this->assertArrayNotHasKey('contact', $response->json('data'));

        $booking = Booking::query()->firstOrFail();
        $this->assertStringStartsWith('BK-', $booking->code);
        $this->assertSame('Guest Buyer', $booking->contact_name); // decrypted via cast

        // Consent for the contact data is recorded.
        $this->assertDatabaseHas('consents', [
            'subject_type' => 'booking',
            'subject_id' => $booking->id,
            'consent_key' => 'booking_contact',
        ]);
    }

    public function test_quantity_below_moq_is_rejected_per_item(): void
    {
        $product = $this->makeVendorWithProduct(['moq' => 5]);

        $this->postJson('/api/v1/bookings', $this->guestPayload($product, [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.quantity');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_stock_caps_the_maximum(): void
    {
        $product = $this->makeVendorWithProduct(['stock' => 10]);

        $this->postJson('/api/v1/bookings', $this->guestPayload($product, [
            'items' => [['product_id' => $product->id, 'quantity' => 11]],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.quantity');
    }

    public function test_draft_listings_cannot_be_booked(): void
    {
        $product = $this->makeVendorWithProduct(['status' => 'draft']);

        $this->postJson('/api/v1/bookings', $this->guestPayload($product))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.0.product_id');
    }

    public function test_a_reseller_flag_is_stored(): void
    {
        $product = $this->makeVendorWithProduct();

        $this->postJson('/api/v1/bookings', $this->guestPayload($product, ['is_reseller' => true]))
            ->assertCreated()
            ->assertJsonPath('data.is_reseller', true);
    }

        public function test_the_vendor_moves_the_booking_through_the_lifecycle(): void
    {
        $product = $this->makeVendorWithProduct();
        $this->postJson('/api/v1/bookings', $this->guestPayload($product))->assertCreated();

        $booking = Booking::query()->firstOrFail();
        $token = $this->vendorToken();
        $this->assertNotSame('', $token);

        foreach (['confirmed', 'picked_up', 'in_transit', 'delivered', 'completed'] as $status) {
            $this->withToken($token)
                ->postJson("/api/v1/vendor/bookings/{$booking->id}/status", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('data.status', $status);
        }

        // The lifecycle is complete; nothing further is allowed.
        $this->withToken($token)
            ->postJson("/api/v1/vendor/bookings/{$booking->id}/status", ['status' => 'confirmed'])
            ->assertUnprocessable();

        $this->assertSame('completed', $booking->fresh()->status);
    }

    public function test_invalid_transitions_are_rejected(): void
    {
        $product = $this->makeVendorWithProduct();
        $this->postJson('/api/v1/bookings', $this->guestPayload($product))->assertCreated();

        $booking = Booking::query()->firstOrFail();

        // pending -> delivered skips the lifecycle.
        $this->withToken($this->vendorToken())
            ->postJson("/api/v1/vendor/bookings/{$booking->id}/status", ['status' => 'delivered'])
            ->assertUnprocessable();
    }

    public function test_another_vendor_cannot_touch_someone_elses_booking(): void
    {
        $product = $this->makeVendorWithProduct();
        $this->postJson('/api/v1/bookings', $this->guestPayload($product))->assertCreated();

        $booking = Booking::query()->firstOrFail();

        $other = $this->postJson('/api/v1/auth/register', [
            'name' => 'Other Vendor',
            'email' => 'other-vendor@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'vendor',
            'display_name' => 'Other Farm',
            'vendor_category' => 'agro',
        ]);
        $other->assertCreated();

        $this->withToken((string) $other->json('token'))
            ->postJson("/api/v1/vendor/bookings/{$booking->id}/status", ['status' => 'confirmed'])
            ->assertForbidden();
    }

    public function test_vendor_sees_contact_data_but_guests_never_do(): void
    {
        $product = $this->makeVendorWithProduct();
        $this->postJson('/api/v1/bookings', $this->guestPayload($product))->assertCreated();

        $this->withToken($this->vendorToken())
            ->getJson('/api/v1/vendor/bookings')
            ->assertOk()
            ->assertJsonPath('data.0.contact.name', 'Guest Buyer')
            ->assertJsonPath('data.0.contact.phone', '9876512345');
    }

    public function test_guest_lookup_needs_code_and_matching_phone(): void
    {
        $product = $this->makeVendorWithProduct();
        $this->postJson('/api/v1/bookings', $this->guestPayload($product))->assertCreated();

        $booking = Booking::query()->firstOrFail();

        // Wrong phone: rejected.
        $this->postJson('/api/v1/bookings/lookup', [
            'code' => $booking->code,
            'phone' => '9999999999',
        ])->assertUnprocessable();

        // Case does not matter for the code; the phone must match.
        $found = $this->postJson('/api/v1/bookings/lookup', [
            'code' => strtolower($booking->code),
            'phone' => '9876512345',
        ]);

        $found->assertOk()
            ->assertJsonPath('data.code', $booking->code)
            ->assertJsonPath('data.status', 'pending');
        $this->assertArrayNotHasKey('contact', $found->json('data'));
    }

    public function test_a_guest_can_cancel_a_pending_booking_with_their_phone(): void
    {
        $product = $this->makeVendorWithProduct();
        $this->postJson('/api/v1/bookings', $this->guestPayload($product))->assertCreated();

        $booking = Booking::query()->firstOrFail();

        $this->postJson('/api/v1/bookings/cancel', [
            'code' => $booking->code,
            'phone' => '9876512345',
        ])->assertOk()->assertJsonPath('data.status', 'cancelled');

        // Cancelling again is refused (already cancelled).
        $this->postJson('/api/v1/bookings/cancel', [
            'code' => $booking->code,
            'phone' => '9876512345',
        ])->assertUnprocessable();
    }
}

