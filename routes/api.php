<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;

Route::post('/sensor', [SensorController::class, 'store']);
Route::get('/sensor',  [SensorController::class, 'latest']); 
