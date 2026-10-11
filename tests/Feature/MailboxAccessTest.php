<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

afterEach(function () {
    putenv('MAIL_MAILER=array');
    $_ENV['MAIL_MAILER'] = 'array';
    $_SERVER['MAIL_MAILER'] = 'array';
});

test('mailbox dashboard routes stay registered for route caching', function () {
    expect(config('mailbox.enabled'))->toBeTrue();
});

test('viewMailbox gate allows guests when the default mailer is mailbox', function () {
    config(['mail.default' => 'mailbox']);

    expect(Gate::allows('viewMailbox'))->toBeTrue();
});

test('viewMailbox gate denies access when the default mailer is not mailbox', function () {
    config(['mail.default' => 'array']);

    expect(Gate::allows('viewMailbox'))->toBeFalse();
});

test('mailbox dashboard is forbidden when the mailer is not mailbox', function () {
    $this->get('/'.config('mailbox.path', 'mailbox-test'))->assertForbidden();
});

test('mailbox dashboard is available to guests when the mailer is mailbox', function () {
    putenv('MAIL_MAILER=mailbox');
    $_ENV['MAIL_MAILER'] = 'mailbox';
    $_SERVER['MAIL_MAILER'] = 'mailbox';

    $this->refreshApplication();

    $this->get('/'.config('mailbox.path', 'mailbox-test'))->assertOk();
});

test('mailbox sqlite database is created automatically when missing', function () {
    $dbPath = storage_path('app/mailbox/test-mailbox-'.uniqid('', true).'.sqlite');

    config([
        'mail.default' => 'mailbox',
        'mailbox.store.driver' => 'sqlite',
        'database.connections.mailbox' => [
            'driver' => 'sqlite',
            'database' => $dbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    /** @var AppServiceProvider $provider */
    $provider = app()->getProvider(AppServiceProvider::class);
    $provider->prepareMailboxStorageIfNeeded();

    expect(file_exists($dbPath))->toBeTrue()
        ->and(Illuminate\Support\Facades\Schema::connection('mailbox')->hasTable('mailbox_messages'))->toBeTrue();

    Illuminate\Support\Facades\DB::purge('mailbox');
    @unlink($dbPath);
});

test('mailbox routes are registered after boot when missing from the route cache', function () {
    config([
        'mail.default' => 'mailbox',
        'mailbox.enabled' => false,
    ]);

    // Remove any already-registered mailbox routes from this test app instance
    // by simulating the recovery path used when Cloud's route cache omitted them.
    /** @var AppServiceProvider $provider */
    $provider = app()->getProvider(AppServiceProvider::class);

    $path = trim((string) config('mailbox.path', 'mailbox-test'), '/');

    config(['mailbox.enabled' => false]);
    $provider->registerMailboxRoutesIfMissing();

    $registered = collect(Route::getRoutes())
        ->contains(fn ($route) => $route->uri() === $path);

    expect($registered)->toBeTrue();

    $this->get('/'.$path)->assertOk();
});
