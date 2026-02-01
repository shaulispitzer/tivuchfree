<?php

namespace App\Enums;

/** @typescript */
enum NotificationType: string
{
    // use EnumHelpers;

    case SUCCESS = 'success';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
    case DEFAULT = 'default';
}
