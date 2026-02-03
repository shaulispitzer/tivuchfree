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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('neighbourhood_id')->nullable()->index();
            $table->string('listing_id');
            $table->decimal('price', 12, 2)->nullable();
            $table->string('street');
            $table->string('building_number')->nullable();
            $table->string('floor');
            $table->string('type');
            $table->date('available_from');
            $table->date('available_to')->nullable();
            $table->unsignedSmallInteger('bedrooms');
            $table->unsignedSmallInteger('square_meter')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->string('furnished');
            $table->boolean('taken')->default(false);
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->string('access')->nullable();
            $table->string('kitchen_dining_room')->nullable();
            $table->string('porch_garden')->nullable();
            $table->boolean('succah_porch')->default(false);
            $table->string('air_conditioning')->nullable();
            $table->string('apartment_condition')->nullable();
            $table->text('additional_info')->nullable();
            $table->boolean('has_dud_shemesh')->default(false);
            $table->boolean('has_machsan')->default(false);
            $table->boolean('has_parking_spot')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
