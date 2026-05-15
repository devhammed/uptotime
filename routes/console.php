<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('monitors:run')
    ->sendOutputTo(storage_path('logs/monitors.log'))
    ->withoutOverlapping()
    ->runInBackground()
    ->everyMinute();
