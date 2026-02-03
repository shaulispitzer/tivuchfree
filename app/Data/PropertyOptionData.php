<?php

namespace App\Data;

use App\Enums\PropertyOptionLabel;
use Spatie\LaravelData\Data;

/** @typescript */
class PropertyOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}

    public static function fromEnum(PropertyOptionLabel $case): self
    {
        return new self(
            value: $case->value,
            label: $case->label(),
        );
    }
}
