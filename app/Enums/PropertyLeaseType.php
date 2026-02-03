<?php

namespace App\Enums;

enum PropertyLeaseType: string implements PropertyOptionLabel
{
    use EnumHelpers;

    case MediumTerm = 'medium_term';
    case LongTerm = 'long_term';

    public function label(): string
    {
        return match ($this) {
            self::MediumTerm => 'Medium term',
            self::LongTerm => 'Long term',
        };
    }
}
