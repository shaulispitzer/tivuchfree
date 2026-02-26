<?php

namespace App\Data;

use App\Enums\PropertyAccess;
use App\Enums\PropertyAirConditioning;
use App\Enums\PropertyApartmentCondition;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyKitchenDiningRoom;
use App\Enums\PropertyLeaseType;
use App\Enums\PropertyPorchGarden;
use App\Models\Property;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

/** @typescript */
class PropertyData extends Data
{
    /**
     * @param  array<int, string>  $image_urls
     * @param  array<int, string>  $neighbourhoods
     */
    public function __construct(
        public int $id,
        public ?int $user_id,
        public ?string $contact_name,
        public ?string $contact_phone,
        public array $neighbourhoods,
        public ?float $price,
        public string $street,
        public ?float $lat,
        public ?float $lon,
        public int $building_number,
        public float $floor,
        public PropertyLeaseType $type,
        public CarbonInterface $available_from,
        public ?CarbonInterface $available_to,
        public float $bedrooms,
        public ?int $square_meter,
        public int $views,
        public PropertyFurnished $furnished,
        public bool $taken,
        public ?int $bathrooms,
        public ?PropertyAccess $access,
        public ?PropertyKitchenDiningRoom $kitchen_dining_room,
        public ?PropertyPorchGarden $porch_garden,
        public bool $succah_porch,
        public ?PropertyAirConditioning $air_conditioning,
        public ?PropertyApartmentCondition $apartment_condition,
        public ?string $additional_info,
        public bool $has_dud_shemesh,
        public bool $has_machsan,
        public bool $has_parking_spot,
        public ?UserData $user,
        public ?string $main_image_url,
        public array $image_urls,
        public CarbonInterface $created_at,
    ) {}

    public static function fromModel(
        Property $property,

    ): self {
        return new self(
            id: $property->id,
            user_id: $property->user_id,
            contact_name: $property->contact_name ?? null,
            contact_phone: $property->contact_phone ?? null,
            neighbourhoods: $property->neighbourhoods ?? [],
            price: $property->price !== null ? (float) $property->price : null,
            street: $property->street,
            lat: $property->lat !== null ? (float) $property->lat : null,
            lon: $property->lon !== null ? (float) $property->lon : null,
            building_number: (int) $property->building_number,
            floor: (float) $property->floor,
            type: $property->type,
            available_from: $property->available_from,
            available_to: $property->available_to,
            bedrooms: (float) $property->bedrooms,
            square_meter: $property->square_meter,
            views: $property->views,
            furnished: $property->furnished,
            taken: $property->taken,
            bathrooms: $property->bathrooms,
            access: $property->access,
            kitchen_dining_room: $property->kitchen_dining_room,
            porch_garden: $property->porch_garden,
            succah_porch: $property->succah_porch,
            air_conditioning: $property->air_conditioning,
            apartment_condition: $property->apartment_condition,
            additional_info: $property->additional_info,
            has_dud_shemesh: $property->has_dud_shemesh,
            has_machsan: $property->has_machsan,
            has_parking_spot: $property->has_parking_spot,
            user: $property->user !== null ? UserData::fromModel($property->user) : null,
            main_image_url: $property->getFirstMediaUrl('main_image') ?: null,
            image_urls: $property->getMedia('images')->map(fn ($media) => $media->getUrl())->all(),
            created_at: $property->created_at,
        );
    }
}
