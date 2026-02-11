<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    /**
     * Display a listing of all properties for admin management.
     */
    public function index(): Response
    {
        $properties = Property::query()
            ->with('user:id,name,email')
            ->latest()
            ->get()
            ->map(fn (Property $property) => [
                'id' => $property->id,
                'street' => $property->street,
                'building_number' => $property->building_number,
                'floor' => $property->floor,
                'bedrooms' => $property->bedrooms,
                'type' => $property->type?->value,
                'user' => [
                    'name' => $property->user?->name,
                    'email' => $property->user?->email,
                ],
            ]);

        return Inertia::render('admin/Properties', [
            'properties' => $properties,
        ]);
    }

    /**
     * Remove the specified property from storage.
     */
    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        $property->delete();

        return redirect()->route('admin.properties.index')->success('Property deleted successfully');
    }
}
