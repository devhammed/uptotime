<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Timebox;
use Illuminate\Http\JsonResponse;
use App\Models\PersonalAccessToken;
use Illuminate\Auth\Events\Lockout;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __construct(
        protected Timebox $timebox,

        protected int $timeboxDuration = 200_000,

        protected bool $rehashOnLogin = true,

        protected int $maxAttempts = 6,
    ) {}

    /**
     * Handle an incoming authentication request.
     *
     * @unauthenticated
     *
     * @response array{message: string, data: \App\Http\Resources\UserResource, meta: array{token_value: string, token_type: string, token_expires_at: \Carbon\CarbonInterface}}
     */
    public function store(LoginRequest $request): UserResource
    {
        $throttleKey = $request->string('email')
            ->lower()
            ->append("|{$request->ip()}")
            ->transliterate()
            ->value();

        if (RateLimiter::tooManyAttempts($throttleKey, $this->maxAttempts)) {
            event(new Lockout($request));

            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        try {
            $email = $request->validated('email');

            $password = $request->validated('password');

            $resource = $this->timebox->call(function () use ($email, $password) {
                $user = User::query()
                    ->where('email', $email)
                    ->first();

                if (! $user || ! Hash::check($password, $user->password)) {
                    throw new AuthenticationException(__('auth.failed'));
                }

                if ($this->rehashOnLogin && Hash::needsRehash($user->password)) {
                    $user->update(['password' => $password]);
                }

                return $user
                    ->toResource()
                    ->additional([
                        'message' => __('auth.login.success'),
                        'meta' => $user->createAuthToken(),
                    ]);
            }, $this->timeboxDuration);

            RateLimiter::clear($throttleKey);

            return $resource;
        } catch (AuthenticationException $exception) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages([
                'email' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Destroy an authenticated session.
     *
     * @response array{message: string}
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var ?PersonalAccessToken $currentToken */
        $currentToken = $user->currentAccessToken();

        $currentToken?->delete();

        return response()->json([
            'message' => __('auth.logout.success'),
        ]);
    }
}
