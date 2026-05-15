<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

describe('store', function () {
    test('user can login with valid credentials', function () {
        $user = User::factory()->create();

        $this
            ->postJson(route('auth.login'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPaths([
                'data.id' => $user->id,
                'data.name' => $user->name,
                'data.email' => $user->email,
                'data.email_verified_at' => $user->email_verified_at?->toISOString(),
                'data.created_at' => $user->created_at?->toISOString(),
                'data.updated_at' => $user->updated_at?->toISOString(),
            ])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'],
                'message',
                'meta' => ['token_value', 'token_type', 'token_expires_at'],
            ]);
    });

    test('login response contains bearer token type', function () {
        $user = User::factory()->create();

        $this
            ->postJson(route('auth.login'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('meta.token_type', 'Bearer');
    });

    test('login creates a personal access token', function () {
        $user = User::factory()->create();

        $this->postJson(route('auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        expect($user->tokens()->count())->toBe(1);
    });

    test('login fails with wrong password', function () {
        $user = User::factory()->create();

        $this
            ->postJson(route('auth.login'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('login fails with non-existent email', function () {
        $this
            ->postJson(route('auth.login'), [
                'email' => 'nobody@example.com',
                'password' => 'password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('login fails with missing email', function () {
        $this
            ->postJson(route('auth.login'), [
                'password' => 'password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('login fails with missing password', function () {
        $this
            ->postJson(route('auth.login'), [
                'email' => 'user@example.com',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    test('login fails with invalid email format', function () {
        $this
            ->postJson(route('auth.login'), [
                'email' => 'not-an-email',
                'password' => 'password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('login fails with password exceeding 72 characters', function () {
        $this
            ->postJson(route('auth.login'), [
                'email' => 'user@example.com',
                'password' => str_repeat('a', 73),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    test('too many failed login attempts triggers throttle', function () {
        $email = 'user@example.com';
        $throttleKey = mb_strtolower($email).'|127.0.0.1';

        foreach (range(1, 6) as $i) {
            RateLimiter::hit($throttleKey);
        }

        $this
            ->postJson(route('auth.login'), [
                'email' => $email,
                'password' => 'password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('rate limiter is cleared after successful login', function () {
        $user = User::factory()->create();
        $throttleKey = mb_strtolower($user->email).'|127.0.0.1';

        RateLimiter::hit($throttleKey);

        $this->postJson(route('auth.login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        expect(RateLimiter::attempts($throttleKey))->toBe(0);
    });
});

describe('destroy', function () {
    test('authenticated user can logout', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('auth.logout'))
            ->assertOk()
            ->assertJsonStructure(['message']);
    });

    test('unauthenticated user cannot logout', function () {
        $this->postJson(route('auth.logout'))
            ->assertUnauthorized();
    });

    test('logout deletes the current access token', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->postJson(route('auth.logout'))
            ->assertOk();

        expect($user->tokens()->count())->toBe(0);
    });

    test('logout only deletes the current token, not all tokens', function () {
        $user = User::factory()->create();
        $tokenA = $user->createAuthToken();
        $user->createAuthToken();

        $this->withToken($tokenA['token_value'])->postJson(route('auth.logout'));

        expect($user->tokens()->count())->toBe(1);
    });

});
