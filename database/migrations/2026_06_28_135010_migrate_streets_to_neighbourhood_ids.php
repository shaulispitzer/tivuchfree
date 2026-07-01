<?php

use App\Models\Neighbourhood;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return array<string, int>
     */
    protected function legacyNameToIdMap(): array
    {
        $map = [];

        Neighbourhood::query()->orderBy('id', 'asc')->each(function (Neighbourhood $neighbourhood) use (&$map): void {
            $englishName = $neighbourhood->getTranslation('name', 'en');
            $map[$englishName] = $neighbourhood->id;
        });

        return $map;
    }

    /**
     * @param  array<int, mixed>  $values
     * @param  array<string, int>  $map
     * @return array<int, int>
     */
    protected function mapNeighbourhoodValues(array $values, array $map): array
    {
        $ids = [];

        foreach ($values as $value) {
            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $ids[] = (int) $value;

                continue;
            }

            if (is_string($value) && isset($map[$value])) {
                $ids[] = $map[$value];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $map = $this->legacyNameToIdMap();

        Schema::table('streets', function (Blueprint $table) {
            $table->foreignId('neighbourhood_id')->nullable()->after('name')->constrained()->cascadeOnUpdate();
        });

        DB::table('streets')
            ->orderBy('id')
            ->chunkById(100, function ($streets) use ($map): void {
                foreach ($streets as $street) {
                    $legacyValue = $street->neighbourhood;
                    $neighbourhoodId = is_string($legacyValue) ? ($map[$legacyValue] ?? null) : null;

                    if ($neighbourhoodId === null) {
                        continue;
                    }

                    DB::table('streets')
                        ->where('id', $street->id)
                        ->update(['neighbourhood_id' => $neighbourhoodId]);
                }
            });

        Schema::table('streets', function (Blueprint $table) {
            $table->dropColumn('neighbourhood');
        });

        DB::table('properties')
            ->whereNotNull('neighbourhoods')
            ->orderBy('id')
            ->chunkById(100, function ($properties) use ($map): void {
                foreach ($properties as $property) {
                    $decoded = json_decode((string) $property->neighbourhoods, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    $ids = $this->mapNeighbourhoodValues($decoded, $map);

                    DB::table('properties')
                        ->where('id', $property->id)
                        ->update([
                            'neighbourhoods' => json_encode($ids, JSON_THROW_ON_ERROR),
                        ]);
                }
            });

        DB::table('property_subscriptions')
            ->whereNotNull('filters')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($map): void {
                foreach ($subscriptions as $subscription) {
                    $filters = json_decode((string) $subscription->filters, true);

                    if (! is_array($filters)) {
                        continue;
                    }

                    $neighbourhoods = $filters['neighbourhoods'] ?? null;

                    if (! is_array($neighbourhoods) || $neighbourhoods === []) {
                        continue;
                    }

                    $filters['neighbourhoods'] = $this->mapNeighbourhoodValues($neighbourhoods, $map);

                    DB::table('property_subscriptions')
                        ->where('id', $subscription->id)
                        ->update([
                            'filters' => json_encode($filters, JSON_THROW_ON_ERROR),
                        ]);
                }
            });

        DB::table('property_stats')
            ->whereNotNull('neighbourhoods')
            ->orderBy('id')
            ->chunkById(100, function ($stats) use ($map): void {
                foreach ($stats as $stat) {
                    $decoded = json_decode((string) $stat->neighbourhoods, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    $ids = $this->mapNeighbourhoodValues($decoded, $map);

                    DB::table('property_stats')
                        ->where('id', $stat->id)
                        ->update([
                            'neighbourhoods' => json_encode($ids, JSON_THROW_ON_ERROR),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $idToName = [];

        Neighbourhood::query()->orderBy('id', 'asc')->each(function (Neighbourhood $neighbourhood) use (&$idToName): void {
            $idToName[$neighbourhood->id] = $neighbourhood->getTranslation('name', 'en');
        });

        Schema::table('streets', function (Blueprint $table) {
            $table->string('neighbourhood')->nullable()->after('name');
        });

        DB::table('streets')
            ->orderBy('id')
            ->chunkById(100, function ($streets) use ($idToName): void {
                foreach ($streets as $street) {
                    $name = $idToName[$street->neighbourhood_id] ?? null;

                    DB::table('streets')
                        ->where('id', $street->id)
                        ->update(['neighbourhood' => $name]);
                }
            });

        Schema::table('streets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('neighbourhood_id');
        });

        DB::table('properties')
            ->whereNotNull('neighbourhoods')
            ->orderBy('id')
            ->chunkById(100, function ($properties) use ($idToName): void {
                foreach ($properties as $property) {
                    $decoded = json_decode((string) $property->neighbourhoods, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    $names = array_values(array_filter(array_map(
                        static fn (mixed $id): ?string => is_numeric($id) ? ($idToName[(int) $id] ?? null) : null,
                        $decoded,
                    )));

                    DB::table('properties')
                        ->where('id', $property->id)
                        ->update([
                            'neighbourhoods' => json_encode($names, JSON_THROW_ON_ERROR),
                        ]);
                }
            });

        DB::table('property_subscriptions')
            ->whereNotNull('filters')
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($idToName): void {
                foreach ($subscriptions as $subscription) {
                    $filters = json_decode((string) $subscription->filters, true);

                    if (! is_array($filters) || ! is_array($filters['neighbourhoods'] ?? null)) {
                        continue;
                    }

                    $filters['neighbourhoods'] = array_values(array_filter(array_map(
                        static fn (mixed $id): ?string => is_numeric($id) ? ($idToName[(int) $id] ?? null) : null,
                        $filters['neighbourhoods'],
                    )));

                    DB::table('property_subscriptions')
                        ->where('id', $subscription->id)
                        ->update([
                            'filters' => json_encode($filters, JSON_THROW_ON_ERROR),
                        ]);
                }
            });

        DB::table('property_stats')
            ->whereNotNull('neighbourhoods')
            ->orderBy('id')
            ->chunkById(100, function ($stats) use ($idToName): void {
                foreach ($stats as $stat) {
                    $decoded = json_decode((string) $stat->neighbourhoods, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    $names = array_values(array_filter(array_map(
                        static fn (mixed $id): ?string => is_numeric($id) ? ($idToName[(int) $id] ?? null) : null,
                        $decoded,
                    )));

                    DB::table('property_stats')
                        ->where('id', $stat->id)
                        ->update([
                            'neighbourhoods' => json_encode($names, JSON_THROW_ON_ERROR),
                        ]);
                }
            });
    }
};
