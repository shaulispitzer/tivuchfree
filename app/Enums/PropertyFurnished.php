<?php

namespace App\Enums;

enum PropertyFurnished: string implements PropertyOptionLabel
{
    use EnumHelpers;

    case Yes = 'yes';
    case Partially = 'partially';
    case No = 'no';

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Yes',
            self::Partially => 'Partially',
            self::No => 'No',
        };
    }
}
