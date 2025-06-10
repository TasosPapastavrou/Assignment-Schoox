<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Events\CourseChange;
use App\Listeners\RemoveCourseCache;

class EventServiceProvider extends ServiceProvider
{

    protected $listen = [
        CourseChange::class => [
            RemoveCourseCache::class,
        ],
    ];

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
        //
    }
}
