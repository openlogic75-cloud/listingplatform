<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rider base of operation: one district + up to five localities
        // (max enforced in validation, M4.2).
        Schema::create('rider_base_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('rider_base_localities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_base_operation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('locality_id')->constrained()->cascadeOnDelete();
            $table->unique(['rider_base_operation_id', 'locality_id']);
            $table->timestamps();
        });

        // Online/offline availability toggle (M4.3). Only online drivers
        // enter the job-matching pools.
        Schema::create('driver_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_online_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_availability');
        Schema::dropIfExists('rider_base_localities');
        Schema::dropIfExists('rider_base_operations');
    }
};
