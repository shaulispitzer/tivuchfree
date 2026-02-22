<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropertyStat>
 */
class PropertyStatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => \App\Models\Property::factory(),
            'neighbourhoods' => [$this->faker->city()],
            'address' => $this->faker->address(),
            'how_got_taken' => $this->faker->randomElement(['website', 'phone', 'referral', 'other']),
            'price_advertised' => $this->faker->randomFloat(2, 1000, 10000),
            'price_taken_at' => $this->faker->randomFloat(2, 1000, 10000),
            'date_taken' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'date_advertised' => $this->faker->dateTimeBetween('-2 years', '-1 year'),
        ];
    }
}
