<?php

namespace App\Policies;

use App\Models\Neighbourhood;
use App\Models\User;

class NeighbourhoodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Neighbourhood $neighbourhood): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Neighbourhood $neighbourhood): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Neighbourhood $neighbourhood): bool
    {
        return $user->is_admin;
    }
}
