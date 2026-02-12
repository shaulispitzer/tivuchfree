<?php

namespace App\Data\Forms;

use App\Data\Casts\IntegerArrayCast;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;

/** @typescript */
class PropertyFormData extends Data
{
    public function __construct(
        public array $neighbourhoods,
        public string $street,
        public float $floor,
        public PropertyLeaseType $type,
        public Carbon $available_from,
        public ?Carbon $available_to,
        public float $bedrooms,
        public PropertyFurnished $furnished,
        public ?int $temp_upload_id = null,
        #[Sometimes]
        #[Nullable]
        #[WithCast(IntegerArrayCast::class)]
        public ?array $image_media_ids = [],
        public ?int $main_image_media_id = null,
    ) {}
}
