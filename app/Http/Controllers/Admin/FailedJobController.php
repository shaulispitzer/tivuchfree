<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FailedJobController extends Controller
{
    public function index(): Response
    {
        $failedJobs = DB::table('failed_jobs')
            ->latest('failed_at')
            ->paginate(20)
            ->through(fn (object $job) => [
                'id' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'payload' => $job->payload,
                'exception' => $job->exception,
                'failed_at' => $job->failed_at,
            ]);

        return Inertia::render('admin/FailedJobs', [
            'failedJobs' => $failedJobs,
        ]);
    }

    public function retry(string $id): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$id]]);

        return back()->success('Job queued for retry.');
    }

    public function retryAll(): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->success('All failed jobs have been queued for retry.');
    }

    public function destroy(string $id): RedirectResponse
    {
        DB::table('failed_jobs')->where('uuid', $id)->delete();

        return back()->success('Failed job deleted.');
    }

    public function destroyAll(): RedirectResponse
    {
        Artisan::call('queue:flush');

        return back()->success('All failed jobs have been deleted.');
    }
}
