<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use App\Repositories\CourseRepositoryInterface;
use App\Services\CourseServices;
use App\Repositories\LoginRepositoryInterface;
use App\Services\LoginServices;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Passport::ignoreRoutes();
        $this->app->bind(CourseRepositoryInterface::class, CourseServices::class);
        $this->app->bind(LoginRepositoryInterface::class, LoginServices::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
