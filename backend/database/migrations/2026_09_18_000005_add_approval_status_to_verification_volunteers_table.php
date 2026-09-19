<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Volunteer approval (M17.1). Volunteers register as pending and cannot
     * sign in until an admin approves them in the dashboard.
     */
    public function up(): void
    {
        Schema::table('verification_volunteers', function (Blueprint $table) {
            $table->string('verification_status', 20)->default('pending')->after('user_id');
            $table->foreignId('reviewed_by')->nullable()->after('tada_notes')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });

        // Volunteers that existed before approval was introduced stay usable.
        DB::table('verification_volunteers')->update([
            'verification_status' => 'approved',
        ]);
    }

    public function down(): void
    {
        Schema::table('verification_volunteers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['verification_status', 'reviewed_at']);
        });
    }
};
