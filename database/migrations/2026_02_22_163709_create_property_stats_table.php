<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->json('neighbourhoods')->nullable();
            $table->string('address')->nullable();
            $table->string('how_got_taken')->nullable();
            $table->decimal('price_advertised', 12, 2)->nullable();
            $table->decimal('price_taken_at', 12, 2)->nullable();
            $table->timestamp('date_taken')->nullable();
            $table->timestamp('date_advertised')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_stats');
    }
};
