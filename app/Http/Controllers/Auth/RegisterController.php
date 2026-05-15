<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\Auth\RegisterRequest;

class RegisterController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @unauthenticated
     *
     * @response array{message: string, data: \App\Http\Resources\UserResource, meta: array{token_value: string, token_type: string, token_expires_at: \Carbon\CarbonInterface}}
     */
    public function store(RegisterRequest $request): UserResource
    {
        $user = User::create(
            $request->safe(['name', 'email', 'password']),
        );

        $user->markEmailAsVerified();

        return $user
            ->toResource()
            ->additional([
                'message' => __('auth.register.success'),
                'meta' => $user->createAuthToken(),
            ]);
    }
}
