<?php

namespace App\Services;

use App\Models\MapViewVisit;
use Illuminate\Http\Request;

class MapViewRecorder
{
    public function record(Request $request): void
    {
        $user = $request->user();
        $visitorKey = $user !== null
            ? 'user:'.$user->getAuthIdentifier()
            : 'session:'.$request->session()->getId();

        MapViewVisit::query()->insertOrIgnore([
            'visited_on' => now()->toDateString(),
            'visitor_key' => $visitorKey,
            'user_id' => $user?->getAuthIdentifier(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
