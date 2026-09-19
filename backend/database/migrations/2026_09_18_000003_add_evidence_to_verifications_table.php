<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            // Validated media paths attached by the volunteer at report time (M5.6).
            // Stored here so admins can see the evidence; paths point at the
            // uploader-scoped rows created via the shared validator (M2.3).
            $table->json('evidence')->nullable()->after('checklist');
        });
    }

    public function down(): void
    {
        Schema::table('verifications', function (Blueprint $table) {
            $table->dropColumn('evidence');
        });
    }
};