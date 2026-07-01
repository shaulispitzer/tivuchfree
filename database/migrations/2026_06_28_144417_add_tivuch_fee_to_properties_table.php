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
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('tivuch_fee')->default(false)->after('reported_taken_at');
            $table->timestamp('reported_tivuch_fee_at')->nullable()->after('tivuch_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['tivuch_fee', 'reported_tivuch_fee_at']);
        });
    }
};
