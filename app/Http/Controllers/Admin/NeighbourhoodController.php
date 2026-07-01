<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NeighbourhoodStoreRequest;
use App\Http\Requests\NeighbourhoodUpdateRequest;
use App\Models\Neighbourhood;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NeighbourhoodController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Neighbourhood::class);

        $neighbourhoods = Neighbourhood::query()
            ->withCount('streets')
            ->orderBy('id')
            ->get()
            ->map(fn (Neighbourhood $neighbourhood) => [
                'id' => $neighbourhood->id,
                'name' => $neighbourhood->getTranslations('name'),
                'streets_count' => $neighbourhood->streets_count,
            ]);

        return Inertia::render('admin/neighbourhoods/Index', [
            'neighbourhoods' => $neighbourhoods,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Neighbourhood::class);

        return Inertia::render('admin/neighbourhoods/Create');
    }

    public function store(NeighbourhoodStoreRequest $request): RedirectResponse
    {
        $neighbourhood = Neighbourhood::create($request->validated());

        return redirect()->route('admin.neighbourhoods.edit', $neighbourhood);
    }

    public function edit(Neighbourhood $neighbourhood): Response
    {
        $this->authorize('update', $neighbourhood);

        return Inertia::render('admin/neighbourhoods/Edit', [
            'neighbourhood' => [
                'id' => $neighbourhood->id,
                'name' => $neighbourhood->getTranslations('name'),
                'streets_count' => $neighbourhood->streets()->count(),
            ],
        ]);
    }

    public function update(NeighbourhoodUpdateRequest $request, Neighbourhood $neighbourhood): RedirectResponse
    {
        $neighbourhood->update($request->validated());

        return redirect()->route('admin.neighbourhoods.edit', $neighbourhood);
    }

    public function destroy(Neighbourhood $neighbourhood): RedirectResponse
    {
        $this->authorize('delete', $neighbourhood);

        if ($neighbourhood->streets()->exists()) {
            return back()->withErrors([
                'neighbourhood' => 'Cannot delete a neighbourhood that still has streets assigned.',
            ]);
        }

        Neighbourhood::destroy($neighbourhood->getKey());

        return redirect()->route('admin.neighbourhoods.index', [])->success('Neighbourhood deleted successfully');
    }
}
