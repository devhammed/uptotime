<?php

declare(strict_types=1);

use App\Models\User;

describe('store', function () {
    test('user can register with valid data', function () {
        $response = $this->postJson(route('auth.register'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'accept_terms' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPaths([
                'data.name' => 'Jane Doe',
                'data.email' => 'jane@example.com',
            ])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at'],
                'message',
                'meta' => ['token_value', 'token_type', 'token_expires_at'],
            ]);

        $user = User::query()
            ->where('email', 'jane@example.com')
            ->first();

        expect($user)->not->toBeNull()
            ->and($user->tokens()->count())->toBe(1)
            ->and($user->email_verified_at)->not->toBeNull();
    });

    test('registration fails with duplicate email', function () {
        User::factory()->create(['email' => 'jane@example.com']);

        $this
            ->postJson(route('auth.register'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'Password123!',
                'accept_terms' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('registration fails with missing name', function () {
        $this
            ->postJson(route('auth.register'), [
                'email' => 'jane@example.com',
                'password' => 'Password123!',
                'accept_terms' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('registration fails with invalid email format', function () {
        $this
            ->postJson(route('auth.register'), [
                'name' => 'Jane Doe',
                'email' => 'not-an-email',
                'password' => 'Password123!',
                'accept_terms' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('registration fails without accepting terms', function () {
        $this
            ->postJson(route('auth.register'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'Password123!',
                'accept_terms' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['accept_terms']);
    });

    test('registration fails with missing password', function () {
        $this
            ->postJson(route('auth.register'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'accept_terms' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    test('registration fails when email is not lowercase', function () {
        $this
            ->postJson(route('auth.register'), [
                'name' => 'Jane Doe',
                'email' => 'Jane@Example.COM',
                'password' => 'Password123!',
                'accept_terms' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });
});
