<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single admin-managed row (M6.2). The donation page renders whatever
        // is stored here; the platform holds no payment data.
        Schema::create('donation_settings', function (Blueprint $table) {
            $table->id();
            $table->string('upi_id')->nullable(); // name@bank format, validated at M6.2
            $table->string('qr_path')->nullable(); // QR image on the public disk
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donation_settings');
    }
};
