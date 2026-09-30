<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Farmer\DashboardController as FarmerDashboardController;
use App\Http\Controllers\Admin\RiceVarietyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\FarmerRegistrationController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\AdvisoryController;
use App\Http\Controllers\Admin\PredictionController;
use App\Http\Controllers\Admin\FarmRecordController;
use App\Http\Controllers\Admin\FarmController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\FarmerController;
use App\Http\Controllers\Admin\FarmerImportController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Farmer\PredictionController as FarmerPredictionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Farmer\FarmController as FarmerFarmController;
use App\Http\Controllers\Farmer\FarmRecordController as FarmerFarmRecordController;
use App\Http\Controllers\Farmer\RiceVarietyController as FarmerRiceVarietyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ModelTrainingController;

Route::get('/', function () {
    return redirect('/login');
});

// ============================================================
// AUTH ROUTES
// ============================================================

// Farmer login (default entry point)
Route::get ('/login', [AuthController::class, 'showFarmerLogin'])->name('login');
Route::post('/login', [AuthController::class, 'loginFarmer'])->name('login.submit');

// Staff / Admin login (separate page)
Route::get ('/staff/login', [AuthController::class, 'showStaffLogin'])->name('staff.login');
Route::post('/staff/login', [AuthController::class, 'loginStaff'])->name('staff.login.submit');

// Logout (shared) — POST only, CSRF-protected
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ============================================================
// FARMER SELF-REGISTRATION (public)
// ============================================================
Route::get ('/farmer/register', [FarmerRegistrationController::class, 'showForm'])->name('farmer.register');
Route::post('/farmer/register', [FarmerRegistrationController::class, 'register'])->name('farmer.register.submit');

// ============================================================
// EMAIL VERIFICATION ROUTES
// ============================================================
// Staff & Admin only — farmers are auto-verified at creation.
// The `not.farmer` middleware redirects any farmer who lands here.
Route::middleware(['auth', 'not.farmer'])->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
});

