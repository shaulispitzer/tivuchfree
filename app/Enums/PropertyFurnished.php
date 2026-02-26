<?php

namespace App\Enums;

enum PropertyFurnished: string implements PropertyOptionLabel
{
    use EnumHelpers;

    case FullyFurnished = 'fully_furnished';
    case PartiallyFurnished = 'partially_furnished';
    case NotFurnished = 'not_furnished';

    public function label(): string
    {
        return match ($this) {
            self::FullyFurnished => 'Fully Furnished',
            self::PartiallyFurnished => 'Partially Furnished',
            self::NotFurnished => 'Not Furnished',
        };
    }
}
