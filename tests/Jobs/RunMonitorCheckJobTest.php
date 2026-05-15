<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Monitor;
use App\Enums\MonitorStatus;
use Illuminate\Support\Carbon;
use App\Jobs\RunMonitorCheckJob;
use Illuminate\Support\Facades\Http;
use App\Notifications\MonitorUpNotification;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MonitorDownNotification;
use Illuminate\Http\Client\ConnectionException;

beforeEach(function () {
    $this->now = Carbon::parse('2026-05-15 12:00:00');
    Carbon::setTestNow($this->now);
    Notification::fake();
});

afterEach(function () {
    Carbon::setTestNow();
});

test('successful response creates check record, sets status to Up, and calculates uptime', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create(['status' => MonitorStatus::Pending]);

    Http::fake([$monitor->url => Http::response('OK')]);

    (new RunMonitorCheckJob($monitor, $this->now))->handle();

    $check = $monitor->checks()->sole();

    expect($check->is_up)->toBeTrue()
        ->and($check->status_code)->toBe(200)
        ->and($check->response_time_ms)->toBeGreaterThanOrEqual(0)
        ->and($check->checked_at->toDateTimeString())->toBe($this->now->toDateTimeString());

    $monitor->refresh();

    expect($monitor->status)->toBe(MonitorStatus::Up)
        ->and($monitor->uptime_percentage)->toBe(100.0);

    Notification::assertNothingSent();
});

test('3xx response is treated as up', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create(['status' => MonitorStatus::Pending]);

    Http::fake([$monitor->url => Http::response('', 301)]);

    (new RunMonitorCheckJob($monitor, $this->now))->handle();

    expect($monitor->checks()->sole()->is_up)->toBeTrue();
});

test('connection exception creates check with status_code 0 and null response time', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create(['status' => MonitorStatus::Pending]);

    Http::fake([$monitor->url => function () {
        throw new ConnectionException('timed out');
    }]);

    (new RunMonitorCheckJob($monitor, $this->now))->handle();

    $check = $monitor->checks()->sole();

    expect($check->is_up)->toBeFalse()
        ->and($check->status_code)->toBe(0)
        ->and($check->response_time_ms)->toBeNull();
});

test('failed check below threshold keeps previous status', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create([
        'status' => MonitorStatus::Up,
        'threshold' => 3,
    ]);

    Http::fake([$monitor->url => Http::response('', 503)]);

    // One prior success so consecutive failure count starts at 0 before this run.
    $monitor->checks()->create([
        'is_up' => true,
        'status_code' => 200,
        'response_time_ms' => 50,
        'checked_at' => $this->now->copy()->subMinutes(5),
    ]);

    (new RunMonitorCheckJob($monitor, $this->now))->handle();

    $monitor->refresh();

    expect($monitor->status)->toBe(MonitorStatus::Up);

    Notification::assertNothingSent();
});

test('status becomes Down and sends MonitorDownNotification when consecutive failures reach threshold', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create([
        'status' => MonitorStatus::Pending,
        'threshold' => 2,
    ]);

    Http::fake([$monitor->url => Http::response('', 500)]);

    // One prior failure, no prior success → this run makes 2 consecutive failures.
    $monitor->checks()->create([
        'is_up' => false,
        'status_code' => 500,
        'response_time_ms' => null,
        'checked_at' => $this->now->copy()->subMinutes(5),
    ]);

    (new RunMonitorCheckJob($monitor, $this->now))->handle();

    $monitor->refresh();

    expect($monitor->status)->toBe(MonitorStatus::Down);

    Notification::assertSentTo($user, MonitorDownNotification::class, function ($n) use ($monitor) {
        return $n->monitor->is($monitor);
    });
});

test('status recovers to Up and sends MonitorUpNotification when check succeeds after being Down', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create(['status' => MonitorStatus::Down]);

    Http::fake([$monitor->url => Http::response('OK')]);

    (new RunMonitorCheckJob($monitor, $this->now))->handle();

    $monitor->refresh();

    expect($monitor->status)->toBe(MonitorStatus::Up);

    Notification::assertSentTo($user, MonitorUpNotification::class, function ($n) use ($monitor) {
        return $n->monitor->is($monitor);
    });
});

test('no notification sent when monitor stays Up', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create(['status' => MonitorStatus::Up]);

    Http::fake([$monitor->url => Http::response('OK')]);

    (new RunMonitorCheckJob($monitor, $this->now))->handle();

    Notification::assertNothingSent();
});

test('uptime percentage reflects ratio of successful checks', function () {
    $user = User::factory()->create();
    $monitor = Monitor::factory()->for($user)->create([
        'status' => MonitorStatus::Up,
    ]);

    Http::fake([$monitor->url => Http::response('', 503)]);

    // Seed 3 up checks.
    $monitor->checks()->createMany(array_fill(0, 3, [
        'is_up' => true,
        'status_code' => 200,
        'response_time_ms' => 100,
        'checked_at' => $this->now->copy()->subMinutes(10),
    ]));

    (new RunMonitorCheckJob($monitor, $this->now))->handle(); // 1 down

    $monitor->refresh();

    // 3 up / 4 total = 75%
    expect($monitor->uptime_percentage)->toBe(75.0);
});
