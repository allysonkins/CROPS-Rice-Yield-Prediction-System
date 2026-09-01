<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Farmer\DashboardController as FarmerDashboardController;
use App\Http\Controllers\Admin\RiceVarietyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\AdvisoryController;
use App\Http\Controllers\Admin\PredictionController;
use App\Http\Controllers\Admin\FarmRecordController;
use App\Http\Controllers\Admin\FarmController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\FarmerController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Farmer\ProfileController;
use App\Http\Controllers\Farmer\PredictionController as FarmerPredictionController;

Route::get('/', function () {
    return redirect('/login');
});

// Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

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
    Route::put('/admin/predictions/{id}', [PredictionController::class, 'update'])->name('admin.predictions.update');
    Route::get('/admin/predictions/{id}', [PredictionController::class, 'show'])->name('admin.predictions.show');

    // ============================================================
// 3. RICE VARIETIES (updated)
// ============================================================
Route::prefix('admin/rice-varieties')->group(function () {
    Route::get('/', [RiceVarietyController::class, 'index'])->name('admin.rice-varieties.index');
    Route::get('/create', [RiceVarietyController::class, 'create'])->name('admin.rice-varieties.create');
    Route::post('/', [RiceVarietyController::class, 'store'])->name('admin.rice-varieties.store');
    Route::get('/{id}/edit', [RiceVarietyController::class, 'edit'])->name('admin.rice-varieties.edit');
    Route::put('/{id}', [RiceVarietyController::class, 'update'])->name('admin.rice-varieties.update');
    Route::delete('/{id}', [RiceVarietyController::class, 'destroy'])->name('admin.rice-varieties.destroy');

    // NEW: Get yield for a variety by seeding method
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
    Route::resource('/admin/farm-records', FarmRecordController::class)->names('admin.farm-records');

    // ============================================================
    // 8. ADVISORIES
    // ============================================================
    Route::resource('/admin/advisories', AdvisoryController::class)->names('admin.advisories');

    // ============================================================
    // 9. FARMERS LIST
    // ============================================================
    Route::resource('/admin/farmers', FarmerController::class)->names('admin.farmers');

    // ============================================================
    // 10. ACTIVITY LOGS (ADMIN ONLY)
    // ============================================================
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/logs', [LogController::class, 'index'])->name('admin.logs.index');
    });

    // ============================================================
    // 11. REPORTS
    // ============================================================
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/generate', [ReportController::class, 'generate'])->name('admin.reports.generate');

    // ============================================================
    // 12. FARMER PROFILE
    // ============================================================
    Route::get('/farmer/profile', [ProfileController::class, 'edit'])->name('farmer.profile.edit');
    Route::put('/farmer/profile', [ProfileController::class, 'update'])->name('farmer.profile.update');

    // ============================================================
    // 13. FARMER PREDICTIONS
    // ============================================================
    Route::get('/farmer/predictions', [FarmerPredictionController::class, 'index'])->name('farmer.predictions.index');
});