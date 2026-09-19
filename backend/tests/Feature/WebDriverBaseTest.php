<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Locality;
use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M15.2: drivers could only set their base of operation in the app and were
 * dead-ended on the website. The web form reuses the API's rules.
 */
class WebDriverBaseTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $email): User
    {
        return User::query()->create([
            'name' => 'Test '.$role,
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: District, 1: Locality, 2: Locality}
     */
    private function makeArea(): array
    {
        $district = District::query()->create(['name' => 'Dimapur', 'is_active' => true]);
        $one = Locality::query()->create(['district_id' => $district->id, 'name' => 'Centre', 'is_active' => true]);
        $two = Locality::query()->create(['district_id' => $district->id, 'name' => 'East', 'is_active' => true]);

        return [$district, $one, $two];
    }

    public function test_the_dashboard_renders_the_base_form(): void
    {
        $this->makeArea();
        $driver = $this->makeUser(User::ROLE_DRIVER, 'form@test.com');

        $this->actingAs($driver)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Base of operation')
            ->assertSee('check-option', false)
            ->assertSee('driver-base.js', false)
            ->assertSee(route('dashboard.driver.base'), false);
    }

    public function test_a_driver_can_set_their_base_from_the_website(): void
    {
        [$district, $one, $two] = $this->makeArea();
        $driver = $this->makeUser(User::ROLE_DRIVER, 'webdriver@test.com');

        $this->actingAs($driver)
            ->put(route('dashboard.driver.base'), [
                'district_id' => $district->id,
                'locality_ids' => [$one->id, $two->id],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('rider_base_operations', [
            'user_id' => $driver->id,
            'district_id' => $district->id,
        ]);
        $this->assertSame(2, $driver->riderBaseOperation->localities()->count());
    }

    public function test_the_base_can_be_replaced(): void
    {
        [$district, $one, $two] = $this->makeArea();
        $driver = $this->makeUser(User::ROLE_DRIVER, 'replace@test.com');

        $this->actingAs($driver)->put(route('dashboard.driver.base'), [
            'district_id' => $district->id,
            'locality_ids' => [$one->id, $two->id],
        ]);

        $this->actingAs($driver)->put(route('dashboard.driver.base'), [
            'district_id' => $district->id,
            'locality_ids' => [$two->id],
        ])->assertRedirect(route('dashboard'));

        $this->assertSame([$two->id], $driver->riderBaseOperation->localities()->pluck('localities.id')->all());
    }

    public function test_a_driver_cannot_pick_more_than_five_localities(): void
    {
        $district = District::query()->create(['name' => 'Kohima', 'is_active' => true]);
        $ids = [];

        for ($i = 0; $i < 6; $i++) {
            $ids[] = Locality::query()->create([
                'district_id' => $district->id,
                'name' => "Locality {$i}",
                'is_active' => true,
            ])->id;
        }

        $driver = $this->makeUser(User::ROLE_DRIVER, 'five@test.com');

        $this->actingAs($driver)
            ->put(route('dashboard.driver.base'), [
                'district_id' => $district->id,
                'locality_ids' => $ids,
            ])
            ->assertSessionHasErrors('locality_ids');
    }

    public function test_a_locality_from_another_district_is_rejected(): void
    {
        [$district] = $this->makeArea();
        $other = District::query()->create(['name' => 'Other', 'is_active' => true]);
        $elsewhere = Locality::query()->create([
            'district_id' => $other->id,
            'name' => 'Elsewhere',
            'is_active' => true,
        ]);

        $driver = $this->makeUser(User::ROLE_DRIVER, 'mismatch@test.com');

        $this->actingAs($driver)
            ->put(route('dashboard.driver.base'), [
                'district_id' => $district->id,
                'locality_ids' => [$elsewhere->id],
            ])
            ->assertSessionHasErrors('locality_ids.0');
    }

    public function test_inactive_service_areas_are_rejected(): void
    {
        $district = District::query()->create(['name' => 'Mon', 'is_active' => true]);
        $hidden = Locality::query()->create([
            'district_id' => $district->id,
            'name' => 'Hidden Area',
            'is_active' => false,
        ]);

        $driver = $this->makeUser(User::ROLE_DRIVER, 'inactive@test.com');

        $this->actingAs($driver)
            ->put(route('dashboard.driver.base'), [
                'district_id' => $district->id,
                'locality_ids' => [$hidden->id],
            ])
            ->assertSessionHasErrors('locality_ids.0');
    }

    public function test_non_drivers_cannot_set_a_base(): void
    {
        [$district, $one] = $this->makeArea();
        $volunteer = $this->makeUser(User::ROLE_VOLUNTEER, 'vol-base@test.com');

        $this->actingAs($volunteer)
            ->put(route('dashboard.driver.base'), [
                'district_id' => $district->id,
                'locality_ids' => [$one->id],
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseCount('rider_base_operations', 0);
    }
}
