<?php

namespace Tests\Feature;

use App\Models\SkillCategory;
use App\Models\User;
use App\Models\WorkerProfile;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M17.3: public skilled-worker directory (web + API), filterable by the
 * canonical skill categories.
 */
class WorkerDirectoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, int>  $categoryIds
     */
    private function makeWorker(string $name, array $categoryIds = [], bool $active = true): User
    {
        $email = strtolower(str_replace([' ', "'"], ['.', ''], $name)).'@test.com';

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_SKILLED_WORKER,
            'is_active' => $active,
        ]);

        $profile = WorkerProfile::query()->create([
            'user_id' => $user->id,
            'services' => 'Custom work in own words',
        ]);

        if ($categoryIds !== []) {
            $profile->skillCategories()->sync($categoryIds);
        }

        return $user->fresh();
    }

    public function test_the_directory_lists_active_skilled_workers(): void
    {
        $electrician = SkillCategory::query()->create(['name' => 'Electrician']);
        $this->makeWorker('Asha Electric', [$electrician->id]);

        $this->get(route('workers'))
            ->assertOk()
            ->assertSee('Asha Electric')
            ->assertSee('Electrician');
    }

    public function test_the_directory_can_filter_by_category(): void
    {
        $electrician = SkillCategory::query()->create(['name' => 'Electrician']);
        $plumber = SkillCategory::query()->create(['name' => 'Plumber']);

        $this->makeWorker('Electra', [$electrician->id]);
        $this->makeWorker('Plumbo', [$plumber->id]);

        $this->get(route('workers', ['category_id' => $electrician->id]))
            ->assertOk()
            ->assertSee('Electra')
            ->assertDontSee('Plumbo');
    }

    public function test_inactive_workers_are_hidden(): void
    {
        $this->makeWorker('Gone Worker', [], active: false);

        $this->get(route('workers'))
            ->assertOk()
            ->assertDontSee('Gone Worker');
    }

    public function test_the_api_returns_workers_and_filter_categories(): void
    {
        $electrician = SkillCategory::query()->create(['name' => 'Electrician']);
        $this->makeWorker('Api Asha', [$electrician->id]);

        $this->getJson('/api/v1/workers')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Api Asha'])
            ->assertJsonFragment(['name' => 'Electrician']);

        $this->getJson('/api/v1/workers?category_id='.$electrician->id)
            ->assertOk()
            ->assertJsonFragment(['name' => 'Api Asha']);
    }

    public function test_the_api_excludes_workers_without_the_category(): void
    {
        $electrician = SkillCategory::query()->create(['name' => 'Electrician']);
        $this->makeWorker('Unrelated Worker', []);

        $this->getJson('/api/v1/workers?category_id='.$electrician->id)
            ->assertOk()
            ->assertJsonMissing(['name' => 'Unrelated Worker']);
    }
}
