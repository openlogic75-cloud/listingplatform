<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Consent;
use App\Models\DataRequest;
use App\Models\DonationSetting;
use App\Models\Media;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * M7.5 - Retention & DPDP scheduled jobs.
 */
class RetentionSweepTest extends TestCase
{
    use RefreshDatabase;

    private function pendingBooking(int $daysAgo): Booking
    {
        $vendor = Vendor::query()->create([
            'user_id' => User::query()->create([
                'name' => 'V',
                'email' => 'v'.uniqid().'@test.com',
                'email_index' => BlindIndex::make('v'.uniqid().'@test.com'),
                'password' => bcrypt('Password123!'),
                'role' => User::ROLE_VENDOR,
                'is_active' => true,
            ])->id,
            'display_name' => 'V',
            'category' => 'traditional',
        ]);

        $code = 'BK-'.strtoupper(uniqid());

        $booking = Booking::query()->create([
            'vendor_id' => $vendor->id,
            'status' => Booking::STATUS_PENDING,
            'contact_name' => 'Guest',
            'contact_phone' => '+5550000',
            'contact_phone_index' => BlindIndex::make('+5550000'),
            'code' => $code,
        ]);

        DB::table('bookings')
            ->where('id', $booking->id)
            ->update(['created_at' => now()->subDays($daysAgo)]);

        return $booking->fresh();
    }

    private function media(string $path, int $daysAgo): Media
    {
        $media = Media::query()->create([
            'disk' => 'public',
            'path' => $path,
            'mime_type' => 'image/png',
            'size' => 10,
        ]);

        DB::table('media')
            ->where('id', $media->id)
            ->update(['created_at' => now()->subDays($daysAgo)]);

        return $media->fresh();
    }

    public function test_cancels_stale_pending_bookings(): void
    {
        $this->pendingBooking(60);
        $fresh = $this->pendingBooking(1);

        Artisan::call('retention:sweep', ['--sweeps' => 'stale-bookings']);

        $this->assertDatabaseHas('bookings', ['id' => $fresh->id, 'status' => Booking::STATUS_PENDING]);
        $this->assertSame(1, Booking::query()->where('status', Booking::STATUS_CANCELLED)->count());
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->pendingBooking(60);

        Artisan::call('retention:sweep', [
            '--sweeps' => 'stale-bookings',
            '--dry-run' => true,
        ]);

        $this->assertSame(1, Booking::query()->where('status', Booking::STATUS_PENDING)->count());
    }

    public function test_deletes_unreferenced_old_media_and_keeps_referenced(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('products/keep.jpg', 'keep');
        Storage::disk('public')->put('products/orphan.jpg', 'orphan');
        Storage::disk('public')->put('qr/qr.png', 'qr');

        $vendor = Vendor::query()->create([
            'user_id' => User::query()->create([
                'name' => 'V',
                'email' => 'v'.uniqid().'@test.com',
                'email_index' => BlindIndex::make('v'.uniqid().'@test.com'),
                'password' => bcrypt('Password123!'),
                'role' => User::ROLE_VENDOR,
                'is_active' => true,
            ])->id,
            'display_name' => 'V',
            'category' => 'traditional',
        ]);

        Product::query()->create([
            'vendor_id' => $vendor->id,
            'title' => 'P',
            'category' => 'traditional',
            'status' => 'active',
            'images' => ['products/keep.jpg'],
        ]);

        DonationSetting::query()->create(['upi_id' => 'x@upi', 'qr_path' => 'qr/qr.png']);

        $this->media('products/keep.jpg', 200);
        $this->media('products/orphan.jpg', 200);
        $this->media('qr/qr.png', 200);

        Artisan::call('retention:sweep', ['--sweeps' => 'media']);

        Storage::disk('public')->assertExists('products/keep.jpg');
        Storage::disk('public')->assertMissing('products/orphan.jpg');
        Storage::disk('public')->assertExists('qr/qr.png');

        $this->assertDatabaseMissing('media', ['path' => 'products/orphan.jpg']);
        $this->assertDatabaseHas('media', ['path' => 'products/keep.jpg']);
    }

    public function test_keeps_fresh_media_within_window(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/recent.jpg', 'recent');

        $this->media('products/recent.jpg', 10);

        Artisan::call('retention:sweep', ['--sweeps' => 'media']);

        Storage::disk('public')->assertExists('products/recent.jpg');
    }

    public function test_revokes_expired_consents_but_keeps_fresh(): void
    {
        $old = Consent::query()->create([
            'subject_type' => 'guest',
            'subject_id' => 1,
            'consent_key' => 'booking_contact',
            'text_version' => '2026-01-01',
            'purpose' => 'Contact for delivery',
            'granted_at' => now()->subYears(3),
        ]);

        $fresh = Consent::query()->create([
            'subject_type' => 'guest',
            'subject_id' => 2,
            'consent_key' => 'booking_contact',
            'text_version' => '2026-01-01',
            'purpose' => 'Contact for delivery',
            'granted_at' => now()->subDays(10),
        ]);

        Artisan::call('retention:sweep', ['--sweeps' => 'consents']);

        $this->assertNotNull($old->fresh()->revoked_at);
        $this->assertNull($fresh->fresh()->revoked_at);
    }

    public function test_warns_on_stuck_deletion_requests_without_mutating(): void
    {
        $stuck = DataRequest::query()->create([
            'user_id' => User::query()->create([
                'name' => 'U',
                'email' => 'u'.uniqid().'@test.com',
                'email_index' => BlindIndex::make('u'.uniqid().'@test.com'),
                'password' => bcrypt('Password123!'),
                'role' => User::ROLE_SKILLED_WORKER,
                'is_active' => true,
            ])->id,
            'type' => DataRequest::TYPE_DELETION,
            'status' => DataRequest::STATUS_PROCESSING,
            'requested_at' => now()->subDays(30),
        ]);

        Artisan::call('retention:sweep', ['--sweeps' => 'deletion-requests']);

        $this->assertDatabaseHas('data_requests', [
            'id' => $stuck->id,
            'status' => DataRequest::STATUS_PROCESSING,
        ]);
    }
}
