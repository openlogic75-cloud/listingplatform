<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Collectors & reseller farm produce (M28).
     *
     * A locality is a sub-division; one collector is signed to each by an
     * admin. Collectors collect farm produce from their sub-division to a hub
     * district; they are not part of the delivery/errand flow.
     */
    public function up(): void
    {
        Schema::create('collector_assignments', function (Blueprint $table) {
            $table->id();
            // One sub-division per collector, and one collector per sub-division.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('locality_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });

        Schema::table('districts', function (Blueprint $table) {
            // Hub districts are where collectors deliver farm produce.
            $table->boolean('is_hub')->default(false)->after('is_active');
        });

        Schema::table('logistics_jobs', function (Blueprint $table) {
            // Collection jobs (type collect_produce) carry a destination hub.
            $table->foreignId('drop_district_id')->nullable()->after('locality_id')
                ->constrained('districts')->nullOnDelete();
            $table->decimal('fee_inr', 10, 2)->nullable()->after('drop_district_id');
        });

        // The first region's hubs (Q10 Nagaland): admin-editable afterwards.
        DB::table('districts')
            ->whereIn('name', ['Dimapur', 'Kohima', 'Chümoukedima'])
            ->update(['is_hub' => true]);
    }

    public function down(): void
    {
        Schema::table('logistics_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('drop_district_id');
            $table->dropColumn('fee_inr');
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropColumn('is_hub');
        });

        Schema::dropIfExists('collector_assignments');
    }
};
