<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Monitor;
use Carbon\CarbonInterface;
use App\Enums\MonitorStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\MonitorUpNotification;
use App\Notifications\MonitorDownNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

#[WithoutRelations]
#[DeleteWhenMissingModels]
class RunMonitorCheckJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Monitor $monitor,
        public ?CarbonInterface $checkedAt = null,
    ) {
        $this->checkedAt ??= now();
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping("monitor-check-{$this->monitor->id}")];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $start = microtime(true);

            $response = Http::get($this->monitor->url);

            $responseTimeMs = (int) round((microtime(true) - $start) * 1000);

            $statusCode = $response->status();
        } catch (ConnectionException) {
            $statusCode = 0;

            $responseTimeMs = null;
        }

        $check = $this->monitor->checks()->create([
            'status_code' => $statusCode,
            'is_up' => $statusCode >= 200 && $statusCode < 400,
            'response_time_ms' => $responseTimeMs,
            'checked_at' => $this->checkedAt,
        ]);

        $previousStatus = $this->monitor->status;

        $newStatus = MonitorStatus::Up;

        if (! $check->is_up) {
            $consecutiveFailures = $this->monitor->checks()
                ->where('is_up', false)
                ->where(function ($query) {
                    $query
                        ->where('checked_at', '>', function ($sub) {
                            $sub->from('monitor_checks')
                                ->selectRaw('MAX(checked_at)')
                                ->where('monitor_id', $this->monitor->id)
                                ->where('is_up', true);
                        })
                        ->orWhereNotExists(function ($sub) {
                            $sub->from('monitor_checks')
                                ->where('monitor_id', $this->monitor->id)
                                ->where('is_up', true);
                        });
                })
                ->count();

            $newStatus = $consecutiveFailures >= $this->monitor->threshold
                ? MonitorStatus::Down
                : $previousStatus;
        }

        $totalChecks = $this->monitor->checks()->count();
        $upChecks = $this->monitor->checks()->where('is_up', true)->count();

        $this->monitor->update([
            'status' => $newStatus,
            'uptime_percentage' => $totalChecks > 0 ? round(($upChecks / $totalChecks) * 100, 2) : null,
        ]);

        if ($previousStatus !== $newStatus) {
            $user = $this->monitor->user;

            if ($newStatus === MonitorStatus::Down) {
                $user->notify(new MonitorDownNotification($this->monitor));
            } elseif ($newStatus === MonitorStatus::Up && $previousStatus === MonitorStatus::Down) {
                $user->notify(new MonitorUpNotification($this->monitor));
            }
        }
    }
}
