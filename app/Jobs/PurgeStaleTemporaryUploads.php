<?php

namespace App\Jobs;

use App\Models\TempUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PurgeStaleTemporaryUploads implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $stale = TempUpload::query()
            ->where('created_at', '<=', now()->subHours(24))
            ->get();

        foreach ($stale as $tempUpload) {
            $tempUpload->delete();
        }
    }
}
