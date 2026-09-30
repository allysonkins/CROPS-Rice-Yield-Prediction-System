<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    // Force our custom pagination view for every ->links() call
    \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.crops');
    \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.crops');

    Blade::if('farmerVerified', function () {
        $user = auth()->user();
        return $user && $user->role === 'farmer' && $user->verified_by_cao_at !== null;
    });

    Blade::if('farmerPending', function () {
        $user = auth()->user();
        return $user && $user->role === 'farmer' && $user->verified_by_cao_at === null;
    });
}
}