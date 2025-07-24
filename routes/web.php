<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Catch-all route for Vue.js SPA (Single Page Application)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
