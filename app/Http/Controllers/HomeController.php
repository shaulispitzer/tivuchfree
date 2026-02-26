<?php

namespace App\Http\Controllers;

use App\Models\PropertyStat;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController extends Controller
{
    private const TIVUCHFREE_COUNT_CACHE_KEY = 'home.tivuchfree_properties_count';

    private const MONEY_SAVED_CACHE_KEY = 'home.money_saved_by_community';

    private const CACHE_TTL_SECONDS = 3600;

    public function index()
    {
        $tivuchfreePropertiesCount = Cache::remember(
            self::TIVUCHFREE_COUNT_CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => PropertyStat::query()->where('how_got_taken', 'tivuchfree')->count(),
        );

        $moneySavedByCommunity = Cache::remember(
            self::MONEY_SAVED_CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            fn () => (float) PropertyStat::query()
                ->where('how_got_taken', 'tivuchfree')
                ->selectRaw('COALESCE(SUM(COALESCE(price_taken_at, price_advertised)), 0) as total')
                ->value('total'),
        );

        return Inertia::render('Home', [
            'tivuchfreePropertiesCount' => $tivuchfreePropertiesCount,
            'moneySavedByCommunity' => $moneySavedByCommunity,
        ]);
    }

    public static function forgetTivuchfreeStatsCache(): void
    {
        Cache::forget(self::TIVUCHFREE_COUNT_CACHE_KEY);
        Cache::forget(self::MONEY_SAVED_CACHE_KEY);
    }
}
