<?php

use Illuminate\Support\Facades\Route;

Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');
