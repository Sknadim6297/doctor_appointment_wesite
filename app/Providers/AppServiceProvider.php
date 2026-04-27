<?php

namespace App\Providers;

use App\Services\AdminAccessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
    public function boot(AdminAccessService $adminAccessService): void
    {
        View::composer('admin.layouts.app', function ($view) use ($adminAccessService) {
            $user = Auth::user();

            $view->with('sidebarTree', $user ? $adminAccessService->visibleSidebarCatalogForUser($user) : []);
        });
    }
}
