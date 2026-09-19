<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\District;
use App\Models\Locality;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Verification;
use App\Models\VerificationFeeSetting;
use App\Models\VerificationVolunteer;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationFeeTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $name): User
    {
        $email = $role.'-'.uniqid().'@test.com';

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function volunteer(): User
    {
        $user = $this->makeUser(User::ROLE_VOLUNTEER, 'Volunteer');

        VerificationVolunteer::query()->create([
            'user_id' => $user->id,
            'availability' => 'Weekends',
        ]);

        return $user->fresh();
    }

    private function vendor(): Vendor
    {
        $user = $this->makeUser(User::ROLE_VENDOR, 'Vendor');

        $district = District::query()->first();
        if ($district === null) {
            $district = District::query()->create(['name' => 'Kohima', 'is_active' => true]);
        }

        return Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Test Shop',
            'category' => 'traditional',
            'district_id' => $district->id,
        ]);
    }

    private function admin(): User
    {
        return $this->makeUser(User::ROLE_ADMIN, 'Admin');
    }

    private function verification(Vendor $vendor, User $volunteer): Verification
    {
        return Verification::create([
            'subject_type' => Vendor::class,
            'subject_id' => $vendor->id,
            'volunteer_id' => $volunteer->id,
            'status' => Verification::STATUS_DRAFT,
            'notes' => 'Verified in person',
        ]);
    }

    /** Public read returns null when no fee is configured. */
    public function test_public_read_returns_null_by_default(): void
    {
        $res = $this->getJson('/api/v1/verification-fee');
        $res->assertOk()->assertJsonPath('data.amount_inr', null);
    }

    /** Public read returns the configured fee. */
    public function test_public_read_returns_amount_when_set(): void
    {
        VerificationFeeSetting::current()->update(['amount_inr' => 350]);

        $res = $this->getJson('/api/v1/verification-fee');
        $res->assertOk()->assertJsonPath('data.amount_inr', 350);
    }

    /** Admin can update the verification fee. */
    public function test_admin_can_update_fee(): void
    {
        $res = $this->actingAs($this->admin())->put('/admin/verification-fee', [
            'amount_inr' => 400,
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('verification_fee_settings', [
            'amount_inr' => 400,
        ]);
    }

    /** Non-admin cannot update the fee. */
    public function test_non_admin_cannot_update_fee(): void
    {
        $vendor = $this->vendor()->user;

        $res = $this->actingAs($vendor)->put('/admin/verification-fee', [
            'amount_inr' => 400,
        ]);

        $res->assertForbidden();
    }

    /** Admin can clear the fee back to null. */
    public function test_admin_can_clear_fee(): void
    {
        VerificationFeeSetting::current()->update(['amount_inr' => 500]);

        $this->actingAs($this->admin())->put('/admin/verification-fee', [
            'amount_inr' => '',
        ]);

        $this->assertDatabaseHas('verification_fee_settings', [
            'amount_inr' => null,
        ]);
    }

    /** Fee is snapshotted onto badge when admin approves a verification. */
    public function test_approve_snapshots_fee_onto_badge(): void
    {
        VerificationFeeSetting::current()->update(['amount_inr' => 300]);

        $volunteer = $this->volunteer();
        $vendor = $this->vendor();
        $verification = $this->verification($vendor, $volunteer);

        // Submit
        $this->actingAs($volunteer, 'sanctum')
            ->postJson("/api/v1/verifications/{$verification->id}/submit")
            ->assertOk();

        // Approve
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/verifications/{$verification->id}/approve")
            ->assertOk();

        $badge = Badge::query()
            ->where('subject_type', Vendor::class)
            ->where('subject_id', $vendor->id)
            ->first();

        $this->assertNotNull($badge);
        $this->assertEquals('300.00', $badge->fee_inr);
    }

    /** Existing badge issued without a fee still has null fee_inr. */
    public function test_existing_badge_without_fee_has_null(): void
    {
        $volunteer = $this->volunteer();
        $vendor = $this->vendor();
        $verification = $this->verification($vendor, $volunteer);

        $this->actingAs($volunteer, 'sanctum')
            ->postJson("/api/v1/verifications/{$verification->id}/submit")
            ->assertOk();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/verifications/{$verification->id}/approve")
            ->assertOk();

        $badge = Badge::query()
            ->where('subject_type', Vendor::class)
            ->where('subject_id', $vendor->id)
            ->first();

        $this->assertNull($badge->fee_inr);
    }

    /** ProductResource includes verification_fee_inr when product is unverified. */
    public function test_catalog_includes_fee_for_unverified_listing(): void
    {
        VerificationFeeSetting::current()->update(['amount_inr' => 250]);

        $vendor = $this->vendor();
        $product = \App\Models\Product::create([
            'vendor_id' => $vendor->id,
            'title' => 'Handwoven shawl',
            'category' => 'traditional',
            'price' => 1500,
            'stock' => 10,
            'status' => 'active',
            'district_id' => $vendor->district_id,
        ]);

        $res = $this->getJson("/api/v1/catalog/{$product->id}");
        $res->assertOk()
            ->assertJsonPath('data.verification_fee_inr', 250);
    }

    /** ProductResource omits verification_fee_inr when product is already verified. */
    public function test_catalog_omits_fee_for_verified_listing(): void
    {
        VerificationFeeSetting::current()->update(['amount_inr' => 250]);

        $volunteer = $this->volunteer();
        $vendor = $this->vendor();
        $product = \App\Models\Product::create([
            'vendor_id' => $vendor->id,
            'title' => 'Handwoven shawl',
            'category' => 'traditional',
            'price' => 1500,
            'stock' => 10,
            'status' => 'active',
            'district_id' => $vendor->district_id,
        ]);

        // Issue badge
        Badge::create([
            'subject_type' => Vendor::class,
            'subject_id' => $vendor->id,
            'volunteer_name' => $volunteer->name,
            'issued_at' => now(),
        ]);

        $res = $this->getJson("/api/v1/catalog/{$product->id}");
        $res->assertOk()
            ->assertJsonMissing(['verification_fee_inr']);
    }
}