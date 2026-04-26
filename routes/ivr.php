<?php

use App\Http\Controllers\IvrController;
use Illuminate\Support\Facades\Route;

Route::any('/', IvrController::class)->name('index');
Route::post('delete-file', [IvrController::class, 'deleteFile'])->name('delete-file');
