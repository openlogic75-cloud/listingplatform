<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Affiliate commissions are recorded, not paid (M21.2 refinement): each
     * conversion starts pending and only counts once the owner personally
     * approves it — they decide who they are willing to pay. Nothing moves
     * through the platform.
     */
    public function up(): void
    {
        Schema::table('referral_events', function (Blueprint $table) {
            $table->string('status', 12)->default('pending')->after('type');
            $table->foreignId('approved_by')->nullable()->after('amount_inr')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('referral_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'approved_at']);
        });
    }
};
