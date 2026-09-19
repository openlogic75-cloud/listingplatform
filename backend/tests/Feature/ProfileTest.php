<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function registerWorker(): string
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Welder W',
            'email' => 'welder@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'skilled_worker',
        ]);

        $response->assertCreated();

        return (string) $response->json('token');
    }

    public function test_a_worker_can_read_and_update_their_profile(): void
    {
        $token = $this->registerWorker();

        $this->withToken($token)->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.role', 'skilled_worker');

        $this->withToken($token)->putJson('/api/v1/profile', [
            'name' => 'Welder W Two',
            'services' => 'Welding, gate repair, shutter fitting',
            'service_areas' => 'Centre, East',
        ])->assertOk()->assertJsonPath('user.name', 'Welder W Two');

        $this->assertDatabaseHas('worker_profiles', [
            'services' => 'Welding, gate repair, shutter fitting',
        ]);
    }

    public function test_registration_creates_the_worker_profile_row(): void
    {
        $this->registerWorker();

        $this->assertDatabaseCount('worker_profiles', 1);
    }

    public function test_a_worker_cannot_escalate_their_role_through_profile_update(): void
    {
        $token = $this->registerWorker();

        // 'role' is not an updatable field: it is ignored, not applied.
        $this->withToken($token)->putJson('/api/v1/profile', [
            'name' => 'Still a worker',
            'role' => 'admin',
            'services' => 'Updated services',
        ])->assertOk();

        $this->assertSame(
            'skilled_worker',
            WorkerProfile::query()->firstOrFail()->user->role
        );
    }

    /**
     * Vendor shop profile (M9.4): read exposes the district name for
     * display, update saves the editable vendor fields; category and
     * district are registration-time only and cannot be changed here.
     */
    public function test_a_vendor_can_read_and_update_their_shop_profile(): void
    {
        $district = District::query()->create([
            'name' => 'Dimapur',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Vendor V',
            'email' => 'vendor@example.test',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => 'vendor',
            'district_id' => $district->id,
            'display_name' => 'Test Shop',
            'vendor_category' => 'traditional',
        ]);

        $response->assertCreated();

        $token = (string) $response->json('token');

        $this->withToken($token)->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.role', 'vendor')
            ->assertJsonPath('user.vendor.display_name', 'Test Shop')
            ->assertJsonPath('user.vendor.category', 'traditional')
            ->assertJsonPath('user.vendor.district_name', 'Dimapur');

        $this->withToken($token)->putJson('/api/v1/profile', [
            'display_name' => 'Renamed Shop',
            'vendor_description' => 'Handwoven goods from Dimapur.',
            'category' => 'agro',
            'district_id' => 999,
        ])->assertOk()
            ->assertJsonPath('user.vendor.display_name', 'Renamed Shop')
            ->assertJsonPath('user.vendor.description', 'Handwoven goods from Dimapur.')
            ->assertJsonPath('user.vendor.category', 'traditional');

        $this->assertDatabaseHas('vendors', [
            'display_name' => 'Renamed Shop',
            'description' => 'Handwoven goods from Dimapur.',
            'category' => 'traditional',
            'district_id' => $district->id,
        ]);
    }
}
