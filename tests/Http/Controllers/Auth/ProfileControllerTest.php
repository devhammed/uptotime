<?php

declare(strict_types=1);

use App\Models\User;

describe('show', function () {
    test('authenticated user can retrieve their profile', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])->getJson(route('auth.profile'))
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
            ]);
    });

    test('unauthenticated user cannot retrieve profile', function () {
        $this->getJson(route('auth.profile'))
            ->assertUnauthorized();
    });

    test('profile does not expose sensitive fields', function () {
        $user = User::factory()->create();
        $token = $user->createAuthToken();

        $this->withToken($token['token_value'])
            ->getJson(route('auth.profile'))
            ->assertOk()
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.remember_token');
    });
});
