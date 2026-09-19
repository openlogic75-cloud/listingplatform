<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\BookingService;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M12.4: the website had no vendor bookings view. It now lists bookings and
 * advances them through the same BookingService lifecycle as the app.
 */
class WebVendorBookingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $email): User
    {
        return User::query()->create([
            'name' => 'Test '.$role,
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makeVendor(string $email = 'bookings-vendor@test.com'): User
    {
        $user = $this->makeUser(User::ROLE_VENDOR, $email);

        Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Booking Shop',
            'category' => 'traditional',
        ]);

        return $user->fresh();
    }

    private function makeBooking(User $vendorUser): Booking
    {
        $product = $vendorUser->vendor->products()->create([
            'title' => 'Honey Jar',
            'category' => 'agro',
            'price' => 180,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        return app(BookingService::class)->createGuestBooking(
            $vendorUser->vendor,
            'Guest Buyer',
            '9876505555',
            [['product_id' => $product->id, 'quantity' => 2]],
        );
    }

    public function test_the_vendor_dashboard_links_to_bookings(): void
    {
        $vendor = $this->makeVendor();

        $this->actingAs($vendor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('dashboard.bookings'), false)
            ->assertSee('Bookings');
    }

    public function test_a_vendor_sees_their_bookings_with_contact_and_items(): void
    {
        $vendor = $this->makeVendor();
        $booking = $this->makeBooking($vendor);

        $this->actingAs($vendor)
            ->get(route('dashboard.bookings'))
            ->assertOk()
            ->assertSee($booking->code)
            ->assertSee('Guest Buyer')
            ->assertSee('9876505555')
            ->assertSee('Honey Jar')
            ->assertSee('Pending');
    }

    public function test_a_vendor_can_advance_a_booking_status(): void
    {
        $vendor = $this->makeVendor();
        $booking = $this->makeBooking($vendor);

        $this->actingAs($vendor)
            ->post(route('dashboard.bookings.status', $booking), ['status' => 'confirmed'])
            ->assertRedirect(route('dashboard.bookings'));

        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->fresh()->status);
    }

    public function test_an_invalid_transition_is_rejected(): void
    {
        $vendor = $this->makeVendor();
        $booking = $this->makeBooking($vendor);

        $this->actingAs($vendor)
            ->post(route('dashboard.bookings.status', $booking), ['status' => 'delivered'])
            ->assertSessionHasErrors('status');

        $this->assertSame(Booking::STATUS_PENDING, $booking->fresh()->status);
    }

    public function test_a_vendor_cannot_change_another_vendors_booking(): void
    {
        $owner = $this->makeVendor('owner-b@test.com');
        $other = $this->makeVendor('other-b@test.com');
        $booking = $this->makeBooking($owner);

        $this->actingAs($other)
            ->post(route('dashboard.bookings.status', $booking), ['status' => 'confirmed'])
            ->assertForbidden();

        $this->assertSame(Booking::STATUS_PENDING, $booking->fresh()->status);
    }

    public function test_non_vendors_are_redirected_from_the_bookings_page(): void
    {
        $driver = $this->makeUser(User::ROLE_DRIVER, 'driver-b@test.com');

        $this->actingAs($driver)
            ->get(route('dashboard.bookings'))
            ->assertRedirect(route('dashboard'));
    }
}
