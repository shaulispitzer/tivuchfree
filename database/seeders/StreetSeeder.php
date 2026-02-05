<?php

namespace Database\Seeders;

use App\Enums\Neighbourhood;
use App\Models\Street;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class StreetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $files = [
            'bar_ilan_streets.json' => Neighbourhood::BarIlan,
            'belz_streets.json' => Neighbourhood::Belz,
            'geula_streets.json' => Neighbourhood::Geula,
            'gush_80_streets.json' => Neighbourhood::Gush80,
            'mekor_baruch_streets.json' => Neighbourhood::MekorBaruch,
            'romema_streets.json' => Neighbourhood::Rommema,
            'sanhedria_murchevet_streets.json' => Neighbourhood::SanhedriaMurchavet,
            'sanhedria_streets.json' => Neighbourhood::Sanhedria,
            'sorotzkin_streets.json' => Neighbourhood::Sorotzkin,
        ];

        foreach ($files as $file => $neighbourhood) {
            $path = resource_path("js/plugins/i18n/{$file}");
            $streets = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

            foreach ($streets as $street) {
                $name = [
                    'en' => $street['en'],
                    'he' => $street['he'],
                ];

                Street::query()->firstOrCreate([
                    'neighbourhood' => $neighbourhood,
                    'name' => $name,
                ]);
            }
        }
    }
}
