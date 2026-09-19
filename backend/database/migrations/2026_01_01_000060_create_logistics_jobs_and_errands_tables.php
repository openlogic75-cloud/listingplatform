<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            // pickup|delivery
            $table->string('type');
            $table->foreignId('collector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('district_id')->constrained()->restrictOnDelete();
            $table->foreignId('locality_id')->constrained()->restrictOnDelete();
            $table->string('address')->nullable();
            // requested|assigned|accepted|in_progress|completed|cancelled
            $table->string('status')->default('requested');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            // Matching query: jobs in a driver's base locality that are live.
            $table->index(['locality_id', 'status']);
        });

        Schema::create('errands', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            // Null customer_id = guest request (no account, contact fields only).
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('contact_name')->nullable(); // encrypted via model cast
            $table->string('contact_phone')->nullable(); // encrypted via model cast
            $table->string('contact_phone_index')->nullable();
            $table->text('description');
            $table->foreignId('pickup_district_id')->constrained('districts')->restrictOnDelete();
            $table->foreignId('pickup_locality_id')->constrained('localities')->restrictOnDelete();
            $table->string('pickup_address')->nullable();
            $table->foreignId('drop_district_id')->constrained('districts')->restrictOnDelete();
            $table->foreignId('drop_locality_id')->constrained('localities')->restrictOnDelete();
            $table->string('drop_address')->nullable();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            // requested|assigned|accepted|in_progress|completed|cancelled
            $table->string('status')->default('requested');
            $table->timestamps();
            $table->index(['pickup_locality_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('errands');
        Schema::dropIfExists('logistics_jobs');
    }
};
