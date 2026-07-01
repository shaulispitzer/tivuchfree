<?php

namespace App\Http\Controllers;

use App\Data\PropertyOptionData;
use App\Http\Requests\GenerateStreetCsvRequest;
use App\Http\Requests\StreetImportRequest;
use App\Http\Requests\StreetStoreRequest;
use App\Http\Requests\StreetUpdateRequest;
use App\Models\Neighbourhood;
use App\Models\Street;
use App\Services\OpenStreetMapStreetCsvGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Spatie\SimpleExcel\SimpleExcelReader;

class StreetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Street::class);

        $search = trim((string) $request->query('search', ''));
        $neighbourhoodId = $request->query('neighbourhood');
        $neighbourhoodId = is_numeric($neighbourhoodId) ? (int) $neighbourhoodId : null;

        $streets = Street::query()
            ->with('neighbourhood')
            ->when($neighbourhoodId, fn ($query) => $query->where('neighbourhood_id', $neighbourhoodId))
            ->when($search !== '', function ($query) use ($search): void {
                $needle = '%'.mb_strtolower($search).'%';

                $query->where(function ($query) use ($needle, $search): void {
                    if ($query->getConnection()->getDriverName() === 'pgsql') {
                        $query->where('name->en', 'ilike', '%'.$search.'%')
                            ->orWhere('name->he', 'ilike', '%'.$search.'%');

                        return;
                    }

                    if ($query->getConnection()->getDriverName() === 'sqlite') {
                        $query->whereRaw('LOWER(json_extract(`name`, \'$."en"\')) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(json_extract(`name`, \'$."he"\')) LIKE ?', [$needle]);

                        return;
                    }

                    $query->whereRaw('LOWER(json_unquote(json_extract(`name`, \'$."en"\'))) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(json_unquote(json_extract(`name`, \'$."he"\'))) LIKE ?', [$needle]);
                });
            })
            ->orderBy('neighbourhood_id')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Street $street) => [
                'id' => $street->id,
                'neighbourhood_id' => $street->neighbourhood_id,
                'neighbourhood' => $street->neighbourhood?->getTranslation('name', 'en'),
                'name' => $street->getTranslations('name'),
                'translatedName' => $street->name,
            ]);

        return Inertia::render('streets/Index', [
            'streets' => $streets,
            'neighbourhoods' => $this->neighbourhoodOptions(),
            'filters' => [
                'search' => $search,
                'neighbourhood' => $neighbourhoodId,
            ],
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
     * Expected columns: Neighbourhood (English name or ID), Name (EN), Name (HE)
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
                $neighbourhoodId = $this->resolveNeighbourhoodId($row);
                $nameEn = $this->resolveColumn($row, ['name_en', 'name_(en)']);
                $nameHe = $this->resolveColumn($row, ['name_he', 'name_(he)']);

                if ($neighbourhoodId && $nameEn && $nameHe) {
                    Street::create([
                        'neighbourhood_id' => $neighbourhoodId,
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
     * Generate a CSV of streets from OpenStreetMap for the given bounding box.
     */
    public function generateCsv(
        GenerateStreetCsvRequest $request,
        OpenStreetMapStreetCsvGenerator $generator,
    ): HttpResponse|JsonResponse {
        $neighbourhood = Neighbourhood::query()->findOrFail($request->integer('neighbourhood_id'));
        $neighbourhoodNameEn = $neighbourhood->getTranslation('name', 'en');

        if (! is_string($neighbourhoodNameEn) || $neighbourhoodNameEn === '') {
            return response()->json([
                'message' => 'The selected neighbourhood is missing an English name.',
            ], 422);
        }

        try {
            $csv = $generator->generateCsv(
                $neighbourhoodNameEn,
                $request->float('south'),
                $request->float('west'),
                $request->float('north'),
                $request->float('east'),
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $filename = Str::slug($neighbourhoodNameEn).'-streets-'.now()->format('Y-m-d').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function resolveNeighbourhoodId(array $row): ?int
    {
        $value = $this->resolveColumn($row, ['neighbourhood', 'neighbourhood_id']);

        if (! $value) {
            return null;
        }

        if (ctype_digit($value)) {
            return Neighbourhood::query()->whereKey((int) $value)->value('id');
        }

        return Neighbourhood::query()
            ->where('name->en', trim($value))
            ->value('id');
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
                'neighbourhood_id' => $street->neighbourhood_id,
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

        return redirect()->route('admin.streets.edit', $street)
            ->success('Street updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Street $street, Request $request): RedirectResponse
    {
        $this->authorize('delete', $street);

        Street::destroy($street->getKey());

        return redirect()->route('admin.streets.index', array_filter([
            'search' => $request->query('search'),
            'neighbourhood' => $request->query('neighbourhood'),
            'page' => $request->query('page'),
        ], fn ($value) => $value !== null && $value !== ''))->success('Street deleted successfully.');
    }

    /**
     * @return array<int, PropertyOptionData>
     */
    protected function neighbourhoodOptions(): array
    {
        return Neighbourhood::optionData();
    }
}
