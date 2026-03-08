<?php

namespace App\Jobs;

use App\Mail\StaleUploadsPurged;
use App\Models\TempUpload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class PurgeStaleTemporaryUploads implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $stale = TempUpload::query()
            ->where('created_at', '<=', now()->subHours(24))
            ->get();

        $uploadCount = $stale->count();
        $mediaCount = 0;

        foreach ($stale as $tempUpload) {
            $mediaCount += $tempUpload->getMedia('images')->count();
            $tempUpload->delete();
        }

        Mail::to('shaulispitzer@gmail.com')->send(
            new StaleUploadsPurged($uploadCount, $mediaCount),
        );
    }
}
