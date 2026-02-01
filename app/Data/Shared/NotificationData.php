<?php

namespace App\Data\Shared;

use Spatie\LaravelData\Data;
use App\Enums\NotificationType;

class NotificationData extends Data
{
    public function __construct(
        public NotificationType $type,
        public string $body
    ) {
    }
}
