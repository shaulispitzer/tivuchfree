<?php

namespace App\Data;

use App\Enums\Locale;
use Spatie\LaravelData\Data;

/** @typescript */
class LocaleData extends Data
{
    public function __construct(
        public Locale $locale,

    ) {}
}
