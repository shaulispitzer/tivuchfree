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
        Schema::table('properties', function (Blueprint $table) {
            $table->json('neighbourhoods')->nullable()->after('user_id');
        });

        DB::table('properties')
            ->whereNotNull('neighbourhood')
            ->orderBy('id')
            ->chunkById(100, function ($properties): void {
                foreach ($properties as $property) {
                    DB::table('properties')
                        ->where('id', $property->id)
                        ->update([
                            'neighbourhoods' => json_encode([$property->neighbourhood], JSON_THROW_ON_ERROR),
                        ]);
                }
            });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['neighbourhood']);
            $table->dropColumn('neighbourhood');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('neighbourhood')->nullable()->index()->after('user_id');
        });

        DB::table('properties')
            ->whereNotNull('neighbourhoods')
            ->orderBy('id')
            ->chunkById(100, function ($properties): void {
                foreach ($properties as $property) {
                    $neighbourhoods = json_decode((string) $property->neighbourhoods, true);
                    $firstNeighbourhood = is_array($neighbourhoods) && count($neighbourhoods) > 0
                        ? $neighbourhoods[0]
                        : null;

                    DB::table('properties')
                        ->where('id', $property->id)
                        ->update([
                            'neighbourhood' => $firstNeighbourhood,
                        ]);
                }
            });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('neighbourhoods');
        });
    }
};
