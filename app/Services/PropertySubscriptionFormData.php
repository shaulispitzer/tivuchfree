<?php

namespace App\Services;

use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use App\Models\Neighbourhood;

class PropertySubscriptionFormData
{
    /**
     * @return array<string, mixed>
     */
    public static function options(): array
    {
        return [
            'neighbourhood_options' => Neighbourhood::optionData(),
            'furnished_options' => array_map(
                fn (PropertyFurnished $f) => ['value' => $f->value, 'label' => $f->label()],
                PropertyFurnished::cases(),
            ),
            'type_options' => array_map(
                fn (PropertyLeaseType $t) => ['value' => $t->value, 'label' => $t->label()],
                PropertyLeaseType::cases(),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function filtersToFrontend(array $filters): array
    {
        $min = $filters['bedrooms_min'] ?? 1;
        $max = $filters['bedrooms_max'] ?? 10;

        $neighbourhoods = $filters['neighbourhoods'] ?? null;
        if (is_array($neighbourhoods)) {
            $neighbourhoods = array_values(array_map(
                static fn (mixed $id): string => (string) (int) $id,
                $neighbourhoods,
            ));
        } else {
            $legacy = $filters['neighbourhood'] ?? null;
            $neighbourhoods = is_string($legacy) && $legacy !== '' ? [$legacy] : [];
        }

        return [
            'neighbourhoods' => $neighbourhoods,
            'hide_taken_properties' => ($filters['availability'] ?? 'all') === 'available',
            'bedrooms_range' => [min($min, $max), max($min, $max)],
            'furnished' => $filters['furnished'] ?? '',
            'type' => $filters['type'] ?? '',
            'available_from' => $filters['available_from'] ?? '',
            'available_to' => $filters['available_to'] ?? '',
        ];
    }
}
