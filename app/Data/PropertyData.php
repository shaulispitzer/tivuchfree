<?php

namespace App\Data;

use App\Enums\Neighbourhood;
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
     */
    public function __construct(
        public int $id,
        public int $user_id,
        public ?Neighbourhood $neighbourhood,
        public ?float $price,
        public string $street,
        public ?string $building_number,
        public string $floor,
        public PropertyLeaseType $type,
        public CarbonInterface $available_from,
        public ?CarbonInterface $available_to,
        public int $bedrooms,
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
        public UserData $user,
        public ?string $main_image_url,
        public array $image_urls,
        public bool $can_edit,
    ) {}

    public static function fromModel(Property $property, bool $canEdit = false): self
    {
        return new self(
            id: $property->id,
            user_id: $property->user_id,
            neighbourhood: $property->neighbourhood,
            price: $property->price !== null ? (float) $property->price : null,
            street: $property->street,
            building_number: $property->building_number,
            floor: $property->floor,
            type: $property->type,
            available_from: $property->available_from,
            available_to: $property->available_to,
            bedrooms: $property->bedrooms,
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
            user: UserData::fromModel($property->user),
            main_image_url: $property->getFirstMediaUrl('main_image') ?: null,
            image_urls: $property->getMedia('images')->map(fn ($media) => $media->getUrl())->all(),
            can_edit: $canEdit,
        );
    }
}
