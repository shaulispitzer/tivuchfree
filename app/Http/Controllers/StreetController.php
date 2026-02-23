<?php

namespace App\Http\Controllers;

use App\Data\PropertyOptionData;
use App\Enums\Neighbourhood;
use App\Http\Requests\StreetImportRequest;
use App\Http\Requests\StreetStoreRequest;
use App\Http\Requests\StreetUpdateRequest;
use App\Models\Street;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Spatie\SimpleExcel\SimpleExcelReader;

class StreetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Street::class);

        $streets = Street::query()
            ->orderBy('neighbourhood')
            ->orderBy('id')
            ->get()
            ->map(fn (Street $street) => [
                'id' => $street->id,
                'neighbourhood' => $street->neighbourhood?->value,
                'name' => $street->getTranslations('name'),
                'translatedName' => $street->name,
            ]);

        return Inertia::render('streets/Index', [
            'streets' => $streets,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Street::class);

        return Inertia::render('streets/Create', [
            'neighbourhoods' => $this->neighbourhoodOptions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StreetStoreRequest $request)
    {
        $street = Street::create($request->validated());

        return redirect()->route('admin.streets.edit', $street);
    }

    /**
     * Import streets from an Excel or CSV file.
     *
     * Expected columns: Neighbourhood (enum value), Name (EN), Name (HE)
     */
    public function import(StreetImportRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $type = match (strtolower($file->getClientOriginalExtension())) {
            'csv' => 'csv',
            'xlsx' => 'xlsx',
            default => 'csv',
        };

        SimpleExcelReader::create($file->getRealPath(), $type)
            ->headersToSnakeCase()
            ->getRows()
            ->each(function (array $row): void {
                $neighbourhood = $this->resolveNeighbourhood($row);
                $nameEn = $this->resolveColumn($row, ['name_en', 'name_(en)']);
                $nameHe = $this->resolveColumn($row, ['name_he', 'name_(he)']);

                if ($neighbourhood && $nameEn && $nameHe) {
                    Street::create([
                        'neighbourhood' => $neighbourhood,
                        'name' => [
                            'en' => $nameEn,
                            'he' => $nameHe,
                        ],
                    ]);
                }
            });

        return redirect()->route('admin.streets.index')
            ->success('Streets imported successfully.');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function resolveNeighbourhood(array $row): ?Neighbourhood
    {
        $value = $this->resolveColumn($row, ['neighbourhood']);

        if (! $value) {
            return null;
        }

        return Neighbourhood::tryFrom(trim($value));
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string>  $keys
     */
    protected function resolveColumn(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * Display the specified resource.
     */
    public function edit(Street $street)
    {
        $this->authorize('update', $street);

        return Inertia::render('streets/Edit', [
            'street' => [
                'id' => $street->id,
                'neighbourhood' => $street->neighbourhood?->value,
                'name' => $street->getTranslations('name'),
            ],
            'neighbourhoods' => $this->neighbourhoodOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StreetUpdateRequest $request, Street $street)
    {
        $street->update($request->validated());

        return redirect()->route('admin.streets.edit', $street);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Street $street)
    {
        $this->authorize('delete', $street);

        $street->delete();

        return redirect()->route('admin.streets.index');
    }

    /**
     * @return array<int, PropertyOptionData>
     */
    protected function neighbourhoodOptions(): array
    {
        return array_map(
            fn (Neighbourhood $neighbourhood) => PropertyOptionData::fromEnum($neighbourhood),
            Neighbourhood::cases(),
        );
    }
}
