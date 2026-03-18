<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\PropertyGeocoder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GeocodeProperty implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Property $property,
        public string $street,
        public ?string $buildingNumber = null,
    ) {}

    public function handle(PropertyGeocoder $geocoder): void
    {
        $coordinates = $geocoder->geocode($this->street, $this->buildingNumber);

        $property = $this->property->fresh();

        if (! $property) {
            return;
        }

        $property->update([
            'lat' => $coordinates['lat'] ?? null,
            'lon' => $coordinates['lon'] ?? null,
        ]);
    }
}
