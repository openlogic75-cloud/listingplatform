<?php

namespace Tests\Feature;

use App\Models\TransportCategory;
use App\Models\User;
use App\Support\BlindIndex;
use Database\Seeders\TransportCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M18.1: admins manage the transport & errand categories.
 */
class AdminTransportCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'transport-admin@test.com'): User
    {
        return User::query()->create([
            'name' => 'Transport Admin',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_an_admin_can_add_categories_with_commas(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.transport.store'), [
                'name' => 'Bike delivery, Errand runner, errand runner',
            ])
            ->assertRedirect(route('admin.transport.index'));

        $this->assertDatabaseHas('transport_categories', ['name' => 'Bike delivery', 'is_active' => true]);
        $this->assertDatabaseHas('transport_categories', ['name' => 'Errand runner', 'is_active' => true]);
        $this->assertSame(2, TransportCategory::query()->count());
    }

    public function test_an_admin_can_rename_and_retire_a_category(): void
    {
        $category = TransportCategory::query()->create(['name' => 'Courier', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->put(route('admin.transport.update', $category), ['name' => 'Parcel courier'])
            ->assertRedirect(route('admin.transport.index'));

        $category->refresh();

        $this->assertSame('Parcel courier', $category->name);
        $this->assertFalse($category->is_active);
    }

    public function test_the_seeder_publishes_common_categories(): void
    {
        $this->seed(TransportCategorySeeder::class);

        $this->assertDatabaseHas('transport_categories', ['name' => 'Bike delivery']);
        $this->assertDatabaseHas('transport_categories', ['name' => 'Errand runner']);
    }

    public function test_non_admins_cannot_manage_categories(): void
    {
        $driver = User::query()->create([
            'name' => 'Driver',
            'email' => 'transport-driver@test.com',
            'email_index' => BlindIndex::make('transport-driver@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_DRIVER,
            'is_active' => true,
        ]);

        $this->actingAs($driver)
            ->get(route('admin.transport.index'))
            ->assertForbidden();
    }
}
