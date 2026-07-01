<?php

namespace App\Policies;

use App\Models\PropertySubscription;
use App\Models\User;

class PropertySubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, PropertySubscription $propertySubscription): bool
    {
        return $user->is_admin;
    }
}
