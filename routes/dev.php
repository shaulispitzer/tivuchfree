<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('testing', function () {
    return Inertia::render('dev/Testing');
})->name('testing');
