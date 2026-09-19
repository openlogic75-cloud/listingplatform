<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_volunteers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('availability')->nullable();
            // TA/DA records for volunteer reimbursements (Q6 pending).
            $table->text('tada_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_id')->constrained('verification_volunteers')->cascadeOnDelete();
            // Polymorphic subject: Vendor or Product.
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->text('notes');
            $table->json('checklist')->nullable();
            $table->decimal('geo_lat', 10, 7)->nullable();
            $table->decimal('geo_lng', 10, 7)->nullable();
            // draft|submitted|approved|rejected
            $table->string('status')->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            // Immutable volunteer-name snapshot: survives volunteer deletion
            // so verified attribution outlives the person (DPDP M7.3).
            $table->string('volunteer_name');
            $table->timestamp('issued_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badges');
        Schema::dropIfExists('verifications');
        Schema::dropIfExists('verification_volunteers');
    }
};
