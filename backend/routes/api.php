<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PlantController;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\Api\PumpController;
use App\Http\Controllers\Api\SimulatorController;

/*
|--------------------------------------------------------------------------
| Smart Plant IoT REST API Routes
|--------------------------------------------------------------------------
*/

// Plants Routes
Route::get('/plants', [PlantController::class, 'index']);
Route::get('/plants/{id}', [PlantController::class, 'show']);
Route::put('/plants/{id}/settings', [PlantController::class, 'updateSettings']);

// Sensor Readings Routes
Route::get('/plants/{id}/latest', [SensorController::class, 'latest']);
Route::get('/plants/{id}/history', [SensorController::class, 'history']);

// Water Pump Control Routes
Route::post('/plants/{id}/pump', [PumpController::class, 'pump']);
Route::get('/plants/{id}/pump-logs', [PumpController::class, 'history']);

// Dummy Sensor Simulator Ingestion Route
Route::post('/simulator/generate', [SimulatorController::class, 'generate']);
