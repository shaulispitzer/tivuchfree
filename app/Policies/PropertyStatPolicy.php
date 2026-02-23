<?php

namespace App\Policies;

use App\Models\PropertyStat;
use App\Models\User;

class PropertyStatPolicy
{
    public function delete(User $user, PropertyStat $propertyStat): bool
    {
        return $user->is_admin;
    }
}
