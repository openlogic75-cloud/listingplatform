<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\DriverAvailability;
use App\Models\Locality;
use App\Models\RiderBaseOperation;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M4.6 - Driver-availability view (Q8): which drivers are online per locality.
 * No map SDK, no geocoding, no ETA, no live GPS — public reads only.
 */
class DriverAvailabilityOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function seedDistrictLocalities(): array
    {
        $district = District::query()->create(['name' => 'Kohima', 'is_active' => true]);
        $centre = Locality::query()->create(['district_id' => $district->id, 'name' => 'Centre', 'is_active' => true]);
        $east = Locality::query()->create(['district_id' => $district->id, 'name' => 'East', 'is_active' => true]);
        $west = Locality::query()->create(['district_id' => $district->id, 'name' => 'West', 'is_active' => true]);

        return ['district' => $district, 'centre' => $centre, 'east' => $east, 'west' => $west];
    }

    private function makeDriver(string $email): User
    {
        return User::query()->create([
            'name' => 'Test Driver',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_DRIVER,
            'is_active' => true,
        ]);
    }

    private function goOnline(User $driver, District $district, array $localities): void
    {
        DriverAvailability::query()->create(['user_id' => $driver->id, 'is_online' => true]);

        $base = RiderBaseOperation::query()->create([
            'user_id' => $driver->id,
            'district_id' => $district->id,
        ]);
        $base->localities()->attach(array_map(fn (Locality $l) => $l->id, $localities));
    }

    public function test_requires_a_district(): void
    {
        $this->getJson('/api/v1/drivers-online')->assertStatus(422);
    }

    public function test_rejects_an_unknown_district(): void
    {
        $this->getJson('/api/v1/drivers-online?district_id=9999')->assertStatus(422);
    }

    public function test_returns_zero_when_no_driver_is_online(): void
    {
        $seeded = $this->seedDistrictLocalities();

        $this->getJson('/api/v1/drivers-online?district_id='.$seeded['district']->id)
            ->assertOk()
            ->assertJsonPath('data.localities', [])
            ->assertJsonPath('data.district_fallback_count', 0);
    }

    public function test_counts_online_drivers_per_locality(): void
    {
        $seeded = $this->seedDistrictLocalities();

        $d1 = $this->makeDriver('d1@test.com');
        $d2 = $this->makeDriver('d2@test.com');
        $this->goOnline($d1, $seeded['district'], [$seeded['centre']]);
        $this->goOnline($d2, $seeded['district'], [$seeded['centre'], $seeded['east']]);

        $this->getJson('/api/v1/drivers-online?district_id='.$seeded['district']->id)
            ->assertOk()
            ->assertJsonCount(2, 'data.localities')
            ->assertJsonPath('data.localities.0.online_drivers', 2);
    }

    public function test_offline_drivers_are_excluded(): void
    {
        $seeded = $this->seedDistrictLocalities();

        $offline = $this->makeDriver('off@test.com');
        $this->goOnline($offline, $seeded['district'], [$seeded['centre']]);
        $offline->driverAvailability()->update(['is_online' => false]);

        $this->getJson('/api/v1/drivers-online?district_id='.$seeded['district']->id)
            ->assertOk()
            ->assertJsonPath('data.localities', [])
            ->assertJsonPath('data.district_fallback_count', 0);
    }

    public function test_narrowing_to_one_locality_uses_district_fallback(): void
    {
        $seeded = $this->seedDistrictLocalities();

        $d3 = $this->makeDriver('d3@test.com');
        $this->goOnline($d3, $seeded['district'], [$seeded['west']]);

        // West has an online driver: locality list populated.
        $this->getJson('/api/v1/drivers-online?district_id='.$seeded['district']->id.'&locality_id='.$seeded['west']->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.localities')
            ->assertJsonPath('data.district_fallback_count', 1);

        // Centre has none: locality list empty, fallback reports the district
        // pool (the same 1 online driver, reachable via district fallback).
        $this->getJson('/api/v1/drivers-online?district_id='.$seeded['district']->id.'&locality_id='.$seeded['centre']->id)
            ->assertOk()
            ->assertJsonPath('data.localities', [])
            ->assertJsonPath('data.district_fallback_count', 1);
    }
}