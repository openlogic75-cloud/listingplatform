<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales reporting (M21.1) and peer-to-peer affiliate commissions (M21.2).
     *
     * No money moves through the platform: these columns only record what the
     * two parties agreed and settled directly.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('status');
            $table->string('referral_code')->nullable()->after('completed_at');
            $table->index('referral_code');
        });

        Schema::table('errands', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->after('status');
            $table->index('referral_code');
        });

        // Referrals can now be owned by a vendor OR a driver, with a
        // commission the owner sets. vendor_id becomes nullable for drivers.
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->change();
            $table->foreignId('owner_user_id')->nullable()->after('vendor_id')
                ->constrained('users')->nullOnDelete();
            $table->string('commission_type', 10)->default('percent')->after('code');
            $table->decimal('commission_value', 10, 2)->default(0)->after('commission_type');
        });

        // Existing codes were vendor-owned.
        DB::table('referrals')
            ->whereNull('owner_user_id')
            ->whereNotNull('vendor_id')
            ->orderBy('id')
            ->eachById(function (object $referral): void {
                $userId = DB::table('vendors')
                    ->where('id', $referral->vendor_id)
                    ->value('user_id');

                if ($userId !== null) {
                    DB::table('referrals')
                        ->where('id', $referral->id)
                        ->update(['owner_user_id' => $userId]);
                }
            });

        Schema::table('referral_events', function (Blueprint $table) {
            $table->decimal('order_value', 10, 2)->nullable()->after('attributed_user_id');
            $table->decimal('amount_inr', 10, 2)->nullable()->after('order_value');
        });
    }

    public function down(): void
    {
        Schema::table('referral_events', function (Blueprint $table) {
            $table->dropColumn(['order_value', 'amount_inr']);
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
            $table->dropColumn(['commission_type', 'commission_value']);
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable(false)->change();
        });

        Schema::table('errands', function (Blueprint $table) {
            $table->dropColumn('referral_code');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'referral_code']);
        });
    }
};
