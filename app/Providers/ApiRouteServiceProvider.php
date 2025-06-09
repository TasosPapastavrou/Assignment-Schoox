<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ApiRouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/api.php'));
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip()); // Or use user ID: $request->user()?->id
        });

         // Course Actions: 30 per minute per user
        RateLimiter::for('course-actions', function (Request $request) {
            return Limit::perMinute(30)->by(optional($request->user())->id ?: $request->ip());
        });

        // Auth Actions: 5 per minute per IP
        RateLimiter::for('auth-actions', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
