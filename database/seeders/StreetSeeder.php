<?php

namespace Database\Seeders;

use App\Models\Neighbourhood;
use App\Models\Street;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class StreetSeeder extends Seeder
{
    /**
     * @return array<string, string>
     */
    protected function neighbourhoodFiles(): array
    {
        return [
            'bar_ilan_streets.json' => 'Bar Ilan',
            'belz_streets.json' => 'Belz',
            'geula_streets.json' => 'Geula',
            'gush_80_streets.json' => 'Gush 80',
            'mekor_baruch_streets.json' => 'Mekor Baruch',
            'romema_streets.json' => 'Romema',
            'sanhedria_murchevet_streets.json' => 'Sanhedria Murchavet',
            'sanhedria_streets.json' => 'Sanhedria',
            'sorotzkin_streets.json' => 'Sorotzkin',
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $neighbourhoodIdsByEnglishName = Neighbourhood::query()
            ->get()
            ->mapWithKeys(fn (Neighbourhood $neighbourhood) => [
                $neighbourhood->getTranslation('name', 'en') => $neighbourhood->id,
            ])
            ->all();

        foreach ($this->neighbourhoodFiles() as $file => $englishName) {
            $neighbourhoodId = $neighbourhoodIdsByEnglishName[$englishName] ?? null;

            if ($neighbourhoodId === null) {
                continue;
            }

            $path = resource_path("js/plugins/i18n/{$file}");
            $streets = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

            foreach ($streets as $street) {
                $name = [
                    'en' => $street['en'],
                    'he' => $street['he'],
                ];

                Street::query()->firstOrCreate([
                    'neighbourhood_id' => $neighbourhoodId,
                    'name' => $name,
                ]);
            }
        }
    }
}
