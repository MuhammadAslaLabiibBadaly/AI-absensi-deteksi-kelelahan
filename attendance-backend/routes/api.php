<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/internal/attendance-log', [AttendanceController::class, 'storeInternalLog']);
Route::get('/attendance-logs', [AttendanceController::class, 'getLogs']);