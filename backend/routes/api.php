<?php

use App\Http\Controllers\Api\PredictionController;
use Illuminate\Support\Facades\Route;

// ============================================================
// API ROUTES
// ============================================================
// Note: The AuthController import was removed — the API auth
// endpoints aren't used by this web-only capstone. Web auth is
// handled by App\Http\Controllers\Web\AuthController instead.
//
// If you build a mobile app later, restore the AuthController
// with the correct namespace (App\Http\Controllers\Api).
// ============================================================

// ── Prediction endpoints ──
// Currently unauthenticated for testing.
// TODO: Wrap with 'auth:sanctum' middleware before production.

Route::post('/predict',     [PredictionController::class, 'predict'])->name('api.predict');
Route::get ('/predictions', [PredictionController::class, 'index'])->name('api.predictions.index');