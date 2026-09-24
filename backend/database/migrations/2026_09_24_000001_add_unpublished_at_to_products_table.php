<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->timestamp('unpublished_at')->nullable()->after('status');
        });

        // Existing unpublished listings count from their last update, so the
        // 7-day delete rule is fair to listings paused before this column.
        DB::table('products')
            ->whereIn('status', ['inactive', 'archived'])
            ->whereNull('unpublished_at')
            ->update(['unpublished_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('unpublished_at');
        });
    }
};
