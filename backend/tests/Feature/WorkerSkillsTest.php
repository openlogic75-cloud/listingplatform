<?php

namespace Tests\Feature;

use App\Models\SkillCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M17.2: workers tick canonical skill categories (plus custom free text) and
 * the API exposes them so the directory/app can filter by category.
 */
class WorkerSkillsTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorker(string $email = 'skills-worker@test.com'): User
    {
        $user = User::query()->create([
            'name' => 'Test Worker',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_SKILLED_WORKER,
            'is_active' => true,
        ]);

        WorkerProfile::query()->create(['user_id' => $user->id]);

        return $user->fresh();
    }

    public function test_a_worker_can_tick_categories_on_the_website(): void
    {
        $electrician = SkillCategory::query()->create(['name' => 'Electrician']);
        $plumber = SkillCategory::query()->create(['name' => 'Plumber']);
        $user = $this->makeWorker();

        $this->actingAs($user)
            ->put(route('dashboard.worker.profile'), [
                'name' => 'Sparky',
                'skill_category_ids' => [$electrician->id, $plumber->id],
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame(
            [$electrician->id, $plumber->id],
            $user->workerProfile->skillCategories()
                ->orderBy('skill_categories.id')
                ->pluck('skill_categories.id')
                ->all(),
        );
    }

    public function test_the_dashboard_form_lists_category_checkboxes(): void
    {
        SkillCategory::query()->create(['name' => 'Electrician', 'is_active' => true]);
        SkillCategory::query()->create(['name' => 'Old Trade', 'is_active' => false]);

        $this->actingAs($this->makeWorker())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Work you provide')
            ->assertSee('Electrician')
            ->assertDontSee('Old Trade')
            ->assertSee('skill_category_ids', false);
    }

    public function test_inactive_categories_cannot_be_selected(): void
    {
        $retired = SkillCategory::query()->create(['name' => 'Old Trade', 'is_active' => false]);
        $user = $this->makeWorker('retired@test.com');

        $this->actingAs($user)
            ->put(route('dashboard.worker.profile'), [
                'name' => 'Sparky',
                'skill_category_ids' => [$retired->id],
            ])
            ->assertSessionHasErrors('skill_category_ids.0');
    }

    public function test_the_api_returns_and_syncs_skill_categories(): void
    {
        $electrician = SkillCategory::query()->create(['name' => 'Electrician']);

        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Api Worker',
            'email' => 'api-worker@test.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'accept_terms' => true,
            'role' => User::ROLE_SKILLED_WORKER,
        ]);
        $register->assertCreated();
        $token = (string) $register->json('token');

        $this->withToken($token)
            ->putJson('/api/v1/profile', [
                'services' => 'Gate repair and welding',
                'skill_category_ids' => [$electrician->id],
            ])
            ->assertOk()
            ->assertJsonPath('user.worker.skill_categories.0.name', 'Electrician')
            ->assertJsonPath('user.worker.services', 'Gate repair and welding');

        $this->withToken($token)
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('user.worker.skill_category_ids.0', $electrician->id);
    }
}
