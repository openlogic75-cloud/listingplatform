<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebBookingTest extends TestCase
{
    use RefreshDatabase;

    private function makeActiveProduct(): Product
    {
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Meera Devi',
            'email' => 'web-vendor@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'vendor',
            'display_name' => 'Meera Organic Farm',
            'vendor_category' => 'agro',
        ]);

        $register->assertCreated();

        return Product::query()->create([
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

    public function test_the_listing_page_shows_a_booking_form_meeting_moq(): void
    {
        $product = $this->makeActiveProduct();

        $this->get("/listings/{$product->id}")
            ->assertOk()
            ->assertSee('Book this item')
            ->assertSee('minimum 2', false);
    }

    /**
     * M15.4: the booking form is a modern card with a quantity stepper and
     * a live estimate (progressive enhancement; the number input still works).
     */
    public function test_the_booking_form_renders_the_stepper_and_estimate(): void
    {
        $product = $this->makeActiveProduct();

        $this->get("/listings/{$product->id}")
            ->assertOk()
            ->assertSee('qty-stepper', false)
            ->assertSee('data-total', false)
            ->assertSee('No account needed')
            ->assertSee('booking-form.js', false)
            // price 180 x MOQ 2 = 360.00 as the initial estimate.
            ->assertSee('360.00');
    }

    public function test_a_web_visitor_can_place_a_guest_booking(): void
    {
        $product = $this->makeActiveProduct();

        $response = $this->post("/listings/{$product->id}/book", [
            'contact_name' => 'Web Guest',
            'contact_phone' => '9876501234',
            'quantity' => 3,
        ]);

        $response->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame('Web Guest', $booking->contact_name);
        $this->assertDatabaseHas('consents', ['subject_id' => $booking->id, 'consent_key' => 'booking_contact']);

        $this->get("/bookings/{$booking->code}")
            ->assertOk()
            ->assertSee($booking->code)
            ->assertSee('Booking received');
    }

    public function test_the_web_form_rejects_quantity_below_moq_and_keeps_input(): void
    {
        $product = $this->makeActiveProduct();

        $response = $this->from("/listings/{$product->id}")->post("/listings/{$product->id}/book", [
            'contact_name' => 'Web Guest',
            'contact_phone' => '9876501234',
            'quantity' => 1,
        ]);

        $response->assertRedirect("/listings/{$product->id}");
        $response->assertSessionHasErrors();

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_the_success_page_404s_for_unknown_codes(): void
    {
        $this->get('/bookings/BK-NOPE00')->assertNotFound();
    }
}
