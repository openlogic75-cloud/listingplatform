<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Locality;
use App\Models\LogisticsJob;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\BlindIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M28.3: a farm-produce collection goes from the farm's sub-division to a hub
 * and is handled by that sub-division's collector.
 */
class CollectionJobTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::query()->create([
            'name' => ucfirst($role).' User',
            'email' => $email,
            'email_index' => BlindIndex::make($email),
            'password' => bcrypt('Password123!'),
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function vendor(string $email = 'flow-vendor@test.com'): User
    {
        $user = $this->user(User::ROLE_VENDOR, $email);

        Vendor::query()->create([
            'user_id' => $user->id,
            'display_name' => 'Farm Shop',
            'category' => 'agro',
        ]);

        return $user->fresh();
    }

    /**
     * @return array{0: District, 1: Locality, 2: District}
     */
    private function area(): array
    {
        $farmDistrict = District::query()->create(['name' => 'Mokokchung', 'is_active' => true]);
        $locality = Locality::query()->create([
            'district_id' => $farmDistrict->id,
            'name' => 'Tuli',
            'is_active' => true,
        ]);
        $hub = District::query()->create(['name' => 'Dimapur', 'is_active' => true, 'is_hub' => true]);

        return [$farmDistrict, $locality, $hub];
    }

    public function test_a_vendor_collection_goes_to_the_signed_collector_and_can_be_completed(): void
    {
        [$district, $locality, $hub] = $this->area();
        $admin = $this->user(User::ROLE_ADMIN, 'flow-admin@test.com');
        $collector = $this->user(User::ROLE_COLLECTOR, 'flow-collector@test.com');
        $vendor = $this->vendor();

        $this->actingAs($admin)->post(route('admin.collectors.assign'), [
            'user_id' => $collector->id,
            'locality_id' => $locality->id,
        ]);

        $product = $vendor->vendor->products()->create([
            'title' => 'Bulk Chilli',
            'category' => Product::CATEGORY_FARM_RESELLER,
            'price' => 60,
            'moq' => 20,
            'district_id' => $district->id,
            'locality_id' => $locality->id,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($vendor, 'sanctum')
            ->postJson('/api/v1/collections', [
                'product_id' => $product->id,
                'destination_district_id' => $hub->id,
                'fee_inr' => 250,
            ])
            ->assertCreated()
            ->assertJsonPath('assigned', true);

        $job = LogisticsJob::query()->firstOrFail();
        $this->assertSame(LogisticsJob::TYPE_COLLECT_PRODUCE, $job->type);
        $this->assertSame($collector->id, $job->collector_id);
        $this->assertSame($hub->id, $job->drop_district_id);

        // Collector sees it in their sub-division, accepts and completes it.
        $this->actingAs($collector, 'sanctum')
            ->getJson('/api/v1/collections')
            ->assertOk()
            ->assertJsonFragment(['id' => $job->id]);

        $this->actingAs($collector, 'sanctum')
            ->postJson("/api/v1/collections/{$job->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', LogisticsJob::STATUS_ACCEPTED);

        $this->actingAs($collector, 'sanctum')
            ->postJson("/api/v1/collections/{$job->id}/status", ['status' => LogisticsJob::STATUS_COMPLETED])
            ->assertOk()
            ->assertJsonPath('data.status', LogisticsJob::STATUS_COMPLETED);
    }

    public function test_a_vendor_can_request_a_collection_from_the_website_dashboard(): void
    {
        [$district, $locality, $hub] = $this->area();
        $vendor = $this->vendor('web-vendor@test.com');

        $product = $vendor->vendor->products()->create([
            'title' => 'Bulk Ginger',
            'category' => Product::CATEGORY_FARM_RESELLER,
            'price' => 80,
            'moq' => 50,
            'district_id' => $district->id,
            'locality_id' => $locality->id,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($vendor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Farm-produce collections')
            ->assertSee('Bulk Ginger');

        $this->actingAs($vendor)
            ->post(route('dashboard.collections.store'), [
                'product_id' => $product->id,
                'destination_district_id' => $hub->id,
                'fee_inr' => 200,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('logistics_jobs', [
            'vendor_id' => $vendor->vendor->id,
            'type' => LogisticsJob::TYPE_COLLECT_PRODUCE,
            'locality_id' => $locality->id,
            'drop_district_id' => $hub->id,
        ]);
    }

    public function test_collections_must_be_farm_produce_to_a_hub(): void
    {
        [$district, $locality, $hub] = $this->area();
        $vendor = $this->vendor('rules-vendor@test.com');

        $notFarm = $vendor->vendor->products()->create([
            'title' => 'Normal Agro',
            'category' => Product::CATEGORY_AGRO,
            'price' => 10,
            'moq' => 1,
            'district_id' => $district->id,
            'locality_id' => $locality->id,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($vendor, 'sanctum')
            ->postJson('/api/v1/collections', [
                'product_id' => $notFarm->id,
                'destination_district_id' => $hub->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $nonHub = District::query()->create(['name' => 'Wokha', 'is_active' => true, 'is_hub' => false]);
        $farm = $vendor->vendor->products()->create([
            'title' => 'Bulk Maize',
            'category' => Product::CATEGORY_FARM_RESELLER,
            'price' => 30,
            'moq' => 10,
            'district_id' => $district->id,
            'locality_id' => $locality->id,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->actingAs($vendor, 'sanctum')
            ->postJson('/api/v1/collections', [
                'product_id' => $farm->id,
                'destination_district_id' => $nonHub->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('destination_district_id');
    }
}
