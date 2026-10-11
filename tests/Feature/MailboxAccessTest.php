<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

afterEach(function () {
    putenv('MAIL_MAILER=array');
    $_ENV['MAIL_MAILER'] = 'array';
    $_SERVER['MAIL_MAILER'] = 'array';
});

test('mailbox is enabled only when the default mailer is mailbox', function () {
    expect(config('mailbox.enabled'))->toBeFalse();

    putenv('MAIL_MAILER=mailbox');
    $_ENV['MAIL_MAILER'] = 'mailbox';
    $_SERVER['MAIL_MAILER'] = 'mailbox';

    $this->refreshApplication();

    expect(config('mail.default'))->toBe('mailbox')
        ->and(config('mailbox.enabled'))->toBeTrue();
});

test('viewMailbox gate allows guests when the default mailer is mailbox', function () {
    config(['mail.default' => 'mailbox']);

    expect(Gate::allows('viewMailbox'))->toBeTrue();
});

test('viewMailbox gate denies access when the default mailer is not mailbox', function () {
    config(['mail.default' => 'array']);

    expect(Gate::allows('viewMailbox'))->toBeFalse();
});

test('mailbox dashboard is unavailable when the mailer is not mailbox', function () {
    $this->get('/'.config('mailbox.path', 'mailbox-test'))->assertNotFound();
});

test('mailbox dashboard is available to guests when the mailer is mailbox', function () {
    putenv('MAIL_MAILER=mailbox');
    $_ENV['MAIL_MAILER'] = 'mailbox';
    $_SERVER['MAIL_MAILER'] = 'mailbox';

    $this->refreshApplication();

    $this->get('/'.config('mailbox.path', 'mailbox-test'))->assertOk();
});

test('mailbox routes are registered when the package skipped them at boot', function () {
    expect(config('mail.default'))->not->toBe('mailbox');

    config([
        'mail.default' => 'mailbox',
        'mailbox.enabled' => false,
    ]);

    /** @var AppServiceProvider $provider */
    $provider = app()->getProvider(AppServiceProvider::class);
    $provider->registerMailboxRoutesIfMissing();

    $path = trim((string) config('mailbox.path', 'mailbox-test'), '/');

    $registered = collect(Route::getRoutes())
        ->contains(fn ($route) => $route->uri() === $path);

    expect($registered)->toBeTrue();

    $this->get('/'.$path)->assertOk();
});
