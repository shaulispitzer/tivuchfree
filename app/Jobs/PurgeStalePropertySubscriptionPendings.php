<?php

namespace App\Jobs;

use App\Models\PropertySubscriptionPending;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PurgeStalePropertySubscriptionPendings implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        PropertySubscriptionPending::query()
            ->where('created_at', '<=', now()->subWeek())
            ->delete();
    }
}
