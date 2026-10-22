<?php

namespace App\Policies;

use App\Models\Advertisement;
use App\Models\User;

class AdvertisementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Advertisement $advertisement): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Advertisement $advertisement): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Advertisement $advertisement): bool
    {
        return $user->is_admin;
    }
}
