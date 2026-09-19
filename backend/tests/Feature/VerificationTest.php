<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Media;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Verification;
use App\Models\VerificationVolunteer;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeVolunteer(string $email = 'volunteer@test.com'): User
    {
        $user = User::query()->create([
            'name' => 'Test Volunteer',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VOLUNTEER,
            'is_active' => true,
        ]);

        VerificationVolunteer::query()->create([
            'user_id' => $user->id,
            'availability' => 'Weekends',
        ]);

        return $user->fresh();
    }

    private function makeAdmin(string $email = 'admin@test.com'): User
    {
        return User::query()->create([
            'name' => 'Test Admin',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

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

    public function test_a_volunteer_can_create_and_view_profile(): void
    {
        $volunteer = $this->makeVolunteer();

        $response = $this->actingAs($volunteer, 'sanctum')
            ->getJson('/api/v1/volunteer/profile');

        $response->assertOk()
            ->assertJsonPath('data.availability', 'Weekends');
    }

    public function test_a_volunteer_can_submit_a_verification_report(): void
    {
        $volunteer = $this->makeVolunteer();
        $vendor = $this->makeVendor();

        $response = $this->actingAs($volunteer, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'subject_type' => Vendor::class,
                'subject_id' => $vendor->id,
                'notes' => 'Visited the shop, confirmed details',
                'checklist' => ['address_confirmed' => true, 'products_verified' => true],
                'geo_lat' => 28.6139,
                'geo_lng' => 77.2090,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', Verification::STATUS_DRAFT);
    }

    public function test_evidence_paths_are_validated_persisted_and_returned(): void
    {
        $volunteer = $this->makeVolunteer();
        $vendor = $this->makeVendor();

        Media::query()->create([
            'uploaded_by' => $volunteer->id,
            'path' => 'evidence/shop-front.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);
        Media::query()->create([
            'uploaded_by' => $volunteer->id,
            'path' => 'evidence/shelf.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $store = $this->actingAs($volunteer, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'subject_type' => Vendor::class,
                'subject_id' => $vendor->id,
                'notes' => 'Visited the shop, confirmed details',
                'evidence' => ['evidence/shop-front.jpg', 'evidence/shelf.jpg'],
            ]);

        $store->assertCreated()
            ->assertJsonPath('data.evidence', [
                'evidence/shop-front.jpg',
                'evidence/shelf.jpg',
            ]);

        $verificationId = $store->json('data.id');

        $this->assertDatabaseHas('verifications', [
            'id' => $verificationId,
            'evidence' => json_encode([
                'evidence/shop-front.jpg',
                'evidence/shelf.jpg',
            ]),
        ]);

        $this->actingAs($volunteer, 'sanctum')
            ->getJson("/api/v1/verifications/{$verificationId}")
            ->assertOk()
            ->assertJsonPath('data.evidence', [
                'evidence/shop-front.jpg',
                'evidence/shelf.jpg',
            ]);
    }

    public function test_evidence_paths_of_other_users_are_dropped(): void
    {
        $volunteer = $this->makeVolunteer();
        $otherUser = $this->makeVolunteer('other@test.com');
        $vendor = $this->makeVendor();

        Media::query()->create([
            'uploaded_by' => $volunteer->id,
            'path' => 'evidence/mine.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);
        Media::query()->create([
            'uploaded_by' => $otherUser->id,
            'path' => 'evidence/theirs.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $this->actingAs($volunteer, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'subject_type' => Vendor::class,
                'subject_id' => $vendor->id,
                'notes' => 'Visited the shop, confirmed details',
                'evidence' => ['evidence/mine.jpg', 'evidence/theirs.jpg'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.evidence', ['evidence/mine.jpg']);
    }

    public function test_a_volunteer_can_submit_and_approve_report_creates_badge(): void
    {
        $volunteer = $this->makeVolunteer();
        $admin = $this->makeAdmin();
        $vendor = $this->makeVendor();

        $response = $this->actingAs($volunteer, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'subject_type' => Vendor::class,
                'subject_id' => $vendor->id,
                'notes' => 'Verified in person',
            ]);

        $verificationId = $response->json('data.id');

        $this->actingAs($volunteer, 'sanctum')
            ->postJson("/api/v1/verifications/{$verificationId}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', Verification::STATUS_SUBMITTED);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/verifications/{$verificationId}/approve");

        $response->assertOk()
            ->assertJsonPath('data.volunteer_name', 'Test Volunteer');

        $this->assertDatabaseHas('badges', [
            'subject_type' => Vendor::class,
            'subject_id' => $vendor->id,
            'volunteer_name' => 'Test Volunteer',
        ]);
    }

    /**
     * Admin dashboard review queue (M9.5). Same service as the API, so the
     * review rules are exercised through the web forms too.
     */
    private function makeSubmittedReport(User $volunteer, Vendor $vendor): Verification
    {
        return Verification::query()->create([
            'volunteer_id' => $volunteer->verificationVolunteer->id,
            'subject_type' => Vendor::class,
            'subject_id' => $vendor->id,
            'notes' => 'The shop exists and sells woven goods.',
            'checklist' => ['address_confirmed' => true],
            'evidence' => ['evidence/demo.jpg'],
            'status' => Verification::STATUS_SUBMITTED,
        ]);
    }

    public function test_admin_sees_submitted_reports_in_the_dashboard_queue(): void
    {
        $volunteer = $this->makeVolunteer();
        $vendor = $this->makeVendor();
        $admin = $this->makeAdmin();

        $this->makeSubmittedReport($volunteer, $vendor);

        $this->actingAs($admin)
            ->get(route('admin.verifications.index'))
            ->assertOk()
            ->assertSee('The shop exists and sells woven goods.')
            ->assertSee('Test Volunteer')
            ->assertSee('Test Shop')
            ->assertSee('evidence/demo.jpg');
    }

    public function test_admin_can_approve_from_the_dashboard_and_a_badge_is_issued(): void
    {
        $volunteer = $this->makeVolunteer();
        $vendor = $this->makeVendor();
        $admin = $this->makeAdmin();

        $verification = $this->makeSubmittedReport($volunteer, $vendor);

        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $verification))
            ->assertRedirect(route('admin.verifications.index'));

        $this->assertSame(Verification::STATUS_APPROVED, $verification->fresh()->status);
        $this->assertDatabaseHas('badges', [
            'subject_type' => Vendor::class,
            'subject_id' => $vendor->id,
            'volunteer_name' => 'Test Volunteer',
        ]);
    }

    public function test_admin_can_reject_from_the_dashboard(): void
    {
        $volunteer = $this->makeVolunteer();
        $vendor = $this->makeVendor();
        $admin = $this->makeAdmin();

        $verification = $this->makeSubmittedReport($volunteer, $vendor);

        $this->actingAs($admin)
            ->post(route('admin.verifications.reject', $verification))
            ->assertRedirect(route('admin.verifications.index'));

        $this->assertSame(Verification::STATUS_REJECTED, $verification->fresh()->status);
        $this->assertDatabaseMissing('badges', [
            'subject_type' => Vendor::class,
            'subject_id' => $vendor->id,
        ]);
    }

    public function test_non_admin_users_are_refused_the_dashboard_queue(): void
    {
        $volunteer = $this->makeVolunteer();

        $this->actingAs($volunteer)
            ->get(route('admin.verifications.index'))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_approve_verification(): void
    {
        $volunteer = $this->makeVolunteer();
        $vendor = $this->makeVendor();

        $response = $this->actingAs($volunteer, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'subject_type' => Vendor::class,
                'subject_id' => $vendor->id,
                'notes' => 'Verified',
            ]);

        $verificationId = $response->json('data.id');

        $this->actingAs($volunteer, 'sanctum')
            ->postJson("/api/v1/verifications/{$verificationId}/submit");

        $this->actingAs($volunteer, 'sanctum')
            ->postJson("/api/v1/verifications/{$verificationId}/approve")
            ->assertStatus(422);
    }

    public function test_verification_status_transitions_are_enforced(): void
    {
        $volunteer = $this->makeVolunteer();
        $vendor = $this->makeVendor();

        $response = $this->actingAs($volunteer, 'sanctum')
            ->postJson('/api/v1/verifications', [
                'subject_type' => Vendor::class,
                'subject_id' => $vendor->id,
                'notes' => 'Verified',
            ]);

        $verificationId = $response->json('data.id');

        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/verifications/{$verificationId}/approve")
            ->assertStatus(422);
    }

    public function test_a_volunteer_can_view_their_queue(): void
    {
        $volunteer = $this->makeVolunteer();
        $vendor = $this->makeVendor();

        Verification::query()->create([
            'volunteer_id' => $volunteer->verificationVolunteer->id,
            'subject_type' => Vendor::class,
            'subject_id' => $vendor->id,
            'notes' => 'Test',
            'status' => Verification::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($volunteer, 'sanctum')
            ->getJson('/api/v1/volunteer/queue');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_volunteer_profile_store_is_idempotent_after_registration(): void
    {
        // RegistrationController auto-creates the verification_volunteers row,
        // so a later store() must update it, not crash on the unique constraint.
        $volunteer = $this->makeVolunteer();

        $this->actingAs($volunteer, 'sanctum')
            ->postJson('/api/v1/volunteer/profile', ['availability' => 'Weekday mornings'])
            ->assertCreated()
            ->assertJsonPath('data.availability', 'Weekday mornings');

        $this->assertSame(
            1,
            VerificationVolunteer::query()->where('user_id', $volunteer->id)->count(),
        );
    }
}
