<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyStat;

class PropertyStatRecorder
{
    /**
     * @param  array{how_got_taken?: string|null, price_taken_at?: float|int|string|null}  $takenData
     */
    public function recordTaken(Property $property, array $takenData = []): PropertyStat
    {
        return PropertyStat::updateOrCreate(
            ['property_id' => $property->id],
            [
                ...$this->baseAttributes($property),
                'how_got_taken' => $takenData['how_got_taken'] ?? null,
                'price_taken_at' => $takenData['price_taken_at'] ?? null,
                'date_taken' => $property->taken_at ?? now(),
            ],
        );
    }

    public function recordDeleted(Property $property): ?PropertyStat
    {
        if (PropertyStat::query()->where('property_id', $property->id)->exists()) {
            return null;
        }

        return PropertyStat::query()->create($this->baseAttributes($property));
    }

    /**
     * @return array{
     *   property_id: int,
     *   type: string|null,
     *   neighbourhoods: array<int, mixed>|null,
     *   address: string|null,
     *   price_advertised: float|int|string|null,
     *   date_advertised: \Illuminate\Support\CarbonImmutable|\Illuminate\Support\Carbon|null
     * }
     */
    protected function baseAttributes(Property $property): array
    {
        return [
            'property_id' => (int) $property->id,
            'type' => $property->type?->value,
            'neighbourhoods' => is_array($property->neighbourhoods) && count($property->neighbourhoods) > 0
                ? $property->neighbourhoods
                : null,
            'address' => $this->address($property),
            'price_advertised' => $property->price,
            'date_advertised' => $property->created_at,
        ];
    }

    protected function address(Property $property): ?string
    {
        $street = trim((string) ($property->street ?? ''));
        $buildingNumber = $property->building_number ? (string) $property->building_number : '';

        $address = trim($street.' '.$buildingNumber);

        return $address !== '' ? $address : null;
    }
}
