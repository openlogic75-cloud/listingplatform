<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vendor_can_register_from_the_website(): void
    {
        $response = $this->post(route('register.attempt'), [
            'name' => 'Alice Vendor',
            'email' => 'alice@vendor.test',
            'phone' => '9000000001',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accept_terms' => true,
            'role' => 'vendor',
            'display_name' => 'Alice Shop',
            'vendor_category' => 'agro',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email_index' => BlindIndex::make('alice@vendor.test'),
            'role' => User::ROLE_VENDOR,
        ]);
        $this->assertDatabaseHas('vendors', [
            'display_name' => 'Alice Shop',
            'category' => 'agro',
        ]);
    }

    public function test_a_volunteer_can_register_and_gets_a_visit_profile(): void
    {
        $this->post(route('register.attempt'), [
            'name' => 'Bob Volunteer',
            'email' => 'bob@volunteer.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accept_terms' => true,
            'role' => 'volunteer',
        ]);

        $this->assertAuthenticated();

        $user = User::query()->where('email_index', BlindIndex::make('bob@volunteer.test'))->firstOrFail();
        $this->assertNotNull($user->verificationVolunteer);
    }

    public function test_a_driver_register_creates_an_availability_row(): void
    {
        $user = User::query()->create([
            'name' => 'Test Driver',
            'email' => 'dup@driver.test',
            'email_index' => BlindIndex::make('dup@driver.test'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_DRIVER,
            'is_active' => true,
        ]);

        $response = $this->post(route('register.attempt'), [
            'name' => 'Dup Driver',
            'email' => 'dup@driver.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accept_terms' => true,
            'role' => 'driver',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(null, $user->driverAvailability);
    }

    public function test_admin_role_cannot_self_register(): void
    {
        $this->post(route('register.attempt'), [
            'name' => 'Not Admin',
            'email' => 'notadmin@test.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accept_terms' => true,
            'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email_index' => BlindIndex::make('notadmin@test.com'),
        ]);
    }

    public function test_guest_is_redirected_away_from_registration(): void
    {
        $user = User::query()->create([
            'name' => 'Signed In',
            'email' => 'signed@in.test',
            'email_index' => BlindIndex::make('signed@in.test'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_SKILLED_WORKER,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect(route('dashboard'));
    }

    /**
     * Member registration renders on the admin design system (M8.12): the
     * wider login-card shell, a plain role radio list, native browser
     * validation on. The vendor-only shop fields still reveal client-side.
     */
    public function test_registration_form_uses_the_admin_design(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('login-card wide', false)
            ->assertSee('admin-tokens.css', false)
            ->assertSee('role-radios', false)
            ->assertSee('vendor-fields', false)
            ->assertDontSee('novalidate', false)
            ->assertDontSee('auth-single', false)
            ->assertDontSee('password-toggle', false)
            ->assertDontSee('role-card', false);
    }
}
