<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Locality;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M28.2: farm produce for resellers is its own category and public section.
 */
class ResellerProduceTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $email = 'farm-vendor@test.com'): User
    {
        $user = User::query()->create([
            'name' => 'Farm Vendor',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Farm Shop',
            'category' => 'agro',
        ]);

        return $user->fresh();
    }

    public function test_reseller_farm_produce_has_its_own_section(): void
    {
        $district = District::query()->create(['name' => 'Mokokchung', 'is_active' => true]);
        $locality = Locality::query()->create([
            'district_id' => $district->id,
            'name' => 'Tuli',
            'is_active' => true,
        ]);
        $vendor = $this->vendor();
        $vendor->vendor->products()->create([
            'title' => 'Bulk Ginger',
            'category' => Product::CATEGORY_FARM_RESELLER,
            'price' => 80,
            'moq' => 50,
            'district_id' => $district->id,
            'locality_id' => $locality->id,
            'status' => Product::STATUS_ACTIVE,
        ]);
        $vendor->vendor->products()->create([
            'title' => 'Single Basket',
            'category' => Product::CATEGORY_AGRO,
            'price' => 80,
            'moq' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->get(route('reseller.produce'))
            ->assertOk()
            ->assertSee('Bulk Ginger')
            ->assertDontSee('Single Basket');

        $this->getJson('/api/v1/reseller-produce')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Bulk Ginger'])
            ->assertJsonMissing(['title' => 'Single Basket']);
    }
}
