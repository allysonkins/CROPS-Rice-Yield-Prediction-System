<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PredictionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// TEMPORARILY REMOVED FOR TESTING - Add back later
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/predict', [PredictionController::class, 'predict']);
//     Route::get('/predictions', [PredictionController::class, 'index']);
// });

// TEMPORARY ROUTES FOR TESTING (No authentication)
Route::post('/predict', [PredictionController::class, 'predict']);
Route::get('/predictions', [PredictionController::class, 'index']);