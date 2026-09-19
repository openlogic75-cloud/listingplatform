<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M23.1: the app's dedicated stays feed.
 */
class StayApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_api_returns_only_stays(): void
    {
        $user = User::query()->create([
            'name' => 'Stay API Owner',
            'email' => 'stay-api@test.com',
            'email_index' => BlindIndex::make('stay-api@test.com'),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Stay API Shop',
            'category' => 'rental_homestay',
        ]);

        $vendor->products()->create([
            'title' => 'Lakeview Homestay',
            'category' => Product::CATEGORY_RENTAL_HOMESTAY,
            'price' => 1800,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $vendor->products()->create([
            'title' => 'Farm Rice',
            'category' => Product::CATEGORY_AGRO,
            'price' => 90,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->getJson('/api/v1/stays')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Lakeview Homestay'])
            ->assertJsonMissing(['title' => 'Farm Rice']);
    }
}
