<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenStreetMapStreetCsvGenerator
{
    private const string OVERPASS_URL = 'https://overpass-api.de/api/interpreter';

    private const int TIMEOUT_SECONDS = 30;

    private const float MAX_BBOX_SPAN = 0.25;

    public function generateCsv(
        string $neighbourhoodNameEn,
        float $south,
        float $west,
        float $north,
        float $east,
    ): string {
        $streets = $this->fetchStreets($south, $west, $north, $east);

        return $this->buildCsv($neighbourhoodNameEn, $streets);
    }

    /**
     * @return array<int, array{name_en: string, name_he: string}>
     */
    public function fetchStreets(float $south, float $west, float $north, float $east): array
    {
        $query = sprintf(
            "[out:json][timeout:25];\nway[\"highway\"][\"name\"](%s,%s,%s,%s);\nout tags;",
            $this->formatCoordinate($south),
            $this->formatCoordinate($west),
            $this->formatCoordinate($north),
            $this->formatCoordinate($east),
        );

        $response = Http::withHeaders([
            'User-Agent' => config('app.name').'/1.0',
        ])
            ->timeout(self::TIMEOUT_SECONDS)
            ->withBody($query, 'text/plain')
            ->post(self::OVERPASS_URL);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to fetch street data from OpenStreetMap. Please try again later.');
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['elements']) || ! is_array($data['elements'])) {
            throw new RuntimeException('Unexpected response from OpenStreetMap.');
        }

        return $this->parseElements($data['elements']);
    }

    /**
     * @param  array<int, array{name_en: string, name_he: string}>  $streets
     */
    public function buildCsv(string $neighbourhoodNameEn, array $streets): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Failed to generate CSV file.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['neighbourhood', 'name_en', 'name_he']);

        $sortedStreets = $streets;
        usort(
            $sortedStreets,
            fn (array $left, array $right): int => strcasecmp($left['name_en'], $right['name_en']),
        );

        foreach ($sortedStreets as $street) {
            fputcsv($handle, [
                $neighbourhoodNameEn,
                $street['name_en'],
                $street['name_he'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        if ($csv === false) {
            throw new RuntimeException('Failed to generate CSV file.');
        }

        return $csv;
    }

    /**
     * @param  array<int, array<string, mixed>>  $elements
     * @return array<int, array{name_en: string, name_he: string}>
     */
    private function parseElements(array $elements): array
    {
        $streets = [];
        $seen = [];

        foreach ($elements as $element) {
            if (! is_array($element) || ($element['type'] ?? null) !== 'way') {
                continue;
            }

            $tags = $element['tags'] ?? null;

            if (! is_array($tags)) {
                continue;
            }

            $nameEn = trim((string) ($tags['name:en'] ?? $tags['name'] ?? ''));

            if ($nameEn === '') {
                continue;
            }

            $nameHe = trim((string) ($tags['name:he'] ?? $tags['name'] ?? ''));

            if ($nameHe === '') {
                continue;
            }

            $key = mb_strtolower($nameEn).'|'.mb_strtolower($nameHe);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $streets[] = [
                'name_en' => $nameEn,
                'name_he' => $nameHe,
            ];
        }

        usort(
            $streets,
            fn (array $left, array $right): int => strcasecmp($left['name_en'], $right['name_en']),
        );

        return $streets;
    }

    private function formatCoordinate(float $coordinate): string
    {
        return rtrim(rtrim(sprintf('%.8F', $coordinate), '0'), '.');
    }

    public static function maxBoundingBoxSpan(): float
    {
        return self::MAX_BBOX_SPAN;
    }
}
