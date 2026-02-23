<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyStat;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyStatController extends Controller
{
    public function index(): Response
    {
        $stats = PropertyStat::query()
            ->latest('id')
            ->get()
            ->map(fn (PropertyStat $stat) => [
                'id' => $stat->id,
                'property_id' => $stat->property_id,
                'type' => $stat->type,
                'neighbourhoods' => $stat->neighbourhoods,
                'address' => $stat->address,
                'how_got_taken' => $stat->how_got_taken,
                'price_advertised' => $stat->price_advertised,
                'price_taken_at' => $stat->price_taken_at,
                'date_taken' => $stat->date_taken?->toDateTimeString(),
                'date_advertised' => $stat->date_advertised?->toDateTimeString(),
                'created_at' => $stat->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/PropertyStats', [
            'stats' => $stats,
        ]);
    }

    public function destroy(PropertyStat $propertyStat): RedirectResponse
    {
        $this->authorize('delete', $propertyStat);

        $propertyStat->delete();

        return redirect()->route('admin.property-stats.index')->success('Property stat deleted successfully');
    }
}
