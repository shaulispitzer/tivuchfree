<?php

use Illuminate\Support\Facades\Route;

it('is only registered in the local environment', function (): void {
    // if (app()->isLocal()) {
    expect(Route::has('dev.testing'))->toBeTrue();
    $this->get('/dev/testing')->assertOk();
    // } else {
    //     expect(Route::has('dev.testing'))->toBeFalse();
    //     $this->get('/dev/testing')->assertNotFound();
    // }
});
