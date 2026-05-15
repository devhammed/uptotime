<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Monitor;

describe('index', function () {
    test('unauthenticated user cannot list monitors', function () {
        $this->getJson(route('monitors.index'))
            ->assertUnauthorized();
    });

    test('authenticated user can list only their own monitors', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = $user->createAuthToken();

        Monitor::factory()->count(3)->for($user)->create();
        Monitor::factory()->count(2)->for($other)->create();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.index'))
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure([
                'data' => [['id', 'url', 'check_interval', 'threshold', 'status', 'last_checked_at', 'uptime_percentage', 'created_at']],
                'message',
            ]);
    });

    test('returns empty list when user has no monitors', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.index'))
            ->assertOk()
            ->assertJsonPath('data', []);
    });

    test('response is paginated with default per_page of 15', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        Monitor::factory()->count(20)->for($user)->create();

        $response = $this->withToken($token['token_value'])
            ->getJson(route('monitors.index'))
            ->assertOk();

        expect($response->json('meta.per_page'))->toBe(15)
            ->and($response->json('meta.total'))->toBe(20)
            ->and($response->json('data'))->toHaveCount(15);
    });

    test('per_page parameter controls page size', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        Monitor::factory()->count(10)->for($user)->create();

        $response = $this->withToken($token['token_value'])
            ->getJson(route('monitors.index', ['per_page' => 5]))
            ->assertOk();

        expect($response->json('data'))->toHaveCount(5);
    });

    test('page parameter navigates to correct page', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        Monitor::factory()->count(10)->for($user)->create();

        $response = $this->withToken($token['token_value'])
            ->getJson(route('monitors.index', ['per_page' => 5, 'page' => 2]))
            ->assertOk();

        expect($response->json('meta.current_page'))->toBe(2)
            ->and($response->json('data'))->toHaveCount(5);
    });

    test('per_page must be a positive integer', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.index', ['per_page' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });

    test('per_page cannot exceed 100', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.index', ['per_page' => 101]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    });

    test('page must be a positive integer', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->getJson(route('monitors.index', ['page' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);
    });
});

describe('store', function () {
    test('unauthenticated user cannot create a monitor', function () {
        $this->postJson(route('monitors.store'), ['url' => 'https://example.com'])
            ->assertUnauthorized();
    });

    test('authenticated user can create a monitor', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'), ['url' => 'https://example.com'])
            ->assertCreated()
            ->assertJsonPaths([
                'data.url' => 'https://example.com',
                'data.check_interval' => 5,
                'data.threshold' => 3,
            ])
            ->assertJsonStructure([
                'data' => ['id', 'url', 'check_interval', 'threshold', 'status', 'last_checked_at', 'uptime_percentage', 'created_at'],
                'message',
            ]);

        expect($user->monitors()->count())->toBe(1);
    });

    test('check_interval and threshold can be set explicitly', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'), [
                'url' => 'https://example.com',
                'check_interval' => 10,
                'threshold' => 5,
            ])
            ->assertCreated()
            ->assertJsonPaths([
                'data.check_interval' => 10,
                'data.threshold' => 5,
            ]);
    });

    test('url is required', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    });

    test('url must be a valid active url', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'), ['url' => 'not-a-url'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    });

    test('url must be unique per user', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        Monitor::factory()->for($user)->create(['url' => 'https://example.com']);

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'), ['url' => 'https://example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url']);
    });

    test('same url can be monitored by different users', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $tokenB = $userB->createAuthToken();

        Monitor::factory()->for($userA)->create(['url' => 'https://example.com']);

        $this->withToken($tokenB['token_value'])
            ->postJson(route('monitors.store'), ['url' => 'https://example.com'])
            ->assertCreated();
    });

    test('check_interval must be an integer', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'), ['url' => 'https://example.com', 'check_interval' => 'five'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_interval']);
    });

    test('check_interval must be at least 1', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'), ['url' => 'https://example.com', 'check_interval' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_interval']);
    });

    test('check_interval cannot exceed 60', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'), ['url' => 'https://example.com', 'check_interval' => 61])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['check_interval']);
    });

    test('threshold must be an integer', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'), ['url' => 'https://example.com', 'threshold' => 'three'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['threshold']);
    });

    test('threshold must be at least 1', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('monitors.store'), ['url' => 'https://example.com', 'threshold' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['threshold']);
    });
});
