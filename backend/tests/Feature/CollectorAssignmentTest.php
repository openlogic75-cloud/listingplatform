<?php

namespace Tests\Feature;

use App\Models\CollectorAssignment;
use App\Models\District;
use App\Models\Locality;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M28.1: a collector is signed by an admin to exactly one sub-division and
 * cannot sign in until then.
 */
class CollectorAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::query()->create([
            'name' => ucfirst($role).' User',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function locality(string $name = 'Tuli'): Locality
    {
        $district = District::query()->create(['name' => 'Mokokchung', 'is_active' => true]);

        return Locality::query()->create([
            'district_id' => $district->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    public function test_admin_signs_one_collector_per_sub_division_and_gates_sign_in(): void
    {
        $locality = $this->locality();
        $admin = $this->user(User::ROLE_ADMIN, 'collector-admin@test.com');
        $collector = $this->user(User::ROLE_COLLECTOR, 'collector@test.com');

        // Unassigned: cannot sign in.
        $this->postJson('/api/v1/auth/login', [
            'email' => 'collector@test.com',
            'password' => 'Password123!',
        ])->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.collectors.assign'), [
                'user_id' => $collector->id,
                'locality_id' => $locality->id,
            ])
            ->assertRedirect(route('admin.collectors.index'));

        $this->assertDatabaseHas('collector_assignments', [
            'user_id' => $collector->id,
            'locality_id' => $locality->id,
            'is_active' => true,
            'assigned_by' => $admin->id,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'collector@test.com',
            'password' => 'Password123!',
        ])->assertOk();
    }

    public function test_only_one_collector_holds_a_sub_division(): void
    {
        $locality = $this->locality();
        $admin = $this->user(User::ROLE_ADMIN, 'assign-admin@test.com');
        $first = $this->user(User::ROLE_COLLECTOR, 'first-collector@test.com');
        $second = $this->user(User::ROLE_COLLECTOR, 'second-collector@test.com');

        foreach ([$first, $second] as $collector) {
            $this->actingAs($admin)->post(route('admin.collectors.assign'), [
                'user_id' => $collector->id,
                'locality_id' => $locality->id,
            ]);
        }

        $this->assertSame(1, CollectorAssignment::query()->where('locality_id', $locality->id)->count());
        $this->assertSame(
            $second->id,
            CollectorAssignment::query()->where('locality_id', $locality->id)->value('user_id'),
        );
    }
}
