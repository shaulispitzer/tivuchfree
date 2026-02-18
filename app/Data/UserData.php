<?php

namespace App\Data;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

/** @typescript */
class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,

        public bool $is_admin,

        public CarbonInterface $created_at,
        // public $class = 'User',
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: (int) ($user->id ?? 0),
            name: $user->name,
            email: $user->email,
            is_admin: $user->is_admin,
            created_at: $user->created_at,
        );
    }

    // for user on order->meta (guest checkout)
    public static function fromArray(array $userData): self
    {
        return new self(
            id: 0,
            name: data_get($userData, 'name'),
            email: data_get($userData, 'email'),
            is_admin: false,
            created_at: CarbonImmutable::now(),
        );
    }
}
