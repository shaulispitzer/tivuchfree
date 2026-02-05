<?php

namespace Database\Factories;

use App\Enums\Neighbourhood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Street>
 */
class StreetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'en' => fake()->streetName(),
                'he' => fake()->streetName(),
            ],
            'neighbourhood' => fake()->randomElement(Neighbourhood::values()),
        ];
    }
}
