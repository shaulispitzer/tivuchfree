<?php

namespace App\Enums;

enum PropertyApartmentCondition: string implements PropertyOptionLabel
{
    use EnumHelpers;

    case BrandNew = 'brand_new';
    case Excellent = 'excellent';
    case Good = 'good';
    case LivedIn = 'lived_in';

    public function label(): string
    {
        return match ($this) {
            self::BrandNew => 'Brand new',
            self::Excellent => 'Excellent',
            self::Good => 'Good',
            self::LivedIn => 'Lived In',
        };
    }
}
