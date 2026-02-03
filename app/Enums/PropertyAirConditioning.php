<?php

namespace App\Enums;

enum PropertyAirConditioning: string implements PropertyOptionLabel
{
    use EnumHelpers;

    case FullyAirconditioned = 'fully_airconditioned';
    case PartlyAirconditioned = 'partly_airconditioned';
    case NotAirconditioned = 'not_airconditioned';

    public function label(): string
    {
        return match ($this) {
            self::FullyAirconditioned => 'Fully Airconditioned',
            self::PartlyAirconditioned => 'Partly Airconditioned',
            self::NotAirconditioned => 'Not Airconditioned',
        };
    }
}
