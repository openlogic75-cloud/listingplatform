<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Canonical transport & errand categories (M18.1). Reference data like
 * districts/skill categories: seeded on deploy, then managed in the admin
 * dashboard. Additive and idempotent.
 */
class TransportCategorySeeder extends Seeder
{
    /** @var array<int, string> */
    private const CATEGORIES = [
        'Bike delivery',
        'Auto / rickshaw',
        'Car / taxi',
        'Truck / tempo',
        'Heavy moving',
        'Errand runner',
        'Courier',
        'Porter / loader',
        'Water tanker',
        'Tractor / farm transport',
    ];

    public function run(): void
    {
        $now = now();
        $added = 0;

        foreach (self::CATEGORIES as $name) {
            if (DB::table('transport_categories')->where('name', $name)->exists()) {
                continue;
            }

            DB::table('transport_categories')->insert([
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $added++;
        }

        $this->command?->info("Transport categories ready ({$added} added).");
    }
}
