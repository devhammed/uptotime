<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Monitor;
use Illuminate\Support\Carbon;
use App\Jobs\RunMonitorCheckJob;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->now = Carbon::parse('2026-05-15 12:00:00');

    Carbon::setTestNow($this->now);

    Queue::fake();
});

afterEach(function () {
    Carbon::setTestNow();
});

test('command has correct signature and exits successfully', function () {
    $this->artisan('monitors:run')->assertSuccessful();
});

test('dispatches job and updates timestamps for each due monitor', function () {
    $user = User::factory()->create();

    $monitors = Monitor::factory()->count(3)->for($user)->create([
        'check_interval' => 5,
        'next_check_at' => $this->now->copy()->subMinute(),
    ]);

    $this->artisan('monitors:run')->assertSuccessful();

    Queue::assertPushedTimes(RunMonitorCheckJob::class, 3);

    foreach ($monitors as $monitor) {
        $monitor->refresh();

        expect($monitor->last_checked_at->toDateTimeString())->toBe($this->now->toDateTimeString())
            ->and($monitor->next_check_at->toDateTimeString())
            ->toBe($this->now->copy()->addMinutes(5)->toDateTimeString());
    }
});

test('skips monitors that are not due yet or have null next_check_at', function () {
    $user = User::factory()->create();

    Monitor::factory()->for($user)->create(['next_check_at' => $this->now->copy()->addMinutes(10)]);
    Monitor::factory()->for($user)->create(['next_check_at' => null]);

    $this->artisan('monitors:run')->assertSuccessful();

    Queue::assertNothingPushed();
});

test('only processes due monitors when mixed with non-due ones', function () {
    $user = User::factory()->create();

    Monitor::factory()->count(2)->for($user)->create(['next_check_at' => $this->now->copy()->subMinute()]);
    Monitor::factory()->count(3)->for($user)->create(['next_check_at' => $this->now->copy()->addMinutes(5)]);
    Monitor::factory()->for($user)->create(['next_check_at' => null]);

    $this->artisan('monitors:run')->assertSuccessful();

    Queue::assertPushedTimes(RunMonitorCheckJob::class, 2);
});

test('dispatches the correct job with the right monitor and timestamp', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create(['next_check_at' => $this->now->copy()->subMinute()]);

    $this->artisan('monitors:run')->assertSuccessful();

    Queue::assertPushed(RunMonitorCheckJob::class, function (RunMonitorCheckJob $job) use ($monitor) {
        return $job->monitor->is($monitor)
            && $job->checkedAt->toDateTimeString() === $this->now->toDateTimeString();
    });
});
