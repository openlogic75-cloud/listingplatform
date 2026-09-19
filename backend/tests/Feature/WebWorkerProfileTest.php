<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkerProfile;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M15.3: skilled workers could read their profile on the website but not
 * edit it. The web form mirrors the API's PUT /profile fields.
 */
class WebWorkerProfileTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $email, string $phone = ''): User
    {
        return User::query()->create([
            'name' => 'Test '.$role,
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'phone' => $phone !== '' ? $phone : null,
            'phone_index' => $phone !== '' ? BlindIndex::make($phone) : null,
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function makeWorker(string $email = 'webworker@test.com'): User
    {
        $user = $this->makeUser(User::ROLE_SKILLED_WORKER, $email);

        WorkerProfile::query()->create(['user_id' => $user->id]);

        return $user->fresh();
    }

    public function test_the_dashboard_renders_the_profile_form(): void
    {
        $this->actingAs($this->makeWorker())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My profile')
            ->assertSee('Work you provide')
            ->assertSee('Other work you provide')
            ->assertSee(route('dashboard.worker.profile'), false);
    }

    public function test_a_worker_can_update_their_profile_from_the_website(): void
    {
        $user = $this->makeWorker();

        $this->actingAs($user)
            ->put(route('dashboard.worker.profile'), [
                'name' => 'Renamed Worker',
                'phone' => '9876501111',
                'services' => 'Welding, gate repair, shutter fitting',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertSame('Renamed Worker', $user->name);
        $this->assertSame('9876501111', $user->phone);
        $this->assertDatabaseHas('worker_profiles', [
            'user_id' => $user->id,
            'services' => 'Welding, gate repair, shutter fitting',
        ]);
    }

    public function test_the_profile_prefills_saved_services(): void
    {
        $user = $this->makeWorker('prefill@test.com');

        $this->actingAs($user)->put(route('dashboard.worker.profile'), [
            'name' => 'Prefill Worker',
            'services' => 'Carpentry and furniture repair',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Carpentry and furniture repair');
    }

    public function test_a_phone_already_used_by_someone_else_is_rejected(): void
    {
        $this->makeUser(User::ROLE_SKILLED_WORKER, 'taken@test.com', '9876502222');
        $user = $this->makeWorker('mine@test.com');

        $this->actingAs($user)
            ->put(route('dashboard.worker.profile'), [
                'name' => 'My Name',
                'phone' => '9876502222',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertNull($user->fresh()->phone);
    }

    public function test_non_workers_cannot_update_the_profile(): void
    {
        $volunteer = $this->makeUser(User::ROLE_VOLUNTEER, 'vol-worker@test.com');

        $this->actingAs($volunteer)
            ->put(route('dashboard.worker.profile'), ['name' => 'Nope'])
            ->assertSessionHasErrors('role');
    }
}
