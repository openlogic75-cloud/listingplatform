<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M12.3: website vendors can edit their shop profile (previously the fields
 * were app-only).
 */
class WebVendorProfileTest extends TestCase
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

    private function makeVendor(string $email = 'webvendor@test.com'): User
    {
        $user = $this->makeUser(User::ROLE_VENDOR, $email);

        Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Web Shop',
            'category' => 'traditional',
        ]);

        return $user->fresh();
    }

    public function test_the_dashboard_renders_the_shop_profile_form(): void
    {
        $this->actingAs($this->makeVendor())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Shop profile')
            ->assertSee('Shop name')
            ->assertSee(route('dashboard.vendor.profile'), false);
    }

    public function test_a_vendor_can_update_their_shop_profile(): void
    {
        $user = $this->makeVendor();

        $this->actingAs($user)
            ->put(route('dashboard.vendor.profile'), [
                'name' => 'Renamed Vendor',
                'phone' => '9876503333',
                'display_name' => 'Renamed Shop',
                'vendor_description' => 'Handwoven goods from Dimapur.',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();

        $this->assertSame('Renamed Vendor', $user->name);
        $this->assertSame('9876503333', $user->phone);
        $this->assertDatabaseHas('vendors', [
            'user_id' => $user->id,
            'display_name' => 'Renamed Shop',
            'description' => 'Handwoven goods from Dimapur.',
            'category' => 'traditional',
        ]);
    }

    public function test_a_phone_already_used_by_someone_else_is_rejected(): void
    {
        $this->makeUser(User::ROLE_VENDOR, 'taken@test.com', '9876504444');
        $user = $this->makeVendor('mine@test.com');

        $this->actingAs($user)
            ->put(route('dashboard.vendor.profile'), [
                'name' => 'Mine',
                'phone' => '9876504444',
                'display_name' => 'My Shop',
            ])
            ->assertSessionHasErrors('phone');

        $this->assertNull($user->fresh()->phone);
    }

    public function test_non_vendors_cannot_update_the_shop_profile(): void
    {
        $volunteer = $this->makeUser(User::ROLE_VOLUNTEER, 'vol-vendor@test.com');

        $this->actingAs($volunteer)
            ->put(route('dashboard.vendor.profile'), [
                'name' => 'Nope',
                'display_name' => 'Nope Shop',
            ])
            ->assertSessionHasErrors('role');
    }
}
