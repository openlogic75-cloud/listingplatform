<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            // traditional|agro|rental_homestay
            $table->string('category');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            // kg, piece, dozen, night, day, ...
            $table->string('unit')->nullable();
            // Minimum order quantity (MOQ semantics pending Q5; default 1).
            $table->unsignedInteger('moq')->default(1);
            $table->unsignedInteger('stock')->nullable();
            $table->string('batch_code')->nullable();
            // Availability window for rentals/homestays (no stock concept).
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();
            $table->json('images')->nullable();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('locality_id')->nullable()->constrained()->nullOnDelete();
            // draft|active|inactive|archived
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
