<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Throwable;
use App\Models\Monitor;
use Illuminate\Console\Command;
use App\Jobs\RunMonitorCheckJob;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;

#[Signature('monitors:run')]
#[Description('Dispatch jobs for due monitors.')]
class RunDueMonitorsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        Monitor::query()
            ->due($now)
            ->eachById(function (Monitor $monitor) use ($now) {
                try {
                    echo "Dispatching monitor check for #{$monitor->id} at {$now->format('Y-m-d H:i:s')}.\n";

                    RunMonitorCheckJob::dispatch($monitor, $now);

                    $monitor->update([
                        'last_checked_at' => $now,
                        'next_check_at' => $now->addMinutes($monitor->check_interval),
                    ]);
                } catch (Throwable $throwable) {
                    report($throwable);
                }
            });

        return self::SUCCESS;
    }
}
