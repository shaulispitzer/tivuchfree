<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('properties')
            ->select(['id', 'additional_info'])
            ->whereNotNull('additional_info')
            ->orderBy('id')
            ->chunkById(200, function ($properties): void {
                foreach ($properties as $property) {
                    $additionalInfo = $property->additional_info;

                    if (! is_string($additionalInfo)) {
                        continue;
                    }

                    $decoded = json_decode($additionalInfo, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        continue;
                    }

                    DB::table('properties')
                        ->where('id', $property->id)
                        ->update([
                            'additional_info' => json_encode([
                                'en' => $additionalInfo,
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ]);
                }
            });

        Schema::table('properties', function (Blueprint $table) {
            $table->json('additional_info')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->text('additional_info')->nullable()->change();
        });
    }
};
