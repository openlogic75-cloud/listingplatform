<?php

namespace Tests\Feature;

use App\Models\DriverAvailability;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VerificationVolunteer;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Dashboard User',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);

        return match ($role) {
            User::ROLE_VENDOR => tap($user, function (User $u) {
                Vendor::query()->create([
                    'user_id' => $u->id,
                    'display_name' => 'Vendor Dashboard Shop',
                    'category' => 'traditional',
                ]);
            }),
            User::ROLE_DRIVER => tap($user, fn (User $u) => DriverAvailability::query()->create(['user_id' => $u->id])),
            default => $user,
        };
    }

    public function test_vendor_dashboard_lists_own_listings_with_a_status(): void
    {
        $user = $this->makeUser('vendor@dash.test', User::ROLE_VENDOR);

        Product::query()->create([
            'vendor_id' => $user->vendor->id,
            'title' => 'Organic Rice 5kg',
            'category' => Product::CATEGORY_AGRO,
            'description' => 'Freshly harvested',
            'price' => 480,
            'unit' => 'bag',
            'moq' => 1,
            'stock' => 20,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Organic Rice 5kg')
            ->assertSee('Active')
            ->assertSee('My listings');
    }

    public function test_driver_can_go_online_and_offline_from_the_dashboard(): void
    {
        $user = $this->makeUser('driver@dash.test', User::ROLE_DRIVER);

        $this->actingAs($user)
            ->post(route('dashboard.availability'), ['is_online' => 1])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('driver_availability', [
            'user_id' => $user->id,
            'is_online' => true,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.availability'), ['is_online' => 0])
            ->assertRedirect(route('dashboard'));

        $availability = $user->driverAvailability->fresh();
        $this->assertFalse((bool) $availability->is_online);
        $this->assertNotNull($availability->last_online_at);
    }

    public function test_non_drivers_cannot_toggle_availability(): void
    {
        $user = $this->makeUser('worker@dash.test', User::ROLE_SKILLED_WORKER);

        $this->actingAs($user)
            ->post(route('dashboard.availability'), ['is_online' => 1])
            ->assertSessionHasErrors('role');
    }

    public function test_a_worker_sees_a_profile_summary(): void
    {
        $user = $this->makeUser('worker2@dash.test', User::ROLE_SKILLED_WORKER);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My profile')
            ->assertSee('Skilled worker');
    }

    /**
     * M15.1: every role renders the premium dashboard layer.
     */
    public function test_a_volunteer_sees_their_visits_on_the_premium_dashboard(): void
    {
        $user = $this->makeUser('vol@dash.test', User::ROLE_VOLUNTEER);
        VerificationVolunteer::query()->create(['user_id' => $user->id, 'availability' => 'Weekends']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My visits')
            ->assertSee('Reports filed')
            ->assertSee('Availability: Weekends')
            ->assertSee('dash-card', false);
    }
}
