<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Farmer\DashboardController as FarmerDashboardController;
use App\Http\Controllers\Admin\RiceVarietyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\AdvisoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\PredictionController;

Route::get('/', function () {
    return redirect('/login');
});

// Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (Logged in users only)
Route::middleware(['auth'])->group(function () {

    // ============================================================
    // 1. DASHBOARDS (by role)
    // ============================================================
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
    Route::get('/farmer/dashboard', [FarmerDashboardController::class, 'index'])->name('farmer.dashboard');

    // ============================================================
    // 2. PREDICTIONS
    // ============================================================
    Route::get('/admin/predictions', [PredictionController::class, 'index'])->name('admin.predictions.index');

    // ============================================================
    // 3. RICE VARIETIES (Full CRUD)
    // ============================================================
    Route::prefix('admin/rice-varieties')->group(function () {
        Route::get('/', [RiceVarietyController::class, 'index'])->name('admin.rice-varieties.index');
        Route::get('/create', [RiceVarietyController::class, 'create'])->name('admin.rice-varieties.create');
        Route::post('/', [RiceVarietyController::class, 'store'])->name('admin.rice-varieties.store');
        Route::get('/{id}/edit', [RiceVarietyController::class, 'edit'])->name('admin.rice-varieties.edit');
        Route::put('/{id}', [RiceVarietyController::class, 'update'])->name('admin.rice-varieties.update');
        Route::delete('/{id}', [RiceVarietyController::class, 'destroy'])->name('admin.rice-varieties.destroy');
    });

    // ============================================================
    // 4. USER MANAGEMENT (ADMIN ONLY)
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
    // 6. ADVISORIES (Full CRUD using Route::resource)
    // ============================================================
    Route::resource('/admin/advisories', AdvisoryController::class);

    // ============================================================
    // 7. REPORTS
    // ============================================================
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');
    Route::get('/admin/reports/generate', [ReportController::class, 'generate'])->name('admin.reports.generate');

});