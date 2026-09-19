<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Verification story + badge details (M25.1): an approved verification
     * becomes a volunteer-signed blog story with their photos, and the badge
     * carries the volunteer's name and photo.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('images')->nullable()->after('cover_image');
        });

        Schema::table('verification_volunteers', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('tada_notes');
        });

        Schema::table('badges', function (Blueprint $table) {
            $table->string('volunteer_photo')->nullable()->after('volunteer_name');
            $table->foreignId('post_id')->nullable()->after('fee_inr')
                ->constrained('posts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_id');
            $table->dropColumn('volunteer_photo');
        });

        Schema::table('verification_volunteers', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
