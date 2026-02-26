<?php

namespace Database\Factories;

use App\Enums\Neighbourhood;
use App\Enums\PropertyAccess;
use App\Enums\PropertyAirConditioning;
use App\Enums\PropertyApartmentCondition;
use App\Enums\PropertyFurnished;
use App\Enums\PropertyKitchenDiningRoom;
use App\Enums\PropertyLeaseType;
use App\Enums\PropertyPorchGarden;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(PropertyLeaseType::values());
        $availableFrom = fake()->dateTimeBetween('now', '+2 months');
        $availableTo = $type === PropertyLeaseType::MediumTerm->value
            ? fake()->dateTimeBetween($availableFrom, '+6 months')
            : null;

        return [
            'user_id' => User::factory(),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_phone_2' => fake()->optional(0.3)->phoneNumber(),
            'neighbourhoods' => [
                fake()->randomElement(Neighbourhood::values()),
            ],
            'price' => fake()->randomFloat(2, 2500, 18000),
            'street' => fake()->streetName(),
            'building_number' => (string) fake()->buildingNumber(),
            'floor' => fake()->randomFloat(1, 0, 12),
            'type' => $type,
            'available_from' => $availableFrom,
            'available_to' => $availableTo,
            'bedrooms' => fake()->numberBetween(1, 6),
            'square_meter' => fake()->numberBetween(50, 180),
            'views' => fake()->numberBetween(0, 200),
            'furnished' => fake()->randomElement(PropertyFurnished::values()),
            'taken' => false,
            'bathrooms' => fake()->numberBetween(1, 3),
            'access' => fake()->randomElement(PropertyAccess::values()),
            'kitchen_dining_room' => fake()->randomElement(PropertyKitchenDiningRoom::values()),
            'porch_garden' => fake()->randomElement(PropertyPorchGarden::values()),
            'succah_porch' => fake()->boolean(),
            'air_conditioning' => fake()->randomElement(PropertyAirConditioning::values()),
            'apartment_condition' => fake()->randomElement(PropertyApartmentCondition::values()),
            'additional_info' => fake()->optional()->paragraph(),
            'has_dud_shemesh' => fake()->boolean(),
            'has_machsan' => fake()->boolean(),
            'has_parking_spot' => fake()->boolean(),
        ];
    }

    public function taken(): static
    {
        return $this->state(fn (array $attributes) => [
            'taken' => true,
            'taken_at' => now(),
        ]);
    }
}
