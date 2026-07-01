<?php

use App\Http\Controllers\LinkRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/{code}', LinkRedirectController::class)
    ->where('code', '^(?!admin$|dashboard$|up$)[A-Za-z0-9]{6}$')
    ->name('links.redirect');
