<?php

namespace App\Data\Forms;

use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Data;

class ReviewFormData extends Data
{
    public function __construct(
        #[Max(255)]
        public string $name,

        #[Email]
        #[Max(255)]
        public string $email,

        #[Nullable]
        #[Max(255)]
        public ?string $role,

        #[Max(2000)]
        public string $message,
    ) {}
}
