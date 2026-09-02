<?php

namespace App\Providers;

use App\Services\BrandingService;
use App\Services\OutletModeService;
use App\Services\OutletScopeService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BrandingService::class, function () {
            return new BrandingService;
        });
        $this->app->singleton(OutletModeService::class, fn () => new OutletModeService);
        $this->app->scoped(OutletScopeService::class, fn () => new OutletScopeService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $brandingService = app(BrandingService::class);
            $view->with('branding', $brandingService->getBrandingData());
        });

        Blade::if('multiOutlet', function () {
            return app(OutletModeService::class)->isMultiOutlet();
        });

        Blade::if('singleOutlet', function () {
            return app(OutletModeService::class)->isSingleOutlet();
        });
    }
}
