<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Badge fee snapshot (M5.4). The verification fee is frozen at issue time
     * so later admin changes never rewrite issued history. Nullable for
     * badges issued before the fee existed or when no fee is configured.
     */
    public function up(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->decimal('fee_inr', 10, 2)->nullable()->after('volunteer_name');
        });
    }

    public function down(): void
    {
        Schema::table('badges', function (Blueprint $table) {
            $table->dropColumn('fee_inr');
        });
    }
};