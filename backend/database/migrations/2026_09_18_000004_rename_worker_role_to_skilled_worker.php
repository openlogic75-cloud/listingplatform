<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename the stored role value `worker` to `skilled_worker` (M10.3).
     * The `worker_profiles` table and its model keep their names — only the
     * role string changes.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'worker')
            ->update(['role' => 'skilled_worker']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'skilled_worker')
            ->update(['role' => 'worker']);
    }
};
