<?php

use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\Patient\DashboardController::class)->name('dashboard');
