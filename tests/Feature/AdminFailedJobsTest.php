<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    DB::table('failed_jobs')->truncate();
});

function insertFailedJob(string $uuid = 'test-uuid-1'): void
{
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\SendEmailJob', 'job' => 'Illuminate\\Queue\\CallQueuedHandler@call']),
        'exception' => 'RuntimeException: Something went wrong in /app/Jobs/SendEmailJob.php:30',
        'failed_at' => now()->toDateTimeString(),
    ]);
}

test('admins can view failed jobs page', function () {
    $admin = User::factory()->admin()->create();
    insertFailedJob();

    $response = $this->actingAs($admin)->get(route('admin.failed-jobs.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/FailedJobs')
            ->has('failedJobs.data', 1)
            ->where('failedJobs.data.0.id', 'test-uuid-1')
            ->where('failedJobs.data.0.queue', 'default')
        );
});

test('admins can retry a failed job', function () {
    $admin = User::factory()->admin()->create();
    insertFailedJob('retry-uuid');

    Artisan::shouldReceive('call')
        ->once()
        ->with('queue:retry', ['id' => ['retry-uuid']]);

    $this->actingAs($admin)
        ->post(route('admin.failed-jobs.retry', 'retry-uuid'))
        ->assertRedirect();
});

test('admins can delete a failed job', function () {
    $admin = User::factory()->admin()->create();
    insertFailedJob('delete-uuid');

    $this->actingAs($admin)
        ->delete(route('admin.failed-jobs.destroy', 'delete-uuid'))
        ->assertRedirect();

    expect(DB::table('failed_jobs')->where('uuid', 'delete-uuid')->count())->toBe(0);
});

test('admins can retry all failed jobs', function () {
    $admin = User::factory()->admin()->create();

    Artisan::shouldReceive('call')
        ->once()
        ->with('queue:retry', ['id' => ['all']]);

    $this->actingAs($admin)
        ->post(route('admin.failed-jobs.retry-all'))
        ->assertRedirect();
});

test('admins can clear all failed jobs', function () {
    $admin = User::factory()->admin()->create();
    insertFailedJob('uuid-1');
    insertFailedJob('uuid-2');

    Artisan::shouldReceive('call')
        ->once()
        ->with('queue:flush');

    $this->actingAs($admin)
        ->delete(route('admin.failed-jobs.destroy-all'))
        ->assertRedirect();
});

test('non-admins cannot access failed jobs', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.failed-jobs.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.failed-jobs.retry', 'some-uuid'))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.failed-jobs.destroy', 'some-uuid'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.failed-jobs.retry-all'))->assertForbidden();
    $this->actingAs($user)->delete(route('admin.failed-jobs.destroy-all'))->assertForbidden();
});

test('guests are redirected from failed jobs', function () {
    $this->get(route('admin.failed-jobs.index'))->assertRedirect(route('login'));
});