// ============================================================
// AUTHENTICATED + VERIFIED ROUTES
// ============================================================
Route::middleware(['auth', 'verified'])->group(function () {

    // ============================================================
    // 1. DASHBOARDS
    // ============================================================
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    Route::get('/farmer/dashboard', [FarmerDashboardController::class, 'index'])->name('farmer.dashboard');

    // ============================================================
    // 2. PREDICTIONS
    // ============================================================
    Route::get('/admin/predictions', [PredictionController::class, 'index'])->name('admin.predictions.index');
    Route::get('/admin/predictions/create', [PredictionController::class, 'create'])->name('admin.predictions.create');
    Route::post('/admin/predictions', [PredictionController::class, 'store'])->name('admin.predictions.store');
    Route::post('/admin/predictions/generate-all', [PredictionController::class, 'generateAll'])->name('admin.predictions.generate-all');
    Route::post('/admin/predictions/regenerate-all', [PredictionController::class, 'regenerateAll'])->name('admin.predictions.regenerate-all');
    Route::get('/admin/predictions/{id}/history', [PredictionController::class, 'history'])->name('admin.predictions.history');
    Route::put('/admin/predictions/{id}', [PredictionController::class, 'update'])->name('admin.predictions.update');
    Route::delete('/admin/predictions/{id}', [PredictionController::class, 'destroy'])->name('admin.predictions.destroy');
    Route::get('/admin/predictions/{id}', [PredictionController::class, 'show'])->name('admin.predictions.show');

    // ============================================================
    // 3. RICE VARIETIES
    // ============================================================
    Route::prefix('admin/rice-varieties')->group(function () {
        Route::get('/', [RiceVarietyController::class, 'index'])->name('admin.rice-varieties.index');
        Route::get('/create', [RiceVarietyController::class, 'create'])->name('admin.rice-varieties.create');
        Route::post('/', [RiceVarietyController::class, 'store'])->name('admin.rice-varieties.store');
        Route::get('/{id}/edit', [RiceVarietyController::class, 'edit'])->name('admin.rice-varieties.edit');
        Route::put('/{id}', [RiceVarietyController::class, 'update'])->name('admin.rice-varieties.update');
        Route::delete('/{id}', [RiceVarietyController::class, 'destroy'])->name('admin.rice-varieties.destroy');
        Route::get('/{id}/details', [RiceVarietyController::class, 'details'])->name('admin.rice-varieties.details');
        Route::get('/{id}/yield', [RiceVarietyController::class, 'getYield'])->name('admin.rice-varieties.yield');
    });

    // ============================================================
    // 4. STAFF ACCOUNTS (ADMIN ONLY)
    // ============================================================
    Route::middleware(['role:admin'])->prefix('admin/users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });

    // ============================================================
    // 5. FARM MAP
    // ============================================================
    Route::get('/admin/map', [MapController::class, 'index'])->name('admin.map');

    // ============================================================
    // 6. FARMS (ADMIN ONLY)
    // ============================================================
    Route::resource('/admin/farms', FarmController::class)->names('admin.farms');

    // ============================================================
    // 7. FARM RECORDS
    // ============================================================
    // Resource route already registers `admin.farm-records.show`.
    // Do NOT add a manual Route::get('/admin/farm-records/{id}') — it
    // duplicates the route name and breaks `php artisan route:cache`.
    Route::resource('/admin/farm-records', FarmRecordController::class)->names('admin.farm-records');
    Route::post('/admin/farm-records/{id}/mark-harvested', [FarmRecordController::class, 'markHarvested'])
        ->name('admin.farm-records.mark-harvested');

    // ============================================================
    // 8. ADVISORIES
    // ============================================================
    Route::resource('/admin/advisories', AdvisoryController::class)->names('admin.advisories');

    // ============================================================
    // 9. FARMERS LIST
    // ============================================================
    // CRITICAL ORDER: named routes MUST come BEFORE the resource route,
    // otherwise /admin/farmers/import, /pending, /credentials, and /slip
    // get bound as {farmer} = "import" or {farmer} = "credentials".

    // 9a. Bulk RSBSA import
    Route::get ('/admin/farmers/import',             [FarmerImportController::class, 'form'])->name('admin.farmers.import.form');
    Route::post('/admin/farmers/import/preview',     [FarmerImportController::class, 'preview'])->name('admin.farmers.import.preview');
    Route::post('/admin/farmers/import/commit',      [FarmerImportController::class, 'commit'])->name('admin.farmers.import.commit');
    Route::get ('/admin/farmers/import/credentials', [FarmerImportController::class, 'credentials'])->name('admin.farmers.import.credentials');
    Route::get ('/admin/farmers/import/template',    [FarmerImportController::class, 'template'])->name('admin.farmers.import.template');
    Route::get ('/admin/farmers/import/diagnostics', [FarmerImportController::class, 'diagnostics'])->name('admin.farmers.import.diagnostics');

    // 9b. CAO verification queue
    Route::get   ('/admin/farmers/pending',     [FarmerController::class, 'pending'])->name('admin.farmers.pending');
    Route::post  ('/admin/farmers/{id}/verify', [FarmerController::class, 'verify'])->name('admin.farmers.verify');
    Route::delete('/admin/farmers/{id}/reject', [FarmerController::class, 'reject'])->name('admin.farmers.reject');

    // 9c. Credentials + PIN management
    Route::get ('/admin/farmers/credentials',    [FarmerController::class, 'credentials'])->name('admin.farmers.credentials');
    Route::post('/admin/farmers/{id}/reset-pin', [FarmerController::class, 'resetPin'])->name('admin.farmers.reset-pin');
    Route::get ('/admin/farmers/{id}/slip',      [FarmerController::class, 'slip'])->name('admin.farmers.slip');
    Route::post('/admin/farmers/credentials/generate-pins-chunk', [FarmerController::class, 'generatePinsChunk'])
        ->name('admin.farmers.generate-pins-chunk');

    // 9d. Standard resource routes
    Route::resource('/admin/farmers', FarmerController::class)->names('admin.farmers');

    // ============================================================
    // 10. ACTIVITY LOGS (ADMIN ONLY)
    // ============================================================
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/logs', [LogController::class, 'index'])->name('admin.logs.index');
        Route::get('/admin/logs/{id}', [LogController::class, 'show'])->name('admin.logs.show');
        Route::delete('/admin/logs/clear', [LogController::class, 'clear'])->name('admin.logs.clear');
    });

    // ============================================================
    // 11. REPORTS
    // ============================================================
    Route::get('/admin/reports',               [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/generate-pdf',  [ReportController::class, 'generatePdf'])->name('admin.reports.generate-pdf');
    Route::get('/admin/reports/generate-excel',[ReportController::class, 'generateExcel'])->name('admin.reports.generate-excel');
    Route::get('/admin/reports/generate',      [ReportController::class, 'generate'])->name('admin.reports.generate');

    // ============================================================
    // 11b. MACHINE LEARNING — MODEL RETRAINING (ADMIN ONLY)
    // ============================================================
    Route::middleware(['role:admin'])->prefix('admin/ml')->name('admin.ml.')->group(function () {
        Route::get('/',            [ModelTrainingController::class, 'index'])->name('index');
        Route::get('/history',     [ModelTrainingController::class, 'history'])->name('history');
        Route::post('/train',      [ModelTrainingController::class, 'start'])->name('start');
        Route::get('/status/{id}', [ModelTrainingController::class, 'status'])->name('status');
        Route::get('/runs/{id}',   [ModelTrainingController::class, 'show'])->name('show');
    });


    // ============================================================
    // 12. UNIFIED PROFILE
    // ============================================================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ============================================================
    // 13. FARMER PROFILE (backward compatibility)
    // ============================================================
    Route::get('/farmer/profile', [ProfileController::class, 'edit'])->name('farmer.profile.edit');
    Route::put('/farmer/profile', [ProfileController::class, 'update'])->name('farmer.profile.update');

    // ============================================================
    // 14. FARMER PREDICTIONS
    // ============================================================
    Route::get('/farmer/predictions', [FarmerPredictionController::class, 'index'])->name('farmer.predictions.index');
    Route::get('/farmer/predictions/{id}/history', [FarmerPredictionController::class, 'history'])
        ->name('farmer.predictions.history');
    Route::get('/farmer/predictions/{id}', [FarmerPredictionController::class, 'show'])->name('farmer.predictions.show');

    // ============================================================
    // 14b. FARMER — ADVISORIES (read-only)
    // ============================================================
    // Reuses AdvisoryController::index(), which already branches on
    // role === 'farmer' and returns only active advisories targeted
    // at farmers (or 'all'). Same view as admin — stats cards, the
    // "New Advisory" button, and the modal are all hidden for farmers.
    Route::get('/farmer/advisories', [AdvisoryController::class, 'index'])
        ->name('farmer.advisories.index');

    // ============================================================
    // 15. FARMER — MY FARMS
    // ============================================================
    // Write routes MUST come before wildcard reads
    // (otherwise /farmer/farms/create binds as {id} = "create").

    // Write actions — require CAO verification
    Route::get('/farmer/farms/create',    [FarmerFarmController::class, 'create'])
        ->middleware('verified.cao')->name('farmer.farms.create');
    Route::post('/farmer/farms',           [FarmerFarmController::class, 'store'])
        ->middleware('verified.cao')->name('farmer.farms.store');
    Route::get('/farmer/farms/{id}/edit',  [FarmerFarmController::class, 'edit'])
        ->middleware('verified.cao')->name('farmer.farms.edit');
    Route::put('/farmer/farms/{id}',       [FarmerFarmController::class, 'update'])
        ->middleware('verified.cao')->name('farmer.farms.update');

    // Read actions — no gate
    Route::get('/farmer/farms',      [FarmerFarmController::class, 'index'])->name('farmer.farms.index');
    Route::get('/farmer/farms/{id}', [FarmerFarmController::class, 'show'])->name('farmer.farms.show');

    // ============================================================
    // 16. FARMER — MY FARM RECORDS
    // ============================================================

    // List all seasons across all farms
    Route::get('/farmer/farm-records', [FarmerFarmRecordController::class, 'index'])
        ->name('farmer.farm-records.index');

    // Write actions — require CAO verification
    Route::middleware('verified.cao')->group(function () {
        Route::get   ('/farmer/farm-records/create',              [FarmerFarmRecordController::class, 'create'])->name('farmer.farm-records.create');
        Route::post  ('/farmer/farm-records',                     [FarmerFarmRecordController::class, 'store'])->name('farmer.farm-records.store');
        Route::get   ('/farmer/farm-records/{id}/edit',           [FarmerFarmRecordController::class, 'edit'])->name('farmer.farm-records.edit');
        Route::put   ('/farmer/farm-records/{id}',                [FarmerFarmRecordController::class, 'update'])->name('farmer.farm-records.update');
        Route::post  ('/farmer/farm-records/{id}/mark-harvested', [FarmerFarmRecordController::class, 'markHarvested'])->name('farmer.farm-records.mark-harvested');
        Route::delete('/farmer/farm-records/{id}',                [FarmerFarmRecordController::class, 'destroy'])->name('farmer.farm-records.destroy');
    });

    // Read — no gate
    Route::get('/farmer/farm-records/{id}', [FarmerFarmRecordController::class, 'show'])
        ->name('farmer.farm-records.show');

    // ============================================================
    // 17. FARMER — RICE VARIETY CATALOG (read-only)
    // ============================================================
    Route::get('/farmer/rice-varieties',      [FarmerRiceVarietyController::class, 'index'])->name('farmer.rice-varieties.index');
    Route::get('/farmer/rice-varieties/{id}', [FarmerRiceVarietyController::class, 'show'])->name('farmer.rice-varieties.show');

});