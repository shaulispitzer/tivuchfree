<?php

namespace App\Enums;

/** @typescript */
enum Locale: string
{
    use EnumHelpers;

    case EN = 'en';
    case HE = 'he';
}
