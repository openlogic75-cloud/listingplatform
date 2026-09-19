<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skilled-worker skill categories (M17.2). Admin-managed canonical list
     * (like localities) plus a pivot so a worker can tick several; custom
     * free-text work stays in worker_profiles.services.
     */
    public function up(): void
    {
        Schema::create('skill_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('skill_category_worker_profile', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['worker_profile_id', 'skill_category_id'], 'worker_skill_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_category_worker_profile');
        Schema::dropIfExists('skill_categories');
    }
};
