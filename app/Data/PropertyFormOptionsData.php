<?php

namespace App\Data;

use App\Enums\PropertyOptionLabel;
use Spatie\LaravelData\Data;

/** @typescript */
class PropertyFormOptionsData extends Data
{
    /**
     * @param  array<int, PropertyOptionData>  $neighbourhoods
     * @param  array<int, PropertyOptionData>  $lease_types
     * @param  array<int, PropertyOptionData>  $furnished
     * @param  array<int, PropertyOptionData>  $access
     * @param  array<int, PropertyOptionData>  $kitchen_dining_room
     * @param  array<int, PropertyOptionData>  $porch_garden
     * @param  array<int, PropertyOptionData>  $air_conditioning
     * @param  array<int, PropertyOptionData>  $apartment_condition
     */
    public function __construct(
        public array $neighbourhoods,
        public array $lease_types,
        public array $furnished,
        public array $access,
        public array $kitchen_dining_room,
        public array $porch_garden,
        public array $air_conditioning,
        public array $apartment_condition,
    ) {}

    /**
     * @param  array<int, PropertyOptionLabel>  $neighbourhoods
     * @param  array<int, PropertyOptionLabel>  $leaseTypes
     * @param  array<int, PropertyOptionLabel>  $furnished
     * @param  array<int, PropertyOptionLabel>  $access
     * @param  array<int, PropertyOptionLabel>  $kitchenDiningRoom
     * @param  array<int, PropertyOptionLabel>  $porchGarden
     * @param  array<int, PropertyOptionLabel>  $airConditioning
     * @param  array<int, PropertyOptionLabel>  $apartmentCondition
     */
    public static function fromEnums(
        array $neighbourhoods,
        array $leaseTypes,
        array $furnished,
        array $access,
        array $kitchenDiningRoom,
        array $porchGarden,
        array $airConditioning,
        array $apartmentCondition,
    ): self {
        return new self(
            neighbourhoods: self::map($neighbourhoods),
            lease_types: self::map($leaseTypes),
            furnished: self::map($furnished),
            access: self::map($access),
            kitchen_dining_room: self::map($kitchenDiningRoom),
            porch_garden: self::map($porchGarden),
            air_conditioning: self::map($airConditioning),
            apartment_condition: self::map($apartmentCondition),
        );
    }

    /**
     * @param  array<int, PropertyOptionLabel>  $cases
     * @return array<int, PropertyOptionData>
     */
    protected static function map(array $cases): array
    {
        return array_map(
            fn (PropertyOptionLabel $case) => PropertyOptionData::fromEnum($case),
            $cases,
        );
    }
}
