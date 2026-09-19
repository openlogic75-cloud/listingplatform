<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\DonationSetting;
use App\Models\Product;
use App\Models\Referral;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ReferralService;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DonationReferralTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(string $email = 'vendor@test.com'): Vendor
    {
        $user = User::query()->create([
            'name' => 'Test Vendor',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        $district = District::query()->first();
        if ($district === null) {
            $district = District::query()->create(['name' => 'Test District', 'is_active' => true]);
        }

        return Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Test Shop',
            'category' => 'traditional',
            'district_id' => $district->id,
        ]);
    }

    public function test_donation_settings_returns_null_when_not_configured(): void
    {
        $response = $this->getJson('/api/v1/donation');

        $response->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_donation_settings_returns_upi_and_qr(): void
    {
        DonationSetting::query()->create([
            'upi_id' => 'shop@upi',
            'qr_path' => 'upi/qr.png',
        ]);

        $response = $this->getJson('/api/v1/donation');

        $response->assertOk();
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('shop@upi', $content['data']['upi_id']);
        $this->assertStringContainsString('upi/qr.png', $content['data']['qr_url']);
        $this->assertStringContainsString('upi://pay?pa=shop%40upi', $content['data']['upi_deep_link']);
    }

    public function test_a_vendor_can_generate_referral_codes(): void
    {
        $vendor = $this->makeVendor();
        $user = $vendor->user;

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/referrals', ['label' => 'my-code']);

        $response->assertCreated()
            ->assertJsonPath('data.code', 'my-code');
    }

    public function test_a_vendor_can_list_referral_stats(): void
    {
        $vendor = $this->makeVendor();
        $user = $vendor->user;

        $service = new ReferralService;
        $service->generateCode($vendor, 'test-code');

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/referrals');

        $response->assertOk()
            ->assertJsonPath('data.codes_count', 1)
            ->assertJsonPath('data.codes.0.code', 'test-code');
    }

    public function test_non_vendor_cannot_create_referral(): void
    {
        $buyer = User::query()->create([
            'name' => 'Buyer',
            'email' => 'buyer@test.com',
            'email_index' => BlindIndex::make('buyer@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR, // Will be overridden
            'is_active' => true,
        ]);
        $buyer->role = 'buyer'; // force non-vendor
        $buyer->save();

        $response = $this->actingAs($buyer, 'sanctum')
            ->postJson('/api/v1/referrals', ['label' => 'test']);

        $response->assertStatus(422);
    }

    public function test_referral_service_records_signups_and_conversions(): void
    {
        $vendor = $this->makeVendor();
        $service = new ReferralService;

        $referral = $service->generateCode($vendor, 'track-me');
        $this->assertNotNull($referral);

        // Simulate a new user signup.
        $newUser = User::query()->create([
            'name' => 'Referred User',
            'email' => 'referred@test.com',
            'email_index' => BlindIndex::make('referred@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => 'buyer',
            'is_active' => true,
        ]);

        $service->recordSignup('track-me', $newUser->id);
        $service->recordConversion('track-me', $newUser->id);

        $stats = $service->stats($vendor);
        $this->assertEquals(1, $stats['total_signups']);
        $this->assertEquals(1, $stats['total_conversions']);
    }

    /**
     * Share buttons (M9.3): the listing detail API returns an owner-aware
     * share URL — plain for everyone, referral-coded for the owner vendor.
     */
    public function test_listing_detail_api_returns_plain_share_url_to_guests(): void
    {
        [$product] = $this->makeCodeAndProduct('shop-code');

        $this->getJson("/api/v1/catalog/{$product->id}")
            ->assertOk()
            ->assertJsonPath('share_url', route('listing.show', $product));
    }

    public function test_listing_detail_api_returns_coded_share_url_to_the_owner(): void
    {
        [$product, , $vendor] = $this->makeCodeAndProduct('shop-code');

        $this->actingAs($vendor->user, 'sanctum')
            ->getJson("/api/v1/catalog/{$product->id}")
            ->assertOk()
            ->assertJsonPath('share_url', route('listing.show', $product).'?ref=shop-code');
    }

    /**
     * @return array{0: Product, 1: string, 2: Vendor}
     */
    private function makeCodeAndProduct(string $code): array
    {
        $vendor = $this->makeVendor();
        $service = new ReferralService;
        $service->generateCode($vendor, $code);

        $product = Product::query()->create([
            'vendor_id' => $vendor->id,
            'category' => 'traditional',
            'title' => 'Shared Basket',
            'price' => 100,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        return [$product, $code, $vendor];
    }

    public function test_listing_page_remembers_ref_query_in_session(): void
    {
        [$product] = $this->makeCodeAndProduct('shop-code');

        $this->get("/listings/{$product->id}?ref=shop-code")
            ->assertOk()
            ->assertSessionHas('referral_code', 'shop-code');
    }

    public function test_listing_page_ignores_unknown_ref_codes(): void
    {
        [$product] = $this->makeCodeAndProduct('shop-code');

        // Unknown codes are ignored, never stored.
        $this->get("/listings/{$product->id}?ref=bogus")
            ->assertOk()
            ->assertSessionMissing('referral_code');
    }

    /**
     * The referral loop stored since M6.3 is now consumed: a web signup with
     * a referral_code in the session attributes the new user to the code,
     * then pops the key so it cannot misattribute later signups.
     */
    public function test_web_signup_consumes_the_referral_session_code(): void
    {
        $vendor = $this->makeVendor();
        $service = new ReferralService;
        $service->generateCode($vendor, 'shop-code');

        $this->withSession(['referral_code' => 'shop-code'])
            ->post(route('register.attempt'), [
                'name' => 'New Driver',
                'email' => 'newdriver@test.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'accept_terms' => true,
                'role' => User::ROLE_DRIVER,
            ])
            ->assertRedirect(route('dashboard'));

        $user = User::query()->where('email_index', BlindIndex::make('newdriver@test.com'))->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('referral_events', [
            'referral_id' => Referral::query()->where('code', 'shop-code')->value('id'),
            'attributed_user_id' => $user->id,
        ]);
        $this->assertEquals(1, Referral::query()->where('code', 'shop-code')->value('signups_count'));
        $this->assertTrue(session()->missing('referral_code'));
    }

    /**
     * M11.1: media uploads normalise to WebP, but the UPI QR must stay
     * lossless PNG — a lossy QR could hurt scannability.
     */
    public function test_the_upi_qr_stays_lossless_png_on_upload(): void
    {
        Storage::fake('public');

        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-qr@test.com',
            'email_index' => BlindIndex::make('admin-qr@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.donation.update'), [
                'upi_id' => 'shop@upi',
                'qr_image' => UploadedFile::fake()->image('qr.png', 240, 240),
            ])
            ->assertRedirect();

        $qr = (string) DonationSetting::query()->firstOrFail()->qr_path;

        $this->assertStringStartsWith('upi/', $qr);
        $this->assertStringEndsWith('.png', $qr);

        $bytes = (string) Storage::disk('public')->get($qr);
        $this->assertSame("\x89PNG", substr($bytes, 0, 4), 'QR is stored as a real PNG');
    }
}
