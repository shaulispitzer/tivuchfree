<?php

namespace App\Models;

use App\Data\PropertyOptionData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Neighbourhood extends Model
{
    /** @use HasFactory<\Database\Factories\NeighbourhoodFactory> */
    use HasFactory;

    use HasTranslations;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    public array $translatable = ['name'];

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class);
    }

    /**
     * @return array<int, PropertyOptionData>
     */
    public static function optionData(): array
    {
        return static::query()
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn (self $neighbourhood) => PropertyOptionData::fromNeighbourhood($neighbourhood))
            ->all();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, string>
     */
    public static function labelsForIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $locale = app()->getLocale();

        return static::query()
            ->whereIn('id', $ids, 'and', false)
            ->orderBy('id', 'asc')
            ->get()
            ->map(function (self $neighbourhood) use ($locale): string {
                $label = $neighbourhood->getTranslation('name', $locale, false);

                if (! is_string($label) || $label === '') {
                    $label = $neighbourhood->getTranslation('name', 'he');
                }

                return $label;
            })
            ->all();
    }
}
