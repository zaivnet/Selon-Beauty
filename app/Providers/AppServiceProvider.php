<?php

namespace App\Providers;

use App\Services\BrandingService;
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
            return new BrandingService();
        });
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
    }
}
