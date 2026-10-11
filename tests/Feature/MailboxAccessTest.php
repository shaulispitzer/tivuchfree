<?php

use Illuminate\Support\Facades\Gate;

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
    $this->get('/mailbox')->assertNotFound();
});

test('mailbox dashboard is available to guests when the mailer is mailbox', function () {
    putenv('MAIL_MAILER=mailbox');
    $_ENV['MAIL_MAILER'] = 'mailbox';
    $_SERVER['MAIL_MAILER'] = 'mailbox';

    $this->refreshApplication();

    $this->get('/mailbox')->assertOk();
});
