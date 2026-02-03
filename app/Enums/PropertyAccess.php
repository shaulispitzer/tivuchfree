<?php

namespace App\Enums;

enum PropertyAccess: string implements PropertyOptionLabel
{
    use EnumHelpers;

    case StepFreeAccess = 'step_free_access';
    case StepsOnly = 'steps_only';
    case ElevatorNonShabbos = 'elevator_non_shabbos';
    case ElevatorShabbos = 'elevator_shabbos';

    public function label(): string
    {
        return match ($this) {
            self::StepFreeAccess => 'Step Free Access',
            self::StepsOnly => 'Steps Only',
            self::ElevatorNonShabbos => 'Elevator (Non-Shabbos)',
            self::ElevatorShabbos => 'Elevator (Shabbos)',
        };
    }
}
