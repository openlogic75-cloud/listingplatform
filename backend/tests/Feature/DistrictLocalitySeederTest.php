<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Locality;
use Database\Seeders\DistrictLocalitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M19.1: the district/locality seed carries the recognised localities for
 * Dimapur, Kohima, Chümoukedima and Niuland, is safe to re-run, and never
 * clobbers admin edits.
 */
class DistrictLocalitySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_four_districts_with_their_localities(): void
    {
        $this->seed(DistrictLocalitySeeder::class);

        foreach (['Dimapur', 'Kohima', 'Chümoukedima', 'Niuland'] as $name) {
            $district = District::query()->where('name', $name)->first();

            $this->assertNotNull($district, "District {$name} missing");
            $this->assertGreaterThan(10, $district->localities()->count(), "Too few localities in {$name}");
        }

        $this->assertTrue((bool) Locality::query()->where('name', 'Kuda')->firstOrFail()->is_active);
    }

    public function test_re_running_does_not_duplicate(): void
    {
        $this->seed(DistrictLocalitySeeder::class);
        $first = Locality::query()->count();

        $this->seed(DistrictLocalitySeeder::class);

        $this->assertSame($first, Locality::query()->count());
    }

    public function test_it_does_not_clobber_an_admin_edit(): void
    {
        $this->seed(DistrictLocalitySeeder::class);

        $locality = Locality::query()->where('name', 'Kuda')->firstOrFail();
        $locality->update(['name' => 'Kuda (renamed)', 'is_active' => false]);

        $this->seed(DistrictLocalitySeeder::class);

        // The edited row is left exactly as the admin set it (the canonical
        // name is re-added separately, which is the intended additive rule).
        $this->assertDatabaseHas('localities', [
            'id' => $locality->id,
            'name' => 'Kuda (renamed)',
            'is_active' => false,
        ]);
    }
}
