<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            // Polymorphic-ish subject: "user" (registered) or "guest" (booking
            // contact, errand contact). Guests have no users row.
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('consent_key'); // registration|booking_contact|errand_contact|notifications
            $table->string('text_version');
            $table->string('purpose');
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id', 'consent_key']);
        });

        Schema::create('data_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // export|deletion
            $table->string('type');
            // pending|processing|completed|rejected
            $table->string('status')->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_requests');
        Schema::dropIfExists('consents');
    }
};
