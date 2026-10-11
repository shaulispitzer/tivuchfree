<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_view_visits', function (Blueprint $table) {
            $table->id();
            $table->date('visited_on');
            $table->string('visitor_key');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['visited_on', 'visitor_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_view_visits');
    }
};
