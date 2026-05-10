<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

Route::post('/sensor',            [SensorController::class, 'store']);
Route::get('/sensor',             [SensorController::class, 'latest']);
Route::post('/sensor/clear-safe', [SensorController::class, 'clearSafe']);
Route::delete('/sensor/{id}',     [SensorController::class, 'destroy']);
