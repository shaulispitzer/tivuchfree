<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/locale', LocaleController::class)->name('locale');
Route::get('playground', function () {
    return Inertia::render('Playground');
})->name('playground');

require __DIR__.'/settings.php';
