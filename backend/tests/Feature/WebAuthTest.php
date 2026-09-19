<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendorUser(string $email = 'vendor@login.test'): User
    {
        $user = User::query()->create([
            'name' => 'Vendor Member',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Login Shop',
            'category' => 'traditional',
        ]);

        return $user;
    }

    private function makeAdmin(string $email = 'login-admin@test.com'): User
    {
        return User::query()->create([
            'name' => 'Admin User',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_a_member_can_sign_in_and_reach_the_dashboard(): void
    {
        $this->makeVendorUser();

        $this->post(route('login.attempt'), [
            'email' => 'vendor@login.test',
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My listings');
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->makeVendorUser();

        $this->post(route('login.attempt'), [
            'email' => 'vendor@login.test',
            'password' => 'WrongPassword1!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_account_is_refused_at_the_member_signin(): void
    {
        $this->makeAdmin();

        $this->post(route('login.attempt'), [
            'email' => 'login-admin@test.com',
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $user = $this->makeVendorUser('inactive@login.test');
        $user->update(['is_active' => false]);

        $this->post(route('login.attempt'), [
            'email' => 'inactive@login.test',
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_guest_is_sent_to_sign_in_then_back_after_login(): void
    {
        $this->makeVendorUser();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->post(route('login.attempt'), [
            'email' => 'vendor@login.test',
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_an_authenticated_member_can_sign_out(): void
    {
        $this->makeVendorUser();

        $this->actingAs(User::query()->where('email_index', BlindIndex::make('vendor@login.test'))->firstOrFail())
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    /**
     * Member sign-in renders on the admin design system (M8.12): the same
     * login-card shell as admin/auth/login.blade.php, with native browser
     * validation kept on (the admin form disables it — deliberately not
     * copied).
     */
    public function test_signin_form_uses_the_admin_design(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('login-page', false)
            ->assertSee('login-card', false)
            ->assertSee('admin-tokens.css', false)
            ->assertDontSee('novalidate', false)
            ->assertDontSee('auth-single', false)
            ->assertDontSee('password-toggle', false);
    }
}
