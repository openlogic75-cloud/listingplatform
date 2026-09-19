<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationVolunteer;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M17.1: volunteers register as pending and cannot sign in until an admin
 * approves them in the dashboard.
 */
class VolunteerApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function registerVolunteer(string $email = 'newvol@test.com'): User
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'New Volunteer',
            'email' => $email,
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => User::ROLE_VOLUNTEER,
        ])->assertCreated();

        return User::query()->where('email_index', BlindIndex::make($email))->firstOrFail();
    }

    private function makeAdmin(string $email = 'admin@approve.test'): User
    {
        return User::query()->create([
            'name' => 'Approver Admin',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_registration_creates_a_pending_volunteer(): void
    {
        $volunteer = $this->registerVolunteer();

        $this->assertSame(
            VerificationVolunteer::STATUS_PENDING,
            $volunteer->verificationVolunteer->verification_status,
        );
    }

    public function test_a_pending_volunteer_cannot_sign_in(): void
    {
        $this->registerVolunteer('pending-signin@test.com');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'pending-signin@test.com',
            'password' => 'secret1234',
        ])->assertForbidden();

        $this->post(route('login.attempt'), [
            'email' => 'pending-signin@test.com',
            'password' => 'secret1234',
        ])->assertSessionHasErrors('email');
    }

    public function test_an_admin_can_approve_and_then_the_volunteer_can_sign_in(): void
    {
        $volunteer = $this->registerVolunteer('to-approve@test.com');
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('admin.volunteers.approve', $volunteer->verificationVolunteer))
            ->assertRedirect(route('admin.volunteers.index'));

        $this->assertSame(
            VerificationVolunteer::STATUS_APPROVED,
            $volunteer->verificationVolunteer->fresh()->verification_status,
        );

        $this->postJson('/api/v1/auth/login', [
            'email' => 'to-approve@test.com',
            'password' => 'secret1234',
        ])->assertOk();
    }

    public function test_an_admin_can_reject_a_volunteer(): void
    {
        $volunteer = $this->registerVolunteer('to-reject@test.com');
        $admin = $this->makeAdmin('reject-admin@test.com');

        $this->actingAs($admin)
            ->post(route('admin.volunteers.reject', $volunteer->verificationVolunteer))
            ->assertRedirect(route('admin.volunteers.index'));

        $this->assertSame(
            VerificationVolunteer::STATUS_REJECTED,
            $volunteer->verificationVolunteer->fresh()->verification_status,
        );

        $this->postJson('/api/v1/auth/login', [
            'email' => 'to-reject@test.com',
            'password' => 'secret1234',
        ])->assertForbidden();
    }

    public function test_the_queue_lists_pending_volunteers(): void
    {
        $this->registerVolunteer('listed@test.com');
        $admin = $this->makeAdmin('list-admin@test.com');

        $this->actingAs($admin)
            ->get(route('admin.volunteers.index'))
            ->assertOk()
            ->assertSee('New Volunteer')
            ->assertSee('Awaiting approval');
    }

    public function test_non_admins_cannot_reach_the_queue(): void
    {
        $volunteer = $this->registerVolunteer('notadmin@test.com');

        $this->actingAs($volunteer)
            ->get(route('admin.volunteers.index'))
            ->assertForbidden();
    }
}
