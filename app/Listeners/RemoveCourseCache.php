<?php

namespace App\Listeners;

use App\Events\CourseChange;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class RemoveCourseCache implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CourseChange $event): void
    {
        Cache::flush();
    }
}
