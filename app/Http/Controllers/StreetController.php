<?php

namespace App\Http\Controllers;

use App\Data\PropertyOptionData;
use App\Enums\Neighbourhood;
use App\Http\Requests\StreetStoreRequest;
use App\Http\Requests\StreetUpdateRequest;
use App\Models\Street;
use Inertia\Inertia;

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

        return redirect()->route('streets.edit', $street);
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

        return redirect()->route('streets.edit', $street);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Street $street)
    {
        $this->authorize('delete', $street);

        $street->delete();

        return redirect()->route('streets.index');
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
