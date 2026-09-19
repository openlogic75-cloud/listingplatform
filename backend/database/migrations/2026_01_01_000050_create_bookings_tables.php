<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guest bookings: no account, only encrypted contact fields plus a
        // booking code + phone blind index for lookup (M3).
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            // pending|confirmed|picked_up|in_transit|delivered|completed|cancelled
            $table->string('status')->default('pending');
            $table->string('contact_name'); // encrypted via model cast
            $table->string('contact_phone'); // encrypted via model cast
            $table->string('contact_phone_index'); // keyed HMAC lookup hash
            $table->boolean('is_reseller')->default(false);
            $table->text('notes')->nullable();
            // Q1 default A: settlement happens offline; the platform never
            // touches money and only tracks fulfilment.
            $table->boolean('settled_offline')->default(true);
            $table->timestamps();
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_snapshot', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('bookings');
    }
};
