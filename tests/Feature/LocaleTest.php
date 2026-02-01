<?php

test('it stores the locale in the session', function () {
    /** @var \Tests\TestCase $this */
    $this->post('/locale', [
        'locale' => 'he',
    ])->assertRedirect();

    expect(session('locale'))->toBe('he');
});
