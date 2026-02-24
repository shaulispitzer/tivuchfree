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
        Schema::create('property_subscription_pendings', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->json('filters');
            $table->string('otp_code', 6);
            $table->timestamp('otp_expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_subscription_pendings');
    }
};
