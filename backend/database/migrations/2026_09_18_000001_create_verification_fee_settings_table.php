<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verification fee settings row (M5.4). Single admin-managed amount in
     * INR. The platform never stores or processes payment credentials — the
     * fee is display-only and collected by the volunteer directly at the visit.
     */
    public function up(): void
    {
        Schema::create('verification_fee_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount_inr', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_fee_settings');
    }
};