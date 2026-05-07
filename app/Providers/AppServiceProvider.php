<?php

namespace App\Providers;

use App\Models\DispatchRequest;
use App\Models\VehicleMaintenanceItem;
use App\Observers\MaintenanceItemObserver;
use Illuminate\Support\Facades\Route;
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
        Route::bind('dispatchRequest', function (string $value) {
            return DispatchRequest::withTrashed()->findOrFail((int) $value);
        });

        VehicleMaintenanceItem::observe(MaintenanceItemObserver::class);
    }
}
