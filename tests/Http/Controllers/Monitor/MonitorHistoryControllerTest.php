<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Monitor;
use App\Models\MonitorCheck;

describe('index', function () {
    test('unauthenticated user cannot view monitor history', function () {
        $monitor = Monitor::factory()->create();

        $this->getJson(route('monitors.history', $monitor))
            ->assertUnauthorized();
    });

    test('authenticated user can view history of their own monitor', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();
        $monitor = Monitor::factory()->for($user)->create();

        MonitorCheck::factory()->count(3)->for($monitor)->create();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', $monitor))
            ->assertOk()
            ->assertJsonPath('data.0.monitor_id', $monitor->id)
            ->assertJsonStructure([
                'data' => [['id', 'monitor_id', 'status_code', 'response_time_ms', 'is_up', 'checked_at']],
                'message',
            ]);
    });

    test('user cannot view history of another user\'s monitor', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = $user->createAuthToken();

        $monitor = Monitor::factory()->for($other)->create();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', $monitor))
            ->assertNotFound();
    });

    test('returns empty list when monitor has no checks', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();
        $monitor = Monitor::factory()->for($user)->create();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', $monitor))
            ->assertOk()
            ->assertJsonPath('data', []);
    });

    test('history is ordered by checked_at descending', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();
        $monitor = Monitor::factory()->for($user)->create();

        $oldest = MonitorCheck::factory()->for($monitor)->create(['checked_at' => now()->subHours(2)]);
        $middle = MonitorCheck::factory()->for($monitor)->create(['checked_at' => now()->subHour()]);
        $newest = MonitorCheck::factory()->for($monitor)->create(['checked_at' => now()]);

        $response = $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', $monitor))
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        expect($ids->first())->toBe($newest->id)
            ->and($ids->get(1))->toBe($middle->id)
            ->and($ids->last())->toBe($oldest->id);
    });

    test('response is paginated with default per_page of 15', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();
        $monitor = Monitor::factory()->for($user)->create();

        MonitorCheck::factory()->count(20)->for($monitor)->create();

        $response = $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', $monitor))
            ->assertOk();

        expect($response->json('meta.per_page'))->toBe(15)
            ->and($response->json('meta.total'))->toBe(20)
            ->and($response->json('data'))->toHaveCount(15);
    });

    test('per_page parameter controls page size', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();
        $monitor = Monitor::factory()->for($user)->create();

        MonitorCheck::factory()->count(10)->for($monitor)->create();

        $response = $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', ['monitor' => $monitor, 'per_page' => 5]))
            ->assertOk();

        expect($response->json('data'))->toHaveCount(5);
    });

    test('page parameter navigates to correct page', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();
        $monitor = Monitor::factory()->for($user)->create();

        MonitorCheck::factory()->count(10)->for($monitor)->create();

        $response = $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', ['monitor' => $monitor, 'per_page' => 5, 'page' => 2]))
            ->assertOk();

        expect($response->json('meta.current_page'))->toBe(2)
            ->and($response->json('data'))->toHaveCount(5);
    });

    test('per_page must be a positive integer', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();
        $monitor = Monitor::factory()->for($user)->create();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', ['monitor' => $monitor, 'per_page' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });

    test('per_page cannot exceed 100', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();
        $monitor = Monitor::factory()->for($user)->create();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', ['monitor' => $monitor, 'per_page' => 101]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });

    test('page must be a positive integer', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();
        $monitor = Monitor::factory()->for($user)->create();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.history', ['monitor' => $monitor, 'page' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    });
});
