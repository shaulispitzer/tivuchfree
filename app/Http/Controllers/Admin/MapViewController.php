<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MapViewVisit;
use Inertia\Inertia;
use Inertia\Response;

class MapViewController extends Controller
{
    public function index(): Response
    {
        $today = now()->toDateString();
        $sevenDaysAgo = now()->subDays(6)->toDateString();
        $thirtyDaysAgo = now()->subDays(29)->toDateString();

        $daily = MapViewVisit::query()
            ->selectRaw('visited_on, COUNT(*) as visitors')
            ->whereDate('visited_on', '>=', $thirtyDaysAgo)
            ->groupBy('visited_on')
            ->orderByDesc('visited_on')
            ->get()
            ->map(fn (MapViewVisit $row): array => [
                'date' => $row->visited_on?->toDateString(),
                'visitors' => (int) $row->visitors,
            ])
            ->values()
            ->all();

        return Inertia::render('admin/MapViews', [
            'summary' => [
                'today' => MapViewVisit::query()->whereDate('visited_on', $today)->count(),
                'last_7_days' => MapViewVisit::query()->whereDate('visited_on', '>=', $sevenDaysAgo)->count(),
                'last_30_days' => MapViewVisit::query()->whereDate('visited_on', '>=', $thirtyDaysAgo)->count(),
                'all_time' => MapViewVisit::query()->count(),
            ],
            'daily' => $daily,
        ]);
    }
}
