<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M21.1: vendor sales report (month/quarter/year totals + PDF download),
 * based on completed bookings.
 */
class VendorSalesReportTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(string $email = 'sales-vendor@test.com'): User
    {
        $user = User::query()->create([
            'name' => 'Sales Vendor',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Sales Shop',
            'category' => 'agro',
        ]);

        return $user->fresh();
    }

    private function completedBooking(User $vendorUser, float $price, int $quantity): Booking
    {
        $product = $vendorUser->vendor->products()->create([
            'title' => 'Rice',
            'category' => 'agro',
            'price' => $price,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $booking = Booking::query()->create([
            'code' => 'BK-'.strtoupper(substr(md5((string) microtime(true)), 0, 6)),
            'vendor_id' => $vendorUser->vendor->id,
            'status' => Booking::STATUS_COMPLETED,
            'completed_at' => now(),
            'contact_name' => 'Guest',
            'contact_phone' => '+5550000',
            'contact_phone_index' => BlindIndex::make('+5550000'),
        ]);

        BookingItem::query()->create([
            'booking_id' => $booking->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price_snapshot' => $price,
        ]);

        return $booking;
    }

    public function test_the_dashboard_shows_month_quarter_and_year_totals(): void
    {
        $vendor = $this->makeVendor();
        $this->completedBooking($vendor, 100, 3); // 300
        $this->completedBooking($vendor, 50, 2);  // 100

        $this->actingAs($vendor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sales')
            ->assertSee('400.00')
            ->assertSee('Download PDF report');
    }

    public function test_the_sales_report_downloads_as_pdf(): void
    {
        $vendor = $this->makeVendor();
        $this->completedBooking($vendor, 100, 3);

        $response = $this->actingAs($vendor)->get(route('dashboard.reports.sales'));

        $response->assertOk();
        $this->assertStringContainsString('pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_non_vendors_cannot_download_the_report(): void
    {
        $user = User::query()->create([
            'name' => 'Volunteer',
            'email' => 'no-sales@test.com',
            'email_index' => BlindIndex::make('no-sales@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VOLUNTEER,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.reports.sales'))
            ->assertSessionHasErrors('role');
    }
}
