<?php

use Illuminate\Support\Facades\Route;

// Catch-all: serve the Vue SPA for all web routes
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
