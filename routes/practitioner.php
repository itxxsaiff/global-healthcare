<?php

use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\Practitioner\DashboardController::class)->name('dashboard');
