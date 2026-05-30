<?php

use App\Http\Controllers\ReadinessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/readyz', ReadinessController::class);
