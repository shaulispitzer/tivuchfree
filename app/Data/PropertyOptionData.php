<?php

namespace App\Data;

use App\Enums\PropertyOptionLabel;
use App\Models\Neighbourhood;
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

    public static function fromNeighbourhood(Neighbourhood $neighbourhood): self
    {
        $locale = app()->getLocale();
        $label = $neighbourhood->getTranslation('name', $locale, false);

        if (! is_string($label) || $label === '') {
            $label = $neighbourhood->getTranslation('name', 'he');
        }

        return new self(
            value: (string) $neighbourhood->id,
            label: $label,
        );
    }
}
