<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transport & errand service categories (M18.1). A separate list from
     * skill categories; drivers tick the work they provide and are listed in
     * the public transport directory.
     */
    public function up(): void
    {
        Schema::create('transport_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transport_category_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transport_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'transport_category_id'], 'user_transport_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_category_user');
        Schema::dropIfExists('transport_categories');
    }
};
