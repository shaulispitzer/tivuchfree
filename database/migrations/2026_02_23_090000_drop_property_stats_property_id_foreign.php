<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_stats', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
        });
    }

    public function down(): void
    {
        Schema::table('property_stats', function (Blueprint $table) {
            $table->foreign('property_id')->references('id')->on('properties')->cascadeOnDelete();
        });
    }
};
