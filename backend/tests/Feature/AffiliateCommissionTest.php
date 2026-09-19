<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Product;
use App\Models\Referral;
use App\Models\ReferralEvent;
use App\Models\User;
use App\Models\Vendor;
use App\Services\BookingService;
use App\Services\ReferralService;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M21.2: vendors and drivers set an affiliate commission per code, and a
 * conversion is credited (with the commission snapshotted) when the order
 * completes.
 */
class AffiliateCommissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(string $email = 'aff-vendor@test.com'): User
    {
        $user = User::query()->create([
            'name' => 'Affiliate Vendor',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Affiliate Shop',
            'category' => 'agro',
        ]);

        return $user->fresh();
    }

    private function makeDriver(string $email = 'aff-driver@test.com'): User
    {
        return User::query()->create([
            'name' => 'Affiliate Driver',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_DRIVER,
            'is_active' => true,
        ]);
    }

    public function test_a_percentage_commission_is_credited_on_completion(): void
    {
        $vendor = $this->makeVendor();
        app(ReferralService::class)->generateCode($vendor->vendor, 'promo', Referral::COMMISSION_PERCENT, 10);

        $product = $vendor->vendor->products()->create([
            'title' => 'Honey',
            'category' => 'agro',
            'price' => 100,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $booking = app(BookingService::class)->createGuestBooking(
            $vendor->vendor,
            'Guest',
            '+5551234',
            [['product_id' => $product->id, 'quantity' => 2]],
            false,
            null,
            'promo',
        );

        // Walk the lifecycle to completed.
        $service = app(BookingService::class);
        foreach ([Booking::STATUS_CONFIRMED, Booking::STATUS_PICKED_UP, Booking::STATUS_IN_TRANSIT, Booking::STATUS_DELIVERED, Booking::STATUS_COMPLETED] as $status) {
            $service->changeStatus($booking, $status);
        }

        $booking->refresh();

        $this->assertNotNull($booking->completed_at);

        $event = ReferralEvent::query()
            ->where('type', ReferralEvent::TYPE_CONVERSION)
            ->firstOrFail();

        // 10% of (100 x 2) = 20.00
        $this->assertSame('20.00', (string) $event->amount_inr);
        $this->assertSame('200.00', (string) $event->order_value);

        $referral = Referral::query()->where('code', 'promo')->firstOrFail();
        $this->assertSame(1, $referral->conversions_count);

        // Recorded pending — the owner has not approved it yet, so no
        // commission counts (M21.2: the platform pays no one).
        $this->assertSame(ReferralEvent::STATUS_PENDING, $event->status);

        $stats = app(ReferralService::class)->statsForOwner($vendor);
        $this->assertSame(0.0, $stats['total_earnings']);
        $this->assertSame(20.0, $stats['pending_earnings']);
        $this->assertSame(1, $stats['pending_count']);

        // The owner approves it themselves.
        app(ReferralService::class)->approveConversion($event, $vendor);

        $stats = app(ReferralService::class)->statsForOwner($vendor);
        $this->assertSame(20.0, $stats['total_earnings']);
        $this->assertSame(0, $stats['pending_count']);
    }

    public function test_the_owner_approves_or_declines_a_commission_from_the_dashboard(): void
    {
        $vendor = $this->makeVendor('approve@test.com');
        $referral = app(ReferralService::class)->generateCode(
            $vendor->vendor, 'promo', Referral::COMMISSION_PERCENT, 10,
        );

        $pending = ReferralEvent::query()->create([
            'referral_id' => $referral->id,
            'type' => ReferralEvent::TYPE_CONVERSION,
            'status' => ReferralEvent::STATUS_PENDING,
            'order_value' => 200,
            'amount_inr' => 20,
        ]);

        $this->actingAs($vendor)
            ->post(route('dashboard.referrals.approve', $pending))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(ReferralEvent::STATUS_APPROVED, $pending->fresh()->status);
        $this->assertSame($vendor->id, $pending->fresh()->approved_by);

        $declined = ReferralEvent::query()->create([
            'referral_id' => $referral->id,
            'type' => ReferralEvent::TYPE_CONVERSION,
            'status' => ReferralEvent::STATUS_PENDING,
            'order_value' => 100,
            'amount_inr' => 10,
        ]);

        $this->actingAs($vendor)
            ->post(route('dashboard.referrals.reject', $declined))
            ->assertRedirect(route('dashboard'));

        $this->assertSame(ReferralEvent::STATUS_REJECTED, $declined->fresh()->status);
    }

    public function test_only_the_owner_can_approve_a_commission(): void
    {
        $owner = $this->makeVendor('owner-aff@test.com');
        $other = $this->makeVendor('other-aff@test.com');

        $referral = app(ReferralService::class)->generateCode(
            $owner->vendor, 'mine', Referral::COMMISSION_PERCENT, 5,
        );

        $event = ReferralEvent::query()->create([
            'referral_id' => $referral->id,
            'type' => ReferralEvent::TYPE_CONVERSION,
            'status' => ReferralEvent::STATUS_PENDING,
            'amount_inr' => 5,
        ]);

        $this->actingAs($other)
            ->post(route('dashboard.referrals.approve', $event))
            ->assertForbidden();
    }

    public function test_a_fixed_commission_is_credited(): void
    {
        $vendor = $this->makeVendor('fixed@test.com');
        app(ReferralService::class)->generateCode($vendor->vendor, 'flat', Referral::COMMISSION_FIXED, 50);

        $product = $vendor->vendor->products()->create([
            'title' => 'Basket',
            'category' => 'agro',
            'price' => 10,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $booking = app(BookingService::class)->createGuestBooking(
            $vendor->vendor, 'Guest', '+5557777',
            [['product_id' => $product->id, 'quantity' => 1]], false, null, 'flat',
        );

        $service = app(BookingService::class);
        foreach ([Booking::STATUS_CONFIRMED, Booking::STATUS_PICKED_UP, Booking::STATUS_IN_TRANSIT, Booking::STATUS_DELIVERED, Booking::STATUS_COMPLETED] as $status) {
            $service->changeStatus($booking, $status);
        }

        $this->assertSame(
            '50.00',
            (string) ReferralEvent::query()->where('type', ReferralEvent::TYPE_CONVERSION)->value('amount_inr'),
        );
    }

    public function test_a_vendor_can_create_a_code_with_a_commission_from_the_website(): void
    {
        $vendor = $this->makeVendor('webcode@test.com');

        $this->actingAs($vendor)
            ->post(route('dashboard.referrals.store'), [
                'label' => 'instagram',
                'commission_type' => Referral::COMMISSION_PERCENT,
                'commission_value' => 7.5,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('referrals', [
            'owner_user_id' => $vendor->id,
            'commission_type' => Referral::COMMISSION_PERCENT,
            'commission_value' => 7.5,
        ]);
    }

    public function test_a_driver_can_create_an_affiliate_code(): void
    {
        $driver = $this->makeDriver();

        $this->actingAs($driver)
            ->post(route('dashboard.referrals.store'), [
                'commission_type' => Referral::COMMISSION_FIXED,
                'commission_value' => 25,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('referrals', [
            'owner_user_id' => $driver->id,
            'vendor_id' => null,
            'commission_type' => Referral::COMMISSION_FIXED,
        ]);
    }

    public function test_other_roles_cannot_create_affiliate_codes(): void
    {
        $volunteer = User::query()->create([
            'name' => 'Volunteer',
            'email' => 'aff-vol@test.com',
            'email_index' => BlindIndex::make('aff-vol@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VOLUNTEER,
            'is_active' => true,
        ]);

        $this->actingAs($volunteer)
            ->post(route('dashboard.referrals.store'), [
                'commission_type' => Referral::COMMISSION_PERCENT,
                'commission_value' => 5,
            ])
            ->assertSessionHasErrors('role');
    }
}
