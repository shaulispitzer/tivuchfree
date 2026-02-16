<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class PropertyGeocoder
{
    /**
     * @return array{lat: float, lon: float}|null
     */
    public function geocode(string $street, ?string $buildingNumber = null): ?array
    {
        $streetValue = trim($street);

        if ($streetValue === '') {
            return null;
        }

        if (is_string($buildingNumber) && trim($buildingNumber) !== '') {
            $streetValue = trim($buildingNumber).' '.$streetValue;
        }

        try {
            /** @var Response $response */
            $response = Http::acceptJson()
                ->withHeaders([
                    'User-Agent' => config('app.name').'/1.0',
                ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'street' => $streetValue,
                    'city' => 'ירושלים',
                    'country' => 'Israel',
                    'format' => 'json',
                    'limit' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $location = $response->json();

            if (! is_array($location) || ! isset($location[0]) || ! is_array($location[0])) {
                return null;
            }

            $lat = $location[0]['lat'] ?? null;
            $lon = $location[0]['lon'] ?? null;

            if (! is_numeric($lat) || ! is_numeric($lon)) {
                return null;
            }

            return [
                'lat' => (float) $lat,
                'lon' => (float) $lon,
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
