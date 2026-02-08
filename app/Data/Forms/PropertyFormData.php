<?php

namespace App\Data\Forms;

use App\Enums\PropertyFurnished;
use App\Enums\PropertyLeaseType;
use Carbon\Carbon;
use Spatie\LaravelData\Data;

/** @typescript */
class PropertyFormData extends Data
{
    public function __construct(
        public string $street,
        public string $floor,
        public PropertyLeaseType $type,
        public Carbon $available_from,
        public ?Carbon $available_to,
        public int $bedrooms,
        public PropertyFurnished $furnished,
    ) {}
}
