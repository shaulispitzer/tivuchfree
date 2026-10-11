<?php

namespace Database\Factories;

use App\Models\MapViewVisit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapViewVisit>
 */
class MapViewVisitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visited_on' => now()->toDateString(),
            'visitor_key' => 'session:'.$this->faker->uuid(),
            'user_id' => null,
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(function () use ($user): array {
            $user ??= User::factory()->create();

            return [
                'visitor_key' => 'user:'.$user->id,
                'user_id' => $user->id,
            ];
        });
    }
}
