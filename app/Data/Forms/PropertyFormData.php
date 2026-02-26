<?php

namespace App\Data\Forms;

use App\Data\Casts\IntegerArrayCast;
use App\Enums\PropertyAccess;
use App\Enums\PropertyAirConditioning;
use App\Enums\PropertyApartmentCondition;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyKitchenDiningRoom;
use App\Enums\PropertyLeaseType;
use App\Enums\PropertyPorchGarden;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

/** @typescript */
class PropertyFormData extends Data
{
    public function __construct(
        public string $contact_name,
        public string $contact_phone,
        public ?string $contact_phone_2 = null,
        public ?string $email,
        public ?float $price,
        public array $neighbourhoods,
        public ?int $building_number,
        public int $street,
        public float $floor,
        public ?float $square_meter,
        public PropertyLeaseType $type,
        public Carbon $available_from,
        public ?Carbon $available_to,
        public float $bedrooms,
        public ?float $bathrooms,
        public PropertyFurnished $furnished,
        public PropertyAccess $access,
        public PropertyKitchenDiningRoom $kitchen_dining_room,
        public PropertyPorchGarden $porch_garden,
        public bool $succah_porch,
        public PropertyAirConditioning $air_conditioning,
        public PropertyApartmentCondition $apartment_condition,
        public ?string $additional_info,
        public ?string $additional_info_en,
        public ?string $additional_info_he,
        public bool $has_dud_shemesh,
        public bool $has_machsan,
        public bool $has_parking_spot,
        public ?int $temp_upload_id = null,

        #[Sometimes]
        #[Nullable]
        #[WithCast(IntegerArrayCast::class)]
        public ?array $image_media_ids = [],
        public ?int $main_image_media_id = null,
    ) {}
}
