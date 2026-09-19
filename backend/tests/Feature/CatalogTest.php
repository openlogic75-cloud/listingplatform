<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private function makeVendor(string $name, string $category = 'agro'): Vendor
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email = strtolower(str_replace(' ', '.', $name)).'@example.test',
            'email_index' => BlindIndex::make($email),
            'password' => 'secret1234',
            'role' => 'vendor',
        ]);

        return Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => $name.' Farm',
            'category' => $category,
        ]);
    }

    private function makeProduct(Vendor $vendor, array $overrides = []): Product
    {
        return Product::query()->create($overrides + [
            'vendor_id' => $vendor->id,
            'category' => 'agro',
            'title' => 'Tomatoes',
            'description' => 'Red and ripe.',
            'price' => 20,
            'status' => 'active',
        ]);
    }

    public function test_guests_can_browse_the_catalog_without_an_account(): void
    {
        $vendor = $this->makeVendor('Farm One');
        $this->makeProduct($vendor, ['title' => 'Fresh tomatoes']);

        // Website: no auth, works.
        $this->get('/catalog')
            ->assertOk()
            ->assertSee('Fresh tomatoes');

        // API: no token, works.
        $this->getJson('/api/v1/catalog')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Fresh tomatoes');
    }

    public function test_draft_and_archived_listings_are_hidden_from_the_catalog(): void
    {
        $vendor = $this->makeVendor('Farm Two');

        $this->makeProduct($vendor, ['title' => 'Visible item', 'status' => 'active']);
        $this->makeProduct($vendor, ['title' => 'Draft item', 'status' => 'draft']);
        $this->makeProduct($vendor, ['title' => 'Archived item', 'status' => 'archived']);

        $this->get('/catalog')->assertOk()->assertSee('Visible item')->assertDontSee('Draft item');

        $this->getJson('/api/v1/catalog')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_search_and_category_filters_narrow_results(): void
    {
        $vendor = $this->makeVendor('Farm Three');

        $this->makeProduct($vendor, ['title' => 'Tomato basket', 'category' => 'agro']);
        $this->makeProduct($vendor, ['title' => 'Cotton hammock', 'category' => 'traditional']);
        $this->makeProduct($vendor, ['title' => 'Mountain cabin', 'category' => 'rental_homestay', 'price' => 900]);

        $this->getJson('/api/v1/catalog?q=tomato')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Tomato basket');

        $this->getJson('/api/v1/catalog?category=rental_homestay')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Mountain cabin');

        // Same filters on the website.
        $this->get('/catalog?q=tomato')->assertOk()->assertSee('Tomato basket')->assertDontSee('Cotton hammock');
    }

    public function test_the_catalog_api_rejects_invalid_filters(): void
    {
        $this->getJson('/api/v1/catalog?category=nonsense')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }

    public function test_vendor_page_lists_only_active_products_and_marks_verification(): void
    {
        $vendor = $this->makeVendor('Farm Four');
        $this->makeProduct($vendor, ['title' => 'Listed honey', 'category' => 'traditional']);
        $this->makeProduct($vendor, ['title' => 'Hidden honey', 'status' => 'inactive']);

        $response = $this->getJson("/api/v1/vendors/{$vendor->id}");

        $response->assertOk()
            ->assertJsonPath('data.display_name', 'Farm Four Farm')
            ->assertJsonPath('data.is_verified', false);

        $titles = collect($response->json('data.listings'))->pluck('title')->all();
        $this->assertContains('Listed honey', $titles);
        $this->assertNotContains('Hidden honey', $titles);
    }
}
