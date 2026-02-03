<?php

namespace App\Enums;

enum PropertyKitchenDiningRoom: string implements PropertyOptionLabel
{
    use EnumHelpers;

    case Separate = 'separate';
    case NotSeparate = 'not_separate';
    case PartlySeparate = 'partly_separate';
    case NoKitchen = 'no_kitchen';

    public function label(): string
    {
        return match ($this) {
            self::Separate => 'Separate',
            self::NotSeparate => 'Not Separate',
            self::PartlySeparate => 'Partly separate',
            self::NoKitchen => 'No Kitchen',
        };
    }
}
