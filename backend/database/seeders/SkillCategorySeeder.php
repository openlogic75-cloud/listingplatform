<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Canonical skilled-work categories (M17.2). Reference data like districts:
 * seeded on deploy, then managed (renamed, retired) in the admin dashboard.
 * Additive — existing rows keep their ids and are left untouched.
 */
class SkillCategorySeeder extends Seeder
{
    /** @var array<int, string> */
    private const CATEGORIES = [
        'Electrician',
        'Plumber',
        'Carpenter',
        'Welder',
        'Mason',
        'Painter',
        'Appliance repair',
        'AC & refrigeration',
        'Mechanic',
        'Tailor',
        'Cleaner',
        'Gardener',
        'Pest control',
        'Beautician',
        'Tutor',
        'IT & electronics',
        'Catering',
        'Handyman',
    ];

    public function run(): void
    {
        $now = now();
        $added = 0;

        foreach (self::CATEGORIES as $name) {
            $exists = DB::table('skill_categories')->where('name', $name)->exists();

            if ($exists) {
                continue;
            }

            DB::table('skill_categories')->insert([
                'name' => $name,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $added++;
        }

        $this->command?->info("Skill categories ready ({$added} added).");
    }
}
