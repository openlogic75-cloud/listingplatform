<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M23.1: the dedicated PG / rentals / homestays section.
 */
class StayTest extends TestCase
{
    use RefreshDatabase;

    private function vendor(string $email = 'stay-vendor@test.com'): Vendor
    {
        $user = User::query()->create([
            'name' => 'Stay Owner',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => User::ROLE_VENDOR,
            'is_active' => true,
        ]);

        return Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Stay Shop',
            'category' => 'rental_homestay',
        ]);
    }

    private function product(Vendor $vendor, string $title, string $category, float $price, ?District $district = null): Product
    {
        return $vendor->products()->create([
            'title' => $title,
            'category' => $category,
            'price' => $price,
            'moq' => 1,
            'unit' => 'night',
            'district_id' => $district?->id,
            'status' => Product::STATUS_ACTIVE,
        ]);
    }

    public function test_the_section_lists_stays_only(): void
    {
        $vendor = $this->vendor();
        $this->product($vendor, 'Hillside Homestay', Product::CATEGORY_RENTAL_HOMESTAY, 1500);
        $this->product($vendor, 'Organic Rice', Product::CATEGORY_AGRO, 120);

        $this->get(route('stays'))
            ->assertOk()
            ->assertSee('Hillside Homestay')
            ->assertDontSee('Organic Rice');
    }

    public function test_the_section_filters_by_district_and_price(): void
    {
        $vendor = $this->vendor('stay-filter@test.com');
        $dimapur = District::query()->create(['name' => 'Dimapur', 'is_active' => true]);
        $kohima = District::query()->create(['name' => 'Kohima', 'is_active' => true]);

        $this->product($vendor, 'Dimapur PG', Product::CATEGORY_RENTAL_HOMESTAY, 900, $dimapur);
        $this->product($vendor, 'Kohima Homestay', Product::CATEGORY_RENTAL_HOMESTAY, 2500, $kohima);

        $this->get(route('stays', ['district_id' => $dimapur->id]))
            ->assertOk()
            ->assertSee('Dimapur PG')
            ->assertDontSee('Kohima Homestay');

        $this->get(route('stays', ['max_price' => 1000]))
            ->assertOk()
            ->assertSee('Dimapur PG')
            ->assertDontSee('Kohima Homestay');
    }

    public function test_inactive_stays_are_hidden(): void
    {
        $vendor = $this->vendor('stay-inactive@test.com');
        $stay = $this->product($vendor, 'Draft Room', Product::CATEGORY_RENTAL_HOMESTAY, 500);
        $stay->update(['status' => Product::STATUS_DRAFT]);

        $this->get(route('stays'))
            ->assertOk()
            ->assertDontSee('Draft Room');
    }

    public function test_the_nav_links_to_the_section(): void
    {
        $this->get(route('catalog'))
            ->assertOk()
            ->assertSee(route('stays'), false);
    }
}
