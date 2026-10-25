<?php

namespace Database\Factories;

use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Advertisement>
 */
class AdvertisementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $image = UploadedFile::fake()->image('ad.jpg', 1200, 800);
        $path = Storage::disk(Advertisement::diskName())->putFile('ads', $image);

        return [
            'position' => Advertisement::MIN_POSITION,
            'url' => fake()->url(),
            'is_active' => false,
            'image_path' => $path,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function position(int $position): static
    {
        return $this->state(fn (array $attributes): array => [
            'position' => $position,
        ]);
    }
}
