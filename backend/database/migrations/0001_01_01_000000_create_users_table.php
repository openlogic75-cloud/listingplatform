<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // name/email/phone are encrypted at rest via the model's
            // "encrypted" casts (M1.5). The *_index columns hold keyed HMAC
            // hashes so lookups never decrypt the table.
            $table->string('name');
            $table->string('email');
            $table->string('email_index')->unique();
            $table->string('phone')->nullable();
            $table->string('phone_index')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            // vendor|driver|collector|worker|volunteer|admin
            $table->string('role');
            // No FK constraint: districts are created by a later migration
            // (users ship with the framework-level migration). Integrity is
            // enforced at the application layer; see M1.3 notes.
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
