<?php

namespace Tests\Feature;

use App\Models\SkillCategory;
use App\Models\User;
use App\Support\BlindIndex;
use Database\Seeders\SkillCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M17.2: admins manage the canonical skill categories.
 */
class AdminSkillCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $email = 'skills-admin@test.com'): User
    {
        return User::query()->create([
            'name' => 'Skills Admin',
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
            ->post(route('admin.skills.store'), ['name' => 'Electrician, Plumber, plumber'])
            ->assertRedirect(route('admin.skills.index'));

        $this->assertDatabaseHas('skill_categories', ['name' => 'Electrician', 'is_active' => true]);
        $this->assertDatabaseHas('skill_categories', ['name' => 'Plumber', 'is_active' => true]);
        // 'plumber' de-duplicates against 'Plumber' within one submission.
        $this->assertSame(2, SkillCategory::query()->count());
    }

    public function test_an_admin_can_rename_and_retire_a_category(): void
    {
        $category = SkillCategory::query()->create(['name' => 'Gardener', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->put(route('admin.skills.update', $category), ['name' => 'Landscaper'])
            ->assertRedirect(route('admin.skills.index'));

        $category->refresh();

        $this->assertSame('Landscaper', $category->name);
        $this->assertFalse($category->is_active);
    }

    public function test_the_seeder_publishes_common_categories(): void
    {
        $this->seed(SkillCategorySeeder::class);

        $this->assertDatabaseHas('skill_categories', ['name' => 'Electrician']);
        $this->assertDatabaseHas('skill_categories', ['name' => 'Plumber']);
    }

    public function test_non_admins_cannot_manage_categories(): void
    {
        $driver = User::query()->create([
            'name' => 'Driver',
            'email' => 'skills-driver@test.com',
            'email_index' => BlindIndex::make('skills-driver@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_DRIVER,
            'is_active' => true,
        ]);

        $this->actingAs($driver)
            ->get(route('admin.skills.index'))
            ->assertForbidden();

        $this->actingAs($driver)
            ->post(route('admin.skills.store'), ['name' => 'Nope'])
            ->assertForbidden();
    }
}
