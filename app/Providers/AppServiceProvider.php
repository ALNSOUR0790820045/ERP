<?php

namespace App\Providers;

use App\Models\Tender;
use App\Policies\TenderPolicy;
use App\Services\StagePermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // تسجيل خدمة الصلاحيات كـ Singleton
        $this->app->singleton(StagePermissionService::class, function ($app) {
            return new StagePermissionService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // تسجيل Policy للعطاءات
        Gate::policy(Tender::class, TenderPolicy::class);
    }
}
