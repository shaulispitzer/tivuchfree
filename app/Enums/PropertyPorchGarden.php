<?php

namespace App\Enums;

enum PropertyPorchGarden: string implements PropertyOptionLabel
{
    use EnumHelpers;

    case Porch = 'porch';
    case Garden = 'garden';
    case No = 'no';

    public function label(): string
    {
        return match ($this) {
            self::Porch => 'Porch',
            self::Garden => 'Garden',
            self::No => 'No',
        };
    }
}
