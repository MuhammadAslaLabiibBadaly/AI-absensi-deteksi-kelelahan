<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExportController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/export/csv', [ExportController::class, 'exportCsv'])->name('export.csv');