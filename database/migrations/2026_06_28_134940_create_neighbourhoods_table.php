<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return array<int, array{en: string, he: string}>
     */
    protected function seedData(): array
    {
        return [
            ['en' => 'Sanhedria', 'he' => 'סנהדריה'],
            ['en' => 'Sanhedria Murchavet', 'he' => 'סנהדריה מורחבת'],
            ['en' => 'Bar Ilan', 'he' => 'בר אילן'],
            ['en' => 'Gush 80', 'he' => 'גוש 80'],
            ['en' => 'Belz', 'he' => 'באלז'],
            ['en' => 'Romema', 'he' => 'רוממה'],
            ['en' => 'Sorotzkin', 'he' => 'סורוטסקין'],
            ['en' => 'Mekor Baruch', 'he' => 'מקור ברוך'],
            ['en' => 'Geula', 'he' => 'גאולה'],
            ['en' => 'Givat Shaul', 'he' => 'גבעת שאול'],
            ['en' => 'Har Nof', 'he' => 'הר נוף'],
            ['en' => 'Ramat Eshkol', 'he' => 'רמת אשכול'],
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('neighbourhoods', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->timestamps();
        });

        $now = now();

        foreach ($this->seedData() as $names) {
            DB::table('neighbourhoods')->insert([
                'name' => json_encode($names, JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neighbourhoods');
    }
};
